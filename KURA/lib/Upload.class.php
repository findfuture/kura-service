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

    //上传类

    namespace lib;

    class Upload{
        
        //文本域名称
        private $_inputName;
        //允许大小，单位M
        private $_allowSize;
        //允许格式
        private $_allowDoc;
        //是否需要FTP上传
        private $_ftp;
        //FTP地址
        private $_ftpHost;
        //FTP端口
        private $_ftpPort;
        //FTP账号
        private $_ftpUname;
        //FTP密码
        private $_ftpPword;
        //FTP目录
        private $_ftpDir;
        //上传FTP后是否删除原图
        private $_ftpDelOld;
        //上传的图片是否需要加密保存
        private $_secret;
        //加密密钥
        private $_key;
        //文件
        private $_file;
        //上传目录
        private $_path;
        //文件名
        private $_fileName;
        
        public function __construct($path = '', $fileName = '')
        {
            $this->_file      = '';
            $this->_inputName = 'upload';
            $this->_allowSize = 2;
            $this->_allowDoc  = array(
                'doc',
                'docx',
                'xls',
                'xlsx',
                'ppt',
                'pptx',
                'txt',
                'rar',
                'zip',
                'jpg',
                'jpeg',
                'png',
                'gif',
                'bmp',
                'pdf',
                'swf'
            );
            $this->_path = $path;
            $this->_fileName = $fileName;
            $this->_ftp = FALSE;
            $this->_ftpDir = '';
            $this->_ftpDelOld = FALSE;
            $this->_key = '67e7f45b468a56f5942df0f1c91a0e2d';
        }
        
        //执行上传
        public function run($config = array())
        {
            //初始化配置
            $this->_config($config);
            if ( ! isset($_FILES[$this->_inputName]))
            {
                return 101;
            }
            $this->_file = $_FILES[$this->_inputName];
            //判断文件名是否是空
            if ($this->_file['name'] == '' OR empty($this->_file['name']))
            {
                return 101;
            }
            //检测文件大小
            if ( ! $this->_checkFileSize())
            {
                return 102;
            }
            //检测文件格式
            $doc = $this->_checkFileDoc();
            if ( ! $doc)
            {
                return 103;
            }
            //上传文件
            $file = $this->_upload($doc);
            if ( ! $file)
            {
                return 104;
            }
            //加密图片
            if ($this->_secret && 
                    in_array($doc, array('jpg', 'jpeg', 'png', 'gif')))
            {
                $pic = ROOTPATH.'/'.UPLOAD_PATH.$file;
                $picData = file_get_contents($pic);
                //写入密钥
                $picData = $this->_key.$picData;
                //重新生成图片
                file_put_contents($pic, $picData);
                return $doc;
            }
            //上传FTP
            if ($this->_ftp)
            {
                $Ftp = ftp_connect($this->_ftpHost, $this->_ftpPort);
                if ( ! $Ftp)
                {
                    L('FTP连接失败', 'upload');
                }
                if ( ! ftp_login($Ftp, $this->_ftpUname, $this->_ftpPword))
                {
                    L('FTP登录失败', 'upload');
                }
                echo $this->_ftpDir;
                if ($this->_ftpDir != '')
                {
                    if ( ! @ftp_chdir($Ftp, $this->_ftpDir))
                    ftp_mkdir($Ftp, $this->_ftpDir);
                }
                if ( ! @ftp_chdir($Ftp, $this->_ftpDir.'/'.date('Ymd')))
                {
                    ftp_mkdir($Ftp, $this->_ftpDir.'/'.date('Ymd'));
                }
                ftp_pasv($Ftp, TRUE);
                ftp_put($Ftp, $this->_ftpDir.'/'.$file, ROOTPATH.'/'.UPLOAD_PATH.'/'.$file, FTP_BINARY);
                if ($this->_ftpDelOld)
                unlink(ROOTPATH.'/'.UPLOAD_PATH.'/'.$file);
            }
            return ($this->_ftpDir == '' ? '' : $this->_ftpDir.'/').$file;
        }
        
        //初始化配置
        private function _config($config = array())
        {
            if (isset($config['inputName']))
            {
                $this->_inputName = $config['inputName'];
            }
            if (isset($config['allowSize']))
            {
                $this->_allowSize = $config['allowSize'];
            }
            if (isset($config['allowDoc']))
            {
                $this->_allowDoc = $config['allowDoc'];
            }
            if (isset($config['ftp']))
            {
                $this->_ftp = $config['ftp'];
            }
            if (isset($config['ftpHost']))
            {
                $this->_ftpHost = $config['ftpHost'];
            }
            if (isset($config['ftpPort']))
            {
                $this->_ftpPort = $config['ftpPort'];
            }
            if (isset($config['ftpUname']))
            {
                $this->_ftpUname = $config['ftpUname'];
            }
            if (isset($config['ftpPword']))
            {
                $this->_ftpPword = $config['ftpPword'];
            }
            if (isset($config['ftpDir']))
            {
                $this->_ftpDir = $config['ftpDir'];
            }
            if (isset($config['ftpDelOld']))
            {
                $this->_ftpDelOld = $config['ftpDelOld'];
            }
            if (isset($config['secret']))
            {
                $this->_secret = $config['secret'];
            }
        }
        
        //检测文件大小
        private function _checkFileSize()
        {
            $fileSize = $this->_file['size'];
            if ($fileSize == 0)
            {
                return FALSE;
            }
            //允许文件的大小
            $allowSize = $this->_allowSize * 1024 * 1024;
            if ($fileSize > $allowSize)
            {
                return FALSE;
            }
            return TRUE;
        }
        
        //取文件后缀
        private function _checkFileDoc()
        {
            $file  = $this->_file['name'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $type  = finfo_file($finfo, $this->_file['tmp_name']);
            //MIME对照表
            $mime  = array(
                '3gp'     => 'video/3gpp', 'ai' => 'application/postscript',
                'aif'     => 'audio/x-aiff', 'aifc' => 'audio/x-aiff',
                'aiff'    => 'audio/x-aiff', 'asc' => 'text/plain',
                'atom'    => 'application/atom+xml', 'au' => 'audio/basic',
                'avi'     => 'video/x-msvideo', 'bcpio' => 'application/x-bcpio',
                'bin'     => 'application/octet-stream', 'bmp' => 'image/bmp',
                'cdf'     => 'application/x-netcdf', 'cgm' => 'image/cgm',
                'class'   => 'application/octet-stream',
                'cpio'    => 'application/x-cpio',
                'cpt'     => 'application/mac-compactpro',
                'csh'     => 'application/x-csh', 'css' => 'text/css',
                'dcr'     => 'application/x-director', 'dif' => 'video/x-dv',
                'dir'     => 'application/x-director', 'djv' => 'image/vnd.djvu',
                'djvu'    => 'image/vnd.djvu',
                'dll'     => 'application/octet-stream',
                'dmg'     => 'application/octet-stream',
                'dms'     => 'application/octet-stream',
                'doc'     => 'application/msword', 'dtd' => 'application/xml-dtd',
                'dv'      => 'video/x-dv', 'dvi' => 'application/x-dvi',
                'dxr'     => 'application/x-director',
                'eps'     => 'application/postscript', 'etx' => 'text/x-setext',
                'exe'     => 'application/octet-stream',
                'ez'      => 'application/andrew-inset', 'flv' => 'video/x-flv',
                'gif'     => 'image/gif', 'gram' => 'application/srgs',
                'grxml'   => 'application/srgs+xml',
                'gtar'    => 'application/x-gtar', 'gz' => 'application/x-gzip',
                'hdf'     => 'application/x-hdf',
                'hqx'     => 'application/mac-binhex40', 'htm' => 'text/html',
                'html'    => 'text/html', 'ice' => 'x-conference/x-cooltalk',
                'ico'     => 'image/x-icon', 'ics' => 'text/calendar',
                'ief'     => 'image/ief', 'ifb' => 'text/calendar',
                'iges'    => 'model/iges', 'igs' => 'model/iges',
                'jnlp'    => 'application/x-java-jnlp-file', 'jp2' => 'image/jp2',
                'jpe'     => 'image/jpeg', 'jpeg' => 'image/jpeg',
                'jpg'     => 'image/jpeg', 'js' => 'application/x-javascript',
                'kar'     => 'audio/midi', 'latex' => 'application/x-latex',
                'lha'     => 'application/octet-stream',
                'lzh'     => 'application/octet-stream',
                'm3u'     => 'audio/x-mpegurl', 'm4a' => 'audio/mp4a-latm',
                'm4p'     => 'audio/mp4a-latm', 'm4u' => 'video/vnd.mpegurl',
                'm4v'     => 'video/x-m4v', 'mac' => 'image/x-macpaint',
                'man'     => 'application/x-troff-man',
                'mathml'  => 'application/mathml+xml',
                'me'      => 'application/x-troff-me', 'mesh' => 'model/mesh',
                'mid'     => 'audio/midi', 'midi' => 'audio/midi',
                'mif'     => 'application/vnd.mif', 'mov' => 'video/quicktime',
                'movie'   => 'video/x-sgi-movie', 'mp2' => 'audio/mpeg',
                'mp3'     => 'audio/mpeg', 'mp4' => 'video/mp4',
                'mpe'     => 'video/mpeg', 'mpeg' => 'video/mpeg',
                'mpg'     => 'video/mpeg', 'mpga' => 'audio/mpeg',
                'ms'      => 'application/x-troff-ms', 'msh' => 'model/mesh',
                'mxu'     => 'video/vnd.mpegurl', 'nc' => 'application/x-netcdf',
                'oda'     => 'application/oda', 'ogg' => 'application/ogg',
                'ogv'     => 'video/ogv', 'pbm' => 'image/x-portable-bitmap',
                'pct'     => 'image/pict', 'pdb' => 'chemical/x-pdb',
                'pdf'     => 'application/pdf',
                'pgm'     => 'image/x-portable-graymap',
                'pgn'     => 'application/x-chess-pgn', 'pic' => 'image/pict',
                'pict'    => 'image/pict', 'png' => 'image/png',
                'pnm'     => 'image/x-portable-anymap',
                'pnt'     => 'image/x-macpaint', 'pntg' => 'image/x-macpaint',
                'ppm'     => 'image/x-portable-pixmap',
                'ppt'     => 'application/vnd.ms-powerpoint',
                'ps'      => 'application/postscript', 'qt' => 'video/quicktime',
                'qti'     => 'image/x-quicktime', 'qtif' => 'image/x-quicktime',
                'ra'      => 'audio/x-pn-realaudio',
                'ram'     => 'audio/x-pn-realaudio', 'ras' => 'image/x-cmu-raster',
                'rdf'     => 'application/rdf+xml', 'rgb' => 'image/x-rgb',
                'rm'      => 'application/vnd.rn-realmedia',
                'roff'    => 'application/x-troff', 'rtf' => 'text/rtf',
                'rtx'     => 'text/richtext', 'sgm' => 'text/sgml',
                'sgml'    => 'text/sgml', 'sh' => 'application/x-sh',
                'shar'    => 'application/x-shar', 'silo' => 'model/mesh',
                'sit'     => 'application/x-stuffit',
                'skd'     => 'application/x-koan', 'skm' => 'application/x-koan',
                'skp'     => 'application/x-koan', 'skt' => 'application/x-koan',
                'smi'     => 'application/smil', 'smil' => 'application/smil',
                'snd'     => 'audio/basic', 'so' => 'application/octet-stream',
                'spl'     => 'application/x-futuresplash',
                'src'     => 'application/x-wais-source',
                'sv4cpio' => 'application/x-sv4cpio',
                'sv4crc'  => 'application/x-sv4crc', 'svg' => 'image/svg+xml',
                'swf'     => 'application/x-shockwave-flash',
                't'       => 'application/x-troff', 'tar' => 'application/x-tar',
                'tcl'     => 'application/x-tcl', 'tex' => 'application/x-tex',
                'texi'    => 'application/x-texinfo',
                'texinfo' => 'application/x-texinfo', 'tif' => 'image/tiff',
                'tiff'    => 'image/tiff', 'tr' => 'application/x-troff',
                'tsv'     => 'text/tab-separated-values', 'txt' => 'text/plain',
                'ustar'   => 'application/x-ustar',
                'vcd'     => 'application/x-cdlink', 'vrml' => 'model/vrml',
                'vxml'    => 'application/voicexml+xml', 'wav' => 'audio/x-wav',
                'wbmp'    => 'image/vnd.wap.wbmp',
                'wbxml'   => 'application/vnd.wap.wbxml', 'webm' => 'video/webm',
                'wml'     => 'text/vnd.wap.wml',
                'wmlc'    => 'application/vnd.wap.wmlc',
                'wmls'    => 'text/vnd.wap.wmlscript',
                'wmlsc'   => 'application/vnd.wap.wmlscriptc',
                'wmv'     => 'video/x-ms-wmv', 'wrl' => 'model/vrml',
                'xbm'     => 'image/x-xbitmap', 'xht' => 'application/xhtml+xml',
                'xhtml'   => 'application/xhtml+xml',
                'xls'     => 'application/vnd.ms-excel',
                'xml'     => 'application/xml', 'xpm' => 'image/x-xpixmap',
                'xsl'     => 'application/xml', 'xslt' => 'application/xslt+xml',
                'xul'     => 'application/vnd.mozilla.xul+xml',
                'xwd'     => 'image/x-xwindowdump', 'xyz' => 'chemical/x-xyz',
                'zip'     => 'application/zip',
                "apk"     => "application/vnd.android.package-archive",
                "bin"     => "application/octet-stream",
                "cab"     => "application/vnd.ms-cab-compressed",
                "gb"      => "application/chinese-gb",
                "gba"     => "application/octet-stream",
                "gbc"     => "application/octet-stream",
                "jad"     => "text/vnd.sun.j2me.app-descriptor",
                "jar"     => "application/java-archive",
                "nes"     => "application/octet-stream",
                "rar"     => "application/x-rar-compressed",
                "sis"     => "application/vnd.symbian.install",
                "sisx"    => "x-epoc/x-sisx-app",
                "smc"     => "application/octet-stream",
                "smd"     => "application/octet-stream",
                "swf"     => "application/x-shockwave-flash",
                "zip"     => "application/x-zip-compressed",
                "wap"     => "text/vnd.wap.wml wml", "mrp" => "application/mrp",
                "wma"     => "audio/x-ms-wma",
                "lrc"     => "application/lrc"
            );
            //截取后缀
            $doc = substr($file, strrpos($file, '.') + 1, strlen($file));
            $doc = strtolower($doc);
            //取MIME类型
            $upFileMime = $mime[$doc];
            if ($upFileMime != $type)
            {
                return FALSE;
            }
            //判断是否允许的格式
            if ( ! in_array($doc, $this->_allowDoc))
            {
                return FALSE;
            }
            return $doc;
        }
        
        //上传文件
        private function _upload($doc)
        {
            //上传文件目录
            $uploadDir = UPLOAD_PATH.'/';
            if ($this->_path != '')
            {
                if ( ! is_dir($uploadDir.$this->_path))
                mkdir($uploadDir.$this->_path);
            }
            else
            {
                //子目录
                $subDir = date('Ymd');
                if ( ! is_dir($uploadDir.$subDir))
                mkdir($uploadDir.$subDir);
            }
            if ($this->_fileName != '')
            {
                $fileName = $this->_path.'/'.$this->_fileName.'.'.$doc;
            }
            else
            {
                //新文件名
                $microtime = str_replace('.', '', str_replace(' ', '', microtime()));
                //新文件名防止重复
                $fileName = $subDir.'/'.$microtime.'.'.$doc;
            }
            //上传
            if ( ! move_uploaded_file($this->_file['tmp_name'],$uploadDir.'/'.$fileName))
            {
                return FALSE;
            }
            return $fileName;
        }
        
    }