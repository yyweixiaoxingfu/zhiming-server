<?php
//

namespace app\system\validate;

use think\Validate;


/**
 * 配置验证器
 * @package app\admin\validate
 * @author 
 */
class RegionValidate extends Validate
{
    public function __construct(array $rules = [], array $message = [], array $field = [])
    {
        parent::__construct($rules, $message, $field);
        $this->id = input('id',0);
        if ($this->id){
            $this->rule['code|地区编号'] = 'require|unique:system_region,code,'.$this->id.',id';
        }else{
            $this->rule['code|地区编号'] = 'require|unique:system_region,code';
        }
    }

    // 定义验证规则
    protected $rule = [
        'region|地区编号'       => 'require|exists:system_region,code',
        'name|地区名称' => 'require',
        'code|地区编号' => 'require|unique:system_region,code',
        'pid' => 'require|exists:system_region,id',
    ];

    protected $message = [
        'region.exists' => '地区编号错误',
        'pid.require' => '参数错误1',
        'pid.exists' => '参数错误2',
    ];



    // 定义场景，供快捷编辑时验证
    protected $scene = [
        'modify'       => ['region'],
        'backend_add'       => ['name','code','pid'],
        'backend_edit'       => ['name','code'],
    ];
}
