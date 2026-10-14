<?php
//

namespace app\member\validate;

use think\Validate;


/**
 * 配置验证器
 * @package app\admin\validate
 * @author 
 */
class MemberValidate extends Validate
{
    // 定义验证规则
    protected $rule = [
        'mobile|手机号'         => 'require|mobile',
        'email|邮箱'            => 'require|email',
        'code|短信验证码'       => 'require',
        'loginpass|密码'        => 'require|length:6,20',   
        'quepass|确认密码'      => 'require|confirm:loginpass',
        'oldpass|旧密码'        => 'require',
        'tradepass|交易密码'    => 'require|number|length:6',
        'quetradepass|确认密码' => 'require|confirm:tradepass',
        'rawData'              => 'require',
        'signature'            => 'require',
        'encryptedData'        => 'require',
        'iv'                   => 'require',
    ];

    // 定义验证提示
    protected $message = [
        'quepass.confirm' => '两次密码不一致',
    ];

    // 定义场景，供快捷编辑时验证
    protected $scene = [
        'mobile_login'          => ['mobile', 'loginpass'], // 会员手机号登录
        'email_login'           => ['email', 'loginpass'], // 会员邮箱登录
        'mobile_codelogin'      => ['mobile', 'code'], // 手机验证码登录
        'email_codelogin'       => ['email', 'code'], // 邮箱验证码登录 
        'mobile_register'       => ['mobile', 'code', 'loginpass', 'quepass'], //注册时验证
        'email_register'        => ['email', 'code', 'loginpass', 'quepass'], //注册时验证
        'mobile_forget'         => ['mobile', 'code', 'loginpass', 'quepass'], //忘记密码时验证
        'email_forget'          => ['email', 'code', 'loginpass', 'quepass'], //忘记密码时验证
        'changeLogin'           => ['oldpass', 'loginpass', 'quepass'], //修改登录密码时验证
        'mobile_changeMobile'   => ['mobile', 'code'], //修改手机号时验证
        'email_changeMobile'    => ['email', 'code'], //修改邮箱时验证
        'bindMobile'            => ['mobile', 'code', 'loginpass', 'quepass'], // 绑定手机号
        'user_add'              => ['mobile', 'loginpass', 'quepass'], //后台添加会员时验证
        'edit'                  => ['mobile'], //后台编辑会员时验证 
        'wxapp_login'           => ['code','rawData','signature','encryptedData','iv'], //微信小程序登录
        'setLogin'              => ['code', 'loginpass', 'quepass'], //设置登录密码
        'setTrade'              => ['code', 'tradepass', 'quetradepass'], //设置登录密码
    ];
}
