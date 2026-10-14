<?php


namespace app\member\library;

use EasyWeChat\Factory;
use EasyWeChat\Kernel\Messages\Text;
use think\facade\Log;
use think\facade\Request;

/**
 * 微信公众号相关
 * Class WechatServer
 * @package app\member\library
 */
class WechatServer
{
    private $error = '';
    private $config;
    private $app;
    public function getError(){
        return $this->error;
    }

    public function __construct()
    {
        $config = config('wechat.official');
        $this->config = [
            'app_id' => $config['appid'],
            'secret'  => $config['secret'],     // AppSecret
            'token'   => 'your-token',          // Token
            'aes_key' => '',                    // EncodingAESKey，兼容与安全模式下请一定要填写！！！
            'response_type' => 'array', // 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
            'oauth' => [
                'scopes'   => ['snsapi_userinfo'],
            ],
        ];
        $this->app = Factory::officialAccount($this->config);
    }

    /**
     * 获取jssdk配置
     * @param $url
     * @return array|string
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getConfig($url){
        try {
            return $this->app->jssdk->buildConfig(['scanQRCode'], true,false,false,[],$url);
        }catch (\Exception $exception){
            $this->error = $exception->getMessage();
            return false;
        }
    }

    /**
     * 获取授权地址
     * @param $url
     */
    public function getAuthUrl($url){
        return $this->app->oauth->scopes(['snsapi_userinfo'])->redirect($url)->getTargetUrl();
    }

    /**
     * 使用code获取用户信息
     */
    public function getUserInfo(){
        try {
            $code = Request::param('code');
            $this->app->request->attributes->set('code',$code);
            return $this->app->oauth->user()->getOriginal();
        }catch (\Exception $exception){
            $this->error = $exception->getMessage();
            return false;
        }
    }

    /**
     * 发送客服消息
     * @param $msg
     * @param $openid
     * @return array|\EasyWeChat\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     */
    public function customer_msg($msg,$openid){
        $message = new Text($msg);
        return $this->app->customer_service->message($message)->to($openid)->send();
    }

    /**
     * 发送模板消息
     * @return array|\EasyWeChat\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     */
    public function template_message($openid,$tpl_id,$data,$url=''){
        $res = $this->app->template_message->send([
            'touser' => $openid,
            'template_id' => $tpl_id,
            'url' => $url,
            'data' => $data,
        ]);
        Log::record(var_export([
            'touser' => $openid,
            'template_id' => $tpl_id,
            'url' => $url,
            'data' => $data,
            'res' => $res
        ],true),'msg');
        return true;
    }


}