<?php

namespace app\ai\home;

use think\Controller;
use think\Db;
use think\Exception;
use think\facade\Log;
use app\ai\home\StreamContext;
use app\ai\model\AiMainModel;
use app\ai\model\AiMoldsModel;
use app\ai\model\AiApikeyModel;
use app\member\model\MemberModel;
use app\member\model\MemberCreditModel;
use app\ai\library\WebSocketClient;
use app\ai\library\StreamResponseHandler;
// error_reporting(E_ALL ^ E_WARNING);
// header("Access-Control-Allow-Origin: *");
// set_time_limit(0);

/**
 * 图片生成控制器
 * 重构自 getpicture.php
 */
class Picture extends Controller
{
    protected $user;
    protected $model;
    protected $apiKey;
    protected $responseData = '';
    protected $proxyAddress;
    protected $isImage;
    protected $conversationId;
    protected $postData;
    protected $postDataJson;
    protected $modelPrice;
    protected $modelType;
    protected $modelValue;
    protected $timestamp;
    protected $apiKeyId;
    protected $openaiApiKey;
    protected $apiAddress;
    protected $oldApiAddress;

    /**
     * 初始化
     */
    public function __construct()
    {
        parent::__construct();
        error_reporting(E_ALL ^ E_WARNING);
        header("Access-Control-Allow-Origin: *");
        set_time_limit(0);
        
        $this->timestamp = time();
    }

    /**
     * 主入口方法
     * @return \think\Response
     */
    public function generate()
    {
        try {
            $this->validateRequest();
            $this->loadUser();
            $this->validateQuota();
            $this->loadModel();
            $this->loadApiKey();
            $this->prepareRequest();
            $this->sendRequest();
            $this->processResponse();
            echo $this->responseData;
            exit();
        } catch (\Exception $e) {
            return json(['error' => ['code' => $e->getCode(), 'message' => $e->getMessage()]]);
        }
    }

    /**
     * 验证请求参数
     * @throws Exception
     */
    protected function validateRequest()
    {
        $user = input('get.user');
        $this->isImage = input('get.isimage');

        if (empty($user)) {
            throw new Exception('invalid_user', 400);
        }

        if (!in_array($this->isImage, ['1', '2'])) {
            throw new Exception('本文件仅限生成图片时调用', 400);
        }
    }

    /**
     * 加载用户信息
     * @throws Exception
     */
    protected function loadUser()
    {
        $this->user = MemberModel::where('rndstr', input('get.user'))->find();
        if (!$this->user) {
            throw new Exception('invalid_user', 404);
        }
    }
     /**
     * 加载模型信息
     * @throws Exception
     */
    protected function loadModel()
    {
        $modelId = $this->user['lastmodelid'];
        $this->model = AiMoldsModel::get($modelId);
        if (!$this->model) {
            throw new Exception('model_not_found', 404);
        }
        
        $this->modelPrice = $this->model['modelprice'];
        $this->modelType  = $this->model['modeltype'];
        $this->modelValue = $this->model['modelvalue'];
    }


    /**
     * 验证用户配额
     * @throws Exception
     */
    protected function validateQuota()
    {
        $lastModelId = $this->user['lastmodelid'];
        $model = AiMoldsModel::get($lastModelId);
        if (!$model) {
            throw new Exception('model_not_found', 404);
        }

        $this->modelPrice = $model['modelprice'];
        $this->modelType  = $model['modeltype'];
        $this->modelValue = $model['modelvalue'];

        // 使用MemberCreditModel检查用户积分是否足够
        $points = MemberCreditModel::getCredit($this->user['id'], 'points', ['credit']);
        if ($points < $this->modelPrice) {
            throw new Exception('out_of_money', 402);
        }
    }

    /**
     * 加载API密钥
     * @throws Exception
     */
    protected function loadApiKey()
    {
        $apiKey = AiApikeyModel::where([
            'isvalid' => true,
            'keytype' => $this->user['lastmodelid']
        ])->order(['lasttime', 'id'])->find();

        if (!$apiKey) {
            throw new Exception('no_valid_apikey', 404);
        }

        $this->apiKeyId = $apiKey['id'];
        $this->openaiApiKey = $apiKey['apikey'];
        $this->apiAddress = $apiKey['apiaddress'];
        $this->oldApiAddress = $apiKey['apiaddress'];
    }

    /**
     * 准备请求数据
     */
    protected function prepareRequest()
    {
        $this->conversationId = explode(',', $this->user['lastquestion'])[0];
        $this->postData = substr($this->user['lastquestion'], strlen($this->conversationId) + 1);
        $this->postDataJson = json_decode($this->postData, true);
        $main = AiMainModel::get(1);
        $this->proxyAddress = $main['proxyaddress'];
    }

    /**
     * 发送请求到AI服务
     * @throws Exception
     */
    protected function sendRequest()
    {
        $headers = $this->buildHeaders();
        $url = $this->buildUrl();
        $postData = $this->buildPostData();

        // 更新API密钥使用时间
        AiApikeyModel::update(['lasttime' => date('Y-m-d H:i:s')], ['id' => $this->apiKeyId]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_CONNECTTIMEOUT => 300,
            CURLOPT_TIMEOUT => 300,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_AUTOREFERER => true,
        ]);

        if (!empty($this->proxyAddress)) {
            curl_setopt($ch, CURLOPT_PROXY, $this->proxyAddress);
        }

        $this->responseData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (empty($this->responseData)) {
            throw new Exception('API接口地址配置错误，请联系网址管理员', 500);
        }

        // 记录日志
        Log::write([
            'headers' => $headers,
            'url' => $url,
            'post_data' => $postData,
            'response' => $this->responseData,
            'http_code' => $httpCode
        ], 'info', 'pic');
    }

    /**
     * 构建请求头
     * @return array
     */
    protected function buildHeaders()
    {
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->openaiApiKey
        ];

        switch ($this->modelType) {
            case '通义千问':
                $headers = [
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->openaiApiKey,
                    'X-DashScope-Async: enable'
                ];
                break;

            case '文心千帆':
                if ($this->modelValue == 'Stable-Diffusion-XL') {
                    $accessToken = $this->getBaiduAccessToken();
                    $headers = ['Content-Type: application/json'];
                }
                break;

            case '腾讯混元':
                if ($this->modelValue == 'TextToImageLite') {
                    $authorization = $this->getTencentHunyuanToken();
                    $headers = [
                        'Authorization: ' . $authorization,
                        'Content-Type: application/json; charset=utf-8',
                        'Host: hunyuan.tencentcloudapi.com',
                        'X-TC-Action: TextToImageLite',
                        'X-TC-Timestamp: ' . $this->timestamp,
                        'X-TC-Version: 2023-09-01',
                        'X-TC-Region: ap-guangzhou'
                    ];
                }
                break;

            case '清华智谱':
                if ($this->modelValue == 'cogview-3') {
                    $token = $this->getQinghuaZhipuToken();
                    $headers = [
                        'Accept: text/event-stream',
                        'Content-Type: application/json',
                        'Authorization: ' . $token
                    ];
                }
                break;

            case '讯飞星火':
                $authUrl = $this->assembleAuthUrl();
                $headers = [
                    'Accept: application/json',
                    'Content-Type: application/json;charset=UTF-8'
                ];
                break;

            case '360智脑':
                $headers = ['Authorization: Bearer ' . $this->openaiApiKey];
                break;

            case 'azure':
                $headers = [
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'api-key: ' . $this->openaiApiKey
                ];
                break;
        }

        return $headers;
    }

    /**
     * 构建请求URL
     * @return string
     */
    protected function buildUrl()
    {
        $baseUrl = $this->apiAddress;

        switch ($this->modelType) {
            case '通义千问':
                return $baseUrl . '/api/v1/services/aigc/text2image/image-synthesis';

            case '文心千帆':
                if ($this->modelValue == 'Stable-Diffusion-XL') {
                    $accessToken = $this->getBaiduAccessToken();
                    return $baseUrl . '/rpc/2.0/ai_custom/v1/wenxinworkshop/text2image/sd_xl?access_token=' . $accessToken;
                }
                break;

            case '清华智谱':
                if ($this->modelValue == 'cogview-3') {
                    return $baseUrl . '/api/paas/v4/images/generations';
                }
                break;

            case '讯飞星火':
                return $this->assembleAuthUrl();

            case '360智脑':
                return $baseUrl . '/v1/images/text2img';

            case 'azure':
                return $baseUrl . '/v1/images/generations';

            default:
                if ($this->isImage == '2' && $this->modelType != 'stablediffusion') {
                    return $baseUrl . '/v1/images/variations';
                }
                return $baseUrl . '/v1/images/generations';
        }

        return $baseUrl;
    }

    /**
     * 构建POST数据
     * @return string|array
     */
    protected function buildPostData()
    {
        if ($this->isImage == '2') {
            if ($this->modelType == 'stablediffusion') {
                return json_encode([
                    'url' => $this->postDataJson['url'],
                    'prompt' => $this->postDataJson['prompt']
                ]);
            } else {
                $imageUrl = $this->postDataJson['url'];
                $imageData = file_get_contents($imageUrl);
                $tmpFile = tempnam(sys_get_temp_dir(), 'tmp');
                file_put_contents($tmpFile, $imageData);
                return [
                    'image' => new \CURLFile($tmpFile, 'image/png', 'testpic'),
                    'model' => $this->postDataJson['model'],
                    'n'     => 1,
                    'size'  => '1024x1024'
                ];
            }
        }

        if ($this->modelType == '讯飞星火') {
            $this->postDataJson['header']['app_id'] = explode(',', $this->openaiApiKey)[0];
            return json_encode($this->postDataJson);
        }

        return $this->postData;
    }

    /**
     * 处理响应数据
     * @throws Exception
     */
    protected function processResponse()
    {
        $response = json_decode($this->responseData);

        if (isset($response->error)) {
            $this->handleErrorResponse($response);
        } elseif (isset($response->error_code)) {
            $this->handleBaiduError($response);
        } elseif (isset($response->detail)) {
            $this->handleApiError($response);
        } elseif (isset($response->data)) {
            $this->handleSuccessResponse($response);
        } elseif (isset($response->request_id)) {
            $this->handleAsyncTask($response);
        }
    }

    /**
     * 处理错误响应
     * @param object $response
     * @throws Exception
     */
    protected function handleErrorResponse($response)
    {
        $errorMap = [
            'Rate limit reached' => 'rate_limit_reached',
            'Your access was terminated' => 'access_terminated',
            "You didn't provide an API key" => 'no_api_key',
            'You exceeded your current quota' => 'insufficient_quota',
            'That model is currently overloaded' => 'model_overloaded',
            'The server had an error' => 'server_overloaded',
            'Something went wrong with your generation' => 'picture_not_allowed'
        ];

        $errCode = $response->error->code ?? '';
        $errMsg = $response->error->message ?? '';

        foreach ($errorMap as $msg => $code) {
            if (strpos($errMsg, $msg) === 0) {
                $errCode = $code;
                break;
            }
        }

        $this->logError($errCode, $errMsg);
        
        if (in_array($errCode, ['access_terminated', 'insufficient_quota', 'invalid_api_key', 'account_deactivated'])) {
            AiApikeyModel::update([
                'isvalid' => 0,
                'lasttime' => date('Y-m-d H:i:s'),
                'errmsg' => $errMsg
            ], ['id' => $this->apiKeyId]);
        }

        throw new Exception($errMsg, 400);
    }

    /**
     * 处理百度错误
     * @param object $response
     * @throws Exception
     */
    protected function handleBaiduError($response)
    {
        $errCode = $response->error_code;
        $errMsg = $response->error_msg;

        if (in_array($errCode, [110, 111])) {
            $this->getBaiduAccessToken(true);
        }

        $this->logError($errCode, $errMsg);
        throw new Exception($errMsg, 400);
    }

    /**
     * 处理API错误
     * @param object $response
     * @throws Exception
     */
    protected function handleApiError($response)
    {
        $this->logError('api_error', $response->detail);
        throw new Exception($response->detail, 400);
    }

    /**
     * 处理成功响应
     * @param object $response
     */
    protected function handleSuccessResponse($response)
    {
        //error_log(date('Y-m-d H:i:s') . " response: " . json_encode($response) . "\n", 3, "picture.log");
        $this->deductUserQuota();
        $imageUrl = $this->uploadImageToStorage($response);
        $this->saveToHistory($imageUrl);
        $this->responseData = json_encode(['data' => [['url' => $imageUrl]]]);
    }

    /**
     * 解析Markdown图片语法
     * @param string $str
     * @return string
     */
    protected function parseMarkdownImage($str)
    {
        if (substr($str, 0, 6) === '![IMG]') {
            $regex = '/!\[IMG]\((.*?)\)/';
            return preg_replace($regex, '[图片]', $str);
        } else {
            return $str;
        }
    }

    /**
     * 处理异步任务
     * @param object $response
     */
    protected function handleAsyncTask($response)
    {
        if (isset($response->output)) {
            $taskId = $response->output->task_id;
            $result = $this->pollAsyncTask($taskId);
            if ($result) {
                $this->deductUserQuota();
                $imageUrl           = $result;
                $this->saveToHistory($imageUrl);
                $this->responseData = json_encode(['data' => [['url' => $imageUrl]]]);
            }else {
                // 处理超时情况
                $this->logError('async_task_timeout', '异步任务处理超时');
                throw new Exception('异步任务处理超时', 500);
            }
        }else {
            // 处理初始请求失败情况
            $this->logError('async_request_failed', json_encode($response));
            throw new Exception('异步请求失败', 400);
        }
    }

    /**
     * 扣除用户配额
     */
    protected function deductUserQuota()
    {
        // 使用MemberCreditModel扣除用户积分
        MemberCreditModel::credit(
            $this->user['id'],
            'points',
            -$this->modelPrice,
            'AI图片生成消耗',
            '',
            '',
            'ai_picture'
        );
    }

    /**
     * 上传图片到存储
     * @param object $response
     * @return string
     */
    protected function uploadImageToStorage($response)
    {
        $imageUrl = $response->data[0]->url ?? $response->data[0]['url'];
        // 如果是百度文心千帆的base64格式
        if ($this->modelType == '文心千帆' && $this->modelValue == 'Stable-Diffusion-XL' && isset($response->data[0]->b64_image)) {
             //error_log(date('Y-m-d H:i:s') . " code: {111111}\n", 3, "picture.log");
            return $this->uploadBase64Image($response->data[0]->b64_image);
        }
        //error_log(date('Y-m-d H:i:s') . " code: {222222}\n", 3, "picture.log");
        return $this->uploadRemoteImage($imageUrl);
    }

    /**
     * 上传base64图片
     * @param string $base64Image
     * @return string
     */
    protected function uploadBase64Image($base64Image)
    {
        $main               = AiMainModel::get(1);
        $imageSiteUrl       = $main['imagesiteurl'];
        $imageSiteToken     = $main['imagesitetoken'];

        $timestamp          = file_get_contents($imageSiteUrl . "?action=getnonce");
        $sign               = md5(md5($timestamp) . $imageSiteToken);
        $tmpFile            = tempnam(sys_get_temp_dir(), 'image');
        file_put_contents($tmpFile, base64_decode($base64Image));
        $result = $this->uploadToImageService($imageSiteUrl, $timestamp, $sign, $tmpFile);
        unlink($tmpFile);
        return $result;
    }

    /**
     * 上传远程图片
     * @param string $imageUrl
     * @return string
     */
    protected function uploadRemoteImage($imageUrl)
    {
        $main               = AiMainModel::get(1);
        $imageSiteUrl       = $main['imagesiteurl'];
        $imageSiteToken     = $main['imagesitetoken'];
        $timestamp          = file_get_contents($imageSiteUrl . "?action=getnonce");
        $sign               = md5(md5($timestamp) . $imageSiteToken);
        $tmpFile            = tempnam(sys_get_temp_dir(), 'image');
        file_put_contents($tmpFile, file_get_contents($imageUrl));
        $result = $this->uploadToImageService($imageSiteUrl, $timestamp, $sign, $tmpFile);
        unlink($tmpFile);
        return $result;
    }

    /**
     * 上传到图片服务
     * @param string $serviceUrl
     * @param string $timestamp
     * @param string $sign
     * @param string $filePath
     * @return string
     * @throws Exception
     */
    protected function uploadToImageService($serviceUrl, $timestamp, $sign, $filePath)
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL             => $serviceUrl,
            CURLOPT_POST            => true,
            CURLOPT_POSTFIELDS      => [
                'action'        => 'upload',
                'nonce'         => $timestamp,
                'sign'          => $sign,
                'image'         => new \CURLFile($filePath, 'image/jpeg', 'image.jpg')
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response || !preg_match('/^https?:\/\/.+/', $response)) {
            throw new Exception('图片上传失败: ' . $response, 500);
        }

        return $response;
    }

    /**
     * 轮询异步任务
     * @param string $taskId
     * @return string|null
     */
    protected function pollAsyncTask($taskId)
    {
        $url = str_replace('/api/v1/services/aigc/text2image/image-synthesis', 
                          '/api/v1/tasks/' . $taskId, $this->buildUrl());
        $headers = $this->buildHeaders();
        $maxAttempts = 60;
        $attempt = 0;
        while ($attempt < $maxAttempts) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL             => $url,
                CURLOPT_RETURNTRANSFER  => true,
                CURLOPT_HTTPHEADER      => $headers,
                CURLOPT_CONNECTTIMEOUT  => 10,
                CURLOPT_SSL_VERIFYPEER  => false,
                CURLOPT_SSL_VERIFYHOST  => false
            ]);
            $response = curl_exec($ch);
            curl_close($ch);
            $result = json_decode($response);
            if (isset($result->output) && $result->output->task_status == 'SUCCEEDED') {
                return $result->output->results[0]->url;
            }
            $attempt++;
            sleep(2);
        }
        return null;
    }

    /**
     * 保存到历史记录
     * @param string $imageUrl
     */
    protected function saveToHistory($imageUrl)
    {
        // 使用if判断替代三元运算符
        $go_question = '';
        if(isset($this->postDataJson['prompt']) && $this->postDataJson['prompt'] != ''){
            $go_question = $this->postDataJson['prompt'];
        }elseif(isset($this->postDataJson['input']['prompt']) && $this->postDataJson['input']['prompt'] != ''){
            $go_question = $this->postDataJson['input']['prompt'];
        }
        if ($this->isImage == '2') {
            $question = '![IMG](' . $this->postDataJson['url'] . ')';
            if ($this->modelType == 'stablediffusion') {
                $question .= $go_question;
            }
        } else {
            $question = $go_question;
        }
        $answer = '![IMG](' . $imageUrl . ')';
        Db::name('ai_chathistory')->insert([
            'title'             => mb_substr(strip_tags($question), 0, 100, 'UTF-8'),
            'question'          => $question,
            'answer'            => $answer,
            'conversationid'    => $this->conversationId,
            'modelid'           => $this->user['lastmodelid'],
            'realtime'          => date('Y-m-d H:i:s'),
            'userid'            => $this->user['id'],
            'iserror'           => 0
        ]);
    }

    /**
     * 记录错误日志
     * @param string $code
     * @param string $message
     */
    protected function logError($code, $message)
    {
        $question = $this->isImage == '2' 
            ? '![IMG](' . $this->postDataJson['url'] . ')' . ($this->modelType == 'stablediffusion' ? $this->postDataJson['prompt'] : '')
            : $this->postDataJson['prompt'] ?? '';

        Db::name('ai_errorlog')->insert([
            'question'          => $question,
            'errmsg'            => $message,
            'conversationid'    => $this->conversationId,
            'modelid'           => $this->user['lastmodelid'],
            'realtime'          => date('Y-m-d H:i:s'),
            'userid'            => $this->user['id'],
            'apikey'            => $this->openaiApiKey
        ]);
    }

    /**
     * 获取百度访问令牌
     * @param bool $forceRefresh
     * @return string
     */
    protected function getBaiduAccessToken($forceRefresh = false)
    {
        $apiKey = explode(',', $this->openaiApiKey);
        
        if (count($apiKey) == 3 && !$forceRefresh) {
            return $apiKey[2];
        }

        $url = $this->oldApiAddress . "/oauth/2.0/token?grant_type=client_credentials&client_id=" . $apiKey[0] . "&client_secret=" . $apiKey[1];
        $response = file_get_contents($url);
        $token = json_decode($response)->access_token;

        AiApikeyModel::update([
            'apikey' => $apiKey[0] . "," . $apiKey[1] . "," . $token
        ], ['id' => $this->apiKeyId]);

        return $token;
    }

    /**
     * 获取清华智谱令牌
     * @return string
     * @throws Exception
     */
    protected function getQinghuaZhipuToken()
    {
        try {
            list($id, $secret) = explode(".", $this->openaiApiKey);
        } catch (\Exception $e) {
            throw new Exception('invalid apikey', 400);
        }

        $payload = [
            "api_key" => $id,
            "exp" => round(microtime(true) * 1000) + 3600 * 1000,
            "timestamp" => round(microtime(true) * 1000),
        ];

        return $this->jwtEncode($payload, $secret, "sha256", ["alg" => "HS256", "sign_type" => "SIGN"]);
    }

    /**
     * 获取腾讯混元令牌
     * @return string
     */
    protected function getTencentHunyuanToken()
    {
        $apiKey = explode(',', $this->openaiApiKey);
        $secretId = $apiKey[0];
        $secretKey = $apiKey[1];

        $algorithm = "TC3-HMAC-SHA256";
        $canonicalHeaders = implode("\n", [
            "content-type:application/json; charset=utf-8",
            "host:hunyuan.tencentcloudapi.com",
            "x-tc-action:texttoimagelite",
            ""
        ]);
        $signedHeaders = implode(";", ["content-type", "host", "x-tc-action"]);
        $hashedRequestPayload = hash("SHA256", $this->postData);
        $canonicalRequest = "POST\n/\n\n" . $canonicalHeaders . "\n" . $signedHeaders . "\n" . $hashedRequestPayload;
        
        $date = gmdate("Y-m-d", $this->timestamp);
        $credentialScope = $date . "/hunyuan/tc3_request";
        $hashedCanonicalRequest = hash("SHA256", $canonicalRequest);
        $stringToSign = $algorithm . "\n" . $this->timestamp . "\n" . $credentialScope . "\n" . $hashedCanonicalRequest;
        
        $secretDate = hash_hmac("SHA256", $date, "TC3" . $secretKey, true);
        $secretService = hash_hmac("SHA256", "hunyuan", $secretDate, true);
        $secretSigning = hash_hmac("SHA256", "tc3_request", $secretService, true);
        $signature = hash_hmac("SHA256", $stringToSign, $secretSigning);
        
        return $algorithm . " Credential=" . $secretId . "/" . $credentialScope . ", SignedHeaders=" . $signedHeaders . ", Signature=" . $signature;
    }

    /**
     * 组装讯飞认证URL
     * @return string
     */
    protected function assembleAuthUrl()
    {
        $ul     = parse_url($this->apiAddress);
        $apiKey = explode(',', $this->openaiApiKey);
        
        if (empty($apiKey[1]) || empty($apiKey[2]) || $ul === false) {
            return $this->apiAddress;
        }

        $timestamp = time();
        $rfc1123Format = gmdate("D, d M Y H:i:s \G\M\T", $timestamp);
        $signString = [
            "host: " . $ul["host"],
            "date: " . $rfc1123Format,
            "POST " . $ul["path"] . " HTTP/1.1"
        ];
        
        $sgin = implode("\n", $signString);
        $sha = hash_hmac('sha256', $sgin, $apiKey[1], true);
        $signature = base64_encode($sha);
        $authUrl = 'api_key="' . $apiKey[2] . '", algorithm="hmac-sha256", headers="host date request-line", signature="' . $signature . '"';
        
        return $this->apiAddress . '?' . http_build_query([
            'host' => $ul['host'],
            'date' => $rfc1123Format,
            'authorization' => base64_encode($authUrl)
        ]);
    }

    /**
     * JWT编码
     * @param array $payload
     * @param string $secret
     * @param string $algorithm
     * @param array $headers
     * @return string
     */
    protected function jwtEncode($payload, $secret, $algorithm, $headers)
    {
        $header = base64_encode(json_encode($headers));
        $payload = base64_encode(json_encode($payload));
        $signature = hash_hmac($algorithm, "$header.$payload", $secret, true);
        $signature = base64_encode($signature);
        return "$header.$payload.$signature";
    }
}