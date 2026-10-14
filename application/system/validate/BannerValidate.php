<?php
namespace app\system\validate;

use think\Validate;

class BannerValidate extends Validate
{


    public $rule = [
        'pic|轮播图'               => 'require',
        'pos|图片位置'             => 'require',
    ];

    protected $message = [

    ];


    // 定义场景，供快捷编辑时验证
    protected $scene = [
        'add'  => ['pic','pos'],
    ];


}
