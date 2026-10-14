<?php
/**
 * GatewayWorker事件处理类
 */

namespace app\im\gateway;

use GatewayWorker\Lib\Gateway;
use think\facade\Db;
use think\facade\Cache;
use app\member\facade\JwtAuth;
use app\member\model\MemberModel;

class Events
{
    /**
     * 当客户端连接时触发
     * @param int $client_id 客户端ID
     * @param mixed $data 客户端连接时传递的数据
     */
    public static function onConnect($client_id)
    {
        // 连接成功，可以发送欢迎消息
        Gateway::sendToClient($client_id, json_encode([
            'type' => 'connect',
            'data' => ['client_id' => $client_id],
            'msg' => '连接成功'
        ]));
        
        // 更新在线统计
        self::onlineStatistics();
    }

    /**
     * 当客户端发送消息时触发
     * @param int $client_id 客户端ID
     * @param mixed $message 客户端发送的消息
     */
    public static function onMessage($client_id, $message)
    {
        try {
            $data = json_decode($message, true);
            if (!$data) {
                throw new \Exception('消息格式错误');
            }

            // 根据消息类型处理
            switch ($data['type']) {
                case 'login':
                    self::handleLogin($client_id, $data);
                    break;
                case 'chat':
                    self::handleChat($client_id, $data);
                    break;
                case 'read':
                    self::handleReadReceipt($client_id, $data);
                    break;
                case 'ping':
                    self::handlePing($client_id);
                    break;
                default:
                    throw new \Exception('未知消息类型');
            }
        } catch (\Exception $e) {
            Gateway::sendToClient($client_id, json_encode([
                'type' => 'error',
                'msg' => $e->getMessage()
            ]));
        }
    }

    /**
     * 处理已读回执
     */
    public static function handleReadReceipt($client_id, $data)
    {
        // 获取用户ID
        $user_id = Gateway::getUidByClientId($client_id);
        if (!$user_id) {
            return;
        }
        
        $chat_identify = $data['chat_identify'] ?? '';
        $last_message_id = $data['last_message_id'] ?? 0;
        
        if (!$chat_identify) {
            return;
        }
        
        try {
            // 更新消息已读状态
            $messageModel = new \app\im\model\Message();
            $where = [
                'chat_identify' => $chat_identify,
                'from_user' => ['neq', $user_id],
                'is_read' => 0
            ];
            
            if ($last_message_id > 0) {
                $where['id'] = ['elt', $last_message_id];
            }
            
            $messageModel->where($where)->update(['is_read' => 1]);
            
            // 如果是群聊，更新已读人数
            if (strpos($chat_identify, 'g') === 0) {
                $group_id = substr($chat_identify, 1);
                $messageModel->where([
                    'to_group' => $group_id,
                    'id' => ['elt', $last_message_id]
                ])->setInc('read_count');
            }
            
            // 通知发送者消息已读
            $from_user = $data['from_user'] ?? 0;
            if ($from_user > 0 && $from_user != $user_id) {
                Gateway::sendToUid($from_user, json_encode([
                    'type' => 'read_notify',
                    'data' => [
                        'user_id' => $user_id,
                        'chat_identify' => $chat_identify,
                        'last_message_id' => $last_message_id
                    ]
                ]));
            }
        } catch (\Exception $e) {
            // 记录错误日志
            error_log('已读回执处理失败: ' . $e->getMessage());
        }
    }

    /**
     * 处理登录
     * @param int $client_id 客户端ID
     * @param array $data 登录数据
     */
    private static function handleLogin($client_id, $data)
    {
        $token = $data['token'] ?? '';
        if (!$token) {
            throw new \Exception('Token不能为空');
        }

        // 验证Token
        $jwtAuth = JwtAuth::guard('default');
        $jwtToken = new \app\member\library\JwtToken();
        
        try {
            // 手动验证token
            $payload = $jwtToken->verifyToken($token);
            
            // 检查token是否在缓存中
            if (!cache($payload['jti'])) {
                throw new \Exception('Token已失效');
            }
            
            // 检查单点登录
            if(config('jwt.single')){
                $current_jti = cache('check_token_'.$payload['guard'].'_'.$payload['id']);
                if($payload['jti'] != $current_jti) {
                    throw new \Exception('Token已失效');
                }
            }
            
            // 获取用户信息
            $memberModel = new MemberModel();
            $userInfo = $memberModel->retrieveById($payload['id']);
            
            if (!$userInfo) {
                throw new \Exception('用户不存在');
            }
            
            $user_id = $userInfo['id'];
        } catch (\Exception $e) {
            throw new \Exception('Token无效: ' . $e->getMessage());
        }
        // 绑定用户ID和客户端ID
        Gateway::bindUid($client_id, $user_id);
        // 加入用户组
        Gateway::joinGroup($client_id, 'user_' . $user_id);
        
        // 获取用户所在的群组并加入对应的群组频道
        $groupIds = Db::name('w_im_group_user')
            ->where('user_id', $user_id)
            ->column('group_id');
        foreach ($groupIds as $groupId) {
            Gateway::joinGroup($client_id, 'group_' . $groupId);
        }

        // 保存用户信息
        Gateway::setSession($client_id, ['user_id' => $user_id, 'user_info' => $userInfo]);
        
        // 更新用户在线状态缓存
        Cache::set('im_user_online_' . $user_id, 1, 3600);
        
        // 获取用户的好友列表
        $friends = Db::name('w_im_friend')
            ->where('create_user', $user_id)
            ->column('friend_user_id');
        
        // 通知用户的好友其上线状态
        foreach ($friends as $friendId) {
            Gateway::sendToUid($friendId, json_encode([
                'type' => 'user_online',
                'data' => ['user_id' => $user_id],
                'msg' => '用户上线'
            ]));
        }

        // 检查是否多设备登录
        $clients = Gateway::getClientIdByUid($user_id);
        $is_multiport = count($clients) > 1;
        
        // 通知用户登录成功
        Gateway::sendToClient($client_id, json_encode([
            'type' => 'login_success',
            'data' => [
                'user_id' => $user_id, 
                'online_users' => self::getOnlineUsers($friends),
                'is_multiport' => $is_multiport
            ],
            'msg' => '登录成功'
        ]));
        
        // 记录登录日志（暂时注释，等待创建w_im_user_login_log表）
        // Db::name('w_im_user_login_log')->insert([
        //     'user_id' => $user_id,
        //     'ip' => '', // Gateway::getClientIp方法不存在，暂时留空
        //     'client_id' => $client_id,
        //     'login_time' => time()
        // ]);
    }

    /**
     * 处理聊天消息
     * @param int $client_id 客户端ID
     * @param array $data 聊天数据
     */
    private static function handleChat($client_id, $data)
    {
        $session = Gateway::getSession($client_id);
        if (!$session || !isset($session['user_id'])) {
            throw new \Exception('请先登录');
        }

        $user_id = $session['user_id'];
        $to_user = $data['to_user'] ?? 0;
        $is_group = $data['is_group'] ?? 0;
        $content = $data['content'] ?? '';
        $type = $data['msg_type'] ?? 'text';
        $at = $data['at'] ?? '';
        $pid = $data['pid'] ?? 0;

        if (!$content) {
            throw new \Exception('消息内容不能为空');
        }

        // 生成消息ID
        $msg_id = uniqid();
        $create_time = time();
        
        // 生成聊天标识
        // 群聊标识格式：group-群组ID
        // 单聊标识格式：小用户ID-大用户ID（确保唯一）
        if ($is_group == 1) {
            $chat_identify = 'group-' . $to_user;
        } else {
            $chat_identify = $user_id < $to_user ? $user_id . '-' . $to_user : $to_user . '-' . $user_id;
        }

        // 更新该聊天的最后一条消息状态为非最后一条
        Db::name('w_im_message')
            ->where('chat_identify', $chat_identify)
            ->update(['is_last' => 0]);

        // 构建消息数据
        $message_data = [
            'id' => $msg_id,                    // 消息唯一ID
            'from_user' => $user_id,            // 发送者ID
            'to_user' => $to_user,              // 接收者ID/群组ID
            'content' => $content,              // 消息内容
            'chat_identify' => $chat_identify,  // 聊天标识
            'type' => $type,                    // 消息类型
            'is_group' => $is_group,            // 是否群聊
            'is_read' => 0,                     // 是否已读
            'is_last' => 1,                     // 是否最后一条消息
            'create_time' => $create_time,      // 创建时间
            'is_undo' => 0,                     // 是否撤回
            'at' => $at,                        // @用户信息
            'pid' => $pid,                      // 父消息ID
            'status' => 1                       // 消息状态
        ];
        
        // 群聊添加已读人数信息
        if ($is_group == 1) {
            $message_data['read_count'] = 0;    // 初始已读人数为0
            $message_data['to_group'] = $to_user; // 群组ID
        }
        // 保存消息到数据库
        Db::name('w_im_message')->insert($message_data);
        
        // 如果是群聊，更新群成员未读数
        if ($is_group == 1) {
            // 获取群成员列表，排除发送者
            $members = Db::name('w_im_group_user')
                ->where('group_id', $to_user)
                ->where('user_id', '<>', $user_id)
                ->column('user_id');
            
            // 更新未读数
            foreach ($members as $memberId) {
                Db::name('w_im_group_user')
                    ->where(['group_id' => $to_user, 'user_id' => $memberId])
                    ->inc('unread')
                    ->update();
            }
        }

        // 发送消息
        if ($is_group == 1) {
            // 群聊消息
            Gateway::sendToGroup('group_' . $to_user, json_encode([
                'type' => 'chat',
                'data' => $message_data
            ]));
        } else {
            // 单聊消息
            Gateway::sendToUid($to_user, json_encode([
                'type' => 'chat',
                'data' => $message_data
            ]));
        }

        // 发送确认消息给发送者
        Gateway::sendToClient($client_id, json_encode([
            'type' => 'chat_confirm',
            'data' => ['msg_id' => $msg_id],
            'msg' => '发送成功'
        ]));
    }

    /**
     * 处理ping消息
     * @param int $client_id 客户端ID
     */
    public static function handlePing($client_id)
    {
        // 检查是否多设备登录
        $user_id = Gateway::getUidByClientId($client_id);
        $is_multiport = false;
        if ($user_id) {
            $clients = Gateway::getClientIdByUid($user_id);
            $is_multiport = count($clients) > 1;
        }
        
        Gateway::sendToClient($client_id, json_encode([
            'type' => 'pong',
            'timestamp' => time(),
            'is_multiport' => $is_multiport
        ]));
    }

    /**
     * 当客户端断开连接时触发
     * @param int $client_id 客户端ID
     */
    public static function onClose($client_id)
    {
        $session = Gateway::getSession($client_id);
        if ($session && isset($session['user_id'])) {
            $user_id = $session['user_id'];
            
            // 检查用户是否还有其他连接
            $clients = Gateway::getClientIdByUid($user_id);
            if (empty($clients)) {
                // 用户所有连接都断开了，更新在线状态
                Cache::set('im_user_online_' . $user_id, 0, 3600);
                
                // 获取用户的好友列表
                $friends = Db::name('w_im_friend')
                    ->where('create_user', $user_id)
                    ->column('friend_user_id');
                
                // 通知用户的好友其离线状态
                foreach ($friends as $friendId) {
                    Gateway::sendToUid($friendId, json_encode([
                        'type' => 'user_offline',
                        'data' => ['user_id' => $user_id],
                        'msg' => '用户离线'
                    ]));
                }
            }
            
            // 记录下线日志（暂时注释，等待创建w_im_user_login_log表）
            // Db::name('w_im_user_login_log')
            //     ->where(['user_id' => $user_id, 'client_id' => $client_id])
            //     ->update(['logout_time' => time()]);
        }
        
        // 更新在线统计
        self::onlineStatistics();
    }
    
    /**
     * 获取在线用户列表
     * @param array $user_ids 用户ID数组
     * @return array 在线用户ID数组
     */
    private static function getOnlineUsers($user_ids)
    {
        $onlineUsers = [];
        foreach ($user_ids as $userId) {
            if (Cache::get('im_user_online_' . $userId) == 1) {
                $onlineUsers[] = $userId;
            }
        }
        return $onlineUsers;
    }
    
    /**
     * 在线统计
     */
    private static function onlineStatistics()
    {
        // 通知后台在线用户数和在线设备数
        $data = [
            'type' => 'statistics',
            'time' => time(),
            'data' => [
                'onlineCount' => Gateway::getAllUidCount() ?? 0,
                'clientCount' => Gateway::getAllClientCount() ?? 0,
            ]
        ];
        
        // 发送给管理员组
        Gateway::sendToGroup('admin-manage', json_encode($data));
        // 发送给ID为1的用户（超级管理员）
        Gateway::sendToUid(1, json_encode($data));
    }
}
