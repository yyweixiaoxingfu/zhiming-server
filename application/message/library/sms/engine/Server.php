<?php

namespace app\message\library\sms\engine;


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

    /**
     * 发送短信通知
     * @param $tpl 模板
     * @param $code 验证码
     * @param $acceptPhone 手机号
     * @return mixed
     */
    abstract function sendSms($tpl, $code,$acceptPhone);
}
