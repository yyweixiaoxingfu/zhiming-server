<?php
//

namespace app\system\validate;

use think\Validate;


/**
 * 配置验证器
 * @package app\admin\validate
 * @author 
 */
class AnnounceValidate extends Validate
{
    // 定义验证规则
    protected $rule = [
        'name|公告名称'       => 'require',
        'detail|公告详情'         => 'require',
    ];
    protected $message = [
        'type.require'    => '请输入获取短信验证码场景',
    ];


}
