<?php
//

namespace app\system\validate;

use think\Validate;


/**
 * 配置验证器
 * @package app\admin\validate
 * @author 
 */
class HelpCenterValidate extends Validate
{
    // 定义验证规则
    protected $rule = [
        'set_id|帮助类型'         => 'require',
        'name|名称'       => 'require',
        'content|详情'         => 'require',
    ];
    protected $message = [

    ];

    protected $scene = [
        'set'       => ['name'],
        'help'       => ['set_id','name','content'],
    ];
}
