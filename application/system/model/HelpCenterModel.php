<?php
//

namespace app\system\model;

use think\Model;
/**
 * 商品分类模型
 * @package app\admin\model
 */
class HelpCenterModel extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $name = 'system_help_center';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

}
