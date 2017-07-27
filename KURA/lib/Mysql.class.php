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

    //MYSQL操作类
    
    namespace lib;

    class Mysql {
        
        //定义成员属性
        private $_conn;                //数据库连接属性
        private $_host;                //主机
        private $_user;                //数据库用户名
        private $_pass;                //数据库密码
        private $_data;                //连接的数据库
        private $_port;                //端口
        private $_char;                //字符编码
        private $_prefix;              //数据表前缀
        private $_table;               //操作的数据表
        
        /*
         * 定于SQL语句的各个小模块
         */
        private $_select;
        private $_from;
        private $_table;
        private $_where;
        private $_order_by;
        private $_group_by;
        private $_limit;
        private $_join;
        
        //数据集
        private $_result;
        //定义接口
        private static $_instance;
        
        //构造函数中链接数据库
        private function __construct($config = array())
        {
            //初始化数据库链接配置信息
            $this->_init_config($config);
            //链接数据库
            $this->_link_to_db();
            //选择数据库
            $this->_choose_db();
            //设定字符集
            $this->_set_name();
        }
        
        //防止克隆
        private function __clone(){}
        
        //返回实例入口
        public static function getInstance($config = array())
        {
            if ( ! (self::$_instance instanceof self))
            {
                self::$_instance = new self($config);
            }
            return self::$_instance;
        }
        
        //组成SQL语句：SELECT `field`
        public function select($field = '')
        {
            $this->_select  = 'SELECT ';
            $this->_join    = '';
            $this->_where   = '';
            $this->_select .= ($field == '') ? '*' : $field;
            return $this;
        }
        
        //组成SQL语句：FROM `table`
        public function from($table = '')
        {
            if ($table == '')
            {
                error(304, lang(304));
            }
            $this->_from  = '';
            $this->_from  = ' FROM '.$this->_prefix.$table;
            $this->_table = $table;
            return $this;
        }
        
        //组成SQL语句：WHERE `field` = 'data'
        public function where($where = '')
        {
            $this->_where = '';
            $this->_where = ' WHERE '.$where;
            return $this;
        }
        
        //组成SQL语句：ORDER BY `field` DESC/ASC
        public function order($order_by = '')
        {
            if ($order_by == '')
            {
                return $this;
            }
            $this->_order_by = '';
            $this->_order_by = ' ORDER BY '.$order_by;
            return $this;
        }
        
        //组成SQL语句：GROUP BY `field`
        public function group($group_by = '')
        {
            if ($group_by == '')
            {
                return $this;
            }
            $this->_group_by = '';
            $this->_group_by = ' GROUP BY '.$group_by;
            return $this;
        }
        
        //组成SQL语句：LIMIT 10,1
        public function limit($limit = 1)
        {
            $this->_limit = '';
            $this->_limit = ' LIMIT '.$limit;
            return $this;
        }
        
        //组成SQL语句：INNER JOIN `table` ON ....
        public function join($join = '', $on = '', $model = 'INNER')
        {
            if ($join == '' OR $on == '')
            {
                return $this;
            }
            $this->_join .= ' '.$model.' JOIN '.$this->_prefix.$join.' ON '.$on;
            return $this;
        }
        
        //取单条数据
        public function one()
        {
            $this->_createSql();
            return $this->_result->fetch_assoc();
        }
        
        //取记录集
        public function all()
        {
            $this->_createSql();
            //临时数组
            $tmp = array();
            while (($row = $this->_result->fetch_assoc()) != FALSE) 
            {
                $tmp[] = $row;
            }
            return $tmp;
        }
        
        //插入数据
        public function add($data = array())
        {
            $stime = microtime(TRUE);
            //初始化SQL
            $sql = '';
            //组成SQL
            $sql = 'INSERT INTO `'.$this->_prefix.$this->_table.'` ';
            //遍历字段
            $field = $value = '(';
            $doc = '';
            foreach ($data as $key => $val)
            {
                $field .= $doc.'`'.$key.'`';
                $value .= $doc;
                $value .= ( ! is_numeric($val)) ? "'".trim($val)."'" : $val;
                $doc    = ',';
            }
            $field .= ')';
            $value .= ')';
            $sql   .= $field.' VALUES '.$value;
            //执行SQL
            if ( ! $this->_conn->query($sql))
            {
                error(305, lang(305).$sql.','.mysqli_error($this->_conn));
            }
            $etime = microtime(TRUE);
            $mysql = array(
                'SQL'  => $sql,
                'TIME' => round($etime - $stime, 4)
            );
            $GLOBALS['_log']['MYSQL'][] = $mysql;
            return mysqli_insert_id($this->_conn);
        }
        
        //更新数据
        public function edit($data = array())
        {
            $stime = microtime(TRUE);
            //组成SQL
            $sql = 'UPDATE `'.$this->_prefix.$this->_table.'` SET ';
            $doc = '';
            foreach ($data as $key => $val)
            {
                $sql .= $doc.'`'.$key.'` = ';
                if (is_numeric($val))
                {
                    $sql .= $val;
                }
                else
                {
                    $sql .= (preg_match('/`?[\w]+`?\s?[+|-]\s?[\d]+/', $val)) ? $val : '"'.$val.'"';
                }
                $doc = ',';
            }
            $sql .= $this->_where.$this->_limit;
            //执行SQL
            if ( ! $this->_conn->query($sql))
            {
                error(305, lang(305).$sql.','.mysqli_error($this->_conn));
            }
            $etime = microtime(TRUE);
            $mysql = array(
                'SQL'  => $sql,
                'TIME' => round($etime - $stime, 4)
            );
            $GLOBALS['_log']['MYSQL'][] = $mysql;
            return TRUE;
        }
        
        //删除数据
        public function delete()
        {
            $stime = microtime(TRUE);
            $sql  = 'DELETE FROM `'.$this->_prefix.$this->_table.'` ';
            $sql .= $this->_where;
            //执行SQL
            if ( ! $this->_conn->query($sql))
            {
                error(305, lang(305).$sql.','.mysqli_error($this->_conn));
            }
            $etime = microtime(TRUE);
            $mysql = array(
                'SQL'  => $sql,
                'TIME' => round($etime - $stime, 4)
            );
            $GLOBALS['_log']['MYSQL'][] = $mysql;
            return TRUE;
        }
        
        //直接执行SQL
        public function query($sql = '')
        {
            if ($sql == '')
            {
                return FALSE;
            }
            $stime = microtime(TRUE);
            $sql = str_replace('[prefix]', $this->_prefix, $sql);
            //执行SQL
            $this->_result = $this->_conn->query($sql);
            $etime = microtime(TRUE);
            $mysql = array(
                'SQL'  => $sql,
                'TIME' => round($etime - $stime, 4)
            );
            $GLOBALS['_log']['MYSQL'][] = $mysql;
            if ( ! $this->_result)
            {
                error(305, lang(305).$sql.','.mysqli_error($this->_conn));
            }
            return $this->_result;
        }
        
        //组装SQL语句
        private function _createSql()
        {
            $stime = microtime(TRUE);
            //拼接SQL语句
            $sql = '';
            $sql = $this->_select.
                   $this->_from.
                   $this->_join.
                   $this->_where.
                   $this->_order_by.
                   $this->_limit.
                   $this->_group_by;
            
            //执行查询
            $this->_result = $this->_conn->query($sql);
            $etime = microtime(TRUE);
            $mysql = array(
                'SQL'  => $sql,
                'TIME' => round($etime - $stime, 4)
            );
            $GLOBALS['_log']['MYSQL'][] = $mysql;
            //如果失败返回错误报告
            if ($this->_result === FALSE)
            {
                error(305, lang(305).$sql.','.mysqli_error($this->_conn));
            }
        }
        
        //初始化数据库链接配置信息
        private function _init_config($conf = array())
        {
            if (empty($conf))
            {
                //加载配置
                $conf = C('MYSQL');
                if ( ! $conf)
                error(301, lang(301));
            }
            $this->_host   = $conf[0];
            $this->_user   = $conf[1];
            $this->_pass   = $conf[2];
            $this->_data   = $conf[3];
            $this->_port   = $conf[4];
            $this->_char   = 'utf8';
            $this->_prefix = $conf[5];
        }
        
        //连接数据库
        private function _link_to_db()
        {
            $this->_conn = mysqli_connect($this->_host.':'.$this->_port,$this->_user,$this->_pass);
            if ( ! $this->_conn)
            error(302, lang(302));
        }
        
        //选择数据库
        private function _choose_db()
        {
            if ( ! mysqli_select_db($this->_conn, $this->_data))
            {
                mysqli_close($this->_conn);
                error(303, '数据库：'.$this->_data.lang(303));
            }
        }
        
        //设定字符集
        private function _set_name()
        {
            mysqli_query($this->_conn, 'SET NAMES '.$this->_char);
        }
        
    }