<?php
namespace app\member\library;

use EasyWeChat\Factory;
use think\facade\Env;

/**
 * 微信小程序
 */
class WechatMiniAppServer
{
    private $error = '';
    private $config;
    private $app;
    public function getError(){
        return $this->error;
    }

    public function __construct($config = [])
    {
        if(empty($config)){
            $config = config('wechat.miniapp');
        }
        $this->config = $config;
        $this->app = Factory::miniProgram($this->config);
    }

    /**
     * 根据code获取用户信息
     */
    public function getAuthByCode($code){
        try {
            return $this->app->auth->session($code);
        }catch (\Exception $exception){
            $this->error = $exception->getMessage();
            return false;
        }
    }

    /**
     * 用户敏感信息解密
     */
    public function decryptData($sessionKey, $iv, $encrypted){
        try {
            return $this->app->encryptor->decryptData($sessionKey, $iv, $encrypted);
        }catch (\Exception $exception){
            $this->error = $exception->getMessage();
            return false;
        }
    }

    /**
     * 获取小程序码（适用于需要的码数量极多，或仅临时使用的业务场景）
     */
    public function getUnlimitQrcode($scene,$page = ''){
        $file_path = Env::get('root_path').'/public/qrcode';
        $file_name = 'wx_mini_'.$scene.'.png';
        if(!file_exists($file_path.'/'.$file_name)){
            try {
                $response = $this->app->app_code->getUnlimit($scene, [
                    'page'  => $page,
                    'width' => 600,
                ]);
                if ($response instanceof \EasyWeChat\Kernel\Http\StreamResponse) {
                    $file_name = $response->saveAs($file_path, $file_name);
                }
            }catch (\Exception $exception){
                $this->error = $exception->getMessage();
                return false;
            }
        }
        $requestScheme = $_SERVER['REQUEST_SCHEME'] ?? 'http';
        $local_url = $requestScheme . '://' . $_SERVER['HTTP_HOST'];
        $url = $local_url.'/qrcode/'.$file_name;
        return $url;
    }

    /**
     * 获取小程序码  适用于需要的码数量较少的业务场景
     */
    public function getQrcode($page,$file_name){
        $file_path = Env::get('root_path').'/public/uploads/qrcode';
        if(!file_exists($file_path.'/'.$file_name)){
            try {
                $response = $this->app->app_code->get($page);
                if ($response instanceof \EasyWeChat\Kernel\Http\StreamResponse) {
                    $file_name = $response->saveAs($file_path, $file_name);
                }
            }catch (\Exception $exception){
                $this->error = $exception->getMessage();
                return false;
            }
        }
        $url = splicingHttpSrc('uploads/qrcode/'.$file_name);
        return $url;
    }

}