<?php

namespace app\ai\service;

use think\Db;
use app\ai\model\AiMainModel;
use app\ai\model\AiMoldsModel;
use app\ai\model\AiApikeyModel;
use app\member\model\MemberModel;

/**
 * AI流式响应服务类
 * 处理流式响应逻辑
 */
class AIStreamService
{
    /**
     * 验证URL是否安全
     * @param string $url URL地址
     * @return bool 是否安全
     */
    private static function isValidUrl(string $url): bool
    {
        // 验证URL格式
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        
        // 只允许http和https协议
        $parsedUrl = parse_url($url);
        if (!isset($parsedUrl['scheme']) || !in_array($parsedUrl['scheme'], ['http', 'https'])) {
            return false;
        }
        
        // 禁止访问本地文件和内部地址
        $host = $parsedUrl['host'];
        if (in_array($host, ['localhost', '127.0.0.1', '::1']) || 
            preg_match('/^(10|172\.(1[6-9]|2[0-9]|3[01])|192\.168)\./', $host)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * 构建问题描述
     * @param string $modeltype 模型类型
     * @param string $modelvalue 模型名称
     * @param array $questionData 解析后的问题数据
     * @param bool $isimage 是否包含图片
     * @param string $imageurl 图片URL
     * @return string
     */
    public static function buildQuestionDescription($modeltype, $modelvalue, $questionData, $isimage, $imageurl = '')
    {
        $imagePrefix = '';

        // 处理图片前缀
        if ($isimage) {
            $imageUrl = self::extractImageUrl($modeltype, $questionData, $imageurl);
            $imagePrefix = $imageUrl ? '![IMG](' . $imageUrl . ')' : '';
        }

        // 获取文本内容
        $textContent = self::extractTextContent($modeltype, $modelvalue, $questionData, $isimage);

        return $imagePrefix . $textContent;
    }

    /**
     * 提取图片URL
     * @param string $modeltype 模型类型
     * @param array $questionData 问题数据
     * @param string $fallbackImageUrl 备用图片URL
     * @return string
     */
    private static function extractImageUrl($modeltype, $questionData, $fallbackImageUrl)
    {
        switch ($modeltype) {
            case "midjourney":
                return $questionData['imageurl'] ?? '';

            case "通义千问":
                return $questionData['input']['messages'][0]['content'][0]['image'] ?? '';

            case "腾讯混元":
                return $questionData['Messages'][0]['Contents'][1]['ImageUrl']['Url'] ?? '';

            case "讯飞星火":
                return $questionData['imageurl'] ?? '';

            case "bard":
            case "claude":
                return $fallbackImageUrl;

            case "OpenAI":
            default:
                return $questionData['messages'][0]['content'][1]['image_url']['url'] ?? '';
        }
    }

    /**
     * 提取文本内容
     * @param string $modeltype 模型类型
     * @param string $modelvalue 模型名称
     * @param array $questionData 问题数据
     * @param bool $isimage 是否包含图片
     * @return string
     */
    private static function extractTextContent($modeltype, $modelvalue, $questionData, $isimage)
    {
        switch ($modeltype) {
            case "midjourney":
                return '';

            case "通义千问":
                return $isimage
                    ? ($questionData['input']['messages'][0]['content'][1]['text'] ?? '')
                    : ($questionData['input']['prompt'] ?? '');

            case "腾讯混元":
                return $isimage
                    ? ($questionData['Messages'][0]['Contents'][0]['Text'] ?? '')
                    : (end($questionData['Messages'])['Content'] ?? '');

            case "讯飞星火":
                return $isimage
                    ? ($questionData['payload']['message']['text'][1]['content'] ?? '')
                    : (end($questionData['messages'])['content'] ?? '');

            case "MiniMax":
                return (end($questionData['messages'])['text'] ?? '');

            case "bard":
                return (end($questionData['contents'])['parts'][0]['text'] ?? '');

            case "claude":
                return $isimage
                    ? ($questionData['messages'][0]['content'][0]['text'] ?? '')
                    : (end($questionData['messages'])['content'] ?? '');

            case "OpenAI":
            default:
                // 处理特殊模型
                if ($modelvalue == "CodeLlama-7b-Instruct" || $modelvalue == "gpt-3.5-turbo-instruct") {
                    return ($questionData['prompt'] ?? '');
                }
                return $isimage
                    ? ($questionData['messages'][0]['content'][0]['text'] ?? '')
                    : (end($questionData['messages'])['content'] ?? '');
        }
    }

    /**
     * 从响应数据中提取答案
     * @param string $responsedata 响应数据
     * @return string
     */
    public static function extractAnswerFromResponse($responsedata)
    {
        $answer = "";
        if (substr(trim($responsedata), -6) == "[DONE]") {
            $responsedata = substr(trim($responsedata), 0, -6) . "]";
        }
        $responsedata = str_replace("\r", "\n", $responsedata);
        $responsearr  = explode("\n\ndata: {", "\n\n" . $responsedata);
        foreach ($responsearr as $msg) {
            $contentarr = json_decode("{" . trim($msg), true);
            if (isset($contentarr['choices'][0]['delta']['content'])) {
                $answer .= $contentarr['choices'][0]['delta']['content'];
            }
        }
        
        return $answer;
    }

    /**
     * 解析Markdown图片
     * @param string $str 字符串
     * @return string
     */
    public static function parseMarkdownImage($str)
    {
        if (substr($str, 0, 6) === '![IMG]') {
            $regex = '/!\[IMG\]\((.*?)\)/';
            return preg_replace($regex, '[图片]', $str);
        } else {
            return $str;
        }
    }

    /**
     * 获取API密钥
     * @param int $modelId 模型ID
     * @return array|false
     */
    public static function getApiKey($modelId)
    {
        return AiApikeyModel::where('isvalid', 1)->where('keytype', $modelId)->find();
    }

    /**
     * 更新API密钥最后使用时间
     * @param int $apikeyid 密钥ID
     * @return bool
     */
    public static function updateApiKeyLastTime($apikeyid)
    {
        return AiApikeyModel::where('id', $apikeyid)->update(['lasttime' => date('Y-m-d H:i:s')]);
    }

    /**
     * 记录对话历史
     * @param array $data 对话数据
     * @return bool
     */
    public static function recordChatHistory($data)
    {
        return Db::name('ai_chathistory')->insert($data);
    }

    /**
     * 记录错误日志
     * @param array $data 错误数据
     * @return bool
     */
    public static function recordErrorLog($data)
    {
        return Db::name('ai_errorlog')->insert($data);
    }

    /**
     * 禁用无效API密钥
     * @param int $apikeyid 密钥ID
     * @param string $errmsg 错误信息
     * @return bool
     */
    public static function disableApiKey($apikeyid, $errmsg)
    {
        return AiApikeyModel::where('id', $apikeyid)->update([
            'isvalid' => 0,
            'lasttime' => date('Y-m-d H:i:s'),
            'errmsg' => $errmsg
        ]);
    }

    /**
     * 获取请求头
     * @param string $modeltype 模型类型
     * @param string $modelvalue 模型值
     * @param string $postdata 请求数据
     * @param bool $isimage 是否是图片
     * @param string $openaiApiKey API密钥
     * @param int $apikeyid 密钥ID
     * @param int $timestamp 时间戳
     * @return array
     */
    public static function getHeaders($modeltype, $modelvalue, $postdata, $isimage, $openaiApiKey, $apikeyid, $timestamp)
    {
        $accesstoken = '';
        switch ($modeltype) {
            case "文心千帆":
                $headers = [
                    'Accept: application/json',
                    'Content-Type: application/json'
                ];

                $apikeyarray = explode(",", $openaiApiKey);
                if (count($apikeyarray) == 2) {
                    $accesstoken = self::getBaiduAccessToken($openaiApiKey, $apikeyid);
                } else {
                    $accesstoken = $apikeyarray[2];
                }
                $postdatajson = json_decode($postdata, true);

                if ($modelvalue == "fuyu_8b") { //识图模型
                    $imageurl = $postdatajson['imageurl'];
                    // 安全处理：添加URL验证和错误处理
                    if (self::isValidUrl($imageurl)) {
                        $img = @file_get_contents($imageurl);
                        if ($img !== false) {
                            unset($postdatajson['imageurl']);
                            $postdatajson["image"] = base64_encode($img);
                        }
                    }
                } else {
                    unset($postdatajson['temperature']);
                    if ($modelvalue == "CodeLlama-7b-Instruct") {
                        $jsonmessages = json_decode($postdata, true)["messages"];
                        $postdatajson['prompt'] = end($jsonmessages)["content"];
                        unset($postdatajson['messages']);
                    } else {
                        //下面这段代码是为了把角色去掉，国内模型都不支持
                        $jsonmessages = $postdatajson["messages"];
                        for ($i = 0; $i < count($jsonmessages); ++$i) {
                            if ($jsonmessages[$i]["role"] !== "system") {
                                $newjson[] = $jsonmessages[$i];
                            }
                        }
                        $postdatajson["messages"] = $newjson;
                    }
                }
                $postdata = json_encode($postdatajson);
                break;
            case "清华智谱":
                $token = self::getQinghuaZhipuToken($openaiApiKey, 3600);
                $headers = [
                    'Accept: text/event-stream',
                    'Content-Type: application/json',
                    'Authorization: ' . $token
                ];

                $postdatajson = json_decode($postdata, true);
                unset($postdatajson['max_tokens']);
                $postdatajson['temperature'] = 0.01;
                $postdata = json_encode($postdatajson);
                break;
            case "通义千问":
                $headers = [
                    'Accept: text/event-stream',
                    'Content-Type: application/json',
                    'X-DashScope-SSE: enable',
                    'Authorization: Bearer ' . $openaiApiKey
                ];
                if (!$isimage) { //纯文本模型。识图模型不需要执行以下代码，setsession时已经拼接好了prompt
                    $jsonmessages = json_decode($postdata, true)["messages"];
                    $postdata = [
                        "model" => $modelvalue,
                        "input" => ["prompt" => "", "history" => []],
                        "parameters" => (object) []
                    ];
                    $postdata["input"]["prompt"] = end($jsonmessages)["content"];
                    for ($i = 0; $i < (count($jsonmessages) - 1); ++$i) {
                        if ($jsonmessages[$i]["role"] == "user") {
                            $postdata["input"]["history"][] = ['user' => $jsonmessages[$i]["content"], 'bot' => $jsonmessages[$i + 1]["content"]];
                            $i++;
                        }
                    }
                    $postdata = json_encode($postdata);
                }
                break;
            case "腾讯混元":
                if (!$isimage) { //纯文本模型。识图模型不需要执行以下代码，setsession时已经拼接好了prompt
                    $jsonmessages = json_decode($postdata, true)["messages"];
                    $postdata = [
                        "Model" => $modelvalue,
                        "TopP" => 0,
                        "Temperature" => 0,
                        "Stream" => true,
                        "Messages" => []
                    ];
                    for ($i = 0; $i < (count($jsonmessages) - 1); ++$i) {
                        if ($jsonmessages[$i]["role"] == "user") {
                            $postdata['Messages'][] = ['Role' => 'user', 'Content' => $jsonmessages[$i]["content"]];
                            $postdata['Messages'][] = ['Role' => 'assistant', 'Content' => $jsonmessages[$i + 1]["content"]];
                            $i++;
                        }
                    }
                    $postdata['Messages'][] = ["Role" => "user", "Content" => end($jsonmessages)["content"]];
                    $postdata = json_encode($postdata);
                }
                $authorization = self::getTencentHunyuanToken($openaiApiKey, $timestamp, $postdata);
                $headers = [
                    "Authorization: " . $authorization,
                    "Content-Type: application/json; charset=utf-8",
                    "Host: hunyuan.tencentcloudapi.com",
                    "X-TC-Action: ChatCompletions",
                    "X-TC-Timestamp: " . $timestamp,
                    "X-TC-Version: 2023-09-01",
                    "X-TC-Region: ap-guangzhou"
                ];
                break;
            case "MiniMax":
                $headers = [
                    'Accept: text/event-stream',
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $openaiApiKey
                ];
                $postdatajson = json_decode($postdata, true);
                $postdatajson['temperature'] = 0.01;
                $postdatajson['tokens_to_generate'] = 2048;
                $postdatajson['bot_setting'] = [];
                $postdatajson['bot_setting'][0]['bot_name'] = "智能助理";
                $postdatajson['bot_setting'][0]['content'] = "智能助理是一款由MiniMax自研的，没有调用其他产品的接口的大型语言模型。MiniMax是一家中国科技公司，一直致力于进行大模型相关的研究。";
                $postdatajson['reply_constraints'] = [];
                $postdatajson['reply_constraints']['sender_type'] = "BOT";
                $postdatajson['reply_constraints']['sender_name'] = "智能助理";

                $jsonmessages = json_decode($postdata, true)["messages"];
                unset($postdatajson['messages']);
                $postdatajson['messages'] = [];
                for ($i = 0; $i < (count($jsonmessages) - 1); ++$i) {
                    if ($jsonmessages[$i]["role"] == "user") {
                        $postdatajson['messages'][] = ['sender_type' => 'USER', 'sender_name' => '用户', 'text' => $jsonmessages[$i]["content"]];
                        $postdatajson['messages'][] = ['sender_type' => 'BOT', 'sender_name' => '智能助理', 'text' => $jsonmessages[$i + 1]["content"]];
                        $i++;
                    }
                }
                $postdatajson['messages'][] = ["sender_type" => "USER", 'sender_name' => '用户', "text" => end($jsonmessages)["content"]];
                $postdata = json_encode($postdatajson);
                break;
            case "bard":
                $headers = [
                    'Content-Type: application/json'
                ];
                if (!$isimage) { //纯文本模型
                    $jsonmessages = json_decode($postdata, true)["messages"];
                    $postdata = [
                        "contents" => [],
                        "generationConfig" => [
                            "temperature" => 0,
                            "topK" => 1,
                            "topP" => 1,
                            "maxOutputTokens" => 2048,
                            "stopSequences" => []
                        ],
                        "safetySettings" => [
                            [
                                "category" => "HARM_CATEGORY_HARASSMENT",
                                "threshold" => "BLOCK_NONE",
                            ],
                            [
                                "category" => "HARM_CATEGORY_HATE_SPEECH",
                                "threshold" => "BLOCK_NONE",
                            ],
                            [
                                "category" => "HARM_CATEGORY_SEXUALLY_EXPLICIT",
                                "threshold" => "BLOCK_NONE",
                            ],
                            [
                                "category" => "HARM_CATEGORY_DANGEROUS_CONTENT",
                                "threshold" => "BLOCK_NONE",
                            ]
                        ]
                    ];
                    for ($i = 0; $i < (count($jsonmessages) - 1); ++$i) {
                        if ($jsonmessages[$i]["role"] == "user") {
                            $postdata['contents'][] = ['role' => 'user', 'parts' => [['text' => $jsonmessages[$i]["content"]]]];
                            $postdata['contents'][] = ['role' => 'model', 'parts' => [['text' => $jsonmessages[$i + 1]["content"]]]];
                            $i++;
                        }
                    }
                    $postdata['contents'][] = ['role' => 'user', 'parts' => [['text' => end($jsonmessages)["content"]]]];
                    $postdata = json_encode($postdata);
                } else { //识图模型
                    $postdatajson   = json_decode($postdata, true);
                    $imageurl       = $postdatajson['imageurl'];
                    // 安全处理：添加URL验证和错误处理
                    if (self::isValidUrl($imageurl)) {
                        $img            = @file_get_contents($imageurl);
                        if ($img !== false) {
                            unset($postdatajson['imageurl']);
                            $postdatajson["contents"][0]["parts"][1]["inline_data"]["data"] = base64_encode($img);
                            $postdata       = json_encode($postdatajson);
                        }
                    }
                }
                break;
            case "claude":
                $headers = [
                    'Accept: text/event-stream',
                    'Content-Type: application/json',
                    'anthropic-version: 2023-06-01',
                    'x-api-key: ' . $openaiApiKey
                ];
                if (!$isimage) { //纯文本模型
                    $postdatajson = json_decode($postdata, true);
                    unset($postdatajson['temperature']);
                    $postdatajson['max_tokens'] = 2000;
                    $jsonmessages = $postdatajson["messages"];
                    for ($i = 0; $i < count($jsonmessages); ++$i) {
                        if ($jsonmessages[$i]["role"] !== "system") {
                            $newjson[] = $jsonmessages[$i];
                        }
                    }
                    $postdatajson["messages"] = $newjson;
                    $postdata = json_encode($postdatajson);
                } else { //识图模型
                    $postdatajson = json_decode($postdata, true);
                    $imageurl = $postdatajson["messages"][0]['imageurl'];
                    // 安全处理：添加URL验证和错误处理
                    if (self::isValidUrl($imageurl)) {
                        $img = @file_get_contents($imageurl);
                        if ($img !== false) {
                            unset($postdatajson["messages"][0]['imageurl']);
                            $postdatajson["messages"][0]["content"][1]["source"]["data"] = base64_encode($img);
                            $postdata = json_encode($postdatajson);
                        }
                    }
                }
                break;
            case "gpt-3.5-turbo-instruct":
                $headers = [
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $openaiApiKey
                ];
                $postdatajson = json_decode($postdata, true);
                $jsonmessages = $postdatajson["messages"];
                $postdatajson['prompt'] = end($jsonmessages)["content"];
                $postdatajson['max_tokens'] = 4000;
                unset($postdatajson['messages']);
                $postdata = json_encode($postdatajson);
                break;
            case "azure":
                $headers = [
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'api-key: ' . $openaiApiKey
                ];
                break;
            default:
                $headers = [
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $openaiApiKey
                ];
                break;
        }
        
        return [
            'headers'       => $headers,
            'postdata'      => $postdata,
            'accesstoken'   => $accesstoken,
        ];
    }

    /**
     * 获取百度AccessToken
     * @param string $openaiApiKey API密钥
     * @param int $apikeyid 密钥ID
     * @return string
     */
    private static function getBaiduAccessToken($openaiApiKey, $apikeyid)
    {
        $apikey   = explode(",", $openaiApiKey);
        $newtoken = json_decode(file_get_contents("https://aip.baidubce.com/oauth/2.0/token?grant_type=client_credentials&client_id=" . $apikey[0] . "&client_secret=" . $apikey[1]))->access_token;
        AiApikeyModel::where('id', $apikeyid)->update(['apikey' => $apikey[0] . "," . $apikey[1] . "," . $newtoken]);
        return $newtoken;
    }

    /**
     * 获取清华智谱Token
     * @param string $apikey API密钥
     * @param int $expSeconds 过期时间（秒）
     * @return string
     */
    private static function getQinghuaZhipuToken($apikey, $expSeconds)
    {
        list($id, $secret) = explode(".", $apikey);
        $payload = array(
            "api_key" => $id,
            "exp" => round(microtime(true) * 1000) + $expSeconds * 1000,
            "timestamp" => round(microtime(true) * 1000),
        );
        return self::jwtEncode(
            $payload,
            $secret,
            "sha256",
            array("alg" => "HS256", "sign_type" => "SIGN")
        );
    }

    /**
     * 获取腾讯混元Token
     * @param string $openaiApiKey API密钥
     * @param int $timestamp 时间戳
     * @param string $postdata 请求数据
     * @return string
     */
    private static function getTencentHunyuanToken($openaiApiKey, $timestamp, $postdata)
    {
        $apikey             = explode(",", $openaiApiKey);
        $secretId           = $apikey[0];
        $secretKey          = $apikey[1];
        $algorithm          = "TC3-HMAC-SHA256";
        $canonicalHeaders   = implode("\n", ["content-type:application/json; charset=utf-8", "host:hunyuan.tencentcloudapi.com", "x-tc-action:chatcompletions", ""]);
        $signedHeaders      = implode("; ", ["content-type", "host", "x-tc-action",]);
        $hashedRequestPayload = hash("SHA256", $postdata);
        $canonicalRequest   = "POST\n/\n\n" . $canonicalHeaders . "\n" . $signedHeaders . "\n" . $hashedRequestPayload;
        $date               = gmdate("Y-m-d", $timestamp);
        $credentialScope    = $date . "/hunyuan/tc3_request";
        $hashedCanonicalRequest = hash("SHA256", $canonicalRequest);
        $stringToSign = $algorithm . "\n" . $timestamp . "\n" . $credentialScope . "\n" . $hashedCanonicalRequest;
        $secretDate = hash_hmac("SHA256", $date, "TC3" . $secretKey, true);
        $secretService = hash_hmac("SHA256", "hunyuan", $secretDate, true);
        $secretSigning = hash_hmac("SHA256", "tc3_request", $secretService, true);
        $signature = hash_hmac("SHA256", $stringToSign, $secretSigning);
        $authorization = $algorithm . " Credential=" . $secretId . "/" . $credentialScope . ", SignedHeaders=" . $signedHeaders . ", Signature=" . $signature;
        return $authorization;
    }

    /**
     * JWT编码
     * @param array $payload 负载
     * @param string $secret 密钥
     * @param string $algorithm 算法
     * @param array $headers 头部
     * @return string
     */
    private static function jwtEncode($payload, $secret, $algorithm, $headers)
    {
        $header     = self::base64UrlEncode(json_encode($headers));
        $payload    = self::base64UrlEncode(json_encode($payload));
        $signature  = hash_hmac($algorithm, "$header.$payload", $secret, true);
        $signature  = self::base64UrlEncode($signature);
        return "$header.$payload.$signature";
    }

    /**
     * Base64 URL编码
     * @param string $data 数据
     * @return string
     */
    private static function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * 获取请求URL
     * @param string $modeltype 模型类型
     * @param string $modelvalue 模型值
     * @param string $apiaddress API地址
     * @param bool $isimage 是否是图片
     * @param string $proxyaddress 代理地址
     * @param int $user_id 用户ID
     * @return string
     */
    public static function getRequestUrl($modeltype, $modelvalue, $apiaddress, $isimage, $proxyaddress, $user_id, $openaiApiKey = '')
    {
        if ($modeltype == "midjourney") {
            $httpprotocol = "http";
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
                $httpprotocol = "https";
            }
            return $httpprotocol . '://' . $_SERVER['HTTP_HOST'] . '/plugins/mj/getmjdescribe.php?user=' . $user_id;
        } else if ($modeltype == "文心千帆") {
            $accesstoken = '';
            if (!empty($openaiApiKey)) {
                $apikeyarray = explode(",", $openaiApiKey);
                if (count($apikeyarray) > 2) {
                    $accesstoken = $apikeyarray[2];
                } else {
                    $accesstoken = self::getBaiduAccessToken($openaiApiKey, 0);
                }
            }
            return $apiaddress . '?access_token=' . $accesstoken;
        } else if ($modeltype == "清华智谱") {
            return $apiaddress . '/api/paas/v4/chat/completions';
        } else if ($modeltype == "通义千问") {
            if (!$isimage) {
                return $apiaddress . '/api/v1/services/aigc/text-generation/generation';
            } else {
                return $apiaddress . '/api/v1/services/aigc/multimodal-generation/generation';
            }
        } else if ($modeltype == "bard") {
            $apiKey = $openaiApiKey;
            if (!$isimage) {
                return $apiaddress . '/v1beta/models/' . $modelvalue . ':streamGenerateContent?key=' . $apiKey;
            } else {
                return $apiaddress . '/v1beta/models/' . $modelvalue . ':generateContent?key=' . $apiKey;
            }
        } else if ($modeltype == "claude") {
            return $apiaddress . '/v1/messages';
        } else if (($modeltype == "azure") || ($modeltype == "MiniMax") || ($modeltype == "无问芯穹") || ($modeltype == "腾讯混元") || ($modeltype == "火山方舟") || ($modeltype == "DeepSeek")) {
            return $apiaddress;
        } else if ($modelvalue == "gpt-3.5-turbo-instruct") {
            return $apiaddress . '/v1/completions';
        } else {
            $url = $apiaddress . '/v1/chat/completions';
            return $url;
        }
    }
}