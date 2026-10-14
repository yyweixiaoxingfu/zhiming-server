<?php
//

namespace app\payment\home;

use app\member\model\MemberCreditModel;
use app\payment\model\PayModel;
use app\order\model\SpOrderModel;
use app\order\home\SpOrder;
use app\admin\model\SpProjectModel;
use app\admin\model\SpExpertModel;
use app\payment\library\DoPay;
use app\payment\library\EpayCore;
use think\Db;
use think\facade\Env;
use Yansongda\Pay\Log;
use app\index\controller\Api;
/**
 * @title 易支付
 */
class Epay extends Api
{
    protected $except = ['epay_notify'];
    private $epay_config;
    
    // 构造函数
    public function __construct() {
        parent::__construct();
        
        // 初始化支付配置
        $this->epay_config = [
            //支付接口地址
            'apiurl'                => config('epay_apiurl'),
            //商户ID
            'pid'                   => config('epay_pid'),
            //平台公钥
            'platform_public_key'   => config('epay_platform_public_key'),
            //商户私钥
            'merchant_private_key'  => config('epay_merchant_private_key'),
        ];
    }
    //易支付
    public function epay(){
        if ($this->request->isPost()) {
            $post_data  = SafeFilter(input('post.'));
            $member     = $this->memberInfo;
            $epay       = new EpayCore($this->epay_config);
            $PayModel   = new PayModel();
            $target     = trim($post_data['target']);
            if(!$target){
                return returnData(0, '关键参数不能为空');
            }
            $targetType = trim($post_data['target_type']);
            if(!$targetType){
                return returnData(0, '关键参数不能为空');
            }
            $payType    = trim($post_data['pay_type']);
            if(!$payType){
                return returnData(0, '关键参数不能为空');
            }
            if(!isset($post_data['money']) || $post_data['money']<=0){
                return returnData(0, '金额不能小于等于0');
            }
            //$extra    = isset($post_data['extra']) ? $post_data['extra'] : '腾势数字人,帮助您快速实现数字人化的最佳选择。';
            $tradeno    = generate_order_number('P', $member['id'], 'pay', 'trade_no');
            try{
                // $post_data['money'] = 0.01;
                // if($payType == 'paypal'){
                //     $post_data['money'] = 1;
                // }
                
                $parameter = array(
                    "type"          => $payType,
                    "notify_url"    => config('api_url').'payment/epay/epay_notify',
                    "return_url"    => config('web_url').'pages/payment/paySuccess',
                    "out_trade_no"  => $tradeno,
                    "name"          => '腾势数字人会员使用费',
                    "money"         => $post_data['money'],
                    "param"         => '',
                );
                Db::startTrans();
                $PayModel->createPay($targetType, $target, $member['id'], $post_data['money'],$tradeno, 'epay_'.$payType);
                $result = $epay->pagePay($parameter,'点击前往支付');
                Db::commit();
                return returnData(1, '获取成功',$result);
                //return  $result;
            }catch(Exception $e){
                Db::rollback();
                return returnData(0, '支付失败:' . $e->getMessage());
            }
        }
    }

    
    public function epay_notify()
    {
        $get_data          = SafeFilter(input('get.'));
        $epay              = new EpayCore($this->epay_config);
        $verify_result     = $epay->verify($get_data); 
        //error_log(date('Y-m-d H:i:s') . " pay_notify_data: " . json_encode($get_data) . "\n", 3, "epay_notify.log");
        if($verify_result) {                                    //验证成功
            $pay_type           = $get_data['type'];
            //$out_trade_no       = $get_data['out_trade_no'];    //商户订单号
            //$trade_no           = $get_data['trade_no'];        //彩虹易支付交易号
            $trade_status       = $get_data['trade_status'];    //交易状态
            $money              = $get_data['money'];
            $time               = time();
            if ($trade_status == 'TRADE_SUCCESS') {
                 Db::startTrans();
                $get_data['data']  = json_encode($get_data);
                $res               = $this->updatePayment($get_data);
                if($res['success'] === false){
                    Db::rollback();
                    echo "fail".$res['message'];
                    exit();
                }
                $out_trade_no = $res['data']['target_no'];
                if($res['success'] && $res['message']=='已完成支付' && !empty($res['data'])){
                    $order  = SpOrderModel::where('order_no',$out_trade_no)->find();
                     if(!empty($order) && $order['status']==1 && $order['pay_time']>0 && $order['cate_type'] == 1){
                        $p_model = SpProjectModel::where('id',$order['link_id'])->value('p_model');
                        echo 'success|'.$order['order_no'].'|'.$p_model;
                        Db::commit();
                        exit;
                     }
                }
                $order      = SpOrderModel::where('order_no',$out_trade_no)->where('status',0)->where('pay_time',0)->find();
                if(empty($order)){
                    Db::rollback();
                    echo "fail:订单不存在";
                    exit();
                }
                $res = SpOrderModel::where('order_no',$order['order_no'])->update([
                    'status'        =>1,
                    'pay_time'      =>$time,
                    'update_time'   =>$time,
                    'pay_type'      =>'epay_'.$pay_type,
                ]);
                if($res === false){
                    Db::rollback();
                    echo "fail:操作错误,更新订单失败";
                    exit();
                }
                if($order['cate_type']==4){
                    $find_data = SpExpertModel::where('id',$order['link_id'])->find();
                }else{
                    $find_data = SpProjectModel::where('id',$order['link_id'])->find();
                }
                if($order['cate_type']==1){
                    $SpOrder = new SpOrder();
                    $calculationResult = $SpOrder->processCalculation($order, $find_data, $order['user_id'], $time, $order['order_no']);
                    if(!$calculationResult['success']){
                        Db::rollback();  
                        echo "fail:".$calculationResult['message'];
                        exit();
                    }
                    $key = isset($calculationResult['key']) ? $calculationResult['key'] : '';
                }
                Db::commit();
                echo "success|key=".$key;
                exit();
                //return returnData(1, '提交成功,正在计算中...',['key'=>$key]);
            }
        }else {
            //验证失败
            echo "fail:验签失败";
        }
    }

    public function updatePayment($get_data)
    {
        $paymentObj = PayModel::where(['trade_no' => $get_data['out_trade_no']])->find();
        if (!$paymentObj) {
            // $msg = '错误支付单';
            // return false;
            return ['success' => false, 'message' => '错误支付单'];
        }
        if($paymentObj['status'] == 'pay'){
             return ['success' => true,  'message' => '已完成支付','data'=>$paymentObj];
        }
        if ($paymentObj['status'] != 'nopay') {
            // $msg = '支付单状态错误';
            // return false;
            return ['success' => false, 'message' => '支付单状态错误'];
        }

        $update = [
            'turn_no'   => $get_data['trade_no'],
            'data'      => isset($get_data['data']) && $get_data['data'] ? $get_data['data'] : '',
            'pay_money' => isset($get_data['money']) && $get_data['money'] ? $get_data['money'] : 0,
            'pay_time'  => isset($get_data['pay_time']) && $get_data['pay_time'] ? $get_data['pay_time'] : time(),
            'status'    => 'pay',
        ];
        foreach ($update as $k => $v) {
            $paymentObj->{$k} = $v;
        }
        $res = PayModel::where(['trade_no' => $get_data['out_trade_no']])->where('status', 'nopay')->update($update);
        if (!$res) {
            return ['success' => false, 'message' => '更新失败'];
        }
        return ['success' => true, 'message' => '更新成功','data'=>$paymentObj];
    }

}