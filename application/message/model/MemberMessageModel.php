<?php
namespace app\message\model;


use think\Model;

class MemberMessageModel extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $name = 'member_message';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    public static function clearUnread($member_id){
        return self::where(['member_id' => $member_id, 'status' => 0])
            ->update(['status' => 1, 'read_time' => time()]);
    }

}