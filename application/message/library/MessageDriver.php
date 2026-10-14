<?php

namespace app\message\library;

use think\Exception;
use think\facade\Cache;
use think\facade\Log;

/**
 * 短信验证码模块驱动
 * Class driver
 * @package app\common\library\sms
 */
class MessageDriver
{
    private $engine;    // 当前短信引擎类
    private $engineName;    // 当前短信引擎名称

    /**
     * 构造方法
     */
    public function __construct($driver = '')
    {
        if (empty($driver)) {
            throw new Exception('请选择消息引擎');
        }
        // 当前引擎名称
        $this->engineName = $driver;
        // 实例化当前存储引擎
        $this->engine = $this->getEngineClass();
    }

    /**
     * 发送消息
     */
    public function send($target, $tpl, $tplParams, $options = [])
    {
        $config = $this->getConfig();
        if($config['is_close']){
            return true;
        }
        return $this->engine->send($target, $tpl, $tplParams, $options);
    }

    /**
     * 获取错误信息
     */
    public function getError()
    {
        return $this->engine->getError();
    }

    /**
     * 获取引擎配置
     */
    public function getConfig()
    {
        return $this->engine->getConfig();
    }

    /**
     * 获取当前的引擎
     */
    private function getEngineClass()
    {
        $classSpace = __NAMESPACE__ . '\\driver\\' . ucfirst($this->engineName);
        if (!class_exists($classSpace)) {
            throw new Exception('未找到消息引擎类: ' . $this->engineName);
        }
        return new $classSpace();
    }

}
