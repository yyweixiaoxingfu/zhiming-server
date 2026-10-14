<?php
namespace app\wechat\library\payDriver;


use app\payment\library\PayAppInterface;
use app\payment\library\PayAppTrait;
use app\payment\library\PaymentConfig;
use Yansongda\Pay\Pay;

class Offical implements PayAppInterface
{
    public $name = '微信公众号支付';
    public $key = 'offical';
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
            'openid'       => $bill['openid'],
        ];
        $PaymentConfig = new PaymentConfig();
        $wechat_config = $PaymentConfig->getPayMentConfig('wechat');
        $wechatpay = Pay::wechat($wechat_config);
        $res    = $wechatpay->mp($order);
        return $res;
    }

    public function callback(array $params, &$msg = '')
    {
        //$config = config('payment.wechat');
        $PaymentConfig = new PaymentConfig();
        $wechat_config = $PaymentConfig->getPayMentConfig('wechat');
        $wechatpay = Pay::wechat($wechat_config);
        try {
            $data = $wechatpay->verify(); // 是的，验签就这么简单！
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