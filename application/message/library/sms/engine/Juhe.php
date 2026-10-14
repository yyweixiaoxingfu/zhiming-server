<?php
namespace app\message\library\sms\engine;


/**
 * 聚合数据短信模板
 * Class Juhe
 * @package app\common\library\sms\engine
 */
class Juhe extends Server
{
    /**
     * 发送短信通知
     */
    public function sendSms($tpl, $tplParams, $acceptPhone)
    {
        $params = [];
        $response = $this->juhecurl($tpl,$tplParams,$acceptPhone);
        $response = json_decode($response,true);
        // 记录日志
        $this->logData = [
            'phone' => $acceptPhone,
            'params' => $tplParams,
            'response' => $response
        ];
        $this->error = $response['reason'];
        return $response['error_code'] == 0;
    }

    // 聚合
    protected function juhecurl($tpl,$tplParams,$acceptPhone){
        $url = 'http://v.juhe.cn/sms/send';
        $ispost = 1;
        // 组装模板变量
        $tpl_value = '';
        foreach ($tplParams as $key => $value){
            $tpl_value .= "#$key#=$value&";
        }
        $tpl_value = trim($tpl_value,'&');
        $tpl_value = urlencode($tpl_value);
        $smsConf = array(
            'key'   => $this->config['app_key'], //您申请的APPKEY
            'mobile'    => $acceptPhone, //接受短信的用户手机号码
            'tpl_id'    => $tpl, //您申请的短信模板ID，根据实际情况修改
            'tpl_value' => $tpl_value //您设置的模板变量，根据实际情况修改
        );
        $httpInfo = array();
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_HTTP_VERSION , CURL_HTTP_VERSION_1_1 );
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT , 30 );
        curl_setopt( $ch, CURLOPT_TIMEOUT , 30);
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER , true );
        if( $ispost )
        {
            curl_setopt( $ch , CURLOPT_POST , true );
            curl_setopt( $ch , CURLOPT_POSTFIELDS , $smsConf );
            curl_setopt( $ch , CURLOPT_URL , $url );
        }
        else
        {
            if($smsConf){
                curl_setopt( $ch , CURLOPT_URL , $url.'?'.$smsConf );
            }else{
                curl_setopt( $ch , CURLOPT_URL , $url);
            }
        }
        $response = curl_exec( $ch );
        if ($response === FALSE) {
            return false;
        }
        $httpCode = curl_getinfo( $ch , CURLINFO_HTTP_CODE );
        $httpInfo = array_merge( $httpInfo , curl_getinfo( $ch ) );
        curl_close($ch);
        return $response;
    }

}
