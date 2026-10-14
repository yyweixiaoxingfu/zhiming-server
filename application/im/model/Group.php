<?php

namespace app\im\model;

use think\Model;
use think\model\concern\SoftDelete;

/**
 * IM群组模型
 * @package app\im\model
 */
class Group extends Model
{
    use SoftDelete;
    protected $pk = 'group_id';
    protected $table = 'w_im_group';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = false;
    protected $deleteTime = 'delete_time';

    /**
     * 获取群组信息
     * @param array $map 查询条件
     * @return array 群组信息
     */
    public static function getGroupInfo($map = [])
    {
        $data = self::where($map)->find();
        if ($data) {
            $data = $data->toArray();
        }
        return $data;
    }

    /**
     * 获取我的群组列表
     * @param array $map 查询条件
     * @return array 群组列表
     */
    public static function getMyGroup($map = [])
    {
        return self::alias('g')
            ->join('yu_group_user gu', 'g.group_id = gu.group_id')
            ->where($map)
            ->field('g.*, gu.role, gu.is_notice, gu.is_top')
            ->select();
    }
}
