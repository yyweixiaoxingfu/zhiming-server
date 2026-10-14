<?php
namespace app\finance\model;

use app\member\model\MemberCreditModel;
use think\Db;
use think\Model;
use think\model\concern\SoftDelete;

/**
 * 商品分类模型
 * @package app\admin\model
 */
class MemberCashModel extends Model
{
    use SoftDelete;
    // 设置当前模型对应的完整数据表名称
    protected $name = 'member_apply_cash';
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;
    protected $deleteTime = 'delete_time';
    public function member(){
        return $this->belongsTo('app\\member\\model\\MemberModel','member_id','id');
    }

    public static function getMethods($method = ''){
        $list = ['wechat'=>'微信','alipay'=>'支付宝','bank'=>'银行卡'];
        if(!empty($method)){
            return $list[$method] ?? '';
        }
        return $list;
    }

    public static function getStatus($status = ''){
        $list = ['review'=>'待审核','agree'=>'已审核','reject'=>'已驳回','payed'=>'已打款','ongoing'=>'处理中'];
        if(!empty($status)){
            return $list[$status] ?? '';
        }
        return $list;
    }

    public function applyCash($member, $params)
    {
        $member_id = $member['id'];
        if($params['amount'] <=0){
            return ['status' => 0, 'error' => '请输入正确的提现金额'];
        }
        $amount = abs($params['amount']);
        if ($amount < config('withdraw_low')) {
            return ['status' => 0, 'error' => '最低提现金额' . config('withdraw_low')];
        }

        $tx_bei = config('withdraw_bei');
        if ($tx_bei == 1) {
            if (!preg_match("/^\d+$/", $amount)) //只包括正整数，注意与上面的正则表达式略有差异
            {
                return ['status' => 0, 'error' => '提现金额需为' . $tx_bei . '的倍数哦！'];
            }
        } elseif ($tx_bei > 0) {
            if (ceil($amount) % $tx_bei != 0) {
                return ['status' => 0, 'error' => '提现金额需为' . $tx_bei . '的倍数哦！'];
            }
        }
        $fee     = config('withdraw_fee');
        $type = 'deposit';
        $member_credit = MemberCreditModel::getCredit($member_id, $type);
        if ($amount > $member_credit) {
            $creadteName = MemberCreditModel::getCreditName('deposit') ;
            return ['status' => 0, 'error' =>$creadteName.'不足'];
        }
        $charge = sprintf('%.2f', $amount * $fee / 100);

        $apply_data   = [
            'sn'          => createNO(0,12,'member_apply_cash','sn'),
            'member_id'   => $member_id,
            'amount'      => $amount,
            'charge'      => $fee,
            'cash'        => bcsub($amount, $charge, 2),
            'status'      => 'review',
            'type'        => $type,
            'method'      => $params['method'],
            'create_time' => time()
        ];
        $allow_method = config('withdraw_type');
        if(!in_array($params['method'],$allow_method)){
            return ['status' => 0, 'error' => '不支持的提现方式'];
        }
        $auto_pay = (bool) config('withdraw_auto_pay');
        switch ($params['method']){
            case 'bank':
                $bank_id = intval($params['bank_id']);
                $bank = MemberBankModel::where(['member_id'=>$member_id,'id'=>$bank_id])->find();
                if(empty($bank)){
                    return ['status' => 0, 'error' => '银行卡信息有误'];
                }
                $account_data = [
                    'account'     => $bank['card_no'],
                    'name'        => $bank['name'],
                    'bank'        => $bank['bank_name'],
                    'mobile'      => $bank['mobile'],
                ];
                break;
            case 'alipay':
                if(empty($params['name']) || empty($params['account'])){
                    return ['status' => 0, 'error' => '请填写提现账户'];
                }
                $account_data = [
                    'account'     => $params['account'],
                    'name'        => $params['name'],
                ];
                break;
            case 'wechat':
                if($auto_pay){
                    if(empty($member['openid_wx']) && empty($member['openid_xcx'])){
                        return ['status' => 0, 'error' => '用户未授权，无法使用微信提现'];
                    }
                    if(!empty($member['openid_wx'])){
                        $account_data = [
                            'account' => $member['openid_wx'],
                        ];
                    }else{
                        $account_data = [
                            'account' => $member['openid_xcx'],
                            'bank' => 'miniapp'
                        ];
                    }
                }else{
                    if((empty($params['name']) || empty($params['account'])) && empty($params['receiving_code']) ){
                        return ['status' => 0, 'error' => '请完善微信收款信息'];
                    }
                    $account_data = [
                        'receiving_code'    =>$params['receiving_code'],
                        'account'           =>$params['account'],
                        'name'              =>$params['name']
                    ];
                }
                break;
            default:
                return ['status' => 0, 'error' => '支付方式不存在'];
                break;
        }
        $apply_data = array_merge($apply_data,$account_data);
        Db::startTrans();
        try {
            $member_credit = new MemberCreditModel();
            $result        = $member_credit->credit($member_id, $type, -$amount, '提现扣除', time(), '', 'cash', '');
            if (!$result) {
                Db::rollback();
                return ['status' => 0, 'error' => '申请失败'];
            }
            $apply_result = Db::name('member_apply_cash')->insert($apply_data);
            if (!$apply_result) {
                Db::rollback();
                return ['status' => 0, 'error' => '申请失败222'];
            }
            Db::commit();
            return ['status' => 1, 'error' => '申请成功'];
        } catch (\Exception $e) {
            //回滚事务
            Db::rollback();
            return ['status' => 0, 'error' => '申请失败'];
        }
    }
}
