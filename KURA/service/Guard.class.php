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

    //接口健康状态守护进程

    namespace service;

    class Guard{
        
        //执行守护
        public static function run()
        {
            //如果没启用网关则不做处理
            if ( ! defined('GETWAY'))
            {
                return TRUE;
            }
            //只有是一次完整的快照调用，才不继续执行后续的收集数据工作，因为快照不会进入到服务控制器中执行代码逻辑，无需收集
            //用于服务接口出现异常，熔断或降级后的直接抛出快照
            if ($GLOBALS['_SNAPSHOT'] && 
                    ! $GLOBALS['_APISTATE'] && 
                    ! isset($GLOBALS['_ERROR']))
            {
                return TRUE;
            }
            //如果没有程序没有遇到报错，接口级或者程序级，则执行降级模块的一系列操作
            if ( ! isset($GLOBALS['_SHUTDOWNERROR']))
            {
                //获取程序运行总耗时
                $ATime = $GLOBALS['_ALLTIME'];
                //降级处理
                \service\Getway::down($ATime);
            }
            if ($GLOBALS['_APISTATE'] > 0)
            {
                switch($GLOBALS['_APISTATE'])
                {
                    //更新熔断重试次数
                    case 3:
                        $success = (isset($GLOBALS['_SHUTDOWNERROR'])) ? 0 : 1;
                        \service\Getway::fuseRetry($success);
                    break;
                    //判断接口是否可以恢复降级
                    case 4:
                        \service\Getway::downRecovery();
                    break;
                }
            }
            //推送错误日志
            if (isset($GLOBALS['_ERROR']))
            {
                \SeasLog::setLogger('errorLog');
                \SeasLog::log(SEASLOG_INFO, json_encode($GLOBALS['_ERROR'], TRUE));
            }
        }
        
    }