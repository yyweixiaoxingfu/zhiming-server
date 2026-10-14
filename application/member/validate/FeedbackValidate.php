<?php
//

namespace app\member\validate;

use think\Validate;


/**
 * 配置验证器
 * @package app\admin\validate
 * @author 
 */
class FeedbackValidate extends Validate
{
    // 定义验证规则
    protected $rule = [
        'content|反馈内容'       => 'require',
    ];

}
