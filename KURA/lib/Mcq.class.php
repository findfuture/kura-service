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

    //MEMCACHEQ 操作类

    namespace lib;

    class Mcq{
        
        private $_conn;
        private $_prefix;
        
        //定义接口
        private static $_instance;
        
        public function __construct()
        {
            //加载配置
            $conf = C('MQ');
            if ( ! $conf)
            {
                error(501, lang(501));
            }
            $this->_conn = new \Memcache();
            if ( ! $this->_conn->connect($conf[0], $conf[1], 3))
            {
                error(501, lang(502));
            }
            $this->_prefix = $conf[2];
        }
        
        //防止克隆
        private function __clone(){}
        
        //返回实例入口
        public static function getInstance()
        {
            if ( ! (self::$_instance instanceof self))
            {
                self::$_instance = new self();
            }
            return self::$_instance;
        }
        
        public function get($key)
        {
            $stime = microtime(TRUE);
            $queue = $this->_conn->get($this->_prefix.$key);
            $etime = microtime(TRUE);
            $data = array(
                'TYPE' => '[读取] -> '.$key,
                'TIME' => round($etime - $stime, 4),
                'DATA' => $queue
            );
            $GLOBALS['_log']['MQ'][] = $data;
            return $queue;
        }
        
        public function set($key, $value)
        {
            $val = (is_object($value) || is_array($value)) ? json_encode($value) : $value;
            $stime = microtime(TRUE);
            $this->_conn->set($this->_prefix.$key, $val, MEMCACHE_COMPRESSED, 0);
            $etime = microtime(TRUE);
            $data = array(
                'TYPE' => '[写入] -> '.$key,
                'TIME' => round($etime - $stime, 4),
                'DATA' => $value
            );
            $GLOBALS['_log']['MQ'][] = $data;
        }
        
    }