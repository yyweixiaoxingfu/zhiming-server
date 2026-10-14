<?php
 

namespace app\admin\model;

use think\Model;

/**
 * 后台配置模型
 * @package app\admin\model
 */
class SpNewsModel extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $name = 'sp_news';
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;
}