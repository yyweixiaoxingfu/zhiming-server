<?php
namespace app\member\transformer;
use app\member\model\MemberCreditLogsModel;
use app\member\model\MemberCreditModel;
use app\member\model\MemberModel;
use app\system\transformer\TransformerAbstract;
class CreditLogsTransformer extends TransformerAbstract{
    public function transform($model,$param = []){
        $data             = $model->toArray();
        $member           = $model->member->toArray();
        $data['memberInfo'] = $member;
        $data['member']   = MemberModel::geMemberInfo(0,$member);
        $data['create_time'] = format_time($data['create_time']);
        return $data;
    }
}