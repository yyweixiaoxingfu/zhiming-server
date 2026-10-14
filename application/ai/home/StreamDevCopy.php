<?php

namespace app\ai\home;

use think\Controller;
use think\Db;
use think\Exception;
use app\ai\model\AiMainModel;
use app\ai\model\AiMoldsModel;
use app\ai\model\AiApikeyModel;
use app\member\model\MemberModel;
use app\ai\library\WebSocketClient;

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
        $tongyinowtext  = "";
        $fuckbaidu      = "";
        $fuckali        = "";
        $fuckbard       = "";
        $fuckbardcount  = 1;
        $claudemsgid    = "msg_1";
        $timestamp      = time();
        $isstart        = true;
        $firsterror     = true;
        $proxyaddress   = AiMainModel::where('id', 1)->value('proxyaddress');
        $isstart        = true;
        $firsterror     = true;
        $post_data      = safeFilter(input('get.'));
        $userrndstr     = isset($post_data['userrndstr']) ? $post_data['userrndstr'] : '';
        $user           = MemberModel::where('rndstr', $userrndstr)->where('lock', 0)->find();
        if (empty($user)) {
            echo 'data: {"error":{"code":"invalid_user","message":"用户不存在或已被锁定"}}' . "\n\n";
            exit;
        }
        if (empty($user["lastquestion"])) {
            echo 'data: {"error":{"code":"repeated_query","message":""}}' . "\n\n";
            exit;
        }
        // 获取基础参数
        $uid            = $user["id"];
        $user_id        = isset($user["userid"]) ? (int)$user["userid"] : 1001;
        $quota          = isset($user["quota"]) ? (int)$user["quota"] : 10000;
        $conversationid = explode(",", $user["lastquestion"])[0];
        $postdata       = substr($user["lastquestion"], strlen($conversationid) + 1);

        $lastmodelid    = $user["lastmodelid"];
        // 获取模型信息
        $model = AiMoldsModel::where('id', $lastmodelid)->find();

        if (empty($model)) {
            echo 'data: {"error":{"code":"model_not_found","message":"模型不存在"}}' . "\n\n";
            exit;
        }
        $modelprice = $model['modelprice'];
        $modelvalue = $model['modelvalue'];
        $modeltype  = $model['modeltype'];

        $isimage    = isset($post_data['isimage']) ? $post_data['isimage'] : 0;
        $isstart    = true;         // 初始化变量
        if ($quota < $modelprice) {
            echo 'data: {"error":{"code":"out_of_money","message":""}}' . "\n\n";
            exit;
        }
        $responsedata   = "";
        $OPENAI_API_KEY = "";
        $api_key = AiApikeyModel::where('isvalid', 1)->where('keytype', $lastmodelid)->find();
        if (empty($api_key)) {
            echo 'data: "error":{"code":"key_not_found","message":"API密钥不存在","cccceeee":"aaadddccc"}' . "\n\n";
            exit;
        }
        $OPENAI_API_KEY = $api_key['apikey'];
        $apiaddress     = $api_key["apiaddress"];
        $apikeyid       = $api_key["id"];
        AiApikeyModel::where('id', $apikeyid)->update(['lasttime' => date('Y-m-d H:i:s')]);
        $iserror        = 0;
        if ($modeltype == "文心千帆") {
            $headers = [
                'Accept: application/json',
                'Content-Type: application/json'
            ];

            $apikeyarray = explode(",", $OPENAI_API_KEY);
            if (count($apikeyarray) == 2) {
                $accesstoken = $this->get_baidu_accesscode($OPENAI_API_KEY, $apikeyid);
            } else {
                $accesstoken = $apikeyarray[2];
            }
            $postdatajson = json_decode($postdata, true);

            if ($modelvalue == "fuyu_8b") { //识图模型
                $imageurl = $postdatajson['imageurl'];
                $img = file_get_contents($imageurl);
                unset($postdatajson['imageurl']);
                $postdatajson["image"] = base64_encode($img);
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
        } else if ($modeltype == "清华智谱") { //新版本清华智谱接口标准已基本兼容OpenAI规范，但temperature值不能为0
            $token = $this->get_qinghuazhipu_token($OPENAI_API_KEY, 3600);
            $headers = [
                'Accept: text/event-stream',
                'Content-Type: application/json',
                'Authorization: ' . $token
            ];

            $postdatajson = json_decode($postdata, true);
            unset($postdatajson['max_tokens']);
            $postdatajson['temperature'] = 0.01;
            $postdata = json_encode($postdatajson);
        } else if ($modeltype == "通义千问") {
            $headers = [
                'Accept: text/event-stream',
                'Content-Type: application/json',
                'X-DashScope-SSE: enable',
                'Authorization: Bearer ' . $OPENAI_API_KEY
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
        } else if ($modeltype == "腾讯混元") { //需要先处理postdata后设置header，因为postdata参与了header中的密钥生成
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
            $authorization = $this->get_tencenthunyuan_token($OPENAI_API_KEY, $timestamp, $postdata);
            $headers = [
                "Authorization: " . $authorization,
                "Content-Type: application/json; charset=utf-8",
                "Host: hunyuan.tencentcloudapi.com",
                "X-TC-Action: ChatCompletions",
                "X-TC-Timestamp: " . $timestamp,
                "X-TC-Version: 2023-09-01",
                "X-TC-Region: ap-guangzhou"
            ];
        } else if ($modeltype == "MiniMax") {
            $headers = [
                'Accept: text/event-stream',
                'Content-Type: application/json',
                'Authorization: Bearer ' . $OPENAI_API_KEY
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
        } else if ($modeltype == "bard") {
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
                $img            = file_get_contents($imageurl);
                unset($postdatajson['imageurl']);
                $postdatajson["contents"][0]["parts"][1]["inline_data"]["data"] = base64_encode($img);
                $postdata       = json_encode($postdatajson);
            }
        } else if ($modeltype == "claude") {
            $headers = [
                'Accept: text/event-stream',
                'Content-Type: application/json',
                'anthropic-version: 2023-06-01',
                'x-api-key: ' . $OPENAI_API_KEY
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
                $img = file_get_contents($imageurl);
                unset($postdatajson["messages"][0]['imageurl']);
                $postdatajson["messages"][0]["content"][1]["source"]["data"] = base64_encode($img);
                $postdata = json_encode($postdatajson);
            }
        } else if ($modelvalue == "gpt-3.5-turbo-instruct") {
            $headers = [
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Bearer ' . $OPENAI_API_KEY
            ];
            $postdatajson = json_decode($postdata, true);
            $jsonmessages = $postdatajson["messages"];
            $postdatajson['prompt'] = end($jsonmessages)["content"];
            $postdatajson['max_tokens'] = 4000;
            unset($postdatajson['messages']);
            $postdata = json_encode($postdatajson);
        } else if ($modeltype == "azure") {
            $headers = [
                'Accept: application/json',
                'Content-Type: application/json',
                'api-key: ' . $OPENAI_API_KEY
            ];
        } else {
            $headers = [
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Bearer ' . $OPENAI_API_KEY
            ];
        }

        $callback = function ($ch, $data) {
            global $OPENAI_API_KEY, $apikeyid, $tongyinowtext, $modeltype, $modelvalue, $responsedata, $iserror, $isstart, $errcode, $errmsg, $accesstoken, $firsterror, $fuckbaidu, $fuckali, $fuckbard, $fuckbardcount, $claudemsgid;
            global $apiaddress, $headers, $postdata, $callback, $proxyaddress, $isimage, $user_id;
            $complete = json_decode($data);
            if (isset($complete->error)) {
                $errcode = $complete->error->code;
                $errmsg = $complete->error->message;
                if (strpos($errmsg, "Rate limit reached") === 0) { //访问频率超限错误返回的code为空，特殊处理一下
                    $errcode = "rate_limit_reached";
                }
                if (strpos($errmsg, "Your access was terminated") === 0) { //违规使用，被封禁，特殊处理一下
                    $errcode = "access_terminated";
                }
                if (strpos($errmsg, "You didn't provide an API key") === 0) { //未提供API-KEY
                    $errcode = "no_api_key";
                }
                if (strpos($errmsg, "You exceeded your current quota") === 0) { //API-KEY余额不足
                    $errcode = "insufficient_quota";
                }
                if (strpos($errmsg, "That model is currently overloaded") === 0) { //OpenAI模型超负荷
                    $errcode = "model_overloaded";
                }
                if (strpos($errmsg, "The server had an error") === 0) { //OpenAI服务器超负荷
                    $errcode = "server_overloaded";
                }
                if (strpos($errmsg, "Your account is not active") === 0) { //账户被封禁
                    $errcode = "account_deactivated";
                }
                $responsedata = $data;
                echo 'data: {"error":{"code":"' . $errcode . '","message":"' . $errmsg . '"}}' . "\n\n";
                $iserror = 1;
            } else if (isset($complete->error_code)) { //百度问心千帆错误提示
                $errcode = $complete->error_code;
                $errmsg = $complete->error_msg;
                if ((($errcode == 110) || ($errcode == 111)) && ($firsterror)) {
                    $accesstoken = $this->get_baidu_accesscode($OPENAI_API_KEY, $apikeyid);
                    $firsterror = false;
                    $this->gogo($modeltype, $modelvalue, $apiaddress, $headers, $postdata, $callback, $proxyaddress, $accesstoken, $isimage, $responsedata, $fuckali, $user_id, $OPENAI_API_KEY);
                    return;
                } else {
                    $responsedata = $data;
                    echo 'data: {"error":{"code":"' . $errcode . '","message":"' . $errmsg . '"}}' . "\n\n";
                    $iserror = 1;
                }
            } else if ((isset($complete->Response)) && (isset($complete->Response->Error)) && (isset($complete->Response->Error->Code))) { //腾讯混元错误提示
                $errcode = $complete->Response->Error->Code;
                $errmsg = $complete->Response->Error->Message;
                $responsedata = $data;
                echo 'data: {"error":{"code":"' . $errcode . '","message":"' . $errmsg . '"}}' . "\n\n";
                $iserror = 1;
            } else if (substr($data, 0, 3) === "id:") {     //处理通义千问的流式数据
                $msg = preg_replace('/id:[\s\S]*?data:/', '', $data);
                $contentarr = json_decode(trim($msg));
                if (isset($contentarr->code)) { //遇到错误
                    $errcode = $contentarr->code;
                    $errmsg = $contentarr->message;
                    echo 'data: {"error":{"code":"' . $errcode . '","message":"' . $errmsg . '"}}' . "\n\n";
                    $iserror = 1;
                } else {
                    if ((isset($contentarr->output)) && (isset($contentarr->output->text))) { //文本模型
                        preg_match('/id:(\d+)/', $data, $matches);
                        $senddata = '{"id":"' . $matches[1] . '","object":"chat.completion.chunk","created":' . time() . ',"choices":[{"index":0,"delta":{"content":""},"finish_reason":null}]}';
                        $senddata = json_decode($senddata);
                        $senddata->choices[0]->delta->content = str_replace($tongyinowtext, "", $contentarr->output->text);
                        $tongyinowtext = $contentarr->output->text;
                        $senddata = 'data: ' . json_encode($senddata) . "\n\n";
                        $responsedata .= $senddata;
                        echo $senddata;
                    }
                    if ((isset($contentarr->output)) && (isset($contentarr->output->choices)) && (isset($contentarr->output->choices[0]->message)) && (isset($contentarr->output->choices[0]->message->content)) && (isset($contentarr->output->choices[0]->message->content[0]->text))) { //识图模型
                        preg_match('/id:(\d+)/', $data, $matches);
                        $senddata = '{"id":"' . $matches[1] . '","object":"chat.completion.chunk","created":' . time() . ',"choices":[{"index":0,"delta":{"content":""},"finish_reason":null}]}';
                        $fuckali = 'data: {"id":"' . ($matches[1] + 1) . '","object":"chat.completion.chunk","created":' . time() . ',"choices":[{"index":0,"delta":{},"finish_reason":"stop"}]}' . "\n\n" . 'data: [DONE]' . "\n\n"; //阿里识图模型返回的流式数据没有结束标识，呵呵
                        $senddata = json_decode($senddata);
                        $senddata->choices[0]->delta->content = str_replace($tongyinowtext, "", $contentarr->output->choices[0]->message->content[0]->text); //只显示增量
                        $tongyinowtext = $contentarr->output->choices[0]->message->content[0]->text; //通义千问每次流式返回的信息都包含之前已发的数据
                        $senddata = 'data: ' . json_encode($senddata) . "\n\n";
                        $responsedata .= $senddata;
                        echo $senddata;
                    }
                    if ((isset($contentarr->output)) && (isset($contentarr->output->finish_reason)) && ($contentarr->output->finish_reason === "stop")) {
                        preg_match('/id:(\d+)/', $data, $matches);
                        $senddata = 'data: {"id":"' . ($matches[1] + 1) . '","object":"chat.completion.chunk","created":' . time() . ',"choices":[{"index":0,"delta":{},"finish_reason":"stop"}]}' . "\n\n" . 'data: [DONE]' . "\n\n";
                        $responsedata .= $senddata;
                        echo $senddata;
                    }
                    if ($isstart) {
                        //$conn->update("user", ["quota[-]" => $modelprice, "credits[+]" => $modelprice, "questioncount[+]" => 1], ["id" => $uid]);
                        $isstart = false;
                    }
                }
            } else if (substr($data, 0, 6) === "event:") { //处理Claude的流式数据
                if (substr($data, 0, 12) === "event: error") { //遇到错误
                    $complete = json_decode("{" . explode("data: {", $data)[1]);
                    $errcode = $complete->error->type;
                    $errmsg = $complete->error->message;
                    $responsedata = $data;
                    echo 'data: {"error":{"code":"' . $errcode . '","message":"' . $errmsg . '"}}' . "\n\n";
                    $iserror = 1;
                } else {
                    if ($isstart) {
                        //$conn->update("user", ["quota[-]" => $modelprice, "credits[+]" => $modelprice, "questioncount[+]" => 1], ["id" => $uid]);
                        $isstart = false;
                    }
                    foreach (explode("\n\nevent: ", "\n\n" . $data) as $msg) {
                        preg_match_all('/data:(.*)/', $msg, $matches);
                        $msg = implode('', $matches[1]);
                        $msgjson = json_decode($msg, true);
                        if ($msgjson["type"] == "message_start") {
                            $claudemsgid = $msgjson["message"]["id"];
                        }
                        if ($msgjson["type"] == "content_block_delta") {
                            $senddata = '{"id":"' . $claudemsgid . '","object":"chat.completion.chunk","created":' . time() . ',"choices":[{"index":0,"delta":{"content":""},"finish_reason":null}]}';
                            $senddata = json_decode($senddata);
                            $senddata->choices[0]->delta->content = $msgjson["delta"]["text"];
                            $senddata = 'data: ' . json_encode($senddata) . "\n\n";
                            $responsedata .= $senddata;
                            echo $senddata;
                        }
                        if ($msgjson["type"] == "content_block_stop") {
                            $senddata = 'data: {"id":"' . $claudemsgid . '","object":"chat.completion.chunk","created":' . time() . ',"choices":[{"index":0,"delta":{},"finish_reason":"stop"}]}' . "\n\n" . 'data: [DONE]' . "\n\n";
                            $responsedata .= $senddata;
                            echo $senddata;
                        }
                    }
                }
            } else if ($modeltype == "文心千帆") {
                if ($isstart) {
                    //$conn->update("user", ["quota[-]" => $modelprice, "credits[+]" => $modelprice, "questioncount[+]" => 1], ["id" => $uid]);
                    $isstart = false;
                }
                if ($modelvalue == "fuyu_8b") {
                    $contentarr = json_decode(trim($data));
                    if (isset($contentarr->result)) {
                        $senddata = '{"id":"' . $contentarr->id . '","object":"chat.completion.chunk","created":' . $contentarr->created . ',"choices":[{"index":0,"delta":{"content":""},"finish_reason":null}]}';
                        $senddata = json_decode($senddata);
                        $senddata->choices[0]->delta->content = $contentarr->result;
                        $senddata = 'data: ' . json_encode($senddata) . "\n\n";
                        $responsedata .= $senddata;
                        echo $senddata;
                        $senddata = 'data: {"id":"' . $contentarr->id . '","object":"chat.completion.chunk","created":' . $contentarr->created . ',"choices":[{"index":0,"delta":{},"finish_reason":"stop"}]}' . "\n\n" . 'data: [DONE]' . "\n\n";
                        $responsedata .= $senddata;
                        echo $senddata;
                    }
                } else {
                    foreach (explode("\n\ndata: ", "\n\n" . $fuckbaidu . $data) as $msg) {
                        if (strlen($msg)) {
                            //error_log(date('Y-m-d H:i:s') . " Reponse: {$msg}\n", 3, "baidu.log");
                            $contentarr = json_decode(trim($msg));
                            if (is_null($contentarr)) {
                                $fuckbaidu = "data: " . $msg; //百度流式返回的数据可能不是完整的json，呵呵
                            } else {
                                $fuckbaidu = "";
                                if (isset($contentarr->result)) {
                                    $senddata = '{"id":"' . $contentarr->id . '","object":"chat.completion.chunk","created":' . $contentarr->created . ',"choices":[{"index":0,"delta":{"content":""},"finish_reason":null}]}';
                                    $senddata = json_decode($senddata);
                                    $senddata->choices[0]->delta->content = $contentarr->result;
                                    $senddata = 'data: ' . json_encode($senddata) . "\n\n";
                                    $responsedata .= $senddata;
                                    echo $senddata;
                                }
                                if ((isset($contentarr->is_end)) && ($contentarr->is_end === true)) {
                                    $senddata = 'data: {"id":"' . $contentarr->id . '","object":"chat.completion.chunk","created":' . $contentarr->created . ',"choices":[{"index":0,"delta":{},"finish_reason":"stop"}]}' . "\n\n" . 'data: [DONE]' . "\n\n";
                                    $responsedata .= $senddata;
                                    echo $senddata;
                                }
                            }
                        }
                    }
                }
            } else if ($modeltype == "腾讯混元") {
                if ($isstart) {
                    //$conn->update("user", ["quota[-]" => $modelprice, "credits[+]" => $modelprice, "questioncount[+]" => 1], ["id" => $uid]);
                    $isstart = false;
                }
                foreach (explode("\n\ndata: ", "\n\n" . $data) as $msg) {
                    if (strlen(trim($msg))) {
                        $contentarr = json_decode(trim($msg));
                        if (isset($contentarr->Choices)) {
                            $senddata = '{"id":"' . $contentarr->Id . '","object":"chat.completion.chunk","created":' . $contentarr->Created . ',"choices":[{"index":0,"delta":{"content":""},"finish_reason":null}]}';
                            $senddata = json_decode($senddata);
                            $senddata->choices[0]->delta->content = $contentarr->Choices[0]->Delta->Content;
                            $senddata = 'data: ' . json_encode($senddata) . "\n\n";
                            $responsedata .= $senddata;
                            echo $senddata;
                        }
                        if ((isset($contentarr->Choices[0]->FinishReason)) && ($contentarr->Choices[0]->FinishReason == "stop")) {
                            $senddata = 'data: {"id":"' . $contentarr->Id . '","object":"chat.completion.chunk","created":' . $contentarr->Created . ',"choices":[{"index":0,"delta":{},"finish_reason":"stop"}]}' . "\n\n" . 'data: [DONE]' . "\n\n";
                            $responsedata .= $senddata;
                            echo $senddata;
                        }
                    }
                }
            } else if ($modeltype == "MiniMax") {
                if ($isstart) {
                    //$conn->update("user", ["quota[-]" => $modelprice, "credits[+]" => $modelprice, "questioncount[+]" => 1], ["id" => $uid]);
                    $isstart = false;
                }
                foreach (explode("\n\ndata: ", "\n\n" . $data) as $msg) {
                    if (strlen(trim($msg))) {
                        $contentarr = json_decode(trim($msg));
                        if ((isset($contentarr->choices[0]->finish_reason))) {
                            $senddata = 'data: {"id":"MiniMax","object":"chat.completion.chunk","created":' . $contentarr->created . ',"choices":[{"index":0,"delta":{},"finish_reason":"stop"}]}' . "\n\n" . 'data: [DONE]' . "\n\n";
                            $responsedata .= $senddata;
                            echo $senddata;
                        } else if ((isset($contentarr->choices)) && (!empty($contentarr->choices[0]->messages[0]->text))) {
                            $senddata = '{"id":"MiniMax","object":"chat.completion.chunk","created":' . $contentarr->created . ',"choices":[{"index":0,"delta":{"content":""},"finish_reason":null}]}';
                            $senddata = json_decode($senddata);
                            $senddata->choices[0]->delta->content = $contentarr->choices[0]->messages[0]->text;
                            $senddata = 'data: ' . json_encode($senddata) . "\n\n";
                            $responsedata .= $senddata;
                            echo $senddata;
                        }
                    }
                }
            } else if ($modeltype == "无问芯穹") {
                if ($isstart) {
                    //$conn->update("user", ["quota[-]" => $modelprice, "credits[+]" => $modelprice, "questioncount[+]" => 1], ["id" => $uid]);
                    $isstart = false;
                }
                foreach (explode("\n\ndata: ", "\n\n" . $fuckbaidu . $data) as $msg) {
                    if (strlen(trim($msg))) {
                        $contentarr = json_decode(trim($msg));
                        if (is_null($contentarr)) {
                            $fuckbaidu = "data: " . $msg; //豆包流式返回的数据可能不是完整的json
                        } else {
                            $fuckbaidu = "";
                            $senddata = "data: " . $msg . "\n\n";
                            $responsedata .= $senddata;
                            echo $senddata;
                            if ((isset($contentarr->choices[0]->finish_reason))) {
                                $senddata = 'data: [DONE]' . "\n\n";
                                $responsedata .= $senddata;
                                echo $senddata;
                            }
                        }
                    }
                }
            } else if ($modeltype == "bard") {
                if ($data == "[]") {
                    $this->gogo($modeltype, $modelvalue, $apiaddress, $headers, $postdata, $callback, $proxyaddress, $accesstoken, $isimage, $responsedata, $fuckali, $user_id, $OPENAI_API_KEY);
                    return;
                } else {
                    $errorcheck = json_decode($data);
                    if (is_array($errorcheck) && isset($errorcheck[0]) && (isset($errorcheck[0]->error))) {
                        $errcode = $errorcheck[0]->error->code;
                        $errmsg = $errorcheck[0]->error->message;
                        $responsedata = $data;
                        echo 'data: {"error":{"code":"' . $errcode . '","message":"' . $errmsg . '"}}' . "\n\n";
                        $iserror = 1;
                    } else if (isset($errorcheck->error)) {
                        $errcode = $errorcheck->error->code;
                        $errmsg = $errorcheck->error->message;
                        $responsedata = $data;
                        echo 'data: {"error":{"code":"' . $errcode . '","message":"' . $errmsg . '"}}' . "\n\n";
                        $iserror = 1;
                    } else {
                        if ($isstart) {
                            //$conn->update("user", ["quota[-]" => $modelprice, "credits[+]" => $modelprice, "questioncount[+]" => 1], ["id" => $uid]);
                            $isstart = false;
                        }
                        $fuckbard .= $data;
                        $candidatesarr = explode("{\n" . '  "candidates": [', $fuckbard);
                        $candidatescount = count($candidatesarr);
                        for ($i = $fuckbardcount; $i < $candidatescount - 1; $i++) {
                            $contentarr = json_decode('{"candidates":[' . substr(trim($candidatesarr[$i]), 0, -1)); //去掉"逗号"
                            $senddata = '{"id":"bard","object":"chat.completion.chunk","created":' . time() . ',"choices":[{"index":0,"delta":{"content":""},"finish_reason":null}]}';
                            $senddata = json_decode($senddata);
                            $senddata->choices[0]->delta->content = $contentarr->candidates[0]->content->parts[0]->text;
                            $senddata = 'data: ' . json_encode($senddata) . "\n\n";
                            $responsedata .= $senddata;
                            echo $senddata;
                        }
                        $fuckbardcount = $candidatescount - 1;
                        $contentarr = json_decode(trim($fuckbard));
                        if (!is_null($contentarr)) {
                            $contentarr = json_decode('{"candidates":[' . substr($candidatesarr[$fuckbardcount], 0, -1));
                            $senddata = '{"id":"bard","object":"chat.completion.chunk","created":' . time() . ',"choices":[{"index":0,"delta":{"content":""},"finish_reason":null}]}';
                            $senddata = json_decode($senddata);
                            $senddata->choices[0]->delta->content = $contentarr->candidates[0]->content->parts[0]->text;
                            $senddata = 'data: ' . json_encode($senddata) . "\n\n";
                            $responsedata .= $senddata;
                            echo $senddata;
                            $senddata = 'data: {"id":"bard","object":"chat.completion.chunk","created":' . time() . ',"choices":[{"index":0,"delta":{},"finish_reason":"stop"}]}' . "\n\n" . 'data: [DONE]' . "\n\n";
                            $responsedata .= $senddata;
                            echo $senddata;
                        }
                    }
                }
            } else if ($modelvalue == "gpt-3.5-turbo-instruct") { //OpenAI的gpt-3.5-turbo-instruct模型不是对话模型，因此返回的消息格式有所差异
                if ($isstart) {
                    //$conn->update("user", ["quota[-]" => $modelprice, "credits[+]" => $modelprice, "questioncount[+]" => 1], ["id" => $uid]);
                    $isstart = false;
                }
                foreach (explode("\n\ndata: ", "\n\n" . $fuckbaidu . $data) as $msg) {
                    if (strlen(trim($msg))) {
                        $contentarr = json_decode(trim($msg));
                        if (is_null($contentarr)) {
                            $fuckbaidu = "data: " . $msg;
                        } else {
                            $fuckbaidu = "";
                            if (isset($contentarr->choices)) {
                                $senddata = '{"id":"' . $contentarr->id . '","object":"chat.completion.chunk","created":' . $contentarr->created . ',"choices":[{"index":0,"delta":{"content":""},"finish_reason":null}]}';
                                $senddata = json_decode($senddata);
                                $senddata->choices[0]->delta->content = $contentarr->choices[0]->text;
                                $senddata = 'data: ' . json_encode($senddata) . "\n\n";
                                $responsedata .= $senddata;
                                echo $senddata;
                            }
                            if ((isset($contentarr->choices[0]->finish_reason)) && ($contentarr->choices[0]->finish_reason == "stop")) {
                                $senddata = 'data: {"id":"' . $contentarr->id . '","object":"chat.completion.chunk","created":' . $contentarr->created . ',"choices":[{"index":0,"delta":{},"finish_reason":"stop"}]}' . "\n\n" . 'data: [DONE]' . "\n\n";
                                $responsedata .= $senddata;
                                echo $senddata;
                            }
                            if ((isset($contentarr->choices[0]->finish_reason)) && ($contentarr->choices[0]->finish_reason == "length")) {
                                $senddata = '{"id":"' . $contentarr->id . '","object":"chat.completion.chunk","created":' . $contentarr->created . ',"choices":[{"index":0,"delta":{"content":""},"finish_reason":null}]}';
                                $senddata = json_decode($senddata);
                                $senddata->choices[0]->delta->content = "...（已达到该模型最大tokens限制）";
                                $senddata = 'data: ' . json_encode($senddata) . "\n\n";
                                $responsedata .= $senddata;
                                echo $senddata;
                                $senddata = 'data: {"id":"' . $contentarr->id . '","object":"chat.completion.chunk","created":' . $contentarr->created . ',"choices":[{"index":0,"delta":{},"finish_reason":"stop"}]}' . "\n\n" . 'data: [DONE]' . "\n\n";
                                $responsedata .= $senddata;
                                echo $senddata;
                            }
                        }
                    }
                }
            } else if (substr($data, 0, 5) === "data:") {
                if ($isstart) {
                    //$conn->update("user", ["quota[-]" => $modelprice, "credits[+]" => $modelprice, "questioncount[+]" => 1], ["id" => $uid]);
                    $isstart = false;
                }
                $responsedata .= $data;
                echo $data;
            } else {
                if ($isstart) {
                    $responsedata = $data;
                    echo 'data: {"error":{"code":"unknown","message":"' . rawurlencode($data) . '"}}' . "\n\n";
                    $isstart = false;
                    $iserror = 1;
                } else {
                    $responsedata .= $data;
                    echo $data;
                }
            }
            flush();
            return strlen($data);
        };

        if ($modeltype == "讯飞星火") {
            $apikeyarray = explode(",", $OPENAI_API_KEY);
            $authUrl = $this->assembleAuthUrl($apiaddress, $apikeyarray[1], $apikeyarray[2]);
            try {
                $ws = new WebSocketClient($authUrl);
                if (strpos($apiaddress, "v1.1") !== false) {
                    $domain = 'general';
                } elseif (strpos($apiaddress, "v2.1") !== false) {
                    $domain = 'generalv2';
                } elseif (strpos($apiaddress, "v3.1") !== false) {
                    $domain = 'generalv3';
                } elseif (strpos($apiaddress, "v3.5") !== false) {
                    $domain = 'generalv3.5';
                }
                $postdatajson = json_decode($postdata, true);
                if (!$isimage) { //纯文本模型
                    $jsonmessages = $postdatajson["messages"];
                    for ($i = 0; $i < count($jsonmessages); ++$i) {
                        if ($jsonmessages[$i]["role"] !== "system") {
                            $newjson[] = $jsonmessages[$i];
                        }
                    }
                    $requestData = '{"header": {"app_id": "' . $apikeyarray[0] . '"},"parameter": {"chat": {"domain": "' . $domain . '"}},"payload": {"message": {"text": ' . json_encode($newjson) . '}}}';
                } else { //识图模型
                    $postdatajson["header"]["app_id"] = $apikeyarray[0];
                    $imageurl = $postdatajson["imageurl"];
                    $img = file_get_contents($imageurl);
                    unset($postdatajson["imageurl"]);
                    $postdatajson["payload"]["message"]["text"][0]["content"] = base64_encode($img);
                    $requestData = json_encode($postdatajson);
                }
                $ws->ping();
                $ws->send($requestData);
                while ($frame = $ws->recv()) {
                    $json = json_decode($frame->playload);
                    if ($json->header->code !== 0) {
                        $iserror = 1;
                        $responsedata = $frame->playload;
                        $errcode = 'unknown';
                        $errmsg = $json->header->message;
                        $fullerrmsg = json_decode('{"error":{"code":"","message":""}}');
                        $fullerrmsg->error->code = $errcode;
                        $fullerrmsg->error->message = $errmsg;
                        $fullerrmsg = json_encode($fullerrmsg);
                        echo "data: " . $fullerrmsg . "\n\n";
                        flush();
                        break;
                    } else {
                        if ($isstart) {
                            //$conn->update("user", ["quota[-]" => $modelprice, "credits[+]" => $modelprice, "questioncount[+]" => 1], ["id" => $uid]);
                            $isstart = false;
                        }
                        $senddata = '{"id":"' . $json->header->sid . '","object":"chat.completion.chunk","created":' . time() . ',"choices":[{"index":0,"delta":{"content":""},"finish_reason":null}]}';
                        $senddata = json_decode($senddata);
                        $senddata->choices[0]->delta->content = $json->payload->choices->text[0]->content;
                        $senddata = 'data: ' . json_encode($senddata) . "\n\n";
                        $responsedata .= $senddata;
                        echo $senddata;
                        flush();
                        if (isset($json->payload->usage)) {
                            $senddata = 'data: {"id":"' . $json->header->sid . '","object":"chat.completion.chunk","created":' . time() . ',"choices":[{"index":0,"delta":{},"finish_reason":"stop"}]}' . "\n\n" . 'data: [DONE]' . "\n\n";
                            $responsedata .= $senddata;
                            echo $senddata;
                            flush();
                            break;
                        }
                    }
                }
                $ws->close();
            } catch (Exception $e) {
                echo 'data: {"error":{"code":"unknown","message":"' . rawurlencode($e->getMessage()) . '"}}' . "\n\n";
                $isstart = false;
                $iserror = 1;
            }
        } else {
            $accesstoken = '';
            $this->gogo($modeltype, $modelvalue, $apiaddress, $headers, $postdata, $callback, $proxyaddress, $accesstoken, $isimage, $responsedata, $fuckali, $user_id, $OPENAI_API_KEY);
        }

        $questionarr = json_decode($postdata, true);
        if ($modeltype == "midjourney") {
            $goodquestion = '![IMG](' . $questionarr['imageurl'] . ')';
        } else if ($modeltype == "通义千问") {
            if ($isimage) {
                $goodquestion = '![IMG](' . $questionarr['input']['messages'][0]['content'][0]['image'] . ')' . ($questionarr['input']['messages'][0]['content'][1]['text']);
            } else {
                $goodquestion = ($questionarr['input']['prompt']);
            }
        } else if ($modeltype == "腾讯混元") {
            if ($isimage) {
                $goodquestion = '![IMG](' . $questionarr['Messages'][0]['Contents'][1]['ImageUrl']['Url'] . ')' . ($questionarr['Messages'][0]['Contents'][0]['Text']);
            } else {
                $goodquestion = (end($questionarr['Messages'])['Content']);
            }
        } else if (($modeltype == "讯飞星火") && ($isimage)) {
            $goodquestion = '![IMG](' . $questionarr['imageurl'] . ')' . ($questionarr['payload']['message']['text'][1]['content']);
        } else if ($modeltype == "MiniMax") {
            $goodquestion = (end($questionarr['messages'])['text']);
        } else if ($modeltype == "bard") {
            if ($isimage) {
                $goodquestion = '![IMG](' . $imageurl . ')' . (end($questionarr['contents'])['parts'][0]['text']);
            } else {
                $goodquestion = (end($questionarr['contents'])['parts'][0]['text']);
            }
        } else if ($modelvalue == "CodeLlama-7b-Instruct") {
            $goodquestion = ($questionarr['prompt']);
        } else if ($modeltype == "claude") {
            if ($isimage) {
                $goodquestion = '![IMG](' . $imageurl . ')' . ($questionarr['messages'][0]['content'][0]['text']);
            } else {
                $goodquestion = (end($questionarr['messages'])['content']);
            }
        } else if ($modelvalue == "gpt-3.5-turbo-instruct") {
            $goodquestion = ($questionarr['prompt']);
        } else { //OpenAI标准接口规范（兼容清华智谱）
            if ($isimage) {
                $goodquestion = '![IMG](' . $questionarr['messages'][0]['content'][1]['image_url']['url'] . ')' . ($questionarr['messages'][0]['content'][0]['text']);
            } else {
                $goodquestion = (end($questionarr['messages'])['content']);
            }
        }
        if (!$iserror) {
            $answer = "";
            if (substr(trim($responsedata), -6) == "[DONE]") {
                $responsedata = substr(trim($responsedata), 0, -6) . "{";
            }

            $responsedata = str_replace("\r", "\n", $responsedata);

            $responsearr = explode("\n\ndata: {", "\n\n" . $responsedata);

            foreach ($responsearr as $msg) {
                $contentarr = json_decode("{" . trim($msg), true);
                if (isset($contentarr['choices'][0]['delta']['content'])) {
                    $answer .= $contentarr['choices'][0]['delta']['content'];
                }
            }
            $goodanswer = trim($answer);
            //$conn->insert('chathistory', ['title' => substr(parseMarkdownImage($goodquestion), 0, 100), 'question' => $goodquestion, 'answer' => $goodanswer, 'conversationid' => $conversationid, 'modelid' => $lastmodelid, 'realtime' => date('Y-m-d H:i:s'), 'userid' => $uid, 'iserror' => $iserror]);
            Db::name('ai_chathistory')->insert(['title' => substr($this->parseMarkdownImage($goodquestion), 0, 100), 'question' => $goodquestion, 'answer' => $goodanswer, 'conversationid' => $conversationid, 'modelid' => $lastmodelid, 'realtime' => date('Y-m-d H:i:s'), 'userid' => $uid, 'iserror' => $iserror]);
        } else {
            if (($errcode == "access_terminated") || ($errcode == "insufficient_quota") || ($errcode == "invalid_api_key") || ($errcode == "account_deactivated")) {
                //$conn->update('apikey', ['isvalid' => 0, 'lasttime' => date('Y-m-d H:i:s'), 'errmsg' => $errcode . "|" . $errmsg], ['id' => $apikeyid]);
                AiApikeyModel::where('id', $apikeyid)->update(['isvalid' => 0, 'lasttime' => date('Y-m-d H:i:s'), 'errmsg' => $errcode . "|" . $errmsg]);
            }
            $goodanswer = trim($responsedata);
            //$conn->insert('errorlog', ['question' => $goodquestion, 'errmsg' => $goodanswer, 'conversationid' => $conversationid, 'modelid' => $lastmodelid, 'realtime' => date('Y-m-d H:i:s'), 'userid' => $uid, 'apikey' => $OPENAI_API_KEY]);
            Db::name('ai_errorlog')->insert(['question' => $goodquestion, 'errmsg' => $goodanswer, 'conversationid' => $conversationid, 'modelid' => $lastmodelid, 'realtime' => date('Y-m-d H:i:s'), 'userid' => $uid, 'apikey' => $OPENAI_API_KEY]);
        }
    }


    public function get_headers($modeltype, $modelvalue, $apiaddress, $headers, $postdata, $proxyaddress, $accesstoken, $isimage, $responsedata, $fuckali, $user_id, $OPENAI_API_KEY)
    { 

        
    }

    public function parseMarkdownImage($str)
    {
        if (substr($str, 0, 6) === '![IMG]') {
            $regex = '/!\[IMG]\((.*?)\)/';
            return preg_replace($regex, '[图片]', $str);
        } else {
            return $str;
        }
    }

    //百度
    public function get_baidu_accesscode($OPENAI_API_KEY, $apikeyid)
    {
        $apikey   = explode(",", $OPENAI_API_KEY);
        $newtoken = json_decode(file_get_contents("https://aip.baidubce.com/oauth/2.0/token?grant_type=client_credentials&client_id=" . $apikey[0] . "&client_secret=" . $apikey[1]))->access_token;
        AiApikeyModel::where('id', $apikeyid)->update(['apikey' => $apikey[0] . "," . $apikey[1] . "," . $newtoken]);
        return $newtoken;
    }

    public function jwt_encode($payload, $secret, $algorithm, $headers)
    {
        $header     = base64_encode(json_encode($headers));
        $payload    = base64_encode(json_encode($payload));
        $signature  = hash_hmac($algorithm, "$header.$payload", $secret, true);
        $signature  = base64_encode($signature);
        return "$header.$payload.$signature";
    }

    //清华智谱
    public function get_qinghuazhipu_token($apikey, $exp_seconds)
    {
        try {
            list($id, $secret) = explode(".", $apikey);
        } catch (Exception $e) {
            throw new Exception("invalid apikey", $e->getMessage());
        }
        $payload = array(
            "api_key" => $id,
            "exp" => round(microtime(true) * 1000) + $exp_seconds * 1000,
            "timestamp" => round(microtime(true) * 1000),
        );
        return $this->jwt_encode(
            $payload,
            $secret,
            "sha256",
            array("alg" => "HS256", "sign_type" => "SIGN")
        );
    }

    //腾讯混元
    public function get_tencenthunyuan_token($OPENAI_API_KEY, $timestamp, $postdata)
    {
        $apikey             = explode(",", $OPENAI_API_KEY);
        $secretId           = $apikey[0];
        $secretKey          = $apikey[1];
        $algorithm          = "TC3-HMAC-SHA256";
        $canonicalHeaders   = implode("\n", ["content-type:application/json; charset=utf-8", "host:hunyuan.tencentcloudapi.com", "x-tc-action:chatcompletions", ""]);
        $signedHeaders      = implode(";", ["content-type", "host", "x-tc-action",]);
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

    public function gogo($modeltype, $modelvalue, $apiaddress, $headers, $postdata, $callback, $proxyaddress, $accesstoken, $isimage, $responsedata, $fuckali, $user_id, $OPENAI_API_KEY)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        if ($modeltype == "midjourney") {
            $httpprotocol = "http";
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
                $httpprotocol = "https";
            }
            curl_setopt($ch, CURLOPT_URL, $httpprotocol . '://' . $_SERVER['HTTP_HOST'] . '/plugins/mj/getmjdescribe.php?user=' . $user_id);
        } else if ($modeltype == "文心千帆") {
            curl_setopt($ch, CURLOPT_URL, $apiaddress . '?access_token=' . $accesstoken);
        } else if ($modeltype == "清华智谱") {
            curl_setopt($ch, CURLOPT_URL, $apiaddress . '/api/paas/v4/chat/completions');
        } else if ($modeltype == "通义千问") {
            if (!$isimage) {
                curl_setopt($ch, CURLOPT_URL, $apiaddress . '/api/v1/services/aigc/text-generation/generation');
            } else {
                curl_setopt($ch, CURLOPT_URL, $apiaddress . '/api/v1/services/aigc/multimodal-generation/generation');
            }
        } else if ($modeltype == "bard") {
            if (!$isimage) {
                curl_setopt($ch, CURLOPT_URL, $apiaddress . '/v1beta/models/' . $modelvalue . ':streamGenerateContent?key=' . $OPENAI_API_KEY);
            } else {
                curl_setopt($ch, CURLOPT_URL, $apiaddress . '/v1beta/models/' . $modelvalue . ':generateContent?key=' . $OPENAI_API_KEY);
            }
        } else if ($modeltype == "claude") {
            curl_setopt($ch, CURLOPT_URL, $apiaddress . '/v1/messages');
        } else if (($modeltype == "azure") || ($modeltype == "MiniMax") || ($modeltype == "无问芯穹") || ($modeltype == "腾讯混元") || ($modeltype == "火山方舟") || ($modeltype == "DeepSeek")) {
            curl_setopt($ch, CURLOPT_URL, $apiaddress);
        } else if ($modelvalue == "gpt-3.5-turbo-instruct") {
            curl_setopt($ch, CURLOPT_URL, $apiaddress . '/v1/completions');
        } else {
            curl_setopt($ch, CURLOPT_URL, $apiaddress . '/v1/chat/completions');
            if (!empty($proxyaddress)) {
                curl_setopt($ch, CURLOPT_PROXY, $proxyaddress);
            }
        }
        //error_log(date('Y-m-d H:i:s') . " Prompt: {$postdata}\n", 3, "stream.log");
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
}
