<?php

    /*
     * 各个服务通用函数
     */

    //调取.NET提供的接口
    function api($url, $data = array(), $key = 'CLIENT')
    {
        if ( ! is_array($data))
        {
            $key  = $data;
            $data = array();
        }
        //需要传递的令牌，先从缓存中获取
        $token = F($key);
        $conf  = C($key);
        $host  = 'http://'.$conf['HOST'];
        if ($conf['PORT'] != '')
        {
            $host .= ':'.$conf['PORT'];
        }
        $url = $host.$url;
        $header = C('HEADER');
        //写入LOGINFO
        if (isset($conf['LOGINFO']) && $conf['LOGINFO'])
        {
            $header[] = 'loginfo:'.json_encode(array(
                'USERNAME'  => '', 
                'IP'        => ip(), 
                'PROJECT'   => C('EXAMPLE'), 
                'PLATFORM'  => CLIENT,
                'DEVICENUM' => H('devicecode')
            ));
        }
        //如果缓存中TOKEN不存在则从服务端获取
        if( ! $token)
        {
            $signTime = date('YmdHis');
            $random = rand(100000, 999999);
            $tokenUrl = 'api/token';
            if (isset($conf['TOKEN']))
            {
                $tokenUrl = $conf['TOKEN'];
            }
            $tokenResult = \lib\Http::send(array(
                'url'  => $host.'/'.$tokenUrl,
                'data' => array(
                    'appid'    => $conf['APPID'],
                    'signTime' => $signTime, 
                    'random'   => $random, 
                    'signCode' => md5($conf['APPID'].$signTime.$random.$conf['SECRET'])
                ),
                'header' => $header
            ));
            $token = $tokenResult['data'];
            F($key, $token, 1800);
        }
        $header[] = 'token:'.$token;
        return \lib\Http::send(array(
            'url'    => $url,
            'data'   => $data,
            'header' => $header,
            'time'   => $conf['TIMEOUT']
        ));
    }
    
    //格式化时间为多久之前的模式
    function chinaTime($time)
    {
        //开始时间
        $start_time = $time;
        //现在时间
        $now_time   = time();
        //计算时间差
        $diff = $now_time - $start_time;
        //格式化
        $time = '';
        if ($diff < 60)
        {
            $time = '刚刚';
        }
        else if ($diff >= 60 && $diff <= 3600)
        {
            $time = floor($diff / 60).'分钟前';
        }
        else if ($diff >= 3600 && $diff <= 86400)
        {
            $time = floor($diff / 3600).'小时前';
        }
        else
        {
            $time = date('Y-m-d H:i', $start_time);
        }
        return $time;
    }