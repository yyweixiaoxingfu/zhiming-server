<?php
//

namespace app\payment\home;

use app\member\model\MemberCreditModel;
use app\payment\library\DoPay;
use app\payment\library\EpayCore;
use think\Db;
use think\facade\Env;
use Yansongda\Pay\Log;
use app\index\controller\Api;

/**
 * @title 支付
 */
class Pay extends Api
{
    protected $except = ['notify'];

    /**
     * @title 支付
     * @param name:target type:number require:1 default: desc:支付对象编号
     * @param name:target_type type:string require:1 default: desc:支付对象类型order：订单，recharge：充值,
     * @param name:pay_type type:string require:1 default: desc:支付方式deposit：余额，alipay：支付宝，wechat：微信
     * @param name:money type:number require:1 default: desc:支付金额
     * @param name:extra type:array require:0 default: desc:其他参数
     * @author bh
     * @url /payment/pay/pay
     * @method POST
     * @header name:Authorization require:1 default:1455523e6d407a3c desc:token
     */
    public function pay()
    {
        if ($this->request->isPost()) {
            $post_data  = SafeFilter(input('post.'));
            $member     = $this->memberInfo;
            $target     = trim($post_data['target']);
            $targetType = trim($post_data['target_type']);
            $payType    = trim($post_data['pay_type']);
            $extra      = empty($post_data['extra']) ? [] : $post_data['extra'];
            $pay        = [
                'pay_type' => $payType,
                'money'    => trim($post_data['money']),
            ];
            $pay  = array_merge($pay,$extra);
            $res  = app(DoPay::class)->doPay($member, $target, $targetType, $pay,$msg);
            if (!isset($res['status']) || $res['status'] == false) {
                return returnData(0, '支付失败:' . $msg);
            }
            return returnData(1, '支付成功', $res);
        }
    }

    public function notify($pay_type)
    {
        $post_data  = input('post.');
        if (empty($post_data)) {
            $post_data = file_get_contents('php://input');  // 尝试获取原始请求内容，微信支付可能使用XML格式
        }
        if (is_string($post_data) && strpos($post_data, '<xml>') !== false) {
            // XML转数组
            $xml = simplexml_load_string($post_data, 'SimpleXMLElement', LIBXML_NOCDATA);
            $post_data = json_decode(json_encode($xml), true);
        }
        //$post_data = json_decode($post_data, true);
        //error_log(date('Y-m-d H:i:s') . " notify_data: " . json_encode($post_data) . "\n", 3, "notify.log");
        $params['notify']   = $post_data;
        $params['pay_type'] = $pay_type;
        $res                = app(DoPay::class)->callback($params, $msg);
        echo $msg;
        exit;
    }

    public  function pay_message(){
        if ($this->request->isPost()) {
            $post_data  = SafeFilter(input('post.'));
            $member     = $this->memberInfo;
            $target_no  = isset($post_data['target_no']) && $post_data['target_no'] ? $post_data['target_no'] : '';
            $trade_no   = isset($post_data['trade_no']) && $post_data['trade_no'] ? $post_data['trade_no'] : '';
            if(!$target_no || !$trade_no){
                return returnData(0, '参数有误',['status'=>'error']);
            }
           $order = Db::name('sp_order')->where('order_no',$target_no)->where('user_id',$member['id'])->where('status','>=',1)->where('pay_time','>',0)->find();
           if(empty($order)){
                return returnData(0, '未检测到支付成功',['status'=>'error']);
           }
           $pay = Db::name('pay')->where('member_id',$member['id'])->where('target_no',$target_no)->where('trade_no',$trade_no)->where('status','pay')->find();
           if(empty($pay)){
                return returnData(0, '未检测到支付记录',['status'=>'error']);
           }
           if(!empty($pay) && !empty($order)){
                return returnData(1, '支付成功',['status'=>'success']);
           }
        }
    }

    //支付方式列表
    public function pay_list()
    { 
        if ($this->request->isPost()) {
            $pay_list = Db::name('admin_config')->where('group','pay_config')->where('name','like','%_type%')->field('name,title,value')->select();
            foreach($pay_list as $key=>$val){
                if($val['value']==1){
                    if($val['name']=='epay_type' ){
                        $epay_class = config('epay_class');
                        if(!empty($epay_class)){
                            foreach($epay_class as $k=>$v){
                                $pay_list[$key]['data'][$k]['name'] = $v;
                                $pay_list[$key]['data'][$k]['value'] = 1;
                                switch($v){
                                    case 'alipay':
                                        $pay_list[$key]['data'][$k]['title'] = '易支付-支付宝';    
                                        break;
                                    case 'wxpay':
                                        $pay_list[$key]['data'][$k]['title'] = '易支付-微信';
                                        break;
                                    case 'paypal':
                                        $pay_list[$key]['data'][$k]['title'] = '易支付-PayPal';
                                        break;
                                    case 'qqpay':
                                        $pay_list[$key]['data'][$k]['title'] = '易支付-QQ支付';
                                        break;
                                }
                            }
                        }
                    }else{
                        $pay_name = explode('_',$val['name'])[0];
                        if($pay_name =='wechat'){
                            $pay_list[$key]['data']['name']  = 'wxnative';
                        }
                        if($pay_name =='alipay'){
                            $pay_list[$key]['data']['name']  = 'aliweb';
                        }
                        $pay_list[$key]['data']['title'] = $val['title'];
                        $pay_list[$key]['data']['value'] = $val['value'];
                    }
                }else{
                    $pay_list[$key]['data'] = [];
                }
                unset($pay_list[$key]['name']);
            }
            return returnData(1, '支付方式列表', $pay_list);
        }
    }

    //积分兑换
    public function points_exchange(){
        if ($this->request->isPost()) {
            $post_data         = SafeFilter(input('post.'));
            $member            = $this->memberInfo;
            $payType           = 'points';
            $frequency_num     = isset($post_data['frequency_num']) && $post_data['frequency_num'] ? intval($post_data['frequency_num']) : 0;
            if($frequency_num<=0){
                return returnData(0, '参数有误');
            }
            $points_frequency = (int)config('points_frequency');//平台兑换参数
            $fast_points      = bcmul($frequency_num, $points_frequency,2);
            if($points_frequency<=0 || $fast_points<=0){
                return returnData(0, '参数有误');
            }
            $my_credit  = MemberCreditModel::getCredit ($member['id'], 'points', ['credit']);
            if($my_credit<$fast_points){
                return returnData(0, '您的积分不足');
            }
            try{
                Db::startTrans();
                $points_result = MemberCreditModel::credit(
                    $member['id'],
                    'points',
                    -$fast_points,
                    '积分兑换体验次数：扣除积分'.$fast_points,
                    '',
                    '',
                    'deduct_points'
                );
                if(!$points_result){ 
                    Db::rollback();
                    return returnData(0, '操作失败');   
                }
                $frequency_result = MemberCreditModel::credit(
                    $member['id'],
                    'frequency',
                    $frequency_num,
                    '积分兑换体验次数：增加体验次数'.$frequency_num,
                    '',
                    '',
                    'add_frequency'
                );
                if(!$frequency_result){ 
                    Db::rollback();
                    return returnData(0, '操作失败');   
                }
                Db::commit();
                return returnData(1, '兑换成功,快去体验吧',);
            }catch(\Exception $e){
                echo $e->getMessage();
                Db::rollback();
                return returnData(0, '操作失败');
            }
        }
    }
}
