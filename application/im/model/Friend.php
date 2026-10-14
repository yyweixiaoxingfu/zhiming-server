<?php

namespace app\im\model;

use think\Model;

/**
 * IM好友模型
 * @package app\im\model
 */
class Friend extends Model
{
    protected $pk = 'friend_id';
    protected $table = 'w_im_friend';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    /**
     * 获取好友列表
     * @param array $map 查询条件
     * @return array 好友列表
     */
    public static function getFriend($map = [])
    {
        $list = self::where($map)->column('friend_user_id, nickname, is_top, is_notice');
        return $list;
    }

    /**
     * 获取好友信息
     * @param array $map 查询条件
     * @return array 好友信息
     */
    public static function getFriendInfo($map = [])
    {
        $data = self::where($map)->find();
        if ($data) {
            $data = $data->toArray();
        }
        return $data;
    }
}
