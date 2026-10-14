<?php
namespace app\payment\library;

use app\member\model\MemberCreditModel;
use app\member\model\MemberModel;
use app\payment\facade\PayApp;
use app\payment\model\PayModel;
use app\order\model\SpOrderModel;
use app\order\home\SpOrder;
use app\admin\model\SpProjectModel;
use app\admin\model\SpExpertModel;
use think\Db;

class DoPay
{
    /**
     * @param arr $user 会员
     * @param string $target 第三方编号
     * @param int $targetType 支付类型
     * @param string $pay 支付信息['paytype'=>'支付方式','money'=>'支付金额'] 其他传参合并
     * */
    public function doPay($user, string $target, string $targetType, array $pay, &$msg = '')
    {
        if (!isset($user)) return false;
        if (empty($pay)) return false;
        if (!isset($pay['money']) || $pay['money'] <= 0) return false; //支付金额有误
        switch ($targetType) {
//            TODO 获取支付关联信息
//            case 'order':
//                $targetInfo = Db::table('w_order')->where(['order_sn' => $target, 'member_id' => $user['id'], 'status' => 0])->find();
//                $needPay    = $targetInfo['total_amount'];
//                $customer   = $targetInfo['member_id'];
//                break;
            case 'recharge':
                $targetInfo = $user;
                $needPay    = $pay['money'];
                $customer   = $target;
                break;
            case 'cs_order':
                $targetInfo = Db::table('w_sp_order')->where(['order_no' => $target, 'user_id' => $user['id'], 'status' => 0])->find();
                if(empty($targetInfo)){
                    $msg = '信息有误0';
                    return false;
                }
                $needPay    = $targetInfo['pay_price'];
                $customer   = $targetInfo['user_id'];
                break;
            case 'ex_order':
                $targetInfo = Db::table('w_sp_order')->where(['order_no' => $target, 'user_id' => $user['id'], 'status' => 0])->find();
                if(empty($targetInfo)){
                    $msg = '信息有误0';
                    return false;
                }
                $needPay    = $targetInfo['pay_price'];
                $customer   = $targetInfo['user_id'];
                break;
            default:
                $msg = '信息有误1';
                return false;
        }
        if (empty($targetInfo)) {
            $msg = '信息有误2';
            return false;
        }
        if ($customer != $user['id']) {
            $msg = '信息有误3';
            return false;
        }
        if ($needPay != $pay['money']) {
            $msg = '金额有误4';
            return false;
        }
        $payed = $this->goPay($user, $target, $targetType, $pay, $bill, $msg);
        if ($payed === false) {
            $res['status'] = false;
            return $res;
        }

        if ($payed !== true) {  //线上支付为参数不为boolean
            $res['payParams'] = $payed;
        }
        $res['status'] = true;
        $res['bills']  = $bill;
        return $res;
    }

    public function goPay($user, $target, $targetType, $pay, &$bill, &$msg)
    {
        $money = $pay['money'];
        if ($money <= 0) {
            $msg = '金额错误';
            return false;
        }

        //创建支付订单
        $tradeno  = generate_order_number('P', $user['id'], 'pay', 'trade_no');
        $PayModel = new PayModel();
        $openid = '';
        if($pay['pay_type'] == 'official'){
            if(empty($user['openid_wx'])){
                $msg = '用户未授权';
                return false;
            }
            $openid = $user['openid_wx'];
        }
        if($pay['pay_type'] == 'miniapp'){
            if(empty($user['openid_xcx'])){
                $msg = '用户未授权';
                return false;
            }
            $openid = $user['openid_xcx'];
        }
        Db::startTrans();
        $createPay = $PayModel->createPay($targetType, $target, $user['id'], $money, $tradeno, $pay['pay_type'],$openid);
        if ($createPay == false) {
            $msg = '支付失败';
            DB::rollBack();
            return false;
        }
        $bill        = $createPay;
        $pay_app_key = $pay['pay_type'];
        if (!PayApp::findPayApp($pay_app_key)) {
            $msg = '支付方式错误';
            return false;
        }
        $params = [
            'payment_bill_id' => $createPay['trade_no'],
            'bill'            => $createPay,
            'user'            => $user,
            'money'           => $money,
            'pay_app_key'     => $pay_app_key,
            'payInfo'         => $pay,
            'target_id'       => $target,
            'target_type'     => $targetType,
        ];
        $payAppObj      = PayApp::drive($pay_app_key);
        $payAppCallBack = $payAppObj->getCallback();
        $payed = $payAppObj->doPay($params, $msg);

        if ($payed == false) {
            DB::rollBack();
            return false;
        }

        if ($payAppCallBack === false) {
            //如alipay、offline等等
            DB::commit();
            return $payed;
        } else {
            // 如deposit等
            $params['status'] = $status = 'succ';
            if (!$this->callback($params, $msg, 'auto')) {
                DB::rollBack();
                return false;
            }
            DB::commit();
            return true;
        }

    }

    public function callback($params, &$msg = '', $type = '')
    {

        if ($type == 'auto') {
            $ret = $params;
        }else {
            $ret = PayApp::drive($params['pay_type'])->callback($params, $msg);
        }
        if (!isset($ret['status']) || $ret['status'] == '') $ret['status'] = 'failed';
        $status = $ret['status'];
        $time   = time();
        //error_log(date('Y-m-d H:i:s') . " pay_notify_status: " . json_encode($ret) . "\n", 3, "pays_notify.log");
        Db::startTrans();
        switch ($status) {
            case 'succ':
                $payment = $this->updatePayment($ret, $msg);
                //error_log(date('Y-m-d H:i:s') . " pay_notify_data: " . json_encode($payment) . "\n", 3, "pay_notify.log");
                if (!$payment) {
                    $msg = '更新支付单错误';
                    Db::rollBack();
                    return false;
                }
                $out_trade_no = $payment['target_no'];
                $pay_type     = $payment['pay_type'];
                if(!empty($payment)){
                    $order  = SpOrderModel::where('order_no',$out_trade_no)->find();
                    if(!empty($order) && $order['status']==1 && $order['pay_time']>0 && $order['cate_type'] == 1){
                        $p_model = SpProjectModel::where('id',$order['link_id'])->value('p_model');
                        $msg = 'success|'.$order['order_no'].'|'.$p_model;
                        Db::commit();
                        return true;
                    }
                }
                $order      = SpOrderModel::where('order_no',$out_trade_no)->where('status',0)->where('pay_time',0)->find();
                if(empty($order)){
                    $msg =  "订单不存在";
                    Db::rollBack();
                    return false;
                }
                $res = SpOrderModel::where('order_no',$order['order_no'])->update([
                    'status'        =>1,
                    'pay_time'      =>$time,
                    'update_time'   =>$time,
                    'pay_type'      =>$pay_type,
                ]);
                if($res === false){
                    $msg =  "操作错误,更新订单失败";
                    Db::rollBack();
                    return false;
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
                        $msg =  $calculationResult['message'];
                        Db::rollback();  
                        return false;
                    }
                    $key = isset($calculationResult['key']) ? $calculationResult['key'] : '';
                    $msg = 'key='.$key;
                }
                break;
            case 'progress':
                $payment = $this->updatePayment($ret, $msg);
                if (!$payment) {
                    $msg = '更新支付单错误';
                    Db::rollBack();
                    return false;
                }
                $ret['bill'] = $payment;
                $targetType = $payment->target_type;
                switch ($targetType) {
//                    TODO 支付成功业务逻辑处理
                    case 'recharge':
                        if (!$this->__recharge_payment($ret, $status, $msg)) {
                            Db::rollBack();
                            return false;
                        }
                        break;
                    default:
                        $msg = '支付数据错误';
                        return false;
                }
                break;
            case 'failed':
            case 'error':
            case 'cancel':
            case 'invalid':
            case 'timeout':
                //todo 失败情况
                break;
        }

        Db::commit();
        return true;
    }

    public function updatePayment($ret, &$msg = '')
    {
        $paymentObj = PayModel::where(['trade_no' => $ret['payment_bill_id']])->find();
        if (!$paymentObj) {
            $msg = '错误支付单';
            return false;
        }

        if($paymentObj['status'] == 'pay'){
             return $paymentObj;
        }

        if ($paymentObj['status'] != 'nopay') {
            $msg = '支付单状态错误';
            return false;
        }

        $update = [
            'turn_no'   => $ret['turn_no'] ?? null,
            'pay_money' => $ret['money'],
            'pay_time'  => $ret['pay_time'] ?? time(),
            'status'    => 'pay',
        ];
        foreach ($update as $k => $v) {
            $paymentObj->{$k} = $v;
        }

        $res = PayModel::where(['trade_no' => $ret['payment_bill_id']])->where('status', 'nopay')->update($update);
        if (!$res) {
            return false;
        }
        return $paymentObj;
    }

    public function __recharge_payment($params, &$status, &$msg = '')
    {
        $bill   = $params['bill'];
        $userId = $bill['target_no'];
        $user   = MemberModel::where('id', $userId)->find();
        if (!$user) {
            $msg = '信息错误';
            return false;
        }
        if ($status == 'succ') {
            $res = MemberCreditModel::credit($user['id'], 'deposit', $bill['money'], '余额充值', '', $bill['trade_no'], 'recharge', '');
            if (!$res) return false;
        }
        return true;
    }

}