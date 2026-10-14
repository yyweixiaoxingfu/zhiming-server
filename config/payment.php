<?php
use think\facade\Env;
return [
    'payApp'                 => [ // 支付
        'deposit'    => \app\payment\library\payDriver\Deposit::class, // 余额支付
        'points'     => \app\payment\library\payDriver\Points::class, // 积分支付
        'alipay'     => \app\alipay\library\payDriver\Alipay::class, // 支付宝APP支付
        'aliwap'     => \app\alipay\library\payDriver\Aliwap::class, // 支付宝H5支付
        'aliweb'     => \app\alipay\library\payDriver\Aliweb::class, // 支付宝电脑支付
        'wechat'     => \app\wechat\library\payDriver\Wechat::class, // 微信APP支付
        'official'   => \app\wechat\library\payDriver\Offical::class, // 微信公众号支付
        'miniapp'    => \app\wechat\library\payDriver\Miniapp::class, // 微信小程序支付
        'wxnative'   => \app\wechat\library\payDriver\Wxnative::class, // 微信扫码支付 PC
        'wxisvmp'    => \app\wechat\library\payDriver\Wxisvmp::class,  // 服务商模式 公众号支付
        'wxisvmini'    => \app\wechat\library\payDriver\Wxisvmini::class, // 服务商模式 小程序支付
    ],
    'cashApp'                => [ // 打款
        'alipay'  => \app\alipay\library\cashDriver\Alipay::class,
        'wechat' => \app\wechat\library\cashDriver\Wechat::class,
    ],
    'alipay'  => [
        'app_id'         => '', //appid
        'notify_url'     => \think\facade\Url::build('/payment/pay/notify', ['pay_type' => 'alipay'], false, true),
        'return_url'     => '',
        'ali_public_key' => '',
        'private_key'    => '',
        'app_cert_public_key' => './static/cert/alipay_app_public.crt', // 证书
        'alipay_root_cert' => './static/cert/alipay_root.crt',
        // 以下为服务商模式配置，普通模式请注释
        //'mode'           => 'service',//设为service
        //'pid'            => '2016082000295641',//这里填写支付服务商的pid信息
    ],
    'wechat'  => [
        'appid'          => '',// APP APPID
        'app_id'         => '', // 公众号 APPID
        'miniapp_id'     => '', // 小程序 APPID
        'mch_id'         => '',
        'key'            => '',
        'notify_url'     => \think\facade\Url::build('/payment/pay/notify', ['pay_type' => 'wechat'], false, true),
        'cert_client'    => './static/cert/apiclient_cert.pem', // optional，退款等情况时用到
        'cert_key'       => './static/cert/apiclient_key.pem',// optional，退款等情况时用到
        // 以下为服务商模式配置，普通模式请注释
        //'sub_appid'      => 'wxb3fxxxxxxxxxxx', // 子商户 APP APPID
        //'sub_app_id'     => 'wxb3fxxxxxxxxxxx', // 子商户 公众号 APPID
        //'sub_miniapp_id' => 'wxb3fxxxxxxxxxxx', // 子商户 小程序 APPID
        //'sub_mch_id'     => '146xxxxxx', // 子商户商户号
        //'mode'           => 'service',
    ],
];