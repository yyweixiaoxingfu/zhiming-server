<?php


namespace app\alipay\library\cashDriver;


use app\finance\library\CashInterface;
use app\payment\library\PaymentConfig;
use Yansongda\Pay\Pay;

class Alipay implements CashInterface
{
    public function doCash(array $params, &$msg = '')
    {
        $order = [
            'out_biz_no' => $params['trade_no'] ?? time(),
            'trans_amount' => $params['amount'],
            'product_code' => 'TRANS_ACCOUNT_NO_PWD',
            'payee_info' => [
                'identity' => $params['account'],
                'name' => $params['name'],
                'identity_type' => 'ALIPAY_LOGON_ID',
            ],
            'biz_scene' => 'DIRECT_TRANSFER'
        ];
        $PaymentConfig = new PaymentConfig();
        $config = $PaymentConfig->getPayMentConfig('alipay');
        //$config = config('payment.alipay');
        try{
            $result = Pay::alipay($config)->transfer($order);
            if(!isset($result['code']) || $result['code'] != 10000){
                $msg = isset($result['sub_msg']) ? $result['sub_msg'] : $result['msg'];
                return false;
            }
            if($result){
                $result['status'] = 'payed';
                $result['trans_id'] = empty($result['order_id']) ? '' : $result['order_id']; // 支付交易流水号
            }
            return $result;
        }catch (\Exception $e) {
            $msg = $e->getMessage();
            return false;
        }
    }

}