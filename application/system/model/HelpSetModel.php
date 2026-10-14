<?php
//

namespace app\system\model;

use think\Model;
/**
 * 商品分类模型
 * @package app\admin\model
 */
class HelpSetModel extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $name = 'system_help_set';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;
    public function helpCenter()
    {
        return $this->hasMany('HelpCenterModel','set_id','id');
    }
    public static function getSetList(){
        return self::column('name','id');
    }
}
