<?php
namespace app\message\library;

use app\member\model\MemberModel;
use app\message\model\MessageActionModel;
use app\message\model\MessageTemplateModel;
use app\user\model\User;
use think\Exception;
use think\facade\Cache;
use think\Queue;

class MessageServer
{
    protected $is_queue = 0;
    protected $error = '';
    public function getError(){
        return $this->error;
    }

    /**
     * 发送短信验证码
     * @param $action 模板类型
     * @param $target 手机号或者邮箱
     * @param string $driver sms email
     * @return bool
     */
    public function sendVerifyCode($action,$target,$driver = 'sms'){
        try {
            $actionInfo = MessageActionModel::where(['action' => $action])->find();
            
            if (empty($actionInfo)) {
                throw new Exception('消息场景未定义');
            }
            $server = new MessageDriver($driver);
            $config = $server->getConfig();
            if (!$config['is_close']) {
                $verifycode = rand(111111, 999999);
                $template   = $this->getActionDriverTemplateInfo($actionInfo, $driver);
                $res        = $this->doSend($server, $target, $template, ['code' => $verifycode]);
                if (!$res) return false;
            } else {
                $verifycode = '123456';
            }
        }catch (\Exception $e){
            $this->error = $e->getMessage();
            return false;
        }
        Cache::set('code_verify_'. $action . '_' .$target, $verifycode, 300);
        return true;
    }

    /**
     * 检测短信验证码是否正确
     */
    public function checkVerifyCode($target,$action,$code){
        $key = 'code_verify_'. $action . '_' .$target;
        $verify = Cache::get($key);
        if (empty($code) || empty($verify)) {
            return false;
        }
        if($verify == $code){
            Cache::rm($key);
            return true;
        }
        return false;
    }

    /**
     * 根据场景发送消息
     * @param $action 模板类型、消息场景
     * @param $target_type 目标类型 member，admin 或 空
     * @param $target_refers 消息发送目标用户信息 $target_type 为member，admin时此处为用户id，$target_type为空时此处应为用户列表
     * @param $params 参数，用于替换模板中的变量
     * @param array $option 其他参数 当前支持 target_type,uid_send 这两个参数
     * @param $option.target_type  选填  $target_type为空并且发送站内信时此参数必填
     * @param $option.uid_send 选填 站内信使用，默认0
     * @return bool
     */
    public function actionSend($action,$target_type,$target_refers,$params,$option = []){
        try {
            $driver_templates = $this->getActionTemplates($action);
            return $this->mulSend($driver_templates,$target_type,$target_refers,$params,$option);
        }catch (\Exception $exception){
            $this->error = $exception->getMessage();
            return false;
        }
    }

    /**
     * 根据自定义模板发送消息
     * @param $title 消息标题，可带变量
     * @param $content 消息内容，可带变量
     * @param $drivers 发送引擎，默认使用数组，单个引擎可直接用字符串 例['info','email'] ; 'sms' ;
     * @param $target_type 目标类型 member，admin 或 空
     * @param $target_refers 消息发送目标用户信息 $target_type 为member，admin时此处为用户id，$target_type为空时此处应为用户列表
     * @param $params 参数，用于替换content，title变量
     * @param array $option 其他参数 当前支持 target_type,uid_send 这两个参数
     * @param $option.target_type  选填  $target_type为空并且发送站内信时此参数必填
     * @param $option.uid_send 选填 站内信使用，默认0
     * @return bool
     */
    public function customSend($title,$content,$drivers,$target_type,$target_refers,$params,$option = []){
        try {
            $driver_templates = $this->getCustomTemplates($drivers,$title,$content);
            return $this->mulSend($driver_templates,$target_type,$target_refers,$params,$option);
        }catch (\Exception $exception){
            $this->error = $exception->getMessage();
            return false;
        }
    }

    /**
     * 批量消息发送
     */
    protected function mulSend($driver_templates,$target_type,$target_refers,$params,$options){
        $type = isset($options['target_type']) ? $options['target_type'] : '';
        switch ($target_type){
            case 'admin':
                $target_infos = User::where('id','in',$target_refers)->select();
                $options['target_type'] = 'admin';
                $type = 'admin';
                break;
            case 'member':
                $target_infos = MemberModel::where('id','in',$target_refers)->select();
                $options['target_type'] = 'member';
                $type = 'member';
                break;
            default:
                $target_infos = is_array($target_refers) || is_object($target_refers) ? $target_refers : [];
        }
        foreach ($driver_templates as $driver_template){
            $driver = $driver_template['driver'];
            $template = $driver_template['template'];
            foreach ($target_infos as $info){
                $tplparams = $this->getInfoParams($type,$info,$params);
                $target = $this->getInfoDriverTarget($type,$info,$driver);
                if(!empty($target)){
                    if($this->is_queue){
                        $queue_data = compact('driver','target','template','tplparams','options');
                        $this->pushToQueue($queue_data);
                    }else{
                        $this->doSend($driver,$target,$template,$tplparams,$options);
                    }
                }
            }
        }
        return true;
    }

    /**
     * 从目标会员信息中提取默认参数值
     * 当前支持默认参数 'user_name'
     */
    protected function getInfoParams($type, $info, $params){
        if($type == 'admin'){
            !isset($params['user_name']) && $params['user_name'] = $info['nickname'];
        }elseif ($type == 'member'){
            !isset($params['user_name']) && $params['user_name'] = $info['name'];
        }
        return $params;
    }

    /**
     * 从目标会员信息中提取发送目标（手机号、邮箱、id、推送id）
     */
    protected function getInfoDriverTarget($type, $info, $driver){
        $target = '';
        switch ($driver){
            case 'sms':
                $target = $info['mobile'] ?? '';break;
            case 'email':
                $target = $info['email'] ?? '';break;
            case 'info':
                $target = $info['id'] ?? '';break;
            case 'notice':
                $target = $info['registration_id'] ?? '';
                break;
            default:;
        }
        return $target;
    }

    /**
     * 根据自定义消息内容和引擎组装消息发送引擎和消息模板
     */
    protected function getCustomTemplates($drivers,$title,$content){
        if(empty($content)){
            throw new Exception('请先定义消息内容');
        }
        $template = ['title' => $title, 'template' => $content];
        $default_drivers = config('message.driver');
        if(!is_array($drivers)){
            $drivers = [$drivers];
        }
        $return_drivers = [];
        foreach ($drivers as $driver){
            if(!isset($default_drivers[$driver])){
                throw new Exception('消息引擎不支持');
            }
            $return_drivers[] = [
                'driver'   => $driver,
                'template' => $template
            ];
        }
        return $return_drivers;
    }

    /**
     * 根据场景获取所有的发送引擎和消息模板
     * @param $action
     * @return array
     * @throws Exception
     */
    protected function getActionTemplates($action){
        $actionInfo = MessageActionModel::where(['action'=>$action])->find();
        if(empty($actionInfo)){
            throw new Exception('消息场景未定义');
        }
        $return_drivers = [];
        $default_drivers = config('message.driver');
        foreach ($default_drivers as $key => $value){
            if(isset($actionInfo[$key.'_enabled']) && $actionInfo[$key.'_enabled']){
                $return_drivers[] = [
                    'driver' => $key,
                    'template' => $this->getActionDriverTemplateInfo($actionInfo,$key)
                ];
            }
        }
        $this->is_queue = $actionInfo['is_queue'] ?? 0;
        return $return_drivers;
    }

    /**
     * 获取消息模板
     */
    protected function getActionDriverTemplateInfo($actionInfo, $driver){
        $action = $actionInfo['action'];
        if(empty($action) || empty($driver)){
            throw new Exception('请先配置参数');
        }
        $cache_key = 'message_temp_'.$driver.'_'.$action;
        if(Cache::has($cache_key)){
            $tempalte = Cache::get($cache_key);
        }else{
            $tempalte = MessageTemplateModel::where(['action'=>$action,'driver'=>$driver])->field('title,template')->find();
            !empty($tempalte) && Cache::set($cache_key,$tempalte->toArray());
        }
        if(empty($tempalte)){
            if(!empty($actionInfo['title']) && !empty($actionInfo['content'])){
                $tempalte = [
                    'title'    => $actionInfo['title'],
                    'template' => $actionInfo['content']
                ];
            }else{
                throw new Exception('消息模板不存在');
            }
        }
        return $tempalte;
    }

    public function pushToQueue($data)
    {
        $jobHandlerClassName = 'app\message\job\SendMessage';
        $jobQueueName        = "SendMessage";
        $jobData             = $data;
        $jobData['ts']       = time();
        return Queue::push($jobHandlerClassName, $jobData, $jobQueueName); // true false
    }

    /**
     * 操作单条消息发送
     * @param $driver 发送引擎
     * @param $target 发送给谁 mobile email member_id ...
     * @param $template 消息模板 ['title','template']
     * @param $tplparams 模板参数
     * @param array $options 其他参数
     * @return bool
     * @throws Exception
     */
    public function doSend($driver,$target,$template,$tplparams,$options = []){
        try {
            if(is_string($driver)){
                $driver = new MessageDriver($driver);
            }
            if (!($driver instanceof MessageDriver)) {
                $this->error = '发送失败';
                return false;
            }
            $res = $driver->send($target, $template, $tplparams, $options);
            if(!$res){
                $this->error = '发送失败:'.$driver->getError() ?? '';
                return false;
            }
        }catch (\Exception $e){
            $this->error = '发送失败:'.$e->getMessage();
            return false;
        }
        return true;
    }
}