<?php

    /*
    * -----------------------------------------------
    * 云掌财经SOA微服务框架SERVICE服务端
    * -----------------------------------------------
    * 使用框架：KURA V2.0.1
    * 开发人员：苏睿 / surui@123.com.cn
    * 最后更新日期：2017/05/07
    * -----------------------------------------------
    * SVN:
    * GIT:
    * -----------------------------------------------
    */

    //网关处理层

    namespace service;

    class Getway{
        
        //redis实例
        private static $_Redis;
        //连续失败次数，达到则熔断
        private static $_FuseErrorNum    = 5;
        //熔断超时时间，超过则变成半熔断状态
        private static $_FuseTimeOut     = 60;
        //熔断半开状态下重试最大次数
        private static $_FuseMaxRetry    = 10;
        //熔断半开状态成功率达到多少恢复
        private static $_FuseSuccessRate = 90;
        //服务运行超时阀值，超过则表示服务慢
        private static $_DownSlowTime    = 3;
        //服务连续慢请求几次被降级
        private static $_DownSlowNum     = 5;
        //服务慢请求连续时间阀值，超过该值，则慢请求记录清空，单位秒
        private static $_DownSlowTimes   = 120;
        //接口配置信息
        private static $_Conf;
        //服务接口地址
        private static $_Url;
        //服务接口配置KEY
        private static $_ServiceKey;
        
        //执行网关
        public static function run()
        {
            if (PROPATH == 'soa')
            {
                return TRUE;
            }
            $GLOBALS['_SNAPSHOT'] = 0;
            $GLOBALS['_APISTATE'] = 0;
            //验证网关白名单
            $white = C('WHITEGETWAY');
            if ($white === FALSE)
            {
                error(209, lang(209));
            }
            if (in_array(ROUTE, $white))
            {
                return TRUE;
            }
            //验证开发人员
            self::_checkDeveloper();
            /*
            if( ! C('ONLINE'))
            {
                return TRUE;
            }
             * 
             */
            //验证实例
            self::_checkExample();
            //连接SOA的REDIS
            self::$_Redis = new \Redis();
            if ( ! self::$_Redis->connect('192.168.1.184', 63790))
            {
                L('SOA的Redis连接失败，请检查！', 'system');
                return TRUE;
            }
            if ( ! self::$_Redis->auth('redis-password-888666'))
            {
                L('SOA的Redis认证失败，请检查！', 'system');
                return TRUE;
            }
            //拼接接口地址
            self::$_Url = '/'.PROPATH.'/'.ROUTE.'/';
            self::$_ServiceKey = 'SOA:H:SERVICE:'.md5(self::$_Url);
            self::$_Conf = self::$_Redis->hGetAll(self::$_ServiceKey);
            if ( ! self::$_Conf)
            {
                L('接口'.self::$_Url.'的配置不存在，请检查！', 'system');
                return TRUE;
            }
            $GLOBALS['_SID'] = self::$_Conf['SID'];
            if (METHOD == 'POST')
            {
                return TRUE;
            }
            define('GETWAY', 1);
            //验证接口
            self::_checkApi();
        }
        
        //保存基准快照，无登录用户条件判断的最新数据，熔断下的默认输出
        public static function snapShot($data = '')
        {
            if ( ! H('USERID'))
            {
                //屏蔽搜索
                if (preg_match('/^search/', ROUTE))
                {
                    return TRUE;
                }
                $key = 'SOA:H:SNAPSHOT:'.md5(REQUESTURI);
                $snapshot = array();
                $snapshot['val']  = $data;
                $snapshot['time'] = time();
                //判断是否存在该KEY
                if ( ! self::$_Redis->exists($key))
                {
                    self::$_Redis->hMset($key, $snapshot);
                }
                else
                {
                    //判断是否超时，这里不用redis的默认超时是因为有可能会造成没有缓存的情况
                    $data = self::$_Redis->hMget($key, array(
                        'time'
                    ));
                    if (time() - $data['time'] <= 600)
                    {
                        return TRUE;
                    }
                    self::$_Redis->hMset($key, $snapshot);
                }
            }
        }
        
        /*
         * 更改接口状态
         * 
         * $state : 状态值 3熔断 4降级 5限流
         * $from  ：这个参数是用来判断是代码报错还是接口API错误
         * 如果from是code说明是捕获到代码级错误而触发的，则直接更改接口状态
         * 如果from是API，则表示是接口错误，不能直接降级，防止网络原因，需要
         * 先判断该接口错误次数是不是超过设定的阀值再决定是否更改接口状态，
         * 该参数主要用于熔断操作
         * $error：错误信息
         */
        public static function changeApiState($state, $from = 'code', $error = array())
        {
            //接口熔断
            if ($state == 3)
            {
                //判断来源，代码级错误直接熔断
                //或者接口错误次数大于等于阀值
                if ($from == 'code' || 
                        self::$_Conf['ERRORNUM'] >= self::$_FuseErrorNum)
                {
                    self::$_Redis->hMset(self::$_ServiceKey, array(
                        'STATE'    => $state,
                        'ERRORNUM' => 0
                    ));
                    self::$_Redis->hMset('SOA:H:FUSE:'.md5(self::$_Url), array(
                        'TIME'    => time(),
                        'MODEL'   => 1,
                        'SUCCESS' => 0,
                        'RETRY'   => 0
                    ));
                    $error['SID'] = self::$_Conf['SID'];
                    $error['STATE'] = $state;
                    //设置异常，用于推送给SOA
                    $GLOBALS['_ERROR'] = $error;
                    //通知SOA更新接状态
                    \lib\Http::send(array(
                        'url' => SOA.'soa/system/synchApiState.html?token='.SOATOKEN.'&id='.self::$_Conf['SID'].'&state='.$state
                    ), FALSE);
                }
                else if (self::$_Conf['STATE'] == 1)
                {
                    self::$_Redis->hIncrBy(self::$_ServiceKey, 'ERRORNUM', 1);
                }
            }
            //降级
            else if ($state == 4)
            {
                //判断该服务在设定的阀值时间内有没有过慢请求，如果有则需要累加慢请求数，如果没有，则慢请求数为1
                $key  = 'SOA:S:DOWN:'.md5(self::$_Url);
                $slow = 0;
                if ( ! self::$_Redis->exists($key))
                {
                    $slow = 1;
                    self::$_Redis->setex($key, self::$_DownSlowTimes, 1);
                }
                else
                {
                    $slow = self::$_Conf['SLOW'] + 1;
                }
                //判断慢请求次数
                if ($slow > self::$_DownSlowNum)
                {
                    //设置接口降级
                    self::$_Redis->hMset(self::$_ServiceKey, array(
                        'STATE' => $state,
                        'SLOW'  => 0
                    ));
                    //先判断是否有基准快照
                    $key = 'SOA:H:SNAPSHOT:'.md5(REQUESTURI);
                    if ( ! self::$_Redis->exists($key))
                    {
                        $USERKEY = $error['USERKEY'];
                        self::$_Redis->setex($USERKEY, 600, $GLOBALS['_RETURN']);
                    }
                    unset($error['USERKEY']);
                    $error['SID'] = self::$_Conf['SID'];
                    $error['STATE'] = $state;
                    //设置异常，用于推送给SOA
                    $GLOBALS['_ERROR'] = $error;
                    //通知SOA更新接状态
                    \lib\Http::send(array(
                        'url' => SOA.'soa/system/synchApiState.html?token='.SOATOKEN.'&id='.self::$_Conf['SID'].'&state='.$state
                    ), FALSE);
                }
                else
                {
                    self::$_Redis->hMset(self::$_ServiceKey, array(
                        'SLOW' => $slow
                    ));
                }
            }
        }
        
        //增加熔断接口重试次数
        public static function fuseRetry($success = 1)
        {
            $key = 'SOA:H:FUSE:'.md5(self::$_Url);
            if ( ! self::$_Redis->exists($key))
            {
                return TRUE;
            }
            self::$_Redis->hIncrBy($key, 'RETRY', 1);
            if ($success)
            self::$_Redis->hIncrBy($key, 'SUCCESS', $success);
        }
        
        //读取快照
        public static function readSnapShot($down = FALSE, $key = '')
        {
            //降级快照特殊处理，因为存的是KV
            if ($down)
            {
                $data = self::$_Redis->get($key);
                $GLOBALS['_SNAPSHOT'] = 1;
                json($data, TRUE);
            }
            else
            {
                $key = 'SOA:H:SNAPSHOT:'.md5(REQUESTURI);
                $data = self::$_Redis->hGetAll($key);
                //设置标记为读取快照，系统会跳过路由步骤，直接返回
                $GLOBALS['_SNAPSHOT'] = 1;
                $data = ( ! $data) ? '' : $data['val'];
                json($data, TRUE);
            }
        }
        
        //降级处理模块
        //降级处理的入口在守护服务中，可以获取到整个服务接口运行的总耗时，以此来确定是否需要降级等后续操作
        public static function down($time = 0)
        {
            //如果服务状态不是正常中，则表示服务在其他状态，熔断、限流等，此时不做降级操作
            if (self::$_Conf['STATE'] != 1 &&
                    self::$_Conf['STATE'] != 4)
            {
                return TRUE;
            }
            //判断接口是不是已经处于降级状态
            if (self::$_Conf['STATE'] == 4)
            {
                //带有用户标识的KEY
                $USERKEY = self::_userKey();
                //先判断是否有快照
                $key = 'SOA:H:SNAPSHOT:'.md5(REQUESTURI);
                if ( ! self::$_Redis->exists($key) && 
                        ! self::$_Redis->exists($USERKEY))
                {
                    self::$_Redis->setex($USERKEY, 600, $GLOBALS['_RETURN']);
                }
            }
            else
            {
                //先进行请求时间与阀值对比
                if ($time < self::$_DownSlowTime)
                {
                    return TRUE;
                }
                //带有用户标识的KEY
                $USERKEY = self::_userKey();
                self::changeApiState(4, '', array(
                    'USERKEY' => $USERKEY,
                    'EID'     => C('EXAMPLE'),
                    'ONLINE'  => C('ONLINE'),
                    'API'     => REQUESTURI,
                    'ATIME'   => $time,
                    'TYPE'    => 3
                ));
            }
        }
        
        //恢复接口降级
        public static function downRecovery()
        {
            $atime = $GLOBALS['_ALLTIME'];
            //如果接口执行时间小于阀值，则表示接口恢复正常
            if ($atime < self::$_DownSlowTime)
            {
                self::$_Redis->hMset(self::$_ServiceKey, array(
                    'STATE' => 1,
                    'SLOW'  => 0
                ));
                //通知SOA更新接状态
                \lib\Http::send(array(
                    'url' => SOA.'soa/system/synchApiState.html?token='.SOATOKEN.'&id='.self::$_Conf['SID'].'&state=1'
                ), FALSE);
            }
        }
        
        //验证开发者
        private static function _checkDeveloper()
        {
            //目前客户端SDK只开放给web/wap
            if ( ! in_array(CLIENT, array(
                'web',
                'wap'
            )))
            {
                return TRUE;
            }
            //开发者账号
            $uname = H('UNAME');
            if ( ! $uname)
            {
                error(701, lang(701));
            }
            //密钥
            $token = H('TOKEN');
            if ( ! $token)
            {
                error(702, lang(702));
            }
            $hash = ROOTPATH.'/KURA/init/'.md5('account').'.php';
            if ( ! is_file($hash))
            {
                error(703, lang(703));
            }
            $account = require $hash;
            if ( ! isset($account[$uname]))
            {
                error(704, lang(704));
            }
            if ($token != $account[$uname]['token'])
            {
                error(705, lang(705));
            }
        }
        
        //验证实例
        private static function _checkExample()
        {
            if (C('STATE') != 1)
            error(706, lang(706));
        }
        
        //验证接口
        private static function _checkApi()
        {
            $state = self::$_Conf['STATE'];
            if ($state == 0)
            {
                error(707, lang(707));
            }
            else if ($state == 6)
            {
                error(708, lang(708));
            }
            //判断接口状态
            if ($state != 1)
            {
                self::_apiState($state);
            }
        }
        
        //判断接口状态
        private static function _apiState($state)
        {
            switch ($state)
            {
                //熔断
                case 3:
                    self::_fuse();
                break;
                //降级
                case 4;
                    self::_down();
                break;
                //限流
                case 5:
                    
                break;
            }
        }
        
        //降级
        private static function _down()
        {
            //先判断是否有快照
            $key  = 'SOA:H:SNAPSHOT:'.md5(REQUESTURI);
            $snap = 0;
            if (self::$_Redis->exists($key))
            {
                $snap = 1;
            }
            else
            {
                //用户标识快照
                $key = self::_userKey();
                if (self::$_Redis->exists($key))
                $snap = 2;
            }
            if ( ! $snap)
            {
                return TRUE;
            }
            //设置随机种子
            $rand = rand(1, 10);
            //十分之一的几率走正常数据
            if ($rand == 10)
            {
                //设置标记为降级,根据此标记系统会收集数据判断是否关闭降级
                $GLOBALS['_APISTATE'] = 4;
            }
            else
            {
                //读取快照
                if ($snap == 1)
                {
                    self::readSnapShot();
                }
                else
                {
                    self::readSnapShot(TRUE, $key);
                }
            }
        }
        
        //限流
        
        
        //熔断
        private static function _fuse()
        {
            $key  = 'SOA:H:FUSE:'.md5(self::$_Url);
            //读取熔断参数
            $conf = self::$_Redis->hGetAll($key);
            if ( ! $conf)
            {
                //初始化
                self::$_Redis->hMset($key, array(
                    'TIME'    => time(),
                    'MODEL'   => 1,
                    'SUCCESS' => 0,
                    'RETRY'   => 0
                ));
                //读取快照
                self::readSnapShot();
                return TRUE;
            }
            //判断熔断模式为全开
            if ($conf['MODEL'] == 1)
            {
                //判断是否超过熔断超时，如果超过则设置为半开
                if (time() - $conf['TIME'] >= self::$_FuseTimeOut)
                {
                    self::$_Redis->hMset($key, array(
                        'MODEL' => 0
                    ));
                }
                //读取快照
                self::readSnapShot();
                return TRUE;
            }
            //重试次数如果等于阀值
            if ($conf['RETRY'] == self::$_FuseMaxRetry)
            {
                //如果接口成功率大于等于预设，则恢复接口正常运行
                if (($conf['SUCCESS'] / $conf['RETRY'] * 100) >= self::$_FuseSuccessRate)
                {
                    //移除熔断
                    self::$_Redis->delete($key);
                    //更新接口状态
                    self::$_Redis->hMset(self::$_ServiceKey, array(
                        'STATE'    => 1,
                        'ERRORNUM' => 0
                    ));
                    //通知SOA更新接状态
                    \lib\Http::send(array(
                        'url' => SOA.'soa/system/synchApiState.html?token='.SOATOKEN.'&id='.self::$_Conf['SID'].'&state=1'
                    ), FALSE);
                }
                //否则清空重试次数和成功次数，再次检测成功率
                else
                {
                    //更新接口状态
                    self::$_Redis->hMset($key, array(
                        'RETRY'   => 0,
                        'SUCCESS' => 0
                    ));
                    //读取快照
                    self::readSnapShot();
                }
            }
            else
            {
                //设置随机种子
                $rand = rand(1, 2);
                //二分之一的几率走正常数据
                if ($rand == 2)
                {
                    //读取快照
                    self::readSnapShot();
                }
                else
                {
                    //设置标记为熔断,根据此标记系统会收集数据判断是否关闭熔断
                    $GLOBALS['_APISTATE'] = 3;
                }
            }
        }
        
        //生成带用户表示的KEY
        private static function _userKey()
        {
            $USERKEY  = 'SOA:S:DOWNSNAPSHOT:';
            $md5  = REQUESTURI;
            $md5 .= ( ! H('USERID')) ? 0 : H('USERID');
            $md5 .= ( ! H('DEVICECODE')) ? '' : H('DEVICECODE');
            $md5 .= ( ! H('DEVICENAME')) ? '' : H('DEVICENAME');
            $md5 .= ( ! H('MODEL')) ? '' : H('MODEL');
            $md5 .= ( ! H('APPVERSION')) ? '' : H('APPVERSION');
            $USERKEY .= md5($md5);
            return $USERKEY;
        }
        
    }