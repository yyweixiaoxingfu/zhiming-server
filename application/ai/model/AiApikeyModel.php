<?php
namespace app\ai\model;

use think\Model;

/**
 * 智能密钥地址配置
 */
class AiApikeyModel extends Model
{
      // 设置当前模型对应的完整数据表名称
    protected $name = 'ai_apikey';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;
}