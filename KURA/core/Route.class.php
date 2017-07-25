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

    //服务路由

    namespace core;
    
    class Route{
        
        private $_pro;
        private $_route;
        
        public function __construct($pro, $route)
        {
            $this->_pro = $pro;
            $this->_route = $route;
        }
        
        //解析路由
        public function run()
        {
            $routeFile = WORK_PATH.'/'.$this->_pro.'/route.php';
            if ( ! is_file($routeFile))
            {
                return FALSE;
            }
            //载入路由配置
            require $routeFile;
            //匹配路由
            if ( ! empty($route) && isset($route[$this->_route]))
            {
                define('ROUTE', $this->_route);
                return $route[$this->_route];
            }
            return FALSE;
        }
        
    }