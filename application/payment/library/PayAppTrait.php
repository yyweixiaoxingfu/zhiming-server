<?php  
namespace app\payment\library;


use think\facade\Env;
use Yansongda\Pay\Log;

trait PayAppTrait
{
    public function getName(){
        return $this->name;
    }
    public function getType(){
        return $this->type;
    }
    public function getKey(){
        return $this->key;
    }
    public function getConfig(){
        return $this->config;
    }
    public function getCallback(){
        return $this->callback ?? false;
    }
    public function doLog($message,$params){
        $key = $this->getKey();
        $file = Env::get('runtime_path').'pay/'.$key.'.log';
        $logger = Log::createLogger($file,'pay.notify');
        $logger->debug($message, $params);
    }
}
