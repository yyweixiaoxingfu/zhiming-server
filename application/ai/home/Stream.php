<?php

namespace app\ai\home;

use think\Controller;
use think\Db;
use think\Exception;
use app\ai\home\StreamContext;
use app\ai\model\AiMainModel;
use app\ai\model\AiMoldsModel;
use app\ai\model\AiApikeyModel;
use app\member\model\MemberModel;
use app\ai\library\WebSocketClient;
use app\ai\library\StreamResponseHandler;
use app\ai\service\AIConfigService;
use app\ai\service\AIModelService;
use app\ai\service\AIAuthService;
use app\ai\service\AIStreamService;
use app\member\model\MemberCreditModel;

if (ob_get_level() > 0) {
    // 如果有缓冲区，则清空缓冲区
    ob_end_clean();
}
// 设置响应头
error_reporting(E_ALL ^ E_WARNING);
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/event-stream");
header("X-Accel-Buffering: no");
set_time_limit(0);
class Stream extends Controller
{
    public function get_stream()
    {
        $fuckali        = "";
        $timestamp      = time();
        $proxyaddress   = AIConfigService::getProxyAddress();
        $get_data       = safeFilter(input('get.'));
        $userrndstr     = isset($get_data['user']) ? $get_data['user'] : '';
        if(!$userrndstr){
            echo 'data: {"error":{"code":"invalid_user","message":"用户不存在或已被锁定"}}' . "\n\n";
            exit;
        }
        $user           = AIAuthService::verifyUser($userrndstr);
        if (empty($user)) {
            echo 'data: {"error":{"code":"invalid_user","message":"用户不存在或已被锁定"}}' . "\n\n";
            exit;
        }
        if (empty($user["lastquestion"])) {
            echo 'data: {"error":{"code":"repeated_query","message":""}}' . "\n\n";
            exit;
        }
        // 获取基础参数
        $user_id        = $user["id"];
        $quota          = isset($user["quota"]) ? (float)$user["quota"] : 0;
        $conversationid = explode(",", $user["lastquestion"])[0];
        $postdata       = substr($user["lastquestion"], strlen($conversationid) + 1);
        $lastmodelid    = $user["lastmodelid"];
        // 获取模型信息
        $model = AIModelService::getModelById($lastmodelid);

        if (empty($model)) {
            echo 'data: {"error":{"code":"model_not_found","message":"模型不存在"}}' . "\n\n";
            exit;
        }
        $modelprice = $model['modelprice'];
        $modelvalue = $model['modelvalue'];
        $modeltype  = $model['modeltype'];

        $isimage    = isset($get_data['isimage']) ? $get_data['isimage'] : 0;
        if (!AIAuthService::checkUserQuota($user, $modelprice)) {
            echo 'data: {"error":{"code":"out_of_money","message":""}}' . "\n\n";
            exit;
        }
        $responsedata   = "";
        $OPENAI_API_KEY = "";
        $api_key = AIStreamService::getApiKey($lastmodelid);
        if (empty($api_key)) {
            echo 'data: "error":{"code":"key_not_found","message":"API密钥不存在"}' . "\n\n";
            exit;
        }
        $OPENAI_API_KEY      = $api_key['apikey'];
        $apiaddress          = $api_key["apiaddress"];
        $apikeyid            = $api_key["id"];
        $domain              = $api_key["domain"];
        AIStreamService::updateApiKeyLastTime($apikeyid);
        $get_headers_data    = AIStreamService::getHeaders($modeltype, $modelvalue, $postdata, $isimage, $OPENAI_API_KEY, $apikeyid, $timestamp);
        $headers             = $get_headers_data['headers'];
        $postdata            = $get_headers_data['postdata'];
        $accesstoken         = $get_headers_data['accesstoken'];
        // 初始化所有需要的变量
        $context = new StreamContext();
        $handler = new StreamResponseHandler($modeltype, $modelvalue, $context);
        $callback = [$handler, 'handleResponse'];
        // 初始化变量
        $imageurl     = '';
        $errcode      = '';
        $errmsg       = '';
        $iserror      = false;
        $responsedata = '';

        // 设置图片URL
        if ($isimage) {
            $questionarr = json_decode($postdata, true);
            $imageurl = $questionarr['imageurl'] ?? '';
            $context->imageUrl = $imageurl;
            unset($questionarr['imageurl']);
        }

        try {
            
            if ($modeltype == "讯飞星火") {
                $apikeyarray = explode(",", $OPENAI_API_KEY);
                $authUrl = $this->assembleAuthUrl($apiaddress, $apikeyarray[1], $apikeyarray[2]);
                // 版本映射配置
                $versionMap = [
                    'v1.1' => 'general',
                    'v2.1' => 'generalv2',
                    'v3.1' => 'generalv3',
                    'v3.5' => 'generalv3.5'
                ];
                if(!$domain){
                     $domain = $this->getXunfeiDomain($apiaddress, $versionMap);
                }
                $ws = null;
                try {
                    $ws = new WebSocketClient($authUrl);
                    $requestData = $this->buildXunfeiRequestData($postdata, $apikeyarray[0], $domain, $isimage);
                    $ws->ping();
                    $ws->send($requestData);
                    $this->processXunfeiStream($ws, $context);
                    // 关键修复：将context中的响应数据赋值给responsedata变量，确保后续能正确保存答案
                    $responsedata = $context->responseData;
                    // 添加调试日志，检查responsedata是否获取到值
                    //error_log(date('Y-m-d H:i:s') . " responsedata: " . substr($responsedata, 0, 100) . "...\n", 3, "response_debug.log");
                } catch (Exception $e) {
                    $this->handleXunfeiError($e, $context);
                    // 记录异常信息到调试日志
                    //error_log(date('Y-m-d H:i:s') . " exception: " . $e->getMessage() . "\n", 3, "response_debug.log");
                } finally {
                    if ($ws !== null) {
                        $ws->close();
                    }
                }
            } else {
                $accesstoken = '';
                $this->sendRequest($modeltype, $modelvalue, $apiaddress, $headers, $postdata, $callback, $proxyaddress, $accesstoken, $isimage, $responsedata, $fuckali, $user_id, $OPENAI_API_KEY);
            }
            // 从context获取错误信息
            $errcode      = $context->errorCode;
            $errmsg       = $context->errorMsg;
            $iserror      = $handler->hasError();
            // 关键修复：只有非讯飞星火模型才从handler获取响应数据，避免覆盖讯飞星火模型的响应数据
            if ($modeltype != "讯飞星火") {
                $responsedata = $handler->getResponseData();
            }
        } catch (Exception $e) {
            $errcode = 'system_error';
            $errmsg = $e->getMessage();
            $iserror = true;
        }

        // 构建问题描述
        $questionarr  = json_decode($postdata, true);
        $goodquestion = '';
        $goodquestion = AIStreamService::buildQuestionDescription($modeltype, $modelvalue, $questionarr, $isimage, $imageurl);
        // 处理响应和错误
        if (!$iserror) {
            $answer     = AIStreamService::extractAnswerFromResponse($responsedata);
            $goodanswer = trim($answer);
            //error_log(date('Y-m-d H:i:s') . " goodanswer: {$goodanswer}\n", 3, "answer.log");
            // 扣除用户积分
            MemberCreditModel::credit(
                $user_id,
                'points',
                -$modelprice,
                'AI文本生成消耗',
                '',
                '',
                'ai_text'
            );
            AIStreamService::recordChatHistory([
                'title'          => substr(AIStreamService::parseMarkdownImage($goodquestion), 0, 100),
                'question'       => $goodquestion,
                'answer'         => $goodanswer,
                'conversationid' => $conversationid,
                'modelid'        => $lastmodelid,
                'realtime'       => date('Y-m-d H:i:s'),
                'userid'         => $user_id,
                'iserror'        => $iserror
            ]);
        } else {
            if (in_array($errcode, ["access_terminated", "insufficient_quota", "invalid_api_key", "account_deactivated"])) {
                AIStreamService::disableApiKey($apikeyid, $errcode . "|" . $errmsg);
            }
            $goodanswer = trim($responsedata);
            AIStreamService::recordErrorLog([
                'question'          => $goodquestion,
                'errmsg'            => $goodanswer,
                'conversationid'    => $conversationid,
                'modelid'           => $lastmodelid,
                'realtime'          => date('Y-m-d H:i:s'),
                'userid'            => 1,
                'apikey'            => $OPENAI_API_KEY
            ]);
        }
    }

    /**
     * 发送请求
     * @param string $modeltype 模型类型
     * @param string $modelvalue 模型值
     * @param string $apiaddress API地址
     * @param array $headers 请求头
     * @param string $postdata 请求数据
     * @param callable $callback 回调函数
     * @param string $proxyaddress 代理地址
     * @param string $accesstoken 访问令牌
     * @param bool $isimage 是否是图片
     * @param string $responsedata 响应数据
     * @param string $fuckali 备用数据
     * @param int $user_id 用户ID
     * @param string $OPENAI_API_KEY API密钥
     * @return void
     */
    private function sendRequest($modeltype, $modelvalue, $apiaddress, $headers, $postdata, $callback, $proxyaddress, $accesstoken, $isimage, &$responsedata, &$fuckali, $user_id, $OPENAI_API_KEY)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        
        $url = AIStreamService::getRequestUrl($modeltype, $modelvalue, $apiaddress, $isimage, $proxyaddress, $user_id);
        curl_setopt($ch, CURLOPT_URL, $url);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, $callback);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 300); // 设置连接超时时间为300秒
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3); // 设置最大重定向次数为3次
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // 允许自动重定向
        curl_setopt($ch, CURLOPT_AUTOREFERER, true); // 自动设置Referer
        curl_exec($ch);
        curl_close($ch);
        if ($fuckali != "") {     
            $responsedata .= $fuckali;
            echo $fuckali;
        }
    }

    /**
     * 组装认证URL
     * @param string $addr 地址
     * @param string $apiSecret API密钥
     * @param string $apiKey API密钥
     * @return string
     */
    public function assembleAuthUrl($addr, $apiSecret, $apiKey)
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
        $authAddr = $addr . '?' . http_build_query(
            array(
                'host' => $ul['host'],
                'date' => $rfc1123_format,
                'authorization' => base64_encode($authUrl),
            )
        );
        return $authAddr;
    }

    /**
     * 获取讯飞星火模型对应的domain
     * @param string $apiaddress API地址
     * @param array $versionMap 版本映射
     * @return string
     */
    private function getXunfeiDomain($apiaddress, $versionMap)
    {
        foreach ($versionMap as $version => $domain) {
            if (strpos($apiaddress, $version) !== false) {
                return $domain;
            }
        }
        return 'general'; // 默认domain
    }

    /**
     * 构建讯飞星火请求数据
     * @param string $postdata 请求数据
     * @param string $appId 应用ID
     * @param string $domain 域名
     * @param bool $isimage 是否是图片
     * @return string
     */
    private function buildXunfeiRequestData($postdata, $appId, $domain, $isimage)
    {
        $postdatajson = json_decode($postdata, true);

        if (!$isimage) {
            // 纯文本模型处理
            $messages = array_filter($postdatajson["messages"] ?? [], function ($msg) {
                return $msg["role"] !== "system";
            });

            return json_encode([
                "header" => ["app_id" => $appId],
                "parameter" => ["chat" => ["domain" => $domain]],
                "payload" => ["message" => ["text" => array_values($messages)]]
            ]);
        } else {
            // 识图模型处理
            $imageurl = $postdatajson["imageurl"] ?? '';
            if (empty($imageurl)) {
                throw new Exception("图片URL不能为空");
            }

            $imgContent = file_get_contents($imageurl);
            if ($imgContent === false) {
                throw new Exception("无法读取图片内容");
            }

            $postdatajson["header"]["app_id"] = $appId;
            unset($postdatajson["imageurl"]);
            $postdatajson["payload"]["message"]["text"][0]["content"] = base64_encode($imgContent);

            return json_encode($postdatajson);
        }
    }

    /**
     * 处理讯飞星火流式响应
     * @param WebSocketClient $ws WebSocket客户端
     * @param StreamContext $context 上下文
     * @return void
     */
    private function processXunfeiStream($ws, $context)
    {
        while ($frame = $ws->recv()) {
            $json = json_decode($frame->playload);

            if (!$json || !isset($json->header)) {
                continue;
            }

            if ($json->header->code !== 0) {
                $this->sendErrorResponse($json->header->message, $context);
                break;
            }

            if (!isset($json->payload->choices->text[0]->content)) {
                continue;
            }

            // 首次响应标记
            if ($context->isStart) {
                $context->isStart = false;
            }

            // 构建标准响应格式
            $response = [
                "id" => $json->header->sid,
                "object" => "chat.completion.chunk",
                "created" => time(),
                "choices" => [[
                    "index" => 0,
                    "delta" => ["content" => $json->payload->choices->text[0]->content],
                    "finish_reason" => null
                ]]
            ];

            $responseData = 'data: ' . json_encode($response) . "\n\n";
            $context->responseData .= $responseData;
            echo $responseData;
            flush();

            // 检查是否结束
            if (isset($json->payload->usage)) {
                $endResponse = 'data: {"id":"' . $json->header->sid . '","object":"chat.completion.chunk","created":' . time() . ',"choices":[{"index":0,"delta":{},"finish_reason":"stop"}]}' . "\n\n" . 'data: [DONE]' . "\n\n";
                $context->responseData .= $endResponse;
                echo $endResponse;
                flush();
                break;
            }
        }
    }

    /**
     * 处理讯飞星火错误
     * @param Exception $exception 异常
     * @param StreamContext $context 上下文
     * @return void
     */
    private function handleXunfeiError($exception, $context)
    {
        $errorResponse = [
            "error" => [
                "code" => "unknown",
                "message" => $exception->getMessage()
            ]
        ];

        $errorData = 'data: ' . json_encode($errorResponse) . "\n\n";
        echo $errorData;
        $context->responseData .= $errorData;
        $context->isError = true;
        $context->errorCode = 'unknown';
        $context->errorMsg = $exception->getMessage();
    }

    /**
     * 发送错误响应
     * @param string $message 错误信息
     * @param StreamContext $context 上下文
     * @return void
     */
    private function sendErrorResponse($message, $context)
    {
        $errorResponse = [
            "error" => [
                "code" => "api_error",
                "message" => $message
            ]
        ];

        $errorData = "data: " . json_encode($errorResponse) . "\n\n";
        echo $errorData;
        $context->responseData .= $errorData;
        $context->isError = true;
        $context->errorCode = 'api_error';
        $context->errorMsg = $message;
    }
}
