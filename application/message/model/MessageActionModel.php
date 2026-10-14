<?php
namespace app\message\model;


use think\facade\Cache;
use think\Model;

class MessageActionModel extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $name = 'message_action';

    public function template(){
        return $this->hasMany(MessageTemplateModel::class,'action','action');
    }

    public static function getDrivers(){
        $drivers = config('message.driver');
        if(empty($drivers)){
            $drivers = ['sms' => '短信', 'email' => '邮件', 'info' => '站内信', 'notice' => '推送'];
        }
        return $drivers;
    }

    public function clear($module, $version=false){
        $deleted = $this->where('module','=',$module);
        if($version){
            $deleted->whereNotIn('version', [$version]);
        }
        $deleted->delete();
        return $deleted;
    }

    public function cached(){
        Cache::clear();
    }
}