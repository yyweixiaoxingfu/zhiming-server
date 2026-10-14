<?php

namespace app\ai\home;

use think\Controller;
use app\ai\model\AiAsrModel;
use app\ai\library\SimpleChineseTranscoder;

header("Access-Control-Allow-Origin: *");
header("X-Accel-Buffering: no");
set_time_limit(0);
class Asr extends Controller
{
    public function index()
    {
        $row                = AiAsrModel::where('id',1)->find();
        //$asrmodel           = $row["asrmodel"];
        $openaiapikey       = $row["openaiapikey"];
        $openaiapiaddress   = $row["openaiapiaddress"];
        if (isset($_FILES['file']) && $_FILES['file']) {
            $filename       = isset($_FILES['file']['tmp_name']) ? $_FILES['file']['tmp_name'] : '';
            if(!$filename){
                   echo "No file uploaded.";
                   exit();
            }
            $model          = 'whisper-1';
            $ch             = curl_init();
            $file_to_upload = new \CURLFile($filename, 'audio/wav');
            $data           = array('file' => $file_to_upload, 'model' => $model, 'language' => 'zh');
            curl_setopt($ch, CURLOPT_URL, $openaiapiaddress);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);

            $headers = array();
            $headers[] = 'Authorization: Bearer ' . $openaiapikey;
            $headers[] = 'Content-Type: multipart/form-data';
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $result = curl_exec($ch);
            if (curl_errno($ch)) {
                echo curl_error($ch);
            } else {
                $transcoder = new SimpleChineseTranscoder;
                echo $transcoder->big5_gb($result);
            }
            curl_close($ch);
        } else {
            echo "No file uploaded.";
            exit();
        }
    }
}