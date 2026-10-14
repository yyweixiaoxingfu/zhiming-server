<?php
namespace app\member\model;

use think\Model;

/**
 * 会员意见反馈
 */
class FeedbackModel extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $name = 'member_feedback';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    public function member(){
        return $this->belongsTo(MemberModel::class,'member_id','id');
    }

}
