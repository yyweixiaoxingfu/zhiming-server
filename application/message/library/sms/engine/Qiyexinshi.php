<?php
namespace app\message\library\sms\engine;


class Qiyexinshi extends Server
{
    /**
     * 发送短信通知
     */
    public function sendSms($tpl, $tplParams, $acceptPhone)
    {
        $post_data['userid'] = intval($this->config['userid']);
        $post_data['account'] =$this->config['account'];
        $post_data['password'] =$this->config['password'];
        $post_data['rt'] = 'json';
        $sms_txt   = $tpl;
        foreach ($tplParams as $key => $value){
            $sms_txt    = str_replace("{".$key."}", $value, $sms_txt);
        }
        $post_data['content'] =$sms_txt ;
        $post_data['mobile'] = $acceptPhone;//多个手机号码用英文半角豆号‘,’分隔
        $url='http://59.110.69.166:8080/sms.aspx?action=send';
        $o='';
        foreach ($post_data as $k=>$v)
        {
            $o.="$k=".urlencode($v).'&';//短信内容需要用urlencode编码，否则可能收到乱码
        }
        $post_data=substr($o,0,-1);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //如果需要将结果直接返回到变量里，那加上这句。
        $response = curl_exec($ch);
        $response = json_decode($response,true);
        // 记录日志
        $this->logData = [
            'phone' => $acceptPhone,
            'params' => $tplParams,
            'response' => $response
        ];
        $this->error  = $response['Message']; //ReturnStatus Message
        return $response['ReturnStatus'] == 'Success';
    }

}