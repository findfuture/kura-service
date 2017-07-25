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

    //图形验证码类

    namespace lib;

    class Verify {
        
        //验证码长度
        private $_len    = 4;
	//宽度
	private $_width  = 150;
	//高度
	private $_height = 40;
	//验证码类型：纯数字、纯字母、数字字母
	private $_type   = 3;
	//字号
	private $_size   = 16;
        //字体
	private $_font;
        //图片资源句柄
	private $_img;
	//验证码随机串
	private $_code;
        
        //初始化
	public function __construct()
	{
            //验证码字体
            $this->_font = ROOTPATH.'/KURA/lib/Verify.ttf';
	}
        
        //生成随机字符串
        private function _create_code()
	{
            switch ($this->_type)
            {
		case 1:
                    $type = 'num';
		break;
		case 2:
                    $type = 'letter';
		break;
                case 3:
                    $type = 'numletter';
                break;
            }
            $this->_code = random($type, $this->_len);
	}
        
        //生成背景
	private function _create_background()
	{
            $this->_img = imagecreatetruecolor($this->_width, $this->_height);
            //背景颜色
            $r = rand(235, 255);
            $g = rand(235, 255);
            $b = rand(235, 255);
            $color = imagecolorallocate($this->_img, $r, $g, $b);
            //着色
            imagefilledrectangle($this->_img, 0, $this->_height, $this->_width, 0, $color);   
	}
        
        //生成文字
	private function _create_font()
	{
            //横坐标 宽度减去20是为了不超出画布范围
            $_x = ($this->_width - 20) / $this->_len;
            //循环字符串长度
            for ($i = 0; $i < $this->_len; $i++) 
            {
                //RGB三色
                $r = rand(50, 150);
                $g = rand(50, 150);
                $b = rand(50, 150);
                $color = imagecolorallocate($this->_img, $r, $g, $b);
                imagettftext($this->_img, $this->_size, rand(-20, 20), $_x * $i + 10, $this->_height / 1.4, $color, $this->_font, $this->_code[$i]);
            }
	}
		
	//生成干扰
	private function _create_line()
	{
            for ($i = 0; $i < 6; $i++) 
            {
                $r = rand(0, 50);
                $g = rand(0, 50);
                $b = rand(0, 50);
                $color = imagecolorallocate($this->_img, $r, $g, $b);  
                imageline($this->_img, rand(0,$this->_width), rand(0,$this->_height), rand(0,$this->_width), rand(0,$this->_height), $color);  
            }
	}
        
        //干扰字母
        private function _create_letter()
        {
            for ($i = 0; $i < 50; $i++)
            {
                $r = rand(200, 255);
                $g = rand(200, 255);
                $b = rand(200, 255);
            	$color = imagecolorallocate($this->_img, $r, $g, $b);
                $rand  = random('letter', 1);
            	imagestring($this->_img, 6, rand(0, $this->_width), rand(0, $this->_height), $rand, $color);
            }
        }
		
	//输出验证码
	private function _create_img()
	{
            header('Content-type:image/png');
            imagejpeg($this->_img);
            imagedestroy($this->_img);
        }
        
        //配置信息
        private function _config($config = array())
        {
            //判断是否有自定义参数
            if (isset($config['len']))
            {
                $this->_len = $config['len'];
            }
            if (isset($config['width']))
            {
                $this->_width = $config['width'];
            }
            if (isset($config['height']))
            {
                $this->_height = $config['height'];
            }
            if (isset($config['type']))
            {
                $this->_type = $config['type'];
            }
            if (isset($config['size']))
            {
                $this->_size = $config['size'];
            }
        }
        
        //执行验证码
        public function run($config = array())
        {
            //配置
            $this->_config($config);
            //生存验证码随机字符串
            $this->_create_code();
            //生成背景
            $this->_create_background();
            //写入干扰字母
            $this->_create_letter();
            //写入字符串
            $this->_create_font();
            //干扰线
            $this->_create_line();
            //输出验证码图片
            $this->_create_img();
            return $this->_code;
        }
        
    }