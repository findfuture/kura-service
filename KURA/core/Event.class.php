<?php

   /*
    * -----------------------------------------------
    * 云掌财经SOA微服务框架监听SOA发送的事件
    * -----------------------------------------------
    * 使用框架：KURA V2.0.1
    * 开发人员：苏睿 / surui@123.com.cn
    * 最后更新日期：2017/05/07
    * -----------------------------------------------
    * SVN:
    * GIT:
    * -----------------------------------------------
    */

    //核心文件

    namespace core;
    
    class Event{
        
        public static function on($event = '', $callback = '')
        {
            if (G('token') != SOATOKEN)
            {
                error(101, 'SOA密钥验证失败！');
            }
            if (ROUTE == $event)
            {
                $callback(G());
            }
            else
            {
                return FALSE;
            }
        }
        
    }