<?php
namespace app\member\model;

use think\Model;

/**
 * 会员账户流水
 */
class MemberCreditLogsModel extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $name = 'member_credit_logs';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    public function member()
    {
        return $this->belongsTo('MemberModel','member_id','id');
    }

}
