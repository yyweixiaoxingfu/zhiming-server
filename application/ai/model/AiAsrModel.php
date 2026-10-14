<?php
namespace app\ai\model;

use think\Model;

/**
 * 智能模型
 */
class AiAsrModel extends Model
{
     protected $name = 'ai_asr';
       // 自动写入时间戳
    protected $autoWriteTimestamp = true;
}