<?php
namespace app\ai\model;

use think\Model;

/**
 * 智能模型
 */
class AiTtsModel extends Model
{
     protected $name = 'ai_tts';
       // 自动写入时间戳
    protected $autoWriteTimestamp = true;
}