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

    //HTTP执行类

    namespace lib;

    class Http{
        
        //发送HTTP请求
        public static function send($param = [], $log = TRUE, $attach = FALSE)
        {
            if ( ! isset($param['url']))
            {
                return FALSE;
            }
            //接口地址
            $url = $param['url'];
            //发送的数据
            $data = ( ! isset($param['data'])) ? [] : $param['data'];
            //HEADER头
            $header = ( ! isset($param['header'])) ? [] : $param['header'];
            //超时时间
            $time = ( ! isset($param['time'])) ? 10 : $param['time'];
            //返回类型：html/json
            $type = ( ! isset($param['type'])) ? 'json' : $param['type'];
            //请求模式
            $method = ( ! empty($data)) ? 'POST' : 'GET';
            $opts = [
                CURLOPT_TIMEOUT        => $time,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_SSL_VERIFYPEER => FALSE,
                CURLOPT_SSL_VERIFYHOST => FALSE,
                CURLOPT_HTTPHEADER     => $header,
                CURLOPT_URL            => $url,
                CURLOPT_CUSTOMREQUEST  => $method,
                CURLOPT_POST           => TRUE, 
                CURLOPT_POSTFIELDS     => (is_array($data)) ? http_build_query($data) : $data
            ];
            $stime = microtime(TRUE);
            $curl = curl_init();
            curl_setopt_array($curl, $opts);
            //返回句柄给异步
            if ($attach)
            {
                return $curl;
            }
            $return = curl_exec($curl);
            //判断返回值以及接口超时
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $errNo = curl_errno($curl);
            if (defined('GETWAY') && 
                    METHOD != 'POST' && 
                    ($httpCode >= 400 || 
                    $return === FALSE || 
                    $errNo == 28))
            {
                //更改接口状态，参数API，具体注释进GETWAY查看
                \service\Getway::changeApiState(3, 'api', [
                    'TYPE'   => 1,
                    'URL'    => $url,
                    'CODE'   => $httpCode,
                    'ERRNO'  => $errNo,
                    'EID'    => C('EXAMPLE'),
                    'ONLINE' => C('ONLINE'),
                    'API'    => REQUESTURI
                ]);
                //设置SHUTDOWN标识，用于直接输出快照
                $GLOBALS['_SHUTDOWNERROR'] = 1;
            }
            curl_close($curl);
            $etime = microtime(TRUE);
            //是否需要记录日志，这里主要是为了屏蔽来自SOA的调用不被记录进接口日志
            if ($log)
            {
                $httpData = [
                    'URL'    => $url,
                    'TIME'   => round($etime - $stime, 4),
                    'METHOD' => (empty($data)) ? 'GET' : 'POST'
                ];
                if ($httpData['METHOD'] == 'POST')
                {
                    if (isset($data['CONTENT']))
                    unset($data['CONTENT']);
                    $httpData['POST'] = $data;
                }
                $GLOBALS['_log']['HTTP'][] = $httpData;
            }
            return ($type  == 'html') ? $return : json_decode($return, TRUE);
        }
        
    }