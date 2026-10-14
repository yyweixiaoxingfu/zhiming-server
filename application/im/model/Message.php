<?php

namespace app\im\model;

use think\Model;

/**
 * IM消息模型
 * @package app\im\model
 */
class Message extends Model
{
    protected $pk = 'msg_id';
    protected $table = 'w_im_message';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = false;
    protected $json = ['extends'];
    protected $jsonAssoc = true;

    /**
     * 添加聊天记录
     * @param array $data 消息数据
     * @return int 消息ID
     */
    public static function addData($data)
    {
       return self::insertGetId($data);
    }

    /**
     * 更新消息状态
     * @param array $update 更新数据
     * @param array $map 查询条件
     * @return int 更新条数
     */
    public static function editData($update, $map)
    {
        return self::where($map)->update($update);
    }

    /**
     * 查询聊天记录
     * @param array $map 查询条件
     * @param array $where 额外条件
     * @param string $sort 排序
     * @param int $listRows 每页条数
     * @param int $pageSize 当前页码
     * @return array 聊天记录
     */
    public static function getList($map, $where, $sort, $listRows, $pageSize)
    {
        $list = self::where($map)
            ->where($where)
            ->order($sort)
            ->paginate(['list_rows' => $listRows, 'page' => $pageSize]);
        return $list;
    }
}
