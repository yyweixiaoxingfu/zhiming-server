<?php

namespace app\ai\home;

use think\Controller;
use app\member\model\MemberModel;
use app\member\model\MemberCreditModel;
use app\index\controller\Api;
use app\ai\model\AiMoldsModel;
use app\ai\model\AiMainModel;
use think\helper\Hash;
use think\Db;

/**
 * @title 个人信息修改
 */
class Personal extends Controller
{
    public function check_user_quota()
    {

        $get_data = SafeFilter(input('get.'));
        if (!isset($get_data['userrndstr']) || empty($get_data['userrndstr'])) {
            return json(['success' => false, 'quota' => 0]);
        }
        $member = MemberModel::where('rndstr', $get_data['userrndstr'])->where('lock', 0)->find();
        if (empty($member)) {
            //echo '{"success":false,"quota":0}';
            return json(['success' => false, 'quota' => 0]);
        }
        // 从MemberCreditModel获取实际积分
        $quota      = MemberCreditModel::getCredit($member['id'], 'points', ['credit']);
        // 积分没有过期时间，返回空字符串
        $expiretime = '';
        if($member['vip_end_time']){
            $expiretime = date('Y-m-d H:i', $member['vip_end_time']);
        }
        return json(['success' => true, 'quota' => $quota, 'expiretime' => $expiretime]);
    }

    public function get_user_conversation()
    {
        $get_data = SafeFilter(input('get.'));
        if (!isset($get_data['userrndstr']) || empty($get_data['userrndstr'])) {
            return json(['success' => false, 'quota' => 0]);
        }
        $member = MemberModel::where('rndstr', $get_data['userrndstr'])->where('lock', 0)->find();
        if (empty($member)) {
            //echo '{"success":false,"quota":0}';
            return json(['success' => false]);
        }
        if ((isset($get_data["type"])) && ($get_data["type"] == "all")) {
            $more = intval($get_data["more"]) ?? 0;
            // 子查询：获取每个会话的最小ID和统计数量
            $subQuery = Db::name('ai_chathistory')
                ->where('ishidden', 0)
                ->where('userid', $member['id'])
                ->field('conversationid, MIN(id) as max_id, COUNT(userid)*2 as num')
                ->group('conversationid')
                ->order('max_id', 'desc')
                ->limit($more * 30, 30)
                ->buildSql();
            // 主查询：连接子查询获取完整数据
            $result = Db::name('ai_chathistory')->alias('t1')
                ->join([$subQuery => 't2'], 't1.conversationid = t2.conversationid AND t1.id = t2.max_id')
                ->field('t1.conversationid, t1.id, t1.title, t1.realtime, t2.num')
                ->group('t1.conversationid')
                ->order('t1.id', 'desc')
                ->select();
            
            if (empty($result)) {
                return json(['success' => true, 'conversation' => []]);
            }
            $questions = array();
            foreach ($result as $row) {
                $questions[] = array(
                    'id' => $row['conversationid'],
                    'title' => htmlspecialchars($row['title']),
                    'realtime' => $row['realtime'],
                    'num' => $row['num']
                );
            }
            
            return json(['success' => true, 'conversation' => $questions]);
        }
        if ((isset($get_data["type"])) && ($get_data["type"] == "single")) {
            //$result = $conn->select("chathistory", "*", ["userid" => $row["id"], "conversationid" => $_GET["conversationid"], "ORDER" => ["id" => "ASC"]]);
            $result = Db::name('ai_chathistory')->where('userid', $member['id'])->where('conversationid', $get_data['conversationid'])->order('id asc')->select();
            if (empty($result)) {
                return json(['success' => true, 'conversation' => []]);
            }
            $conversations = array();
            foreach ($result as $row) {
                if (!empty($row["modelid"])) {
                    $row2 = AiMoldsModel::where('id', $row["modelid"])->find();
                } else {
                    $row2 = AiMoldsModel::order('sequenceid asc,id asc')->find();
                }
                $conversations[] = array(
                    'id'        => $row['id'],
                    'title'     => htmlspecialchars($row['title']),
                    'question'  => htmlspecialchars($row['question']),
                    'model'     => $row2['modelname'],
                    'answer'    => $row['answer'],
                    'realtime'  => $row['realtime']
                );
            }
            return json(['success' => true, 'conversation' => $conversations]);
        }
    }

    public function user_info(){
        if($this->request->isPost()){
            $post_data = SafeFilter(input('post.'));
            $email          = $post_data['email'];
            if (empty($email)) {
                return json(['success' => false, 'message' => '请输入电子邮箱']);
            }
            $password       = $post_data['password'];
            $userrndstr     = $post_data['userrndstr'];
            $user_id        = $post_data['user_id'];
            $member = MemberModel::where('rndstr', $userrndstr)->where('lock', 0)->find();
            if (empty($member)) {
                return json(['success' => false, 'message' => '用户登录状态异常，请刷新网页再试']);
            }
            //$conn->has("user",["AND" => ["email" => $email,"rndstr[!]" => $userrndstr]])
            $users = MemberModel::where('email',$email)->where('id','<>',$user_id)->where('lock',0)->find();
            if(!empty($users)){
                return json(['success' => false, 'message' => '您输入的邮箱已被其他人绑定']);
            }
            $password      = htmlpurifier(stripslashes($password));
            $password      = MemberModel::setPasswordAttr($password);//加密
            $data = array(
                'email' => $email,
                'login_pwd' => $password,
                'update_time' => time(),
            );
            $result = MemberModel::where('id',$user_id)->update($data);
            if($result){
                return json(['success' => true, 'message' => '修改成功']);
            }else{
                return json(['success' => false, 'message' => '修改失败']);
            }
            
        }
        $userrndstr  = SafeFilter(input('userrndstr',''));
        $main_data   = AiMainModel::get(1);
        $member      = MemberModel::where('rndstr', $userrndstr)->where('lock', 0)->find();
        $this->assign('main_data',$main_data);
        $this->assign('member',$member);
        $this->assign('adminweixinid =',$main_data['adminweixinid']);
        $this->assign('userrndstr',$userrndstr);
        $this->assign('web_site_name',config('web_site_title'));
        return $this->fetch();
    }

    //
    public function show_log(){
        $get_data   = SafeFilter(input('get.'));
        $userrndstr = isset($get_data['userrndstr']) ? $get_data['userrndstr'] : '';
        $page       = isset($get_data['page']) ? intval($get_data['page']) : 1;
        $limit      = 5;
        $start      = ($page - 1) * $limit;
        $num        = $start;
        $member     = MemberModel::where('rndstr', $userrndstr)->where('lock', 0)->find();
        // $totalCount = Db::name('ai_chathistory')->where('userid',$member['id'])->where('ishidden',0)->order('id desc')->count();
        // $result     = Db::name('ai_chathistory')->where('userid',$member['id'])->where('ishidden',0)->order('id desc')->page($page,$limit)->select();
        $totalCount = Db::name('ai_chathistory')->where('userid',$member['id'])->order('id desc')->count();
        $result     = Db::name('ai_chathistory')->where('userid',$member['id'])->order('id desc')->page($page,$limit)->select();
        if (!empty($result)) {
            foreach ($result as &$row) {
                $num++;
                if (!empty($row["modelid"])) {
                    $row2 = AiMoldsModel::where('id',$row["modelid"])->find();
                    $row['modelname'] = $row2['modelname'];
                } else {
                    $row2 = AiMoldsModel::order('sequenceid asc,id asc')->find();
                    $row['modelname'] = $row2['modelname'];
                }
                $row['num'] = $num;
            }
        }
        $totalPage  = ceil($totalCount / $limit);
        $prev_page  = max(1, $page - 1); // 上一页
        $next_page  = min($totalPage, $page + 1); // 下一页
        $start_page = max(1, $page - 3); // 开始页码
        $end_page   = min($totalPage, $page + 3); // 结束页码

        $this->assign('member',$member);
        $this->assign('userrndstr',$userrndstr);
        $this->assign('web_site_name',config('web_site_title'));
        $this->assign('result',$result);

        $this->assign('totalCount',$totalCount);
        $this->assign('totalPage',$totalPage);
        $this->assign('page',$page);
        $this->assign('limit',$limit);
        $this->assign('prev_page',$prev_page);
        $this->assign('next_page',$next_page);
        $this->assign('start_page',$start_page);
        $this->assign('end_page',$end_page);
        return $this->fetch();
    }

    public function del_user_conversation(){
        $get_data   = SafeFilter(input('get.'));
        $userrndstr = isset($get_data['userrndstr']) ? $get_data['userrndstr'] : '';
        $id         = isset($get_data['conversationid']) ? intval($get_data['conversationid']) : 0;
        $member     = MemberModel::where('rndstr', $userrndstr)->where('lock', 0)->find();
        if ((isset($get_data["type"])) && ($get_data["type"] == "all")) {
            $res = Db::name('ai_chathistory')->where('userid',$member['id'])->update(['ishidden'=>1]);
            if($res){
                return json(['success' => true, 'message' => '删除成功']);
            }else{
                return json(['success' => false, 'message' => '找不到对话记录']);
            }
        }
        if ((isset($get_data["type"])) && ($get_data["type"] == "single")) {
            $res = Db::name('ai_chathistory')->where('userid',$member['id'])->where('conversationid',$id)->update(['ishidden'=>1]);
            if($res){
                return json(['success' => true, 'message' => '删除成功']);
            }else{
                return json(['success' => false, 'message' => '找不到对话记录']);
            }
        }
    }
}
