<?php
namespace app\member\transformer;
use app\member\model\MemberCreditModel;
use app\member\model\MemberModel;
use app\system\transformer\TransformerAbstract;
class MemberTransformer extends TransformerAbstract{
    public function transform($model,$param = []){
        $data                = $model->toArray();
        $data['user']        = MemberModel::geMemberInfo(0,$data);
        if(!empty($data['tid'])){
            $data['tui_user']    = MemberModel::geMemberInfo($data['tid']);
        }else{
            $data['tui_user'] = '';
        }
        $deposit             = MemberCreditModel::getCredit($data['id'], 'deposit', ['credit']);
        $points              = MemberCreditModel::getCredit($data['id'], 'points', ['credit']);
        $frequency           = MemberCreditModel::getCredit($data['id'], 'frequency', ['credit']);
        $data['deposit']     = '<a href="'.url('credit_log/index',['uid'=>$data['id'],'m_type'=>'deposit']).'">' . $deposit . '</a>';
        $data['points']      = '<a href="'.url('credit_log/index',['uid'=>$data['id'],'m_type'=>'points']).'">' . $points . '</a>';
        $data['frequency']   = '<a href="'.url('credit_log/index',['uid'=>$data['id'],'m_type'=>'frequency']).'">' . $frequency . '</a>';
        $data['lock_status'] = $data['lock'] == 0?'正常':'锁定';

        return $data;
    }
}