<?php

namespace app\payment\library\payDriver;


use app\member\model\MemberCreditModel;
use app\payment\library\PayAppInterface;
use app\payment\library\PayAppTrait;

class Points implements PayAppInterface
{
    public $name = '积分支付';
    public $key = 'points';
    public $type = 'online';
    public $callback = true;
    private $app,$config;
    public function __construct($app,$config)
    {
        $this->app = $app;
        $this->config = $config;
    }
    use PayAppTrait;

    public function doPay(array $params, &$msg=''){
        $user = $params['user'];
        $bill = $params['bill'];
       $payRes = MemberCreditModel::credit($user['id'],'points',-$bill['money'],$this->name,'','','deposit','');
        if(!$payRes){
            $msg = '积分不足';
            return false;
        }
        return true;
    }

    public function callback(array $params, &$msg=''){
        return true;
    }
}