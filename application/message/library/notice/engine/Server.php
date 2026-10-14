<?php

namespace app\message\library\notice\engine;


abstract class Server
{
    protected $error;
    protected $config;
    protected $logData;

    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * 返回错误信息
     * @return mixed
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * 返回日志信息
     */
    public function getLogs()
    {
        return $this->logData;
    }


    abstract function send($target,$title, $message,$options);
}
