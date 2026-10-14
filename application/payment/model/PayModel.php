<?php
//

namespace app\payment\model;

use app\member\model\MemberCreditModel;
use think\Model;


class PayModel extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $name = 'pay';
    protected $autoWriteTimestamp = true;

    /**
     *  @param name:type type:string desc:支付类型 'recharge'=>充值,'order'=>下单
     *  @param name:target_no type:string desc:充值或下单单号
     *  @param name:member_id type:string desc:会员id
     *  @param name:tradeno type:string desc:交易码
     *  @param name:pay_type type:string desc:支付方式'bank'=>银行卡,'alipay'=>支付宝,'wechat'=>微信
     *  @param name:openid_wx
     */
    public function createPay($type, $target_no, $member_id, $money, $tradeno, $pay_type,$openid_wx='')
    {
        $arr = [
            'target_type'   => $type,
            'target_no'     => $target_no,
            'member_id'     => $member_id,
            'money'         => $money,
            'trade_no'      => $tradeno,
            'status'        => 'nopay',
            'pay_type'      => $pay_type,
            'openid'        => $openid_wx
        ];
        $res = self::create($arr);
        return $res;
    }

    public static function getTargetTypes($value = ''){
        $list = ['order'=>'下单','cs_order'=>'测算下单','ex_order'=>'咨询下单','orderall'=>'下单','recharge'=>'充值','open'=>'开团','join'=>'参团'];
        if(empty($value)){
            return $list;
        }else{
            return $list[$value] ?? '';
        }
    }

    public static function getStatus($value = ''){
        $list = ['pay'=>'已支付','nopay'=>'未支付'];
        if(empty($value)){
            return $list;
        }else{
            return $list[$value] ?? '';
        }
    }

    public static function getPayTypes($value = ''){
        $current = MemberCreditModel::getCreditName();
        $list = ['frequency'=>'体验次数','wechat'=>'微信','alipay'=>'支付宝','aliweb'=>'支付宝-网页','wxnative'=>'微信-网页','epay_paypal'=>'易支付-PayPal','epay_alipay'=>'易支付-支付宝','epay_wxpay'=>'易支付-微信','deposit'=>$current['deposit']];
        if(empty($value)){
            return $list;
        }else{
            return $list[$value] ?? '';
        }
    }

}
