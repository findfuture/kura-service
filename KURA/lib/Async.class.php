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

    //异步

    namespace lib;
    use React\Promise\Deferred;

    class Async{
        
        //批量CURL句柄
        protected $_multiCurl;
        //任务队列
        protected $_tasks = [];
        //延迟队列
        protected $_deferred = [];
        //定义接口
        private static $_instance;

        public function __construct()
        {
            $this->_multiCurl = curl_multi_init();
            if ( ! $this->_multiCurl)
            {
                error(101, 'CURL初始化失败');
            }
        }
        
        //防止克隆
        private function __clone(){}
        
        //返回实例入口
        public static function getInstance()
        {
            if ( ! (self::$_instance instanceof self))
            {
                self::$_instance = new self();
            }
            return self::$_instance;
        }
        
        //添加任务
        public function add($param = [], $key = null)
        {
            if (is_null($key))
            {
                $key = count($this->_tasks);
            }
            if ( ! isset($param['url']) || $param['url'] == '')
            {
                error(102, '请指定需要调用的服务URL地址');
            }
            //获得单个任务句柄
            $curl = \lib\Http::send($param, 0, 1);
            //加入任务队列
            $this->_tasks[$key] = $curl;
            curl_multi_add_handle($this->_multiCurl, $curl);
            //加入延迟队列
            $deferred = new Deferred();
            $this->_deferred[$key] = $deferred;
            return $this;
        }
        
        //批量执行任务
        public function run($debug = FALSE)
        {
            //输出
            $result = [];
            //开始遍历任务
            do{
                while (($code = curl_multi_exec($this->_multiCurl, $active)) == CURLM_CALL_MULTI_PERFORM);
                //处理完毕立即跳出
                if ($code != CURLM_OK)
                {
                    break;
                }
                //遍历所有任务，找出完成的
                while ($done = curl_multi_info_read($this->_multiCurl)) {
                    //执行信息
                    $info  = curl_getinfo($done['handle']);
                    //错误信息
                    $error = curl_error($done['handle']);
                    //错误编号
                    $errno = curl_errno($done['handle']);
                    //返回内容
                    $cont  = curl_multi_getcontent($done['handle']);
                    $taskName = $task = null;
                    //这里是为了在任务队列中找到完成的那一个
                    foreach ($this->_tasks as $taskName => $task)
                    {
                        if ($done['handle'] == $task)
                        break;
                    }
                    //确定延迟队列的句柄
                    $deferred = $this->_deferred[$taskName];
                    //如果有错误则通知延迟队列一个失败信息并跳过
                    if ($errno != 0)
                    {
                        $deferred->reject(array(
                            'info'  => $info,
                            'error' => $error,
                            'errno' => $errno,
                            'cont'  => $cont
                        ));
                        continue;
                    }
                    //成功通知
                    $deferred->resolve(array(
                        'info'  => $info,
                        'error' => $error,
                        'errno' => $errno,
                        'cont'  => $cont
                    ));
                    $cont = json_decode($cont, TRUE);
                    if ($debug)
                    {
                        $result[$taskName] = compact('info', 'error', 'errno', 'cont');
                    }
                    else
                    {
                        $result[$taskName] = $cont;
                    }
                    curl_multi_remove_handle($this->_multiCurl, $done['handle']);
                    curl_close($done['handle']);
                }
                if ($active > 0)
                {
                    curl_multi_select($this->_multiCurl, 0.05);
                }
            } while($active);
            curl_multi_close($this->_multiCurl);
            return $result;
        }
        
    }