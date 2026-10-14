<?php
namespace app\ai\model;
use think\Model;

/**
 * 智能模型
 */
class AiRoleModel extends Model
{
     protected $name = 'ai_role';
       // 自动写入时间戳
    protected $autoWriteTimestamp = true;
}