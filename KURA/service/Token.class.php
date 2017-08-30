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

    //密钥验证

    namespace service;

    class Token{
        
        //执行密钥验证
        public static function run()
        {
            //客户端标识列表
            $client = [
                'ios', 
                'android', 
                'web', 
                'wap', 
                'tv', 
                'pc'
            ];
            $white = C('WHITEROUTE');
            if ($white === FALSE)
            {
                error(208, lang(208));
            }
            if (in_array('*', $white) || in_array(ROUTE, $white))
            {
                if (isset($_SERVER['HTTP_CLIENT']) &&
                        in_array($_SERVER['HTTP_CLIENT'], $client))
                {
                    $client = $_SERVER['HTTP_CLIENT'];
                }
                else
                {
                    $client = ( ! G('clock')) ? 'http' : 'clock';
                }
                define('CLIENT', $client);
                return TRUE;
            }
            //没有指定平台标识
            if ( ! isset($_SERVER['HTTP_CLIENT']) || 
                    ! in_array($_SERVER['HTTP_CLIENT'], $client))
            {
                error(201, lang(201));
            }
            define('CLIENT', $_SERVER['HTTP_CLIENT']);
            //加载项目配置
            $appid  = C('APPID');
            $secret = C('SECRET');
            if ( ! isset($_SERVER['HTTP_APPID']) || 
                    $_SERVER['HTTP_APPID'] != $appid[CLIENT])
            {
                error(202, lang(202));
            }
            $appId = $_SERVER['HTTP_APPID'];
            if ( ! isset($_SERVER['HTTP_NONCE']) || 
                    $_SERVER['HTTP_NONCE'] == '')
            {
                error(203, lang(203));
            }
            $nonce = $_SERVER['HTTP_NONCE'];
            if ( ! isset($_SERVER['HTTP_CURTIME']) || 
                    $_SERVER['HTTP_CURTIME'] == '')
            {
                error(204, lang(204));
            }
            $curtime = $_SERVER['HTTP_CURTIME'];
            if ( ! isset($_SERVER['HTTP_OPENKEY']))
            {
                error(205, lang(205));
            }
            if (time() - $curtime > 10)
            {
                error(206, lang(206));
            }
            $openKey = $_SERVER['HTTP_OPENKEY'];
            if ($openKey != md5($appId.$nonce.$curtime.$secret[CLIENT]))
            {
                error(207, lang(207));
            }
        }
        
    }