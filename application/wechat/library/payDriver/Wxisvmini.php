<?php


namespace app\wechat\library\payDriver;


use app\payment\library\PayAppInterface;
use app\payment\library\PayAppTrait;
use app\shop\model\ShopPayAccountModel;
use think\Db;
use Yansongda\Pay\Pay;

class Wxisvmini implements PayAppInterface
{
    public $name = '微信支付';
    public $key = 'wxisvmini';
    public $type = 'online';
    private $app,$config;
    public function __construct($app,$config)
    {
        $this->app = $app;
        $this->config = $config;
    }
    use PayAppTrait;

    public function setConfig($shop_id,&$msg){
        $config = config('wechatisv');
        $config['notify_url'] = \think\facade\Url::build('/payment/pay/notify', ['pay_type' => 'wxisvmini'], false, true);
        $sub_mch_id = ShopPayAccountModel::getShopPayAccount($shop_id,'wechat');
        if(empty($sub_mch_id)){
            $msg = '微信支付账户未配置';
            return false;
        }
        $config['sub_mch_id'] = $sub_mch_id;
        return $config;
    }

    public function doPay(array $params, &$msg=''){
        $bill = $params['bill'];
        $config = $this->setConfig($bill['shop_id'],$msg);
        if(!$config){
            return false;
        }
        $openid = Db::name('member')->where('id',$bill['member_id'])->value('openid_xcx');
        if (!$openid) return false;
        $order = [
            'out_trade_no' => $bill['trade_no'],
            'body' => '购买',
            'total_fee' => $bill['money']*100,
            'openid' => $openid,
        ];

        $wechatpay = Pay::wechat($config);
        $res = $wechatpay->miniapp($order);
        $res = $res->all();
        return $res;
    }

    public function callback(array $params, &$msg=''){
        $config = config('wechatisv');
        $wechatpay = Pay::wechat($config);
        //error_log(var_export($wechatpay, true), 3, __FILE__ . '11111111111111111.log');
        try{
            $data = $wechatpay->verify(); // 是的，验签就这么简单！
            //error_log(var_export($data, true), 3, __FILE__ . '222.log');
            //error_log(var_export($data['result_code'], true), 3, __FILE__ . '333.log');
            if ($data['result_code'] == 'SUCCESS') {
                error_log(var_export(111, true), 3, __FILE__ . '444.log');
                $ret['payment_bill_id'] = $data['out_trade_no'];
                $ret['money'] = $data['total_fee']/100 ?? null;
                $ret['turn_no']  = $data['transaction_id'] ?? null;
                $ret['pay_time'] = $data['success_time']?strtotime($data['success_time']):time();

                $status = $data['result_code'];
                error_log(var_export($status, true), 3, __FILE__ . '666.log');
                if($status == 'SUCCESS'){
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