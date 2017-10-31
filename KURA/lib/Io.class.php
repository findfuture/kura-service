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

    //IO输入输出类

    namespace lib;

    class Io{
        
        public static function post($key = '', $default = '', $replace = '')
        {
            if ($key == '')
            {
                return FALSE;
            }
            if ( ! isset($_POST[$key]))
            {
                return ($default !== '') ? $default : FALSE;
            }
            $val = $_POST[$key];
            if (is_int($val))
            {
                return $val;
            }
            if (is_array($val))
            {
                $tmp = [];
                foreach ($val as $v)
                $tmp[] = self::_replace(trim($v), $replace);
                return $tmp;
            }
            else
            {
                return self::_replace(trim($val), $replace);
            }
        }
        
        public static function get($key = '', $default = '', $replace = '')
        {
            if ($key == '')
            {
                return FALSE;
            }
            //如果传统GET没有找到的话则使用PATHINFO模式去找
            if (isset($_GET[$key]))
            {
                $val = ($_GET[$key] == '') ? $default : $_GET[$key];
            }
            else
            {
                $val = self::_findGetPathinfo($key);
                if ($val === FALSE)
                return ($default !== '') ? $default : FALSE;
            }
            $val = trim($val);
            if (strpos($val, '.') > 0)
            {
                $arr = explode('.', $val);
                $val = $arr[0];
            }
            if (is_int($val))
            {
                return $val;
            }
            return self::_replace($val, $replace);
        }
        
        //PATHINFO模式下去找GET的值
        private static function _findGetPathinfo($key)
        {
            $url = $_SERVER['REQUEST_URI'];
            //拆分URL为数组
            $arr = explode('/', ltrim($url, '/'));
            for ($i = 0; $i < (LEVEL + 2); $i++)
            {
                array_shift($arr);
            }
            $k = array_search($key, $arr);
            if ($k === FALSE)
            {
                return FALSE;
            }
            if (isset($arr[$k + 1]) === FALSE)
            {
                return FALSE;
            }
            return $arr[$k + 1];
        }
        
        //过滤
        private static function _replace($val, $replace)
        {
            $conf = ($replace == '') ? C('IO') : $replace;
            //过滤配置
            $rule = explode('|', $conf);
            foreach ($rule as $r)
            {
                if ($r == 'escape')
                {
                    $val = self::_escape($val);
                }
                else if ($r == 'sql')
                {
                    $val = self::_sqlClear($val);
                }
                else
                {
                    $val = self::_xssClear($val);
                }
            }
            return $val;
        }
        
        //字符转义
        private static function _escape($val)
        {
            if ( ! get_magic_quotes_gpc())
            {
                $val = addslashes($val);
            }
            return strip_tags($val);
        }
        
        //SQL注入清理 
        private static function _sqlClear($val)
        {
            $preg = '/|char|insert|update|delete|select|drop|outfile|load_file|show|union|join|execute/is';
            return preg_replace($preg, '', $val);
        }
        
        //XSS注入清理
        private static function _xssClear($val)
        {
            $val = rawurldecode($val);
            $search  = 'abcdefghijklmnopqrstuvwxyz';
            $search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'; 
            $search .= '1234567890!@#$%^&*()';
            $search .= '~`";:?+/={}[]-_|\'\\';
            for ($i = 0; $i < strlen($search); $i++)
            {
                $val = preg_replace('/(&#[xX]0{0,8}'.dechex(ord($search[$i])).';?)/i', $search[$i], $val);
                $val = preg_replace('/(&#0{0,8}'.ord($search[$i]).';?)/', $search[$i], $val);
            }
            //屏蔽危险DOM
            $dom = [
                'javascript', 'vbscript', 'expression', 'applet', 'meta', 
                'xml', 'blink', 'link', 'script', 'embed', 
                'object', 'iframe', 'frame', 'frameset', 'ilayer', 
                'layer', 'bgsound', 'base'
            ];
            foreach ($dom as $d)
            {
                $val = preg_replace('/[<|&lt;]+'.$d.'[>|&gt;]+(.*)[<|&lt;]+\/'.$d.'[>|&gt;]+/', '', $val);
            }
            //屏蔽事件
            $event = [
                'onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 
                'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 
                'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 
                'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 
                'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 
                'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 
                'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 
                'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 
                'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 
                'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 
                'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 
                'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 
                'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 
                'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 
                'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 
                'onstop', 'onsubmit', 'onunload'
            ];
            $val = preg_replace('/'.implode('|', $event).'/is', '', $val);
            //字符串黑名单
            $black = [
                'document.cookie' => '',
                'document.write'  => '',
                '.parentNode'     => '',
                '.innerHTML'      => '',
                'window.location.href' => '',
                'location.href'   => '',
                '-moz-binding'    => '',
                'alert'           => '',
                '<!--'            => '&lt;!--',
                '-->'             => '--&gt;',
                '<![CDATA['       => '&lt;![CDATA[',
                '<comment>'       => '&lt;comment&gt;',
            ];
            //替换黑名单字符串
            $val = str_replace(array_keys($black), array_values($black), $val); 
            return $val;
        }
        
    }