<?php
namespace app\message\library\sms\engine;

use app\message\library\sms\package\yunsms\SmsApi;

class Yunxinshi extends Server
{


    /**
     * 发送短信通知
     */
    public function sendSms($tpl, $tplParams, $acceptPhone)
    {
        $uid = trim($this->config['uid']);
        $password =trim($this->config['password']);
        $api = new SmsApi($uid,$password);
        $response = $api->send($acceptPhone,$tplParams,$tpl);
        // 记录日志
        $this->logData = [
            'phone' => $acceptPhone,
            'params' => $tplParams,
            'response' => $response
        ];
        $this->error  = $response['message'];
        return $response['stat'] == 100;

    }

}