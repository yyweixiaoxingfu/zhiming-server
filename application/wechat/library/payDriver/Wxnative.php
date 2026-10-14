<?php
namespace app\wechat\library\payDriver;


use app\payment\library\PayAppInterface;
use app\payment\library\PayAppTrait;
use app\payment\library\PaymentConfig;
use Yansongda\Pay\Pay;

class Wxnative implements PayAppInterface
{
    public $name = '微信扫码支付'; // PC网站、实体店单品或订单、媒体广告支付等场景
    public $key = 'wxnative';
    public $type = 'online';
    private $app, $config;

    public function __construct($app, $config)
    {
        $this->app    = $app;
        $this->config = $config;
    }
    use PayAppTrait;

    public function doPay(array $params, &$msg = '')
    {

        $bill  = $params['bill'];
        $order = [
            'out_trade_no' => $bill['trade_no'],
            'body'         => '购买商品',
            'total_fee'    => $bill['money'] * 100,
        ];
        //$wechat_config = config('payment.wechat');
        $PaymentConfig = new PaymentConfig();
        $wechat_config = $PaymentConfig->getPayMentConfig('wechat');
        $wechat_pay    = Pay::wechat($wechat_config);
        $res           = $wechat_pay->scan($order);
        return $res;
    }

    public function callback(array $params, &$msg = '')
    {
        $PaymentConfig = new PaymentConfig();
        $wechat_config = $PaymentConfig->getPayMentConfig('wechat');
        $wechat_pay = Pay::wechat($wechat_config);
        try {
            $data = $wechat_pay->verify(); // 是的，验签就这么简单！
            if ($data['result_code'] == 'SUCCESS') {
                $ret['payment_bill_id'] = $data['out_trade_no'];
                $ret['money']    = $data['total_fee'] / 100 ?? null;
                $ret['turn_no']  = $data['transaction_id'] ?? null;
                $ret['pay_time'] = $data['success_time']?strtotime($data['success_time']):time();
                $status = $data['result_code'];
                if ($status == 'SUCCESS') {
                    echo "success";
                    $ret['status'] = 'succ';
                } else {
                    echo "fail";
                    $ret['status'] = 'failed';
                }
            } else {
                $ret['status'] = 'failed';
            }
        } catch (\Exception $e) {
            $ret['status'] = 'failed';
        }
        return $ret;
    }
}