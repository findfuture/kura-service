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

    //根据URL发现服务

    namespace core;
    use core\Route;
    use core\Service;
    
    class Find{
        
        //项目目录
        protected $_pro;
        //服务方法
        protected $_action;
        //当前URL
        protected $_url;
        
        //执行发现
        public function run($before = [])
        {
            //URL地址
            $this->_url = $_SERVER['REQUEST_URI'];
            $urlArr = explode('/', $this->_url);
            if (LEVEL == 0 && $this->_url == '/')
            {
                error(403, lang(403));
            }
            else
            {
                if ((count($urlArr) - LEVEL - LEVEL) == 1)
                error(403, lang(403));
            }
            //获取服务根目录
            if ( ! isset($urlArr[(LEVEL + 1)]))
            {
                error(401, lang(401));
            }
            $this->_pro = $urlArr[(LEVEL + 1)];
            if ($this->_pro != 'kura-init' && 
                    ! is_dir(WORK_PATH.'/'.$this->_pro))
            {
                error(404, lang(404).$this->_pro);
            }
            else if ($this->_pro == 'kura-init')
            {
                //初始化项目
                \service\Init::work($urlArr[(LEVEL + 2)]);
            }
            define('PROPATH', $this->_pro);
            //定义请求模式
            define('METHOD', $_SERVER['REQUEST_METHOD']);
            //定义原始URL
            define('REQUESTURI', $this->_url);
            //执行配置初始化，从SOA平台拉取
            Service::run('init');
            //加载公用函数
            require WORK_PATH.'/global.php';
            //路由解析类
            $route = new Route($this->_pro, $urlArr[(LEVEL + 2)]);
            //解析路由
            $routeData = $route->run();
            //解析出路由，则匹配路由指向的服务
            if ($routeData !== FALSE)
            {
                $path = $this->_pro.'/'.$routeData;
            }
            //如果不存在路由则按URL路径进行操作
            else
            {
                $path = $this->_url;
            }
            //校验服务文件是否存在
            $service = $this->_serviceFile($path);
            if ( ! $service)
            {
                error(405, lang(405));
            }
            $service = str_replace('/', '\\', $service);
            //服务类
            if ( ! class_exists($service))
            {
                error(406, lang(406).$service);
            }
            //初始化类
            $obj = new $service();
            //执行的方法
            $action = $this->_action;
            $actionArr = explode('->', $action);
            $action = $actionArr[0];
            unset($actionArr[0]);
            if ( ! empty($actionArr))
            {
                //定义反射标识
                $GLOBALS['_REFLEX'] = TRUE;
            }
            if ( ! method_exists($obj, $action))
            {
                error(407, lang(407).$action);
            }
            $GLOBALS['_SERVICE'] = 'http://'.$_SERVER['SERVER_NAME'].$this->_url;
            //执行前置任务
            Service::run($before);
            //判断是否是快照输出
            if (isset($GLOBALS['_SNAPSHOT']) && $GLOBALS['_SNAPSHOT'])
            {
                return TRUE;
            }
            $obj->$action();
            //执行版本反射
            if (empty($actionArr))
            {
                return TRUE;
            }
            foreach ($actionArr as $K => $V)
            {
                if ($K == count($actionArr))
                unset($GLOBALS['_REFLEX']);
                $newService = preg_replace('/(.*)\\\(.*)\\\(.*)/', "$1\\\\$2\\$V\\\\$3", $service);
                $newService = new $newService();
                $result  = isset($GLOBALS['_RETURN']) ? json_decode($GLOBALS['_RETURN'], TRUE) : [];
                $result  = (empty($result)) ? $result : $result['data'];
                $newService->$action($result);
            }
        }
        
        //确定具体的服务文件
        private function _serviceFile($path)
        {
            //拆分目录
            $path = explode('/', $path);
            //服务文件
            $file = '';
            foreach ($path as $key => $val)
            {
                $file .= $val;
                $serviceFile = WORK_PATH.'/'.$file.CLASS_EXT.'.php';
                if ( ! is_file($serviceFile))
                {
                    $file .= '/';
                    continue;
                }
                $this->_action = $path[$key + 1];
                $GLOBALS['_SERVICE_FILE'] = $serviceFile.' -> '.$this->_action;
                return $file;
            }
            return FALSE;
        }
        
    }