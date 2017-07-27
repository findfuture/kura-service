<?php

    /*
     * 库拉驱动
     */

    //记录开始运行时间
    $GLOBALS['_SMICROTIME'] = microtime(TRUE);
    $GLOBALS['_STIME'] = time();

    //禁止输出报错
    //error_reporting(0);
    //捕获系统异常
    register_shutdown_function('shutdownCallback');
    function shutdownCallback()
    {
        if ( ! defined('GETWAY'))
        {
            return TRUE;
        }
        $error = error_get_last();
        if ( ! empty($error))
        {
            //捕获到异常则熔断接口，代码级错误立即熔断
            \service\Getway::changeApiState(3, 'code', array(
                'TYPE'   => 2,
                'EID'    => C('EXAMPLE'),
                'ONLINE' => C('ONLINE'),
                'API'    => REQUESTURI,
                'MSG'    => $error['message'],
                'FILE'   => $error['file'],
                'LINE'   => $error['line']
            ));
            if ( ! isset($GLOBALS['_JSON']))
            //输出快照
            \service\Getway::readSnapShot();
        }
        //守护进程
        \service\Guard::run();
    }
    //定义页面编码
    header('Content-type:text/html;charset=UTF-8');
    //设置时区
    date_default_timezone_set('PRC');
   
    //系统常量定义
    const VERSION = '1.0.1';
    //版权
    const COPYRIGHT = '云掌财经';
    //项目配置
    $GLOBALS['_conf'] = array();
    //日志
    $GLOBALS['_log']  = array();
    
    //工作目录
    define('WORK_PATH', 'work');
    //类后缀
    define('CLASS_EXT', '.class');
    //文件缓存目录
    define('CACHE_PATH', 'cache');
    //语言包目录
    define('LANG_PATH', 'lang');
    //日志目录
    define('LOG_PATH', 'log');
    //上传目录 
    define('UPLOAD_PATH', 'upload');
    
    //加载语言包
    $lang = require LANG_PATH.'/zh-cn.php';
    //加载核心文件
    require 'core/Core'.CLASS_EXT.'.php';
    
    //配置前置服务，系统运行前会自动启动以下配置的服务
    $before = array();
    //TOKEN验证
    $before[] = 'token';
    //网关
    //$before[] = 'getway';
    
    //配置后置服务，系统运行结束后执行以下配置的服务
    $after = array();
    //记录日志
    //$after[] = 'log';

    //运行库拉
    core\Core::run($before, $after);