<?php


namespace app\finance\validate;


use think\Validate;

class CashValidate extends Validate
{
    protected $rule = [
//        'tradepass|交易密码' => 'require|number|length:6',
        'amount|提现金额' => 'require|float|gt:0',
        'method|提现方式' => 'require',
        'bank_id|银行卡' => 'number',
        'name|姓名'  => '',
        'account|账号' => ''
    ];

}