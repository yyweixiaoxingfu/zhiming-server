<?php

namespace app\im\model;

use think\Model;

/**
 * IM群聊用户模型
 * @package app\im\model
 */
class GroupUser extends Model
{
    protected $pk = 'id';
    protected $table = 'w_im_group_user';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = false;

    /**
     * 获取群管理员列表
     * @param int $group_id 群组ID
     * @return array 管理员列表
     */
    public static function getGroupManage($group_id)
    {
        $list = self::where(['group_id' => $group_id, 'role' => ['in', [1, 2]]])->column('user_id, role');
        return $list;
    }

    /**
     * 更新群成员信息
     * @param array $update 更新数据
     * @param array $map 查询条件
     * @return int 更新条数
     */
    public static function editGroupUser($map, $update)
    {
        return self::where($map)->update($update);
    }
}
