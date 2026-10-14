<?php

namespace app\im\controller;

use app\im\model\Message;
use app\im\model\Group;
use app\im\model\GroupUser;
use app\im\model\Friend;
use app\member\model\MemberModel;
use think\Db;
use think\facade\Cache;
use think\facade\Log;
use think\facade\Env;

/**
 * IM核心控制器
 * @package app\im\controller
 */
class Im extends Base
{
    protected $fileType = ['file', 'image', 'video', 'voice', 'emoji'];

    /**
     * 获取联系人列表
     * @return json
     */
    public function getContacts()
    {
        $user_id = $this->memberId;
        try {
            // 获取所有用户列表（根据CS项目的member模块）
            $memberModel = new MemberModel();
            $userList = $memberModel->where('id', '<>', $user_id)->select()->toArray();
            
            // 获取好友关系
            $friendList = Friend::where('create_user', $user_id)->column('friend_user_id, nickname, is_top, is_notice');
            // 处理联系人列表
            $contacts = [];
            foreach ($userList as $user) {
                $contact = [
                    'id'            => $user['id'],
                    'user_id'       => $user['id'],
                    'displayName'   => $friendList[$user['id']]['name'] ?? $user['name'],
                    'realname'      => $user['name'],
                    'name_py'       => $user['name'],
                    'avatar'        => $user['avatar'] ?? '',
                    'lastContent'   => '',
                    'unread'        => 0,
                    'lastSendTime'  => time() * 1000,
                    'is_group'      => 0,
                    'setting'       => [],
                    'is_at'         => 0,
                    'is_top'        => $friendList[$user['id']]['is_top'] ?? 0,
                    'is_notice'     => $friendList[$user['id']]['is_notice'] ?? 1,
                    'is_online'     => 0,
                    'location'      => ''
                ];
                $contacts[] = $contact;
            }
            
            return json(['code' => 1, 'msg' => 'success', 'data' => $contacts]);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '获取联系人列表失败: ' . $e->getMessage(), 'data' => []]);
        }
    }

    /**
     * 获取聊天列表
     * @return json
     */
    public function getChatList()
    {
        $user_id = $this->memberId;
        try {
            // 获取单聊聊天记录
            $singleChats = $this->getSingleChats($user_id);
            // 获取群聊聊天记录
            $groupChats = $this->getGroupChats($user_id);
            // 合并聊天列表
            $chatList = array_merge($singleChats, $groupChats);
            // 按最后发送时间排序
            usort($chatList, function($a, $b) {
                return $b['lastSendTime'] - $a['lastSendTime'];
            });
            
            return json(['code' => 1, 'msg' => 'success', 'data' => $chatList]);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '获取聊天列表失败: ' . $e->getMessage(), 'data' => []]);
        }
    }

    /**
     * 获取单聊聊天记录
     * @param int $user_id 用户ID
     * @return array 单聊聊天记录
     */
    private function getSingleChats($user_id)
    {
        // 查询最近的单聊消息
        $map1 = [['to_user', '=', $user_id], ['is_last', '=', 1], ['is_group', '=', 0]];
        $map2 = [['from_user', '=', $user_id], ['is_last', '=', 1], ['is_group', '=', 0]];
        $lastMsgList = Db::name('im_message')
            ->field('from_user,to_user,content as lastContent,create_time as lastSendTime,chat_identify,type,del_user')
            ->whereOr([$map1, $map2])
            ->order('create_time desc')
            ->select();
        
        // 获取好友关系
        $friendList = Friend::where('create_user', $user_id)->column('friend_user_id, nickname, is_top, is_notice');
        
        // 获取用户信息
        $memberModel = new MemberModel();
        $userList = $memberModel->column('id, name, avatar');
        
        // 处理单聊聊天记录
        $singleChats = [];
        foreach ($lastMsgList as $msg) {
            $contact_id = $msg['from_user'] == $user_id ? $msg['to_user'] : $msg['from_user'];
            $user = $userList[$contact_id] ?? [];
            if (empty($user)) continue;
            
            $singleChats[] = [
                'id' => $contact_id,
                'user_id' => $contact_id,
                'displayName' => $friendList[$contact_id]['nickname'] ?? $user['name'],
                'realname' => $user['name'],
                'name_py' => $user['name'],
                'avatar' => $user['avatar'] ?? '',
                'lastContent' => $msg['lastContent'] ?? '',
                'unread' => 0,
                'lastSendTime' => $msg['lastSendTime'] * 1000,
                'is_group' => 0,
                'setting' => [],
                'is_at' => 0,
                'is_top' => $friendList[$contact_id]['is_top'] ?? 0,
                'is_notice' => $friendList[$contact_id]['is_notice'] ?? 1,
                'is_online' => 0,
                'location' => ''
            ];
        }
        
        return $singleChats;
    }

    /**
     * 获取群聊聊天记录
     * @param int $user_id 用户ID
     * @return array 群聊聊天记录
     */
    private function getGroupChats($user_id)
    {
        // 获取用户所在的群组
        $groupUserList = GroupUser::where('user_id', $user_id)->column('group_id, role, is_notice, is_top, unread');
        $groupIds = array_keys($groupUserList);
        if (empty($groupIds)) return [];
        
        // 获取群组信息
        $groups = Group::where('group_id', 'in', $groupIds)->select()->toArray();
        
        // 查询群组最近消息
        $lastMsgList = Db::name('im_message')
            ->field('to_user,content as lastContent,create_time as lastSendTime,chat_identify,type,del_user')
            ->where([['to_user', 'in', $groupIds], ['is_group', '=', 1], ['is_last', '=', 1]])
            ->select();
        $lastMsgList = array_column($lastMsgList, null, 'to_user');
        
        // 处理群聊聊天记录
        $groupChats = [];
        foreach ($groups as $group) {
            $group_id = 'group-' . $group['group_id'];
            $lastMsg = $lastMsgList[$group['group_id']] ?? [];
            
            $groupChats[] = [
                'id' => $group_id,
                'group_id' => $group['group_id'],
                'displayName' => $group['name'],
                'realname' => $group['name'],
                'name_py' => $group['name_py'] ?? $group['name'],
                'avatar' => $group['avatar'] ?? '',
                'lastContent' => $lastMsg['lastContent'] ?? '',
                'unread' => $groupUserList[$group['group_id']]['unread'] ?? 0,
                'lastSendTime' => $lastMsg['lastSendTime'] * 1000 ?? time() * 1000,
                'is_group' => 1,
                'setting' => [],
                'is_at' => 0,
                'is_top' => $groupUserList[$group['group_id']]['is_top'] ?? 0,
                'is_notice' => $groupUserList[$group['group_id']]['is_notice'] ?? 1,
                'is_online' => 1,
                'location' => ''
            ];
        }
        
        return $groupChats;
    }

    /**
     * 发送消息
     * @return json
     */
    public function sendMessage()
    {
        $param              = $this->request->param();
        $user_id            = $this->memberId;
        $param['user_id']   = $user_id;
        
        try {
            $is_group    = $param['is_group'] ?? 0;
            $toContactId = $param['toContactId'];
            
            // 生成聊天标识
            if ($is_group == 1) {
                $group_id = explode('-', $toContactId)[1] ?? 0;
                $chat_identify = $toContactId;
                $to_user = $group_id;
            } else {
                $chat_identify = $this->chat_identify($user_id, $toContactId);
                $to_user = $toContactId;
            }
            
            // 准备消息数据
            $messageData = [
                'id' => uniqid(),
                'from_user' => $user_id,
                'to_user' => $to_user,
                'content' => $param['content'] ?? '',
                'chat_identify' => $chat_identify,
                'type' => $param['type'] ?? 'text',
                'is_group' => $is_group,
                'is_read' => 0, // 初始为未读状态
                'is_last' => 1,
                'create_time' => time(),
                'is_undo' => 0,
                'at' => $param['at'] ?? '',
                'pid' => $param['pid'] ?? 0,
                'file_id' => $param['file_id'] ?? 0,
                'file_cate' => $param['file_cate'] ?? 0,
                'file_size' => $param['file_size'] ?? 0,
                'file_name' => $param['file_name'] ?? '',
                'extends' => $param['extends'] ?? null,
                'status' => 1
            ];
            
            // 对于群聊，添加已读人数字段
            if ($is_group == 1) {
                $messageData['read_count'] = 0; // 已读人数初始化为0
            }
            
            // 保存消息
            $message = new Message();
            $message->update(['is_last' => 0], ['chat_identify' => $chat_identify]);
            $message->save($messageData);
            
            // 更新群成员未读数量
            if ($is_group == 1) {
                GroupUser::where(['group_id' => $group_id, 'user_id' => ['<>', $user_id]])->update(['unread' => Db::raw('unread+1')]);
                // 尝试通过WebSocket发送消息
                try {
                    $this->sendWebSocketMessage($to_user, $messageData, true);
                } catch (\Exception $e) {
                    // WebSocket发送失败不影响HTTP响应
                     Log::error('WebSocket发送失败: ' . $e->getMessage());
                }
            } else {
                // 尝试通过WebSocket发送消息
                try {
                    $this->sendWebSocketMessage($to_user, $messageData, false);
                } catch (\Exception $e) {
                    // WebSocket发送失败不影响HTTP响应
                     Log::error('WebSocket发送失败: ' . $e->getMessage());
                }
            }
            
            return json(['code' => 1, 'msg' => '发送成功', 'data' => $messageData]);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '发送消息失败: ' . $e->getMessage(), 'data' => []]);
        }
    }
    
    /**
     * 测试发送消息
     * @return json
     */
    public function testSendMessage()
    {
        // 测试用的参数
        $param = [
            'toContactId' => 2, // 接收者ID
            'content' => '测试消息', // 消息内容
            'type' => 'text', // 消息类型
            'is_group' => 0 // 0表示单聊，1表示群聊
        ];
        
        // 模拟用户ID
        $user_id = 1;
        
        try {
            $is_group    = $param['is_group'] ?? 0;
            $toContactId = $param['toContactId'];
            
            // 生成聊天标识
            if ($is_group == 1) {
                $group_id = explode('-', $toContactId)[1] ?? 0;
                $chat_identify = $toContactId;
                $to_user = $group_id;
            } else {
                $chat_identify = $this->chat_identify($user_id, $toContactId);
                $to_user = $toContactId;
            }
            
            // 准备消息数据
            $messageData = [
                'id' => uniqid(),
                'from_user' => $user_id,
                'to_user' => $to_user,
                'content' => $param['content'] ?? '',
                'chat_identify' => $chat_identify,
                'type' => $param['type'] ?? 'text',
                'is_group' => $is_group,
                'is_read' => 0, // 初始为未读状态
                'is_last' => 1,
                'create_time' => time(),
                'is_undo' => 0,
                'at' => $param['at'] ?? '',
                'pid' => $param['pid'] ?? 0,
                'file_id' => $param['file_id'] ?? 0,
                'file_cate' => $param['file_cate'] ?? 0,
                'file_size' => $param['file_size'] ?? 0,
                'file_name' => $param['file_name'] ?? '',
                'extends' => $param['extends'] ?? null,
                'status' => 1
            ];
            
            // 对于群聊，添加已读人数字段
            if ($is_group == 1) {
                $messageData['read_count'] = 0; // 已读人数初始化为0
            }
            
            // 保存消息
            $message = new Message();
            $message->update(['is_last' => 0], ['chat_identify' => $chat_identify]);
            $message->save($messageData);
            
            // 更新群成员未读数量
            if ($is_group == 1) {
                GroupUser::where(['group_id' => $group_id, 'user_id' => ['<>', $user_id]])->update(['unread' => Db::raw('unread+1')]);
                // 尝试通过WebSocket发送消息
                try {
                    $this->sendWebSocketMessage($to_user, $messageData, true);
                } catch (\Exception $e) {
                    // WebSocket发送失败不影响HTTP响应
                     Log::error('WebSocket发送失败: ' . $e->getMessage());
                }
            } else {
                // 尝试通过WebSocket发送消息
                try {
                    $this->sendWebSocketMessage($to_user, $messageData, false);
                } catch (\Exception $e) {
                    // WebSocket发送失败不影响HTTP响应
                     Log::error('WebSocket发送失败: ' . $e->getMessage());
                }
            }
            
            return json(['code' => 1, 'msg' => '测试发送成功', 'data' => $messageData]);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '测试发送消息失败: ' . $e->getMessage(), 'data' => []]);
        }
    }
    
    /**
     * 通过WebSocket发送消息
     * @param int $to_user 接收者ID
     * @param array $messageData 消息数据
     * @param bool $is_group 是否群聊
     */
    private function sendWebSocketMessage($to_user, $messageData, $is_group = false)
    {
        // 尝试使用GatewayClient发送WebSocket消息
        try {
            // 导入GatewayClient
            require_once Env::get('app_path') . 'im/library/GatewayClient.php';
            
            // 配置GatewayClient
            \app\im\library\GatewayClient::setRegisterAddress(config('gateway.registerAddress'));
            
            // 构建WebSocket消息
            $wsMessage = json_encode([
                'type' => 'chat',
                'data' => $messageData
            ]);
            
            // 发送消息
            if ($is_group) {
                \app\im\library\GatewayClient::sendToGroup('group_' . $to_user, $wsMessage);
            } else {
                \app\im\library\GatewayClient::sendToUid($to_user, $wsMessage);
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * 标记消息为已读
     */
    public function markMessageRead()
    {
        $user_id = $this->request->post('user_id');
        $chat_identify = $this->request->post('chat_identify');
        $last_message_id = $this->request->post('last_message_id', 0);
        
        if (!$user_id || !$chat_identify) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }
        
        try {
            // 更新消息已读状态
            $messageModel = new Message();
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
            
            return json(['code' => 0, 'msg' => '标记成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '标记失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 获取未读消息数量
     */
    public function getUnreadCount()
    {
        $user_id = $this->request->post('user_id');
        
        if (!$user_id) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }
        
        try {
            // 获取用户所有未读消息总数
            $messageModel = new Message();
            $totalUnread = $messageModel->where([
                ['to_user', '=', $user_id],
                ['is_read', '=', 0]
            ])->count();
            
            // 获取每个聊天的未读消息数
            $unreadList = $messageModel->field('chat_identify, count(*) as count')
                ->where([
                    ['to_user', '=', $user_id],
                    ['is_read', '=', 0]
                ])
                ->group('chat_identify')
                ->select();
            
            $unreadMap = [];
            foreach ($unreadList as $item) {
                $unreadMap[$item['chat_identify']] = $item['count'];
            }
            
            return json([
                'code' => 0,
                'data' => [
                    'total' => $totalUnread,
                    'list' => $unreadMap
                ]
            ]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '获取失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 获取聊天记录
     * @return json
     */
    public function getMessageList()
    {
        $param = $this->request->param();
        $is_group = isset($param['is_group']) ? $param['is_group'] : 0;
        $toContactId = $param['toContactId'];
        $user_id = $this->memberId;
        
        try {
            // 生成聊天标识
            if ($is_group == 1) {
                $group_id = explode('-', $toContactId)[1] ?? 0;
                $chat_identify = $toContactId;
            } else {
                $chat_identify = $this->chat_identify($user_id, $toContactId);
            }
            
            // 设置当前聊天消息为已读
            $this->setIsRead($is_group, $toContactId, $user_id);
            
            // 查询聊天记录
            $listRows = $param['limit'] ?? 20;
            $pageSize = $param['page'] ?? 1;
            $last_id = $param['last_id'] ?? 0;
            
            $map = ['chat_identify' => $chat_identify, 'status' => 1];
            $where = [];
            if ($last_id) {
                $where[] = ['msg_id', '<', $last_id];
                $pageSize = 1;
            }
            
            $list = Message::where($map)->where($where)->order('msg_id desc')->paginate(['list_rows' => $listRows, 'page' => $pageSize]);
            $messages = $list->toArray()['data'];
            
            // 获取用户信息
            $memberModel = new MemberModel();
            $userList = $memberModel->column('id, name, avatar');
            
            // 处理聊天记录
            $data = [];
            foreach ($messages as $msg) {
                $fromUser = $userList[$msg['from_user']] ?? [];
                $data[] = [
                    'msg_id' => $msg['msg_id'],
                    'id' => $msg['id'],
                    'status' => 'succeed',
                    'type' => $msg['type'],
                    'sendTime' => $msg['create_time'] * 1000,
                    'content' => $msg['content'],
                    'preview' => '',
                    'download' => '',
                    'is_read' => $msg['is_read'],
                    'is_group' => $msg['is_group'],
                    'at' => $msg['at'] ? explode(',', $msg['at']) : [],
                    'toContactId' => $msg['is_group'] == 1 ? $chat_identify : $msg['to_user'],
                    'from_user' => $msg['from_user'],
                    'file_id' => $msg['file_id'],
                    'file_cate' => $msg['file_cate'],
                    'fileName' => $msg['file_name'],
                    'fileSize' => $msg['file_size'],
                    'fromUser' => [
                        'id' => $msg['from_user'],
                        'realname' => $fromUser['name'] ?? '',
                        'avatar' => $fromUser['avatar'] ?? ''
                    ],
                    'extUrl' => '',
                    'role' => 3,
                    'extends' => $msg['extends']
                ];
            }
            
            return json(['code' => 1, 'msg' => 'success', 'data' => array_reverse($data), 'total' => $list->total()]);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '获取聊天记录失败: ' . $e->getMessage(), 'data' => []]);
        }
    }

    /**
     * 设置消息已读
     * @param int $is_group 是否群聊
     * @param string $to_user 接收者
     * @param int $user_id 当前用户ID
     * @return string 聊天标识
     */
    protected function setIsRead($is_group, $to_user, $user_id)
    {
        if ($is_group == 1) {
            $group_id = explode('-', $to_user)[1] ?? 0;
            $chat_identify = $to_user;
            // 更新群成员未读数量
            GroupUser::where(['group_id' => $group_id, 'user_id' => $user_id])->update(['unread' => 0]);
        } else {
            $chat_identify = $this->chat_identify($user_id, $to_user);
            // 更新单聊消息已读状态
            Message::where(['chat_identify' => $chat_identify, 'to_user' => $user_id, 'is_read' => 0])->update(['is_read' => 1]);
        }
        return $chat_identify;
    }

    /**
     * 生成聊天标识
     * @param int $user_id 用户ID
     * @param int $to_user 接收者ID
     * @return string 聊天标识
     */
    protected function chat_identify($user_id, $to_user)
    {
        return $user_id < $to_user ? $user_id . '-' . $to_user : $to_user . '-' . $user_id;
    }

    /**
     * 设置消息已读
     * @return json
     */
    public function setMsgIsRead()
    {
        $param = $this->request->param();
        $is_group = $param['is_group'] ?? 0;
        $toContactId = $param['toContactId'];
        $user_id = $this->memberId;
        
        try {
            $this->setIsRead($is_group, $toContactId, $user_id);
            return json(['code' => 1, 'msg' => '设置成功']);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '设置失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 撤回消息
     * @return json
     */
    public function undoMessage()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $user_id = $this->memberId;
        
        try {
            $message = Message::where('id', $id)->find();
            if (empty($message)) {
                return json(['code' => 0, 'msg' => '消息不存在']);
            }
            
            // 检查权限
            if ($message['from_user'] != $user_id) {
                return json(['code' => 0, 'msg' => '没有权限撤回该消息']);
            }
            
            // 检查撤回时间限制（2分钟）
            if (time() - $message['create_time'] > 120) {
                return json(['code' => 0, 'msg' => '消息超过2分钟，无法撤回']);
            }
            
            // 更新消息
            $message->content = '撤回了一条消息';
            $message->type = 'event';
            $message->is_undo = 1;
            $message->at = '';
            $message->save();
            
            return json(['code' => 1, 'msg' => '撤回成功']);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '撤回失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 删除消息
     * @return json
     */
    public function removeMessage()
    {
        $param      = $this->request->param();
        $id         = $param['id'];
        $user_id    = $this->memberId;
        
        try {
            $message = Message::where('id', $id)->find();
            if (empty($message)) {
                return json(['code' => 0, 'msg' => '消息不存在']);
            }
            
            // 更新消息删除状态
            if ($message['is_group'] == 1) {
                // 群聊消息，记录删除用户
                $delUser = $message['del_user'] ? explode(',', $message['del_user']) : [];
                if (!in_array($user_id, $delUser)) {
                    $delUser[] = $user_id;
                    $message->del_user = implode(',', $delUser);
                    $message->save();
                }
            } else {
                // 单聊消息，直接删除
                $message->delete();
            }
            
            return json(['code' => 1, 'msg' => '删除成功']);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '删除失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 消息免打扰
     * @return json
     */
    public function isNotice()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $is_notice = $param['is_notice'] ?? 1;
        $is_group = $param['is_group'] ?? 0;
        $user_id = $this->memberId;
        
        try {
            if ($is_group == 1) {
                // 群聊免打扰
                $group_id = explode('-', $id)[1] ?? 0;
                GroupUser::where(['user_id' => $user_id, 'group_id' => $group_id])->update(['is_notice' => $is_notice]);
            } else {
                // 单聊免打扰
                $friend = Friend::where(['create_user' => $user_id, 'friend_user_id' => $id])->find();
                if ($friend) {
                    $friend->is_notice = $is_notice;
                    $friend->save();
                } else {
                    Friend::create([
                        'create_user' => $user_id,
                        'friend_user_id' => $id,
                        'is_notice' => $is_notice
                    ]);
                }
            }
            
            return json(['code' => 1, 'msg' => '设置成功']);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '设置失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 设置聊天置顶
     * @return json
     */
    public function setChatTop()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $is_top = $param['is_top'] ?? 1;
        $is_group = $param['is_group'] ?? 0;
        $user_id = $this->memberId;
        
        try {
            if ($is_group == 1) {
                // 群聊置顶
                $group_id = explode('-', $id)[1] ?? 0;
                GroupUser::where(['user_id' => $user_id, 'group_id' => $group_id])->update(['is_top' => $is_top]);
            } else {
                // 单聊置顶
                $friend = Friend::where(['create_user' => $user_id, 'friend_user_id' => $id])->find();
                if ($friend) {
                    $friend->is_top = $is_top;
                    $friend->save();
                } else {
                    Friend::create([
                        'create_user' => $user_id,
                        'friend_user_id' => $id,
                        'is_top' => $is_top
                    ]);
                }
            }
            
            return json(['code' => 1, 'msg' => '设置成功']);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '设置失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 删除聊天
     * @return json
     */
    public function delChat()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $is_group = $param['is_group'] ?? 0;
        $user_id = $this->memberId;
        
        try {
            // 这里可以添加聊天删除逻辑
            return json(['code' => 1, 'msg' => '删除成功']);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '删除失败: ' . $e->getMessage()]);
        }
    }
}
