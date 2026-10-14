<?php

namespace app\im\controller;

use app\im\model\Message;
use app\im\model\Group;
use app\im\model\GroupUser;
use app\im\model\Friend;
//use think\facade\Db;
use think\Db;
use think\facade\Env;

/**
 * IM测试控制器
 * @package app\im\controller
 */
class Test extends Base
{
    /**
     * 测试数据库连接
     * @return json
     */
    public function testDb()
    {
        try {
            // 测试消息表
            $messageCount = Message::count();
            // 测试群组表
            $groupCount = Group::count();
            // 测试群用户表
            $groupUserCount = GroupUser::count();
            // 测试好友表
            $friendCount = Friend::count();
            // 测试配置表
            $configCount = Db::name('w_im_config')->count();

            return json([
                'code' => 1,
                'msg' => '数据库连接成功',
                'data' => [
                    'message_count' => $messageCount,
                    'group_count' => $groupCount,
                    'group_user_count' => $groupUserCount,
                    'friend_count' => $friendCount,
                    'config_count' => $configCount
                ]
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 0,
                'msg' => '数据库连接失败: ' . $e->getMessage(),
                'data' => []
            ]);
        }
    }

    /**
     * 测试用户关联
     * @return json
     */
    public function testUser()
    {
        try {
            // 使用CS项目的member模块测试用户关联
            return json([
                'code' => 1,
                'msg' => '用户关联成功',
                'data' => [
                    'user_id' => $this->memberId,
                    'member_info' => $this->memberInfo
                ]
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 0,
                'msg' => '用户关联失败: ' . $e->getMessage(),
                'data' => []
            ]);
        }
    }

    
}
