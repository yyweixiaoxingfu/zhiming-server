<?php
namespace app\message\validate;


use think\Validate;

class MessageValidate extends Validate
{
    // 定义验证规则
    protected $rule = [
        'action|场景' => 'require|alphaDash|length:1,20',
        'driver|引擎' => 'require',
        'template|模板' => 'require',
        'desc|场景描述' => 'require',
        'keywords_desc|模板变量定义' => 'require',
        'id|ID' => 'require',
    ];
    protected $message = [

    ];

    protected $scene = [
        'template' => ['action','driver','template'],
        'add' => ['driver','action','desc','keywords_desc'],
        'edit' => ['id','desc','keywords_desc'],
    ];

}