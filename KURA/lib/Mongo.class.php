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

    //MONBODB 操作类

    namespace lib;

    class Mongo{
        
        private $_conn;
        private $_prefix;
        
        /*
         * MONGODB语句的各个小模块
         */
        private $_select;
        private $_from;
        private $_where;
        private $_order;
        private $_limit;
        
        //定义接口
        private static $_instance;
        
        public function __construct()
        {
            //加载配置
            $conf = C('MONGO');
            if ( ! $conf)
            {
                error(801, lang(801));
            }
            try{
                $Mongo = new \MongoClient('mongodb://'.$conf[1].':'.$conf[2].'@'.$conf[0].':'.$conf[4].'/'.$conf[3]);
            }
            catch(\MongoConnectionException $exception){
                L($exception, 'mongodb');
                error(802, lang(802));
            }
            $this->_conn   = $Mongo->$conf[3];
            $this->_prefix = $conf[5];
            $this->_where  = array();
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
        
        //设置字段
        public function select($fields = '')
        {
            $this->_select = array();
            if ($fields == '')
            {
                return $this;
            }
            $arr = explode(',', $fields);
            $tmp = array();
            foreach ($arr as $field)
            {
                $tmp[$field] = 1;
            }
            $this->_select = $tmp;
            return $this;
        }
        
        //设置表
        public function from($table = '')
        {
            $this->_from = '';
            $this->_from = $this->_prefix.$table;
            return $this;
        }
        
        //设置排序
        public function order($order = '')
        {
            $arr = explode(' ', $order);
            $this->_order = array(
                $arr[0] => (strtolower($arr['1']) == 'desc' ? -1 : 1)
            );
            return $this;
        }
        
        //设置条件
        public function where($where = array())
        {
            if (empty($where))
            {
                return $this;
            }
            $this->_where = $where;
            return $this;
        }
        
        //设置条数
        public function limit($limit = '')
        {
            $this->_limit = $limit;
            return $this;
        }
        
        //增加
        public function add($data = array())
        {
            $table = $this->_from;
            $this->_conn->$table->insert($data);
            $this->_where = array();
        }
        
        //编辑
        public function edit($data = array())
        {
            $table = $this->_from;
            $this->_conn->$table->update($this->_where, array(
                '$set' => $data
            ));
            $this->_where = array();
        }
        
        //删除
        public function delete()
        {
            $table = $this->_from;
            $this->_conn->$table->remove($this->_where);
            $this->_where = array();
        }
        
        //查询全部
        public function all()
        {
            $table  = $this->_from;
            $result = $this->_conn->$table->find($this->_where, $this->_select);
            if ( ! empty($this->_order))
            {
                $result->sort($this->_order);
            }
            if ($this->_limit != '')
            {
                if (is_numeric($this->_limit))
                {
                    $result->limit($this->_limit);
                }
                else
                {
                    $arr = explode(',', $this->_limit);
                    $result->limit(trim($arr[1]));
                    $result->skip(trim($arr[0]));
                }
            }
            $tmp = array();
            foreach ($result as $row)
            {
                //排除默认_id
                unset($row['_id']);
                $tmp[] = $row;
            }
             //重置where
            $this->_where = array();
            $this->_order = array();
            $this->_limit = '';
            return $tmp;
        }
        
        //查询单条
        public function one()
        {
            $table = $this->_from;
            $result = $this->_conn->$table->findOne($this->_where, $this->_select);
            //排除默认_id
            unset($result['_id']);
            //重置where
            $this->_where = array();
            $this->_order = array();
            $this->_limit = '';
            return $result;
        }
        
        //查总数
        public function count()
        {
            $table = $this->_from;
            $count = $this->_conn->$table->find($this->_where)->count();
            //重置where
            $this->_where = array();
            return $count;
        }
        
    }