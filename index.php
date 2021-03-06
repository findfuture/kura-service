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
    
    //SOA微服务平台地址，用户服务端与SOA平台连接，"/"结尾
    define('SOA', '');
    //SOA密钥
    define('SOATOKEN', '');
    
    //项目根目录，项目所有涉及到目录的地方以此作为根目录
    define('ROOTPATH', dirname(__FILE__));
    
    //项目所在目录相对于APACHE/NGINX的程序运行根目录的层级数
    //例如：环境的根目录是wwwroot，如果程序直接部署在根目录，则
    //此处写0，否则写项目位于根目录的层级数
    //例如：wwwroot/kura-service/...，则此时写1
    //例如：wwwroot/aaa/bbb/ccc/...，则此时写3
    define('LEVEL', 0);

    //引入库拉
    require ROOTPATH.'/KURA/kura.php';