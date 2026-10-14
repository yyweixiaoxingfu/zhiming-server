<?php

namespace app\ai\home;

use think\Controller;
use think\Db;
use think\Exception;
use app\member\model\MemberModel;
use app\ai\model\AiMainModel;
use app\ai\model\AiTtsModel;
use app\ai\library\WebSocketClient;

if (ob_get_level() > 0) {
    // 如果有缓冲区，则清空缓冲区
    ob_end_clean();
}
header("Access-Control-Allow-Origin: *");
header("Content-Type: audio/mp3");
header("X-Accel-Buffering: no");
set_time_limit(0);

class Tts extends Controller
{
    public function get_tts()
    {
        $get_data           = safeFilter(input('get.'));
        $get_userrndstr     = isset($get_data['user']) ? $get_data['user'] : '';
        $get_msg            = isset($get_data['msg']) ? $get_data['msg'] : '';
        //if($this->request->isPost()){
        $post_data      = safeFilter(input('post.'));
        $main_data      = AiMainModel::get(1);
        $proxyaddress   = $main_data["proxyaddress"];
        $license        = $main_data["license"];

        $msg            = isset($post_data['msg']) ? $post_data['msg'] : $get_msg;
        if (!$msg) {
            echo 'data: {"error":{"code":"msg_error","message":"请输入要合成的文字"}}' . "\n\n";
        }
        $userrndstr     = isset($post_data['userrndstr']) ? $post_data['userrndstr'] : $get_userrndstr;
        if (!$userrndstr) {
            echo 'data: {"error":{"code":"invalid_user","message":"用户不存在或已被锁定"}}' . "\n\n";
            exit;
        }
        $user            = MemberModel::where('rndstr', $userrndstr)->where('lock', 0)->find();
        if (($userrndstr != 'admin') && empty($user)) {
            echo 'data: {"error":{"code":"invalid_user","message":"用户不存在或已被锁定"}}' . "\n\n";
            exit;
        }
        $tts_config             = AiTtsModel::get(1);
        if (empty($tts_config["ttsmodel"])) {
            echo 'data: {"error":{"code":"invalid_tts_model","message":"TTS model is not selected."}}' . "\n\n";
            exit;
        }
        $ttsmodel               = $tts_config["ttsmodel"];
        $prompt                 = urldecode($msg);
        switch ($ttsmodel) {
            case "openai":
                $this->openaitts($prompt, $tts_config["openaiapikey"], $tts_config["openaiapiaddress"], $tts_config["openaimodel"], $tts_config["openairole"], $tts_config["openaispeed"], $proxyaddress);
                break;
            case "xunfei":
                $this->xunfeitts($prompt, $tts_config["xunfeiapikey"], $tts_config["xunfeiapiaddress"], $tts_config["xunfeirole"], $tts_config["xunfeipitch"], $tts_config["xunfeispeed"], $tts_config["xunfeivolume"]);
                break;
            case "microsoft":
                $this->microsofttts($prompt, $license, $tts_config["microsoftapiaddress"], $tts_config["microsoftrole"], $tts_config["microsoftspeed"], $tts_config["microsoftvolume"]);
                break;
            default:
                echo 'data: {"error":{"code":"invalid_tts_model","message":"TTS model is not selected."}}' . "\n\n";
                exit;
        }
        //}
    }

    public function microsofttts($prompt, $license, $microsoftapiaddress, $microsoftrole, $microsoftspeed, $microsoftvolume)
    {
        $postdata = [
            "input" => $prompt,
            "role" => $microsoftrole,
            "volume" => $microsoftvolume,
            "speed" => $microsoftspeed
        ];
        $postdata = json_encode($postdata);
        $headers  = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $license
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_URL, $microsoftapiaddress);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $data) {
            echo $data; // 实时输出音频流数据
            return strlen($data);
        });
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_exec($ch);
        curl_close($ch);
    }

    public function xunfeitts($prompt, $xunfeiapikey, $xunfeiapiaddress, $xunfeirole, $xunfeipitch, $xunfeispeed, $xunfeivolume)
    {
        function assembleAuthUrl($addr, $apiSecret, $apiKey)
        {
            $ul = parse_url($addr);
            if (($apiKey . $apiSecret == "") || ($ul === false)) {
                return $addr;
            }
            $timestamp = time();
            $rfc1123_format = gmdate("D, d M Y H:i:s \G\M\T", $timestamp);
            $signString = array("host: " . $ul["host"], "date: " . $rfc1123_format, "GET " . $ul["path"] . " HTTP/1.1");
            $sgin = implode("\n", $signString);
            $sha = hash_hmac('sha256', $sgin, $apiSecret, true);
            $signature_sha_base64 = base64_encode($sha);
            $authUrl = 'api_key="' . $apiKey . '", algorithm="hmac-sha256", headers="host date request-line", signature="' . $signature_sha_base64 . '"';
            $authAddr = $addr . '?' . http_build_query(array(
                'host' => $ul['host'],
                'date' => $rfc1123_format,
                'authorization' => base64_encode($authUrl),
            ));
            return $authAddr;
        }
        $apikeyarray = explode(",", $xunfeiapikey);
        $authUrl = assembleAuthUrl($xunfeiapiaddress, $apikeyarray[1], $apikeyarray[2]);
        try {
            $ws = new WebSocketClient($authUrl);
            $requestData = '{"common": {"app_id": "' . $apikeyarray[0] . '"},"business": {"aue": "lame", "sfl":1, "tte":"UTF8", "vcn": "' . $xunfeirole . '", "pitch": ' . $xunfeipitch . ', "volume": ' . $xunfeivolume . ', "speed": ' . $xunfeispeed . '}, "data": {"status": 2, "text": "' . base64_encode($prompt) . '"}}';
            $ws->ping();
            $ws->send($requestData);
            while ($frame = $ws->recv()) {
                $json = json_decode($frame->playload);
                if (!isset($json->data)) {
                    break;
                }
                if ((isset($json->data)) && (isset($json->data->audio))) {
                    echo base64_decode($json->data->audio);
                }
                flush();
                if ((isset($json->data)) && (isset($json->data->status)) && ($json->data->status == 2)) {
                    break;
                }
            }
            $ws->close();
        } catch (Exception $e) {
            echo 'data: {"error":{"code":"unknown","message":"' . urlencode($e->getMessage()) . '"}}' . "\n\n";
        }
    }

    public function openaitts($prompt, $openaiapikey, $openaiapiaddress, $openaimodel, $openairole, $openaispeed, $proxyaddress)
    {
        $postdata = [
            "model" => $openaimodel,
            "input" => $prompt,
            "voice" => $openairole,
            "speed" => $openaispeed
        ];
        $postdata = json_encode($postdata);
        $headers  = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $openaiapikey
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_URL, $openaiapiaddress);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $data) {
            echo $data; // 实时输出音频流数据
            return strlen($data);
        });
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        if (!empty($proxyaddress)) {
            curl_setopt($ch, CURLOPT_PROXY, $proxyaddress);
        }
        $responsedata = curl_exec($ch);
        curl_close($ch);
        echo $responsedata;
    }
}
