<?php

namespace app\ai\service;

use app\ai\model\AiMainModel;
use think\Db;

/**
 * AI配置服务类
 * 处理AI配置相关逻辑
 */
class AIConfigService
{
    /**
     * 获取AI主配置
     * @param int $id 配置ID
     * @return array|false
     */
    public static function getMainConfig($id = 1)
    {
        return AiMainModel::get($id);
    }

    /**
     * 获取代理地址
     * @return string
     */
    public static function getProxyAddress()
    {
        return AiMainModel::where('id', 1)->value('proxyaddress');
    }

    /**
     * 生成随机种子
     * @return int
     */
    public static function generateRandomSeed()
    {
        // 简化实现，移除不必要的事务和锁
        // 使用更高效的随机数生成方式
        return mt_rand(10000000, 99999999);
    }

    /**
     * 构建充值面板
     * @param string $paymentType 支付类型
     * @return string
     */
    public static function buildRechargePanel($paymentType = '')
    {
        $isweixinpay = strpos($paymentType, 'weixin') !== false;
        $isalipay = strpos($paymentType, 'alipay') !== false;
        $isshoppay = strpos($paymentType, 'shop') !== false;
        $SpConfig['customer_img']       = setUpImg(config('w_customer_img')); 
        $SpConfig['customer_mobile']    = config('w_customer_mobile');
        $rechargetext = '<div id="buypanel" style="height:100%;"><div style="height: 80%; display: flex; flex-direction: column; justify-content: center; align-items: center;"><p style="font-size: 25px;padding: 1rem 0;">请联系客服充值</p><img src="'.$SpConfig['customer_img'].'" style="width: 50%;"><p style="font-size: 20px;padding: 1rem 0;">'.$SpConfig['customer_mobile'].'</p></div></div>';
        
        // $cardtype = Db::name('ai_cardtype')->where('ishidden', '<>', 1)->order('id asc')->select();
        // foreach ($cardtype as $row) {
        //     $rechargetext .= '<div class="product" productid="' . $row["id"] . '"><div class="product-name">' . $row["cardname"] . '</div><div class="product-price">' . $row["price"] . ' 元</div><div class="product-limit">' . $row["quota"] . ' 积分</div><div class="product-days">' . $row["extenddays"] . ' 天</div></div>';
        // }
        
        // $rechargetext .= '</div><div style="height: 20%; display: flex; justify-content: center; align-items: center;"><input type=hidden id="productid">';
        
        // if ($isweixinpay) {
        //     $rechargetext .= '<button onclick="showwxpayqrcode();" style="background-color: #007bff; color: #fff; border: none; padding: 5px 10px; font-size: 16px; cursor: pointer; margin:5px;">微信支付</button>';
        // }
        
        // if ($isalipay) {
        //     $rechargetext .= '<button onclick="showalipayqrcode();" style="background-color: #007bff; color: #fff; border: none; padding: 5px 10px; font-size: 16px; cursor: pointer; margin:5px;">支付宝</button>';
        // }
        
        // if ($isshoppay) {
        //     $rechargetext .= '<button onclick="gotoshop();" style="background-color: #007bff; color: #fff; border: none; padding: 5px 10px; font-size: 16px; cursor: pointer; margin:5px;">购卡</button><button onclick="showrechargecard();" style="background-color: #007bff; color: #fff; border: none; padding: 5px 10px; font-size: 16px; cursor: pointer; margin:5px;">卡密充值</button>';
        // }
        return $rechargetext;
    }
}
