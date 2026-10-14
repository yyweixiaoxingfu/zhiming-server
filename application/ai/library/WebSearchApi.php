<?php
namespace app\ai\library;
use think\Db;
use app\ai\model\AiMainModel;
use think\Controller;
header("Content-Type:application/json;charset=utf-8");
date_default_timezone_set('Asia/Shanghai');
header("Access-Control-Allow-Origin: *");
class WebSearchApi
{
    public function ddg_get_api($post_data){
        // 获取数据库中的代理配置
        $main_data    = AiMainModel::get(1);
        $proxyaddress = $main_data['proxyaddress'];
        
        // 创建自定义配置的HTTP客户端
        $options = array(
            'timeout' => 30, // 增加超时时间到30秒
        );
        
        // 如果有代理，设置代理配置
        if (!empty($proxyaddress)) {
            $options['proxy'] = $proxyaddress;
        }
        
        // 使用自定义配置初始化客户端
        $client = new \DDG\API\Http\Client($options);
        $categories = new \DDG\API\Categories();
        $categories->setClient($client);
        
        try {
            $result = $categories->get($post_data);
            return $result;
        } catch (\Exception $e) {
            // 错误处理
            $content = array(
                'code' => 500,
                'msg' => "API访问出错: " . $e->getMessage()
            );
            return json_encode($content, JSON_UNESCAPED_UNICODE);
        }
    }
    public function send_post_lw($post_data)
    {
        $main_data     = AiMainModel::get(1);
        $proxyaddress  = $main_data['proxyaddress'];
        $headers       = array();
        $headers[] = "Host: lite.duckduckgo.com";
        $headers[] = "Content-Type: application/x-www-form-urlencoded";
        $headers[] = "Origin: https://lite.duckduckgo.com";
        $headers[] = "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:107.0) Gecko/20100101 Firefox/107.0";
        $headers[] = "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8";
        $headers[] = "Accept-Language: zh-CN,zh;q=0.8,zh-TW;q=0.7,zh-HK;q=0.5,en-US;q=0.3,en;q=0.2";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://lite.duckduckgo.com/lite/");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        if (!empty($proxyaddress)) {
            curl_setopt($ch, CURLOPT_PROXY, $proxyaddress);
        }
        $result = curl_exec($ch);
        // 移除调试代码，让程序继续执行
        if (curl_errno($ch)) {
            $content = array(
                'code' => 500,
                'msg' => "访问出错: " . curl_error($ch) . " (错误码: " . curl_errno($ch) . ")"
            );
            curl_close($ch);
            return json_encode($content, JSON_UNESCAPED_UNICODE);
        }
        curl_close($ch);
        return $result;
    }

    public function search_send_post($messages=''){
        @$message     = $messages;
        if (empty($message)) {
            $content = array(
                'code' => 203,
                'msg' => "请输入待回答的问题"
            );
             return json_encode($content, JSON_UNESCAPED_UNICODE);
        }
        $message_bm = urlencode($message);
        $post_data  = "q=$message_bm&df=&kl=wt-wt";
        $data       = $this->send_post_lw($post_data);
        $result     = json_decode($data, true);
        if($result['code'] === 500){
            return $data;
        }
        preg_match_all("/<a rel=\"nofollow\" href=\"(.*?)\" class=\'result-link\'>.*<\/a>/", $data, $urlss);
        preg_match_all("/<td class=\'result-snippet\'>\s*(.*?)\s*<\/td>/", $data, $contentss);
        $urls       = $urlss[1];
        $contents   = $contentss[1];
        $array      = array();
        $date       = date('Y/m/d');
        for ($i = 0; $i < 3; $i++) {
            $js = $i + 1;
            $content = $contents[$i];
            $url = $urls[$i];
            $content_bq = strip_tags($content);
            $string = "[$js]:\"$content_bq\"\nURL:$url";
            array_push($array, $string);
        };
        $allcontents    = implode("\n\n", $array);
        $resultwb       = "$message\n\nWeb search results:\n\n" . $allcontents . "\n\nCurrent date:$date\n\nInstructions: Using the provided web search results, write a comprehensive reply to the given query. Make sure to cite results using [[number](URL)] notation after the reference. If the provided search results refer to multiple subjects with the same name, write separate answers for each subject.\n\nQuery: $message\n\nReply in 中文";
        $content = array(
            'code'      => 200,
            'msg'       => "获取成功",
            'message'   => urldecode($message),
            'online'    => urldecode($resultwb)
        );
        return json_encode($content, JSON_UNESCAPED_UNICODE);
    }
}
