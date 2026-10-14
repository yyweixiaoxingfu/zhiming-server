<?php

namespace app\alipay\library\payDriver;


use app\payment\library\PayAppInterface;
use app\payment\library\PayAppTrait;
use app\payment\library\PaymentConfig;
use Yansongda\Pay\Pay;

class Aliwap implements PayAppInterface
{
    public $name = '支付宝H5支付';
    public $key = 'aliwap';
    public $type = 'online';
    private $app,$config;
    public function __construct($app,$config)
    {
        $this->app = $app;
        $this->config = $config;
    }
    use PayAppTrait;

    public function doPay(array $params, &$msg=''){

        $bill = $params['bill'];
        $order = [
            'out_trade_no' => $bill['trade_no'],
            'total_amount' => $bill['money'],
            'subject'      => '购买商品',
        ];
        $PaymentConfig = new PaymentConfig();
        $alipay_config = $PaymentConfig->getPayMentConfig('alipay');
        $alipay = Pay::alipay($alipay_config);
        $res = $alipay->wap($order);
        $res = $res->getContent();
        return $res;
    }

    public function callback(array $params, &$msg=''){
        $PaymentConfig = new PaymentConfig();
        $alipay_config = $PaymentConfig->getPayMentConfig('alipay');
        $alipay = Pay::alipay($alipay_config);

        try{
            $data = $alipay->verify($params['notify']); // 是的，验签就这么简单！
            if ($data['trade_status'] == 'TRADE_SUCCESS') {
                $ret['payment_bill_id'] = $data['out_trade_no'];
                $ret['money'] = $data['total_amount'] ?? null;
                $ret['turn_no'] = $data['trade_no'] ?? null;
                $ret['pay_time'] = $data['notify_time']?strtotime($data['notify_time']):time();
                $status = $data['trade_status'];
                if($status == 'TRADE_FINISHED' || $status == 'TRADE_SUCCESS'){
                    echo "success";
                    $ret['status'] = 'succ';
                }else{
                    echo "fail";
                    $ret['status'] = 'failed';
                }
            }else{
                $ret['status'] = 'failed';
            }
        } catch (\Exception $e) {
            $ret['status'] = 'failed';
        }
        return $ret;
    }


}