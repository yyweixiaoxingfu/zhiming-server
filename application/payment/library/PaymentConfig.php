<?php
namespace app\payment\library;
/* *
 * 彩虹易支付SDK服务类
 * 说明：
 * 包含发起支付、查询订单、回调验证等功能
 */
use think\Db;
class PaymentConfig
{
    public function getPayMentConfig($pay_type){
        $pay_ment_data = Db::name('admin_config')->where('group','pay_config')->where('name','like','%'.$pay_type.'%')->column('value','name');
        $pay_data = [];
        switch ($pay_type) {
            case 'wechat':
               $pay_data = [
                    'appid'          => $pay_ment_data['wechat_appid'] ?? '',// APP APPID
                    'app_id'         => $pay_ment_data['wechat_app_id'] ?? '', // 公众号 APPID
                    'miniapp_id'     => $pay_ment_data['wechat_miniapp_id'] ?? '', // 小程序 APPID
                    'mch_id'         => $pay_ment_data['wechat_mch_id'] ?? '',
                    'key'            => $pay_ment_data['wechat_key'] ?? '',
                    'notify_url'     => \think\facade\Url::build('/payment/pay/notify', ['pay_type' => 'wechat'], false, true),
                    'cert_client'    => './static/cert/apiclient_cert.pem', // optional，退款等情况时用到
                    'cert_key'       => './static/cert/apiclient_key.pem',// optional，退款等情况时用到
               ];
               break;
            case 'alipay':
               $pay_data = [
                    'app_id'                => $pay_ment_data['alipay_app_id'] ?? '', //appid
                    'notify_url'            => \think\facade\Url::build('/payment/pay/notify', ['pay_type' => 'alipay'], false, true),
                    //'return_url'            => $pay_ment_data['alipay_return_url'] ?? '',
                    'return_url'            => $pay_ment_data['alipay_return_url'] ?? '',
                    //'ali_public_key'        => $pay_ment_data['alipay_ali_public_key'] ?? '',
                    //'private_key'           => $pay_ment_data['alipay_private_key'] ?? '',
                    // 'app_cert_public_key'   => isset($pay_ment_data['alipay_cert_public_key']) && $pay_ment_data['alipay_cert_public_key'] ? get_file_path($pay_ment_data['alipay_cert_public_key']) : './static/cert/alipay_app_public.crt', // 证书
                    // 'alipay_root_cert'      => isset($pay_ment_data['alipay_root_cert']) && $pay_ment_data['alipay_root_cert'] ? get_file_path($pay_ment_data['alipay_root_cert']) : './static/cert/alipay_root.crt',
               ];
               if($pay_ment_data['alipay_ali_public_key'] && $pay_ment_data['alipay_private_key']){
                    $pay_data['ali_public_key']      = $pay_ment_data['alipay_ali_public_key'] ?? '';
                    $pay_data['private_key']         = $pay_ment_data['alipay_private_key'] ?? '';
                    // $pay_data['app_cert_public_key'] = '';
                    // $pay_data['alipay_root_cert']    = '';
               }else{
                    // $pay_data['ali_public_key']      ='';
                    // $pay_data['private_key']         ='';
                    $pay_data['app_cert_public_key'] = isset($pay_ment_data['alipay_cert_public_key']) && $pay_ment_data['alipay_cert_public_key'] ? get_file_path($pay_ment_data['alipay_cert_public_key']) : './static/cert/alipay_app_public.crt'; // 证书;
                    $pay_data['alipay_root_cert']    = isset($pay_ment_data['alipay_root_cert']) && $pay_ment_data['alipay_root_cert'] ? get_file_path($pay_ment_data['alipay_root_cert']) : './static/cert/alipay_root.crt';
               }
               break;
        }
        return $pay_data;
    }

}
?>