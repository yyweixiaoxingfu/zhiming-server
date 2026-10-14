<?php

namespace app\payment\library\payDriver;


use app\member\model\MemberCreditModel;
use app\payment\library\PayAppInterface;
use app\payment\library\PayAppTrait;

class Deposit implements PayAppInterface
{
    public $name = '余额支付';
    public $key = 'deposit';
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
       $payRes = MemberCreditModel::credit($user['id'],'deposit',-$bill['money'],$this->name,'','','deposit','');
        if(!$payRes){
            $msg = '余额不足';
            return false;
        }
        return true;
    }

    public function callback(array $params, &$msg=''){
        return true;
    }
}