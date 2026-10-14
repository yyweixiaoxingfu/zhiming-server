<?php

namespace app\ai\library;

use think\Db;
use app\ai\model\AiMainModel;
use think\Controller;

class BaiduTranslate extends Controller
{
    public function baidutranslatezhtoen($text)
    {
        $row        = AiMainModel::where('id', 1)->find();;
        $API_KEY    = $row["baidutranslateapikey"];
        $SECRET_KEY = $row["baidutranslatesecretkey"];
        $curl       = curl_init();
        $postData   = array(
            'grant_type'    => 'client_credentials',
            'client_id'     => $API_KEY,
            'client_secret' => $SECRET_KEY
        );
        curl_setopt_array($curl, array(
            CURLOPT_URL             => 'https://aip.baidubce.com/oauth/2.0/token',
             CURLOPT_TIMEOUT        => 30,
            CURLOPT_CUSTOMREQUEST   => 'POST',
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_SSL_VERIFYHOST  => false,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Accept: application/json'
            ),
            CURLOPT_POSTFIELDS      => http_build_query($postData)
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        $rtn = json_decode($response);
        if (isset($rtn->error)) {
            return $text;
            exit;
        }
        $accesstoken = $rtn->access_token;
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL             => "https://aip.baidubce.com/rpc/2.0/mt/texttrans/v1?access_token=" . $accesstoken,
            CURLOPT_TIMEOUT         => 30,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_CUSTOMREQUEST   => 'POST',
            CURLOPT_SSL_VERIFYHOST  => false,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_POSTFIELDS      => '{"from":"zh","to":"en","q":"' . $text . '"}',
            CURLOPT_HTTPHEADER      => array(
                'Content-Type: application/json',
                'Accept: application/json'
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        $result = json_decode($response);
        if (isset($result->error)) {
            return $text;
        } else {
            return $result->result->trans_result[0]->dst;
        }
    }
}
