<?php
namespace app\ai\model;

use think\Model;

/**
 * 智能模型
 */
class AiSceneModel extends Model
{
     protected $name = 'ai_scene';
       // 自动写入时间戳
    protected $autoWriteTimestamp = true;
}