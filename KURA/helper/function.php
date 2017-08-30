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

    //库拉常用函数库
    
    //加载项目配置
    function C($key = '')
    {
        if ( ! defined('CONF'))
        {
            $conf = require WORK_PATH.'/'.PROPATH.'/conf.php';
            if (PROPATH != 'soa')
            {
                $example = $conf['EXAMPLE'];
                $online  = $conf['ONLINE'];
                $hash    = ROOTPATH.'/KURA/init/'.md5($example.$online).'.php';
                if (is_file($hash))
                {
                    $hash = require $hash;
                    $conf = array_merge($conf, $hash);
                }
            }
            $GLOBALS['_conf'] = $conf;
        }
        if ($key != '')
        {
            return ( ! isset($GLOBALS['_conf'][$key])) ? FALSE : $GLOBALS['_conf'][$key];
        }
        return $GLOBALS['_conf'];
    }
    
    //文件缓存
    function F($name, $val = '', $time = 600)
    {
        //缓存目录
        if ($name == '')
        {
            return FALSE;
        }
        $doc = '';
        //HASH缓存目录
        $pathArr = array_slice(str_split($hash = md5($name), 2), 0, 2);
        $cache = ROOTPATH.'/KURA/'.CACHE_PATH.'/';
        foreach ($pathArr as $p)
        {
            $cache .= $doc.$p;
            if ( ! is_dir($cache)) mkdir($cache, 0777);
            $doc = '/';
        }
        $cache .= '/'.$hash.'.php';
        //读取缓存
        if ($val == '')
        {
            if ( ! is_file($cache))
            {
                return FALSE;
            }
            $data = include($cache);
            $cacheTime = $data[1];
            if ($cacheTime == 0)
            {
                return $data[0];
            }
            if (time() >= $cacheTime)
            {
                return FALSE;
            }
            return $data[0];
        }
        //删除缓存
        else if (is_null($val))
        {
            if ( ! is_file($cache))
            {
                return FALSE;
            }
            unlink($cache);
        }
        //设置缓存
        else
        {
            if ($val == '' OR empty($val))
            {
                return FALSE;
            }
            $time = ($time == 0) ? 0 : time() + $time;
            $val = '<?PHP return '.var_export([$val, $time], TRUE).';';
            writeFile($cache, $val);
            return TRUE;
        }
    }
    
    //MYSQL数据库实例
    function D($table = '')
    {
        $instance = \lib\Mysql::getInstance();
        if ($table == '')
        {
            return $instance;
        }
        $instance->from($table);
        return $instance;
    }
    
    //队列
    function Q($key, $val = '')
    {
        $Mcq = \lib\Mcq::getInstance();
        if ($val == '')
        {
            return $Mcq->get($key);
        }
        else
        {
            $Mcq->set($key, $val);
        }
    }
    
    //REDIS实例
    function R($key = '')
    {
        $instance = \lib\Redis::getInstance($key);
        if ($key == '')
        {
            return $instance;
        }
        $instance->setKey($key);
        return $instance;
    }
    
    //MONGODB实例
    function M($table = '')
    {
        $instance = \lib\Mongo::getInstance($table);
        if ($table == '')
        {
            return $instance;
        }
        $instance->from($table);
        return $instance;
    }
    
    //GET
    function G($key = '', $default = '', $replace = '')
    {
        if ($key == '')
        return $_GET;
        return \lib\Io::get($key, $default, $replace);
    }
    
    //POST
    function P($key = '', $default = '', $replace = '')
    {
        if ($key == '')
        return $_POST;
        return \lib\Io::post($key, $default, $replace);
    }
    
    //HEADER
    function H($key)
    {
        $key = strtoupper($key);
        $key = 'HTTP_'.$key;
        if ( ! isset($_SERVER[$key]))
        {
            return FALSE;
        }
        return trim($_SERVER[$key]);
    }
    
    //写日志
    function L($val = '', $path = '')
    {
        $log = ROOTPATH.'/KURA/'.LOG_PATH;
        if ($path != '')
        {
            $log .= '/'.$path;
            if ( ! is_dir($log))
                mkdir($log);
        }
        $log .= '/'.date('Ymd').'.log';
        file_put_contents($log, $val."\r\n", FILE_APPEND);
    }
    
    //引入插件
    function V($file = '')
    {
        $file = ROOTPATH.'/KURA/vendor/'.$file;
        if ( ! is_file($file))
        {
            error(103, lang(103));
        }
        require $file;
    }
    
    //读取静态资源
    function S($file = '')
    {
        $file = ROOTPATH.'/KURA/static/'.$file;
        if ( ! is_file($file))
        {
            error(104, lang(104));
        }
        return file_get_contents($file);
    }
    
    //输出JSON数据
    function json($data = [], $isJson = FALSE)
    {
        //设置输出标识，避免SHUTDOWNCALLBACK重复输出
        $GLOBALS['_JSON'] = TRUE;
        if ($isJson)
        {
            echo $data;
        }
        else if (isset($GLOBALS['_SHUTDOWNERROR']))
        {
            //如果有SHUTDOWN，表示有接口出错，则直接抛出快照
            \service\Getway::readSnapShot();
        }
        else
        {
            $json = [];
            if ( ! isset($data['code']))
            {
                $json['result']['code'] = 100;
            }
            else
            {
                $json['result']['code'] = $data['code'];
                unset($data['code']);
            }
            if ( ! isset($data['msg']))
            {
                $json['result']['msg'] = '执行成功';
            }
            else
            {
                $json['result']['msg'] = $data['msg'];
                unset($data['msg']);
            }
            if ( ! isset($data['list']))
            {
                $data['list'] = [];
            }
            $json['data'] = $data;
            $data = json_encode($json, JSON_UNESCAPED_UNICODE);
            $GLOBALS['_RETURN'] = $data;
            if (isset($GLOBALS['_REFLEX']) && $GLOBALS['_REFLEX'])
            {
                return TRUE;
            }
            //GET模式保存快照
            if (defined('GETWAY') && METHOD == 'GET')
            {
                //保存无登录状态快照
                \service\Getway::snapshot($data);
            }
            echo $data;
        }
    }
    
    //错误提示
    function error($code, $msg)
    {
        $return = [];
        $return['result']['code'] = $code;
        $return['result']['msg']  = $msg;
        exit(json_encode($return, JSON_UNESCAPED_UNICODE));
    }
    
    //加载语言包
    function lang($code = 0)
    {
        global $lang;
        return (isset($lang[$code])) ? $lang[$code] : FALSE;
    }
    
    /*
     * 写文件操作，多处用到
     * 1、文件缓存
     * 2、LOG生成
     * 3、同步配置
     */
    function writeFile($file = '', $val = '', $model = 'w')
    {
        $fopen = fopen($file, $model);
        if ( ! $fopen)
        {
            return FALSE;
        }
        if ( ! flock($fopen, LOCK_EX))
        {
            return FALSE;
        }
        if ( ! fwrite($fopen, $val))
        {
            return FALSE;
        }
        flock($fopen, LOCK_UN);
        fclose($fopen);
    }
    
    //随机字符串
    function random($type = 'numletter', $len = 4)
    {
        //初始化字符串池
        $pool = '';
        //判断类型
        switch ($type)
        {
            //字母数字混合
            case 'numletter':
                $pool = '0123456789abcdefghijklmnopqrstuvwxyz';
            break;
            //纯数字
            case 'num':
                $pool = '0123456789';
            break;
            //纯数字但是不包括0
            case 'numnozero':
                $pool = '123456789';
            break;
            //纯字母
            case 'letter':
                $pool = 'abcdefghijklmnopqrstuvwxyz';
            break;
        }
        //初始化字符串
        $str = '';
        for ($i = 0; $i < $len; $i++)
        {
            $str .= substr($pool, mt_rand(0, strlen($pool) - 1), 1);
        }
        return $str;
    }
    
    //加密
    function encode($string = '', $key = '67e7f45b468a56f5942df0f1c91a0e2d')
    {
        //编码字符串
        $encode_str = base64_encode($string);
        //编码KEY
        $encode_key = base64_encode($key);
        //取得KEY的长度
        $key_length = strlen($encode_key);
        //加密后返回的字符串
        $return_str = '';
        //循环字符串并生成新的加密字符串
        for($i = 0; $i < strlen($encode_str); $i++)
        {
            $return_str .= ($i < $key_length) ? $encode_str[$i].$encode_key[$i] : $encode_str[$i];
        }
        //替换"="，避免还原出错
        return str_replace('=', '@||@', $return_str);
    }
    
    //解密
    function decode($string = '', $key = '67e7f45b468a56f5942df0f1c91a0e2d')
    {
        //还原
        $string = str_split(str_replace('@||@', '=', $string));
        //编码KEY
        $encode_key = str_split(base64_encode($key));
        //取得KEY的长度
        $key_length = count($encode_key);
        //遍历已加密字符
        foreach ($string as $k => $v)
        {
            if ($k >= $key_length)
            {
                break;
            }
            if ( ! isset($string[$k+$k+1]))
            {
                break;
            }
            if ($string[$k+$k+1] == $encode_key[$k])
            {
                unset($string[$k+$k+1]);
            }
        }
        //反编译
        return base64_decode(implode('', $string));
    }
    
    //获取客户端IP，摘取自TP
    function ip($type = 0)
    {
        $type = $type ? 1 : 0;
        static $ip = NULL;
        if ($ip !== NULL) return $ip[$type];
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos = array_search('unknown',$arr);
            if(FALSE !== $pos) unset($arr[$pos]);
            $ip = trim($arr[0]);
        }
        elseif(isset($_SERVER['HTTP_CLIENT_IP']))
        {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        elseif(isset($_SERVER['REMOTE_ADDR']))
        {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        $long = sprintf("%u",ip2long($ip));
        $ip   = $long ? [$ip, $long] : ['0.0.0.0', 0];
        return $ip[$type];
    }