<?php


namespace app\message\library\driver;


use app\message\library\notice\Driver;
use app\message\library\push\Jpush;
use think\Exception;

class Notice extends MessageAbstract
{
    public function setConfig()
    {
        $config = config('message.notice');
        if(empty($config)){
            throw new Exception('请先配置推送参数');
        }
        $this->config = $config;
    }

    public function send($target,$template,$params = [],$options = []){
        try {
            $config = $this->getConfig();
            $driver = new Driver($config);
            $title = $template['title'];
            $message = $template['template'];
            foreach ($params as $key => $value){
                $message    = str_replace("{".$key."}", $value, $message);
            }
            $result = $driver->send($target,$title, $message,$options);
            if(!$result){
                $this->error = $driver->getError();
                return false;
            }
        }catch (\Exception $e){
            $this->error = $e->getMessage();
            return false;
        }
        return true;

    }

}