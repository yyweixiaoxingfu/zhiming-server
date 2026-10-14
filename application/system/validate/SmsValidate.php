<?php
//

namespace app\system\validate;

use think\Validate;


/**
 * 配置验证器
 * @package app\admin\validate
 * @author 
 */
class SmsValidate extends Validate
{
    // 定义验证规则
    protected $rule = [
        'type|验证码类型'            => 'require',
        'mobile|手机号'             => 'require|mobile',
        'email|邮箱'                => 'require|email',
        'captcha|图形验证码'         => 'require',
        'uuid'                     => 'require',
    ];

    // 定义验证提示
    protected $message = [
        'type.require'    => '请输入获取短信验证码场景',
    ];

    // 定义场景，供快捷编辑时验证
    protected $scene = [
        'send_mobile'       => ['type','mobile','captcha','uuid'], //发送短信验证码时验证
        'send_email'        => ['type','email','captcha','uuid'], //发送短信验证码时验证
    ];
}
