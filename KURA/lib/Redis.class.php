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

    //REDIS操作类

    namespace lib;

    class Redis{
        
        private $_conn;
        private $_expire;
        private $_prefix;
        //操作的KEY
        private $_key;
        
        //定义接口
        private static $_instance;
        
        public function __construct($key = '')
        {
            //记在配置
            $conf = C('REDIS');
            if ( ! $conf)
            {
                error(601, lang(601));
            }
            $this->_conn = new \Redis();
            if ( ! $this->_conn->connect($conf[0], $conf[1], 3))
            {
                error(602, lang(602));
            }
            if ($conf[3] != '')
            {
                if ( ! $this->_conn->auth($conf[3]))
                error(603, lang(603));
            }
            $this->_expire = 0;
            $this->_prefix = $conf[2];
            $this->_key    = $key;
        }
        
        //防止克隆
        private function __clone(){}
        
        //返回实例入口
        public static function getInstance($key = '')
        {
            if ( ! (self::$_instance instanceof self))
            {
                self::$_instance = new self($key);
            }
            return self::$_instance;
        }
        
        //设置key
        public function setKey($key = '')
        {
            $this->_key = '';
            $this->_key = $key;
        }

        public function get($param = array())
        {
            //缓存名称
            $key  = $this->_prefix;
            $key .= ($this->_key != '') ? $this->_key : $param['key'];
            //缓存类型
            $type  = $param['type'];
            $stime = microtime(TRUE);
            switch ($type)
            {
                case 'get':
                    $data = $this->_conn->get($key);
                break;
                case 'hmget':
                    $field = $param['field'];
                    $data = $this->_conn->hmGet($key, $field);
                break;
                case 'hgetall':
                    $data = $this->_conn->hGetAll($key);
                break;
                case 'lrange':
                    $s = $param['start'];
                    $e = $param['end'];
                    $data = $this->_conn->lrange($key, $s, $e);
                break;
                case 'zrange':
                    $s = $param['start'];
                    $e = $param['end'];
                    $data = $this->_conn->zRange($key, $s, $e);
                break;
                case 'zrevrange':
                    $s = $param['start'];
                    $e = $param['end'];
                    $data = $this->_conn->zRevRange($key, $s, $e);
                break;
                case 'zrangebyscore':
                    $s = $param['start'];
                    $e = $param['end'];
                    $p = $param['param'];
                    $data = $this->_conn->zRangeByScore($key, $s, $e, $p);
                break;
                case 'zrevrangebyscore':
                    $s = $param['start'];
                    $e = $param['end'];
                    $p = $param['param'];
                    $data = $this->_conn->zRevRangeByScore($key, $s, $e, $p);
                break;
                case 'zcard':
                    $data = $this->_conn->zCard($key);
                break;
                case 'zscore':
                    $val = $param['val'];
                    $data = $this->_conn->zScore($key, $val);
                break;
                case 'exists':
                    $data = $this->_conn->exists($key);
                break;
            }
            $etime = microtime(TRUE);
            $redis = array(
                'TYPE' => '[读取] -> '.$type,
                'KEY'  => $key,
                'TIME' => round($etime - $stime, 4)
            );
            $GLOBALS['_log']['REDIS'][] = $redis;
            return $data;
        }
        
        public function set($param = array())
        {
            //缓存名称
            $key  = $this->_prefix;
            $key .= ($this->_key != '') ? $this->_key : $param['key'];
            //缓存类型
            $type  = $param['type'];
            if (isset($param['data']))
            //缓存数据
            $data  = $param['data'];
            $stime = microtime(TRUE);
            switch ($type)
            {
                case 'hmset':
                    $this->_conn->hMset($key, $data);
                break;
                case 'zadd':
                    $order = $param['order'];
                    $this->_conn->zAdd($key, $order, $data);
                break;
                case 'hincrby':
                    $field = $param['field'];
                    $this->_conn->hIncrBy($key, $field, $data);
                break;
                case 'set':
                    if ( ! isset($param['expire']))
                    {
                        $expire = $this->_expire;
                    }
                    else
                    {
                        $expire = $param['expire'];
                    }
                    $data = (is_object($data) || is_array($data)) ? json_encode($data) : $data;
                    if(is_int($expire) && $expire)
                    {
                        $this->_conn->setex($key, $expire, $data);
                    }
                    else
                    {
                        $this->_conn->set($key, $data);
                    }
                break;
                case 'incr':
                    $this->_conn->incr($key);
                break;
                case 'lpush':
                    $this->_conn->lPush($key, $data);
                break;
                case 'rpush':
                    $this->_conn->rPush($key, $data);
                break;
            }
            $etime = microtime(TRUE);
            $setData = array(
                'TYPE' => '[写入] -> '.$type,
                'KEY'  => $key,
                'TIME' => round($etime - $stime, 4)
            );
            if (isset($param['data']))
            $setData['DATA'] = $data;
            $GLOBALS['_log']['REDIS'][] = $setData;
        }
        
        public function del($param = array())
        {
            //缓存名称
            $key  = $this->_prefix;
            $key .= ($this->_key != '') ? $this->_key : $param['key'];
            //缓存类型
            $type  = $param['type'];
            $stime = microtime(TRUE);
            switch ($type)
            {
                case 'delete':
                    $this->_conn->delete($key);
                break;
                case 'zrem':
                    $val  = $param['val'];
                    $this->_conn->zRem($key, $val);
                    $key .= ' -> '.$val;
                break;
            }
            $etime = microtime(TRUE);
            $GLOBALS['_log']['REDIS'][] = array(
                'TYPE' => '[删除] -> '.$type,
                'KEY'  => $key,
                'TIME' => round($etime - $stime, 4)
            );
        }
        
    }