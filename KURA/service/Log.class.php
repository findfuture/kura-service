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

    //日志记录

    namespace service;

    class Log{
        
        //执行日志记录服务
        public static function run()
        {
            if (PROPATH == 'soa')
            {
                return TRUE;
            }
            //加载配置
            $conf = C('LOG');
            //日志黑名单
            $black = $conf[0];
            if ( ! empty($black))
            {
                $black = explode('|', $black);
                if (in_array(ROUTE, $black))
                return TRUE;
            }
            $log = $GLOBALS['_log'];
            //设置模块目录
            \SeasLog::setLogger('soa');
            $timeEnd = microtime(TRUE);
            //日志基本信息
            $log['INFO'] = array(
                //实例ID
                'EXAMPLE'  => C('EXAMPLE'),
                //所在环境
                'ONLINE'   => C('ONLINE'),
                //日志流水号
                'ORDERNO'  => date('YmdHis').rand(1000, 9999),
                //开始执行时间
                'STIME'    => $GLOBALS['_STIME'],
                //结束执行时间
                'ETIME'    => time(),
                //总耗时
                'ATIME'    => round($timeEnd - $GLOBALS['_SMICROTIME'], 4),
                //访问平台
                'PLATFORM' => CLIENT,
                //服务地址
                'SERVICE'  => $GLOBALS['_SERVICE'],
                //服务文件
                'SERVICE_FILE' => $GLOBALS['_SERVICE_FILE'],
                //是否是快照数据
                'SNAPSHOT' => (isset($GLOBALS['_SNAPSHOT'])) ? $GLOBALS['_SNAPSHOT'] : 0,
                //服务ID，在网关层设定
                'SID'      => (isset($GLOBALS['_SID'])) ? $GLOBALS['_SID'] : 0
            );
            $GLOBALS['_ALLTIME']    = $log['INFO']['ATIME'];
            //写日志
            \SeasLog::log(SEASLOG_INFO, json_encode($log));
        }
        
    }