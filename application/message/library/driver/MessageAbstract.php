<?php
namespace app\message\library\driver;


abstract class MessageAbstract
{
    protected $error = '';
    protected $config = [];

    public function getError(){
        return $this->error;
    }

    public function getConfig(){
        if(empty($this->config)){
            $this->setConfig();
        }
        return $this->config;
    }

    /**
     * 设置配置参数
     */
    abstract function setConfig();

    /**
     * 发送消息
     */
    abstract function send($target,$template,$params = [],$options = []);

}