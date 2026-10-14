<?php

namespace app\im\model;

use think\Model;
use app\member\model\MemberModel;

/**
 * IM用户模型
 * 使用member表作为用户数据源
 * @package app\im\model
 */
class User extends Model
{
    // 不使用SoftDelete，因为member表没有delete_time字段
    protected $pk = 'id';
    protected $table = 'w_member';
    
    /**
     * 获取用户信息
     * @param array $map 查询条件
     * @return array 用户信息
     */
    public static function getUserInfo($map = [])
    {
        // 使用MemberModel获取用户信息
        $memberModel = new MemberModel();
        $data = $memberModel->where($map)->find();
        if ($data) {
            $data = $data->toArray();
            // 转换字段名，确保IM模块能正常使用
            $data['user_id'] = $data['id'];
            $data['realname'] = $data['name'];
            $data['avatar'] = $data['avatar'];
        }
        return $data;
    }

    /**
     * 获取用户列表
     * @param array $map 查询条件
     * @return array 用户列表
     */
    public static function getUserList($map = [])
    {
        // 使用MemberModel获取用户列表
        $memberModel = new MemberModel();
        $list = $memberModel->where($map)->select()->toArray();
        
        // 转换字段名，确保IM模块能正常使用
        foreach ($list as &$item) {
            $item['user_id'] = $item['id'];
            $item['realname'] = $item['name'];
            $item['avatar'] = $item['avatar'];
        }
        
        return $list;
    }
}
