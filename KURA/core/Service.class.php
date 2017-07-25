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

    //执行系统服务

    namespace core;
    
    class Service{
        
        //执行服务
        public static function run($q = array())
        {
            if (empty($q))
            {
                return FALSE;
            }
            if ( ! is_array($q))
            {
                $service = '\\service\\'.ucfirst($q);
                $service::run();
            }
            else
            {
                foreach ($q as $service)
                {
                    $service = '\\service\\'.ucfirst($service);
                    $service::run();
                }
            }
        }
        
    }