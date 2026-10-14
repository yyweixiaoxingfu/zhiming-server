<?php

namespace app\message\home;

use app\index\controller\Api;
use app\member\model\MemberModel;
use app\message\library\MessageServer;
use app\message\model\MemberMessageModel;
use app\system\model\AnnounceModel;

/**
 * @title 会员消息
 * Class Index
 * @package app\message\home
 */
class Index extends Api
{
    protected $except = ['test'];

    /**
     * @title 用户消息首页
     * @author zwp
     * @url /message/index/index
     * @method POST
     * @header name:Authorization require:1 default:1455523e6d407a3c desc:token
     */
    public function index(){
        $member_id = $this->memberID;
        $announce = AnnounceModel::field('id,name,create_time')->order('sort asc,create_time desc')->find();
        $message = MemberMessageModel::where(['member_id' => $member_id])->field('id,type,create_time')->order('create_time desc,id desc')->find();
        $unread = MemberMessageModel::where(['member_id' => $member_id,'status'=>0])->count();
        return returnData(1,'',['announce' => $announce, 'message' => $message, 'unread_message' => $unread]);
    }

    /**
     * @title 用户消息列表
     * @author zwp
     * @url /message/index/messages
     * @method POST
     * @header name:Authorization require:1 default:1455523e6d407a3c desc:token
     */
    public function messages()
    {
        $member_id = $this->memberID;
        $logs = MemberMessageModel::where(['member_id' => $member_id])
            ->order('create_time desc,id desc')
            ->field('id,type,content,status,msg_type,msg_id,create_time')
            ->paginate()
            ->each(function ($item) {
                $item['create_time'] = empty($item['create_time']) ? '' : date('Y-m-d H:i', $item['create_time']);
                return $item;
            });
        return returnData(1, '', $logs);
    }

    /**
     * @title 消息详情
     * @param name:id type:string require:1 default: desc:消息id
     * @author zwp
     * @url /message/index/detail
     * @method POST
     * @header name:Authorization require:1 default:1455523e6d407a3c desc:token
     */
    public function detail()
    {
        $member_id = $this->memberID;
        $id = $this->request->param('id');
        if (empty($id)) {
            return returnData(0, '参数错误');
        }
        $info = MemberMessageModel::where(['member_id' => $member_id, 'id' => $id])->field('id,type,content,status,msg_type,msg_id,create_time')->find();
        if ($info['status'] == 0) {
            $info->save(['status' => 1, 'read_time' => time()]);
        }
        $info['create_time'] = empty($info['create_time']) ? '' : date('Y-m-d H:i', $info['create_time']);
        return returnData(1, '', $info);
    }

    /**
     * @title 标记已读
     * @param name:id type:string require:1 default: desc:消息id，多个id可以使用数组或者英文,隔开
     * @author zwp
     * @url /message/index/remarkRead
     * @method POST
     * @header name:Authorization require:1 default:1455523e6d407a3c desc:token
     */
    public function remarkRead()
    {
        $member_id = $this->memberID;
        $id = $this->request->param('id');
        if (empty($id)) {
            return returnData(0, '参数错误');
        }
        $res = MemberMessageModel::where(['member_id' => $member_id, 'status' => 0])
            ->where('id', 'in', $id)
            ->update(['status' => 1, 'read_time' => time()]);
        if ($res) {
            return returnData(1, '标记成功');
        } else {
            return returnData(0, '标记失败');
        }
    }

}