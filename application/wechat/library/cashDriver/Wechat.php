<?php


namespace app\wechat\library\cashDriver;


use app\finance\library\CashInterface;
use app\payment\library\PaymentConfig;
use Yansongda\Pay\Pay;

class Wechat implements CashInterface
{
    public function doCash(array $params, &$msg = ''){
        $order = [
            'partner_trade_no' => $params['trade_no'] ?? time(),   //商户订单号
            'openid' => $params['openid'],  //收款人的openid
            'check_name' => 'NO_CHECK',  //NO_CHECK：不校验真实姓名\FORCE_CHECK：强校验真实姓名
            // 're_user_name'=>'张三', //check_name为 FORCE_CHECK 校验实名的时候必须提交
            'amount' => intval($params['amount'] * 100), //企业付款金额，单位为分
            'desc' => $params['remark'] ?? '帐户提现', //付款说明
        ];
        // 如果您需要通过 APP/小程序的商户账号appid进行转账，请传入参数type
        if(isset($params['type']) && in_array($params['type'],['app','miniapp'])){
            $order['type'] = $params['type'];
        }
        
        $PaymentConfig = new PaymentConfig();
        $wechat_config = $PaymentConfig->getPayMentConfig('wechat');
        try{
            $result = Pay::wechat($wechat_config)->transfer($order);
            if($result['return_code'] !== 'SUCCESS'){
                $msg = isset($result['return_msg']) ? $result['return_msg'] : $result['return_msg'];
                return false;
            }
            if($result){
                $result['status'] = 'payed';
                $result['trans_id'] = empty($result['payment_no']) ? '' : $result['payment_no']; // 微信交易流水号
            }
            return $result;
        }catch (\Exception $e) {
            $msg = $e->getMessage();
            return false;
        }
    }

}