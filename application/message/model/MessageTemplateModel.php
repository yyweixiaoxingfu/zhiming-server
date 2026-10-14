<?php


namespace app\message\model;


use think\Model;

class MessageTemplateModel extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $name = 'message_template';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    public static function getTemplate($action, $driver)
    {
        return self::where(['action' => $action, 'driver' => $driver])->find();
    }

    public function setTemplate($data)
    {
        $info = $this->where(['action' => $data['action'], 'driver' => $data['driver']])->find();
        if (empty($info)) {
            $res = $this->create($data);
        } else {
            $res = $info->save($data);
        }
        return $res;
    }
}