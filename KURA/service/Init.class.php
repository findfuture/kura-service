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

    //与SOA服务器同步初始化配置数据

    namespace service;

    class Init{
        
        //执行同步
        public static function run($active = FALSE, $example = FALSE, $online = FALSE)
        {
            if ( ! $active && PROPATH == 'soa')
            {
                return TRUE;
            }
            //实例ID
            $example = ($example !== FALSE) ? $example : C('EXAMPLE');
            //所在环境
            $online  = ($online !== FALSE) ? $online : C('ONLINE');
            //检测是否初始化过
            $hash = ROOTPATH.'/KURA/init/'.md5($example.$online).'.php';
            if ( ! $active && is_file($hash))
            {
                return TRUE;
            }
            //判断是否需要同步开发人员数据
            $accountHash = ROOTPATH.'/KURA/init/'.md5('account').'.php';
            $account = (is_file($accountHash)) ? 0 : 1;
            $data = \lib\Http::send(array(
                'url' => SOA.'soa/system/synch.html?token='.SOATOKEN.'&id='.$example.'&online='.$online.'&account='.$account
            ), FALSE);
            if ( ! isset($data['code']) || $data['code'] != 100)
            {
                return TRUE;
            }
            $data = $data['msg'];
            $init = array();
            $init['STATE'] = $data['state'];
            if ($online)
            {
                $init['URL']    = $data['proUrl'];
                $init['MYSQL']  = json_decode($data['proMysql'], TRUE);
                $init['REDIS']  = json_decode($data['proRedis'], TRUE);
                $init['MONGO']  = json_decode($data['proMongo'], TRUE);
                $init['MQ']     = json_decode($data['proMq'], TRUE);
                $init['LOG']    = json_decode($data['proLog'], TRUE);
                $init['KEY']    = $data['proKey'];
                $init['APPID']  = json_decode($data['appidOnline'], TRUE);
                $init['SECRET'] = json_decode($data['secretOnline'], TRUE);
            }
            else
            {
                $init['URL']    = $data['testUrl'];
                $init['MYSQL']  = json_decode($data['testMysql'], TRUE);
                $init['REDIS']  = json_decode($data['testRedis'], TRUE);
                $init['MONGO']  = json_decode($data['testMongo'], TRUE);
                $init['MQ']     = json_decode($data['testMq'], TRUE);
                $init['LOG']    = json_decode($data['testLog'], TRUE);
                $init['KEY']    = $data['testKey'];
                $init['APPID']  = json_decode($data['appid'], TRUE);
                $init['SECRET'] = json_decode($data['secret'], TRUE);
            }
            $val = '<?PHP return '.var_export($init, TRUE).';';
            writeFile($hash, $val);
            /*
             * 说明：C方法中会默认加载项目配置conf.php
             * 同步方法开头用到了C方法，所以此时的GLOBALS里的_conf是含有项目默认配置信息的
             * 然后此处将默认配置和SOA的实例配置合并，方便调用
             */
            $conf = require $hash;
            $GLOBALS['_conf'] = array_merge($GLOBALS['_conf'], $conf);
            /*
             * 这里需要判断是因为同步可能是2次，一次测试环境，一次开发环境
             * 如果这里不判断，则不管是同步测试还是开发，都会同步一次开发人员信息，增加额外不必要的开销
             */
            if ($account)
            {
                $init = array();
                foreach ($data['account'] as $row)
                {
                    $init[$row['username']] = array(
                        'token' => md5($row['username'].$row['state'].SOATOKEN)
                    );
                }
                $val = '<?PHP return '.var_export($init, TRUE).';';
                writeFile($accountHash, $val);
            }
            return TRUE;
        }
        
    }