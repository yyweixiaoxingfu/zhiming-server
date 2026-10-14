<?php
namespace app\ai\library;
use app\ai\model\AiMainModel;

/**
 * 百度内容审核类
 * 用于调用百度AI内容审核API进行文本审核
 */
class BaiduContentSensor
{
    private static $tokenCache = null;
    private static $tokenExpireTime = 0;
    
    /**
     * 检查文本是否通过百度内容审核
     * @param string $text 需要审核的文本
     * @return bool 是否通过审核
     */
    public function checkContent(string $text): bool
    {
        try {
            // 获取配置
            $config = AiMainModel::getConfig(1);
            if (!$config) {
                return true; // 配置获取失败，默认通过
            }

            $apiKey = $config['baiduapikey'];
            $secretKey = $config['baidusecretkey'];

            // 参数验证
            if (empty($apiKey) || empty($secretKey)) {
                return true; // 缺少API密钥，默认通过
            }

            // 获取访问令牌
            $accessToken = $this->getAccessToken($apiKey, $secretKey);
            if (empty($accessToken)) {
                return true; // 获取令牌失败，默认通过
            }

            // 内容审核
            $result = $this->censorText($accessToken, $text);
            return $result;
        } catch (\Exception $e) {
            // 记录错误日志
            error_log('百度内容审核失败: ' . $e->getMessage(), 3, 'baidu_content_sensor.log');
            return true; // 异常情况下默认通过
        }
    }

    /**
     * 获取百度AI访问令牌
     * @param string $apiKey API密钥
     * @param string $secretKey 密钥
     * @return string|null 访问令牌
     */
    private function getAccessToken(string $apiKey, string $secretKey): ?string
    {
        // 检查缓存是否有效
        if (!empty(self::$tokenCache) && time() < self::$tokenExpireTime) {
            return self::$tokenCache;
        }

        $tokenUrl = 'https://aip.baidubce.com/oauth/2.0/token';
        $tokenData = [
            'grant_type' => 'client_credentials',
            'client_id' => $apiKey,
            'client_secret' => $secretKey
        ];

        $response = $this->curlRequest($tokenUrl, $tokenData, 'POST');
        if (!$response) {
            return null;
        }

        $tokenData = json_decode($response, true);
        if (isset($tokenData['error']) || empty($tokenData['access_token'])) {
            return null;
        }

        // 缓存令牌，设置过期时间（提前10分钟过期）
        self::$tokenCache = $tokenData['access_token'];
        self::$tokenExpireTime = time() + $tokenData['expires_in'] - 600;

        return self::$tokenCache;
    }

    /**
     * 调用百度内容审核API
     * @param string $accessToken 访问令牌
     * @param string $text 需要审核的文本
     * @return bool 是否通过审核
     */
    private function censorText(string $accessToken, string $text): bool
    {
        $censorUrl = "https://aip.baidubce.com/rest/2.0/solution/v1/text_censor/v2/user_defined?access_token={$accessToken}";
        $censorData = ['text' => $text];

        $response = $this->curlRequest($censorUrl, $censorData, 'POST');
        if (!$response) {
            return true;
        }

        $result = json_decode($response, true);

        // 审核结果：1-合规，2-不合规，3-疑似，4-审核失败
        return isset($result['conclusionType']) && $result['conclusionType'] == 1;
    }

    /**
     * 封装CURL请求方法
     * @param string $url 请求URL
     * @param array $data 请求数据
     * @param string $method 请求方法
     * @return string|false 响应结果或false
     */
    private function curlRequest(string $url, array $data = [], string $method = 'GET')
    {
        $ch = curl_init();

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json'
            ]
        ];

        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = http_build_query($data);
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($httpCode !== 200) {
            error_log("百度API请求失败: URL={$url}, HTTP_CODE={$httpCode}, ERROR={$error}", 3, 'baidu_content_sensor.log');
            return false;
        }

        return $response;
    }
}
