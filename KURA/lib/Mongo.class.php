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
        private $_db;
        private $_prefix;
        
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
            $this->_prefix = $conf[5];
            $this->_conn = new \Mongo();
            if ( ! $this->_conn->connect('mongodb://'.$conf[1].':'.$conf[2].'@'.$conf[0].':'.$conf[4].'/'.$conf[3]))
            {
                error(802, lang(802));
            }
            $this->_db = $this->_conn->$conf[3];
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
        
        //增加
        public function add($table = '', $data = array())
        {
            $table = $this->_prefix.$table;
            $this->_db->$table->insert($data);
        }
        
        //编辑
        public function edit()
        {
            
        }
        
        //删除
        public function delete()
        {
            
        }
        
        //查询全部
        public function all()
        {
            
        }
        
        //查询单条
        public function one()
        {
            
        }
        
        //查总数
        public function total()
        {
            
        }
        
    }