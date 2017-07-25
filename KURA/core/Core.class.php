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

    //核心文件

    namespace core;
    use core\Find;
    use core\Service;
    
    class Core{
        
        //初始化库拉
        public static function run($before = array(), $after = array())
        {
            //自动加载
            spl_autoload_register('Core\Core::autoload');
            //加载助手
            require ROOTPATH.'/KURA/helper/function.php';
            //通过URL执行服务发现
            $find = new Find();
            $find->run($before);
            //执行后置服务
            Service::run($after);
        }
        
        public static function autoload($class)
        {
            if (FALSE !== strpos($class, '\\'))
            {
                $name = strstr($class, '\\', TRUE);
            }
            else
            {
                $name = $class;
            }
            $class = str_replace('\\', '/', $class);
            //系统服务自动识别路径
            if (in_array($name, array('lib', 'core', 'service', 'vendor')))
            {
                //插件目录文件名自定义
                if ($name == 'vendor')
                {
                    $file = ROOTPATH.'/KURA/'.$class.'.php';
                }
                else
                {
                    $file = ROOTPATH.'/KURA/'.$class.CLASS_EXT.'.php';
                }
                if (is_file($file))
                {
                    require $file;
                }
                else
                {
                    error(101, lang(101).$file);
                }
            }
            else
            {
                $file = WORK_PATH.'/'.$class.CLASS_EXT.'.php';
                if (is_file($file))
                {
                    require $file;
                }
                else
                {
                    error(102, lang(102).$file);
                }
            }
        }
        
    }