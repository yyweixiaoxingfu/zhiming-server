<?php

namespace app\message\library\notice;

use think\Exception;

/**
 * 推送模块驱动
 * Class Driver
 * @package app\message\library\notice
 */
class Driver
{
    private $config;
    private $engine;    // 当前短信引擎类
    private $engineName;    // 当前短信引擎名称

    /**
     * 构造方法
     * Driver constructor.
     * @param $config
     * @throws Exception
     */
    public function __construct($config = [])
    {
        // 配置信息
        if(!empty($config)){
            $this->config = $config;
        }
        // 当前引擎名称
        $this->engineName = $this->config['default'];
        // 实例化当前存储引擎
        $this->engine = $this->getEngineClass();
    }

    public function send($target,$title, $message,$options)
    {
        if(empty($tpl)){
            throw new Exception('请配置短信模板');
        }
        $res = $this->engine->send($target,$title, $message,$options);
        return $res;
    }

    public function getError()
    {
        return $this->engine->getError();
    }

    /**
     * 获取发送日志
     * @return mixed
     */
    public function getLogs(){
        $logData = $this->engine->getLogs();
        $logData['type'] = $this->engineName;
        return $logData;
    }

    private function getEngineClass()
    {
        $classSpace = __NAMESPACE__ . '\\engine\\' . ucfirst($this->engineName);
        if (!class_exists($classSpace)) {
            throw new Exception('未找到引擎类: ' . $this->engineName);
        }
        $config = $this->config['engine'][$this->engineName] ?? [];
        if (empty($config)) {
            throw new Exception('请配置参数');
        }
        return new $classSpace($config);
    }

}
