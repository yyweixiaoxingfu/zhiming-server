<?php
namespace app\message\library\driver;


use app\message\library\sms\Driver as SmsDriver;
use think\Exception;
use think\facade\Log;

class Sms extends MessageAbstract
{
    public function setConfig(){
        $smsConfig = config('message.sms');
        if(empty($smsConfig)){
            throw new Exception('请先配置短信发送参数');
        }
        $this->config = $smsConfig;
    }

    /**
     * 通用短信发送
     */
    public function send($target,$template,$params = [],$options = []){
        try {
            $smsConfig = $this->getConfig();
            $SmsDriver = new SmsDriver($smsConfig);
            $result = $SmsDriver->sendSms($template['template'], $params,$target);
            if(!$result){
                $this->error = $SmsDriver->getError();
                return false;
            }
            Log::record(var_export($SmsDriver->getLogs() ?? [],true),'sms');
        }catch (\Exception $e){
            $this->error = $e->getMessage();
            return false;
        }
        return true;
    }

}