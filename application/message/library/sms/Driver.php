<?php

namespace app\message\library\sms;

use think\Exception;
use think\facade\Cache;
use think\facade\Log;

/**
 * 短信验证码模块驱动
 * Class driver
 * @package app\common\library\sms
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

    /**
     * 发送短信通知
     * @param $tpl 短信模板类型
     * @param array $tplParams 数组，模板变量
     * @param string $mobile 手机号
     * @param bool $isEnable 模板是否启用
     * @return bool
     */
    public function sendSms($tpl, $tplParams, $mobile)
    {
        if(empty($tpl)){
            throw new Exception('请配置短信模板');
        }
        $res = $this->engine->sendSms($tpl, $tplParams, $mobile);
        return $res;
    }

    /**
     * 获取错误信息
     * @return mixed
     */
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

    /**
     * 获取当前的存储引擎
     * @return mixed
     * @throws Exception
     */
    private function getEngineClass()
    {
        $classSpace = __NAMESPACE__ . '\\engine\\' . ucfirst($this->engineName);
        if (!class_exists($classSpace)) {
            throw new Exception('未找到存储引擎类: ' . $this->engineName);
        }
        $config = $this->config['engine'][$this->engineName] ?? [];
        if (empty($config)) {
            throw new Exception('请配置短信参数');
        }
        return new $classSpace($config);
    }

}
