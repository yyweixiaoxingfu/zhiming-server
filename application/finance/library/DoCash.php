<?php

namespace app\finance\library;

use app\finance\facade\CashApp;
use think\Db;

class DoCash
{
    static public function doCash($cash, $cashType, $params, &$msg = '')
    {
        // TODO 数据处理
        // 如果微信提现使用的账户为APP/小程序，$params中需传入参数type，['type' => 'app']/['type' => 'miniapp']
        return self::doDriver($cash, $cashType, $params,$msg);
    }

    static public function doDriver($cash, $cashType, $params,&$msg)
    {
        $data = [
            'trade_no'  => $cash['sn'], // 商家提现单号
            'amount'    => $cash['cash'] ?? '', // 转账金额
            'name'      => $cash['name'] ?? '', // 姓名
            'account'   => $cash['account'] ?? '', // 提现账户
            'bank'      => $cash['bank'] ?? '', // 提现账户
            'mobile'    => $cash['mobile'] ?? '', // 提现账户
        ];
        if($cashType == 'wechat'){
            $data['openid'] = $cash['account'] ?? ''; // 微信提现 用户openid
            !empty($cash['bank']) && in_array($cash['bank'],['app','miniapp']) && $data['type'] = $cash['bank'];
        }
        $data = array_merge($data,$params);
        try {
            $cashAppObj = CashApp::drive($cashType);
            $cashed = $cashAppObj->doCash($data, $msg);
        }catch (\Exception $e){
            $msg = $e->getMessage();
            return false;
        }
        return $cashed;
    }
}