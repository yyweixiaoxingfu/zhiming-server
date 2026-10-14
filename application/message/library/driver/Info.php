<?php
namespace app\message\library\driver;


use app\message\model\MemberMessageModel;
use app\user\model\Message;
use think\Exception;

/**
 * 站内信
 * Class Info
 * @package app\message\library
 */
class Info extends MessageAbstract
{
    public function setConfig()
    {
        $config = config('message.info');
        if(empty($config)){
            throw new Exception('请先配置参数');
        }
        $this->config = $config;
    }

    public function send($target,$template,$params = [],$options = []){
        if(empty($options['target_type']) || !in_array($options['target_type'],['admin','member'])){
            throw new Exception('站内信类型未定义');
        }
        $title = $template['title'];
        $content = $template['template'];
        foreach ($params as $key => $value){
            $title = str_replace("{".$key."}", $value, $title);
            $content = str_replace("{".$key."}", $value, $content);
        }
        $type = $options['target_type'];
        $insertData = [
            'uid_send' => $options['uid_send'] ?? 0,
            'type' => $title,
            'content' => $content,
            'status' => 0,
        ];
        foreach ($options as $key => $value){
            if(!in_array($key,['target_type','uid_send'])){
                $insertData[$key] = $value;
            }
        }
        if($type == 'admin'){
            $insertData['uid_receive'] = $target;
            return Message::create($insertData);
        }elseif($type == 'member'){
            $insertData['member_id'] = $target;
            return MemberMessageModel::create($insertData);
        }
        return true;
    }

}