<?php
namespace app\finance\validate;

use think\Validate;


/**
 * 配置验证器
 * @package app\admin\validate
 * @author 
 */
class MemberBankValidate extends Validate
{
    // 定义验证规则
    protected $rule = [
        'name|开户人姓名'      => 'require',
        'card_no|提现信息'     => 'require|number',
        'bank_name|名称'      => 'require',
        'mobile|手机号'       => 'require|mobile',
    ];

    // 定义验证提示
    protected $message = [
    ];
}
