<?php
namespace app\message\library\sms\engine;


class Duanxinbao extends Server
{
    /**
     * 发送短信通知
     */
    public function sendSms($tpl, $tplParams, $acceptPhone)
    {
        $smsText   = $tpl;
        foreach ($tplParams as $key => $value){
            $smsTxt    = str_replace("{".$key."}", $value, $smsText);
        }
        $response = $this->send($acceptPhone,$smsTxt);
        // 记录日志
        $this->logData = [
            'phone' => $acceptPhone,
            'params' => $tplParams,
            'response' => $response
        ];
        $this->error = $response;
        return $response == 0;
    }

    protected function send($pnum,$psms){
        $secret = trim($this->config['secret']);  //秘钥
        $key = trim($this->config['key']);  //账号
        $sendurl='http://api.smsbao.com/sms?u='.$key.'&p='.$secret.'&m='.$pnum.'&c='.urlencode($psms);

        return file_get_contents($sendurl);
    }
}