<?php
namespace app\system\model;

use think\Model;

class BannerModel extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $name = 'system_banner';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;
    public static function getPos(){
        return [
            'home'=>'首页'
        ];
    }

    public static function getLinkTypes(){
        return [
            'url' => '网页地址',
            'shop' => '店铺',
            'goods' => '商品',
        ];
    }


}
