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
            $data = \lib\Http::send([
                'url' => SOA.'soa/system/synch.html?token='.SOATOKEN.'&id='.$example.'&online='.$online.'&account='.$account
            ], FALSE);
            if ( ! isset($data['code']) || $data['code'] != 100)
            {
                return TRUE;
            }
            $data = $data['msg'];
            $init = [];
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
                $init = [];
                foreach ($data['account'] as $row)
                {
                    $init[$row['username']] = [
                        'token' => md5($row['username'].$row['state'].SOATOKEN)
                    ];
                }
                $val = '<?PHP return '.var_export($init, TRUE).';';
                writeFile($accountHash, $val);
            }
            return TRUE;
        }
        
        //初始化项目
        public static function work($work)
        {
            //项目目录
            $workPath = ROOTPATH.'/'.WORK_PATH.'/'.$work;
            if (is_dir($workPath))
            {
                error(105, lang(105));
            }
            //创建项目根目录
            mkdir($workPath);
            //创建路由文件
            $php  = "<?php\r\n";
            $php .= "\r\n";
            $php .= "    /*\r\n";
            $php .= "     * 服务路由配置文件\r\n";
            $php .= "     */\r\n";
            $php .= "\r\n";
            $php .= "    \$route = array(\r\n";
            $php .= "\r\n";
            $php .= "    );";
            file_put_contents($workPath.'/route.php', $php);
            //创建配置文件
            $php  = "<?php\r\n";
            $php .= "\r\n";
            $php .= "    define('CONF', TRUE);\r\n";
            $php .= "\r\n";
            $php .= "    /*\r\n";
            $php .= "     * 项目配置文件\r\n";
            $php .= "     */\r\n";
            $php .= "\r\n";
            $php .= "    \$conf = array();\r\n";
            $php .= "\r\n";
            $php .= "    //核心配置区域 =======================================================\r\n";
            $php .= "\r\n";
            $php .= "    //服务是否上线，0：测试，1：上线\r\n";
            $php .= "    //系统会根据此项配置调用不同的参数配置\r\n";
            $php .= "    \$conf['ONLINE']  = 0;\r\n";
            $php .= "    //实例ID，在SOA后台查看\r\n";
            $php .= "    \$conf['EXAMPLE'] = 0;\r\n";
            $php .= "    //路由白名单，\"*\"表示所有路由均在白名单中，不进行TOKEN验证\r\n";
            $php .= "    \$conf['WHITEROUTE'] = array(\r\n";
            $php .= "    );\r\n";
            $php .= "    //通用HEADER头\r\n";
            $php .= "    \$conf['HEADER'] = array(\r\n";
            $php .= "        'Content-type:application/x-www-form-urlencoded;charset=UTF-8'\r\n";
            $php .= "    );\r\n";
            $php .= "    //IO过滤配置\r\n";
            $php .= "    \$conf['IO'] = 'escape|sql|xss';\r\n";
            $php .= "    //网关白名单\r\n";
            $php .= "    \$conf['WHITEGETWAY'] = array(\r\n";
            $php .= "    );\r\n";
            $php .= "\r\n";
            $php .= "    return \$conf;";
            file_put_contents($workPath.'/conf.php', $php);
            //创建SPI目录
            $workPath .= '/spi/';
            mkdir($workPath);
            //创建SPI公用父类
            $php  = "<?php\r\n";
            $php .= "\r\n";
            $php .= "    namespace ".$work."\spi;\r\n";
            $php .= "\r\n";
            $php .= "    class Init {\r\n";
            $php .= "\r\n";
            $php .= "        public function __construct(){\r\n";
            $php .= "\r\n";
            $php .= "        }\r\n";
            $php .= "\r\n";
            $php .= "    }";
            file_put_contents($workPath.'/Init.class.php', $php);
            error(106, lang(106));
        }
        
    }