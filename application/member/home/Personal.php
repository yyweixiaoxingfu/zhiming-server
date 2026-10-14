<?php

namespace app\member\home;


use app\member\model\FeedbackModel;
use app\member\model\MemberModel;
use app\index\controller\Api;
use app\member\validate\FeedbackValidate;
use app\message\library\MessageServer;
use think\helper\Hash;
use app\admin\model\SpExpertModel;

/**
 * @title 个人信息修改
 */
class Personal extends Api
{
    /**
     * @title 设置登录密码
     * @method POST
     * @url /member/personal/setLoginPass
     * @header name:Authorization require:1 default:1455523e6d407a3c desc:token
     * @param name:code type:string require:1 default: desc:短信验证码
     * @param name:loginpass type:string require:1 default: desc:登录密码
     * @param name:quepass type:string require:1 default: desc:确认密码
     */
    public function setLoginPass()
    {
        if ($this->request->isPost()) {
            $POST   = SafeFilter(input('post.'));
            $member = $this->memberInfo;
            $result = $this->validate($POST, 'MemberValidate.setLogin');
            if (true !== $result) {
                return returnData(0, $result);
            }
            $code = SafeFilter($POST['code']);
            $checkSmsRes  = app(MessageServer::class)->checkVerifyCode($member['mobile'],'setlogin',$code);
            if($checkSmsRes == 0){
                return returnData(0,'短信验证码有误');
            }
            $newPassword = htmlpurifier(stripslashes($POST['loginpass']));
            $newPassword = MemberModel::setPasswordAttr($newPassword);//加密
            $res         = $member->save(array('login_pwd' => $newPassword));
            if ($res) {
                return returnData(1, '设置成功!');
            } else {
                return returnData(0, '设置失败!');
            }
        }
    }
    /**
     * @title 设置支付密码
     * @method POST
     * @url /member/personal/setTradePass
     * @header name:Authorization require:1 default:1455523e6d407a3c desc:token
     * @param name:code type:string require:1 default: desc:短信验证码
     * @param name:tradepass type:string require:1 default: desc:登录密码
     * @param name:quetradepass type:string require:1 default: desc:确认密码
     */
    public function setTradePass()
    {
        if ($this->request->isPost()) {
            $POST   = SafeFilter(input('post.'));
            $member = $this->memberInfo;
            $result = $this->validate($POST, 'MemberValidate.setTrade');
            if (true !== $result) {
                return returnData(0, $result);
            }
            $code = SafeFilter($POST['code']);
            $checkSmsRes  = app(MessageServer::class)->checkVerifyCode($member['mobile'],'settrade',$code);
            if($checkSmsRes == 0){
                return returnData(0,'短信验证码有误');
            }
            $newPassword = htmlpurifier(stripslashes($POST['tradepass']));
            $newPassword = MemberModel::setPasswordAttr($newPassword);//加密
            $res         = $member->save(array('trade_pwd' => $newPassword));
            if ($res) {
                return returnData(1, '设置成功!');
            } else {
                return returnData(0, '设置失败!');
            }
        }
    }
    /**
     * @title 修改登录密码
     * @method POST
     * @url /member/personal/changePass
     * @header name:Authorization require:1 default:1455523e6d407a3c desc:token
     * @param name:oldpass type:string require:1 default: desc:原密码
     * @param name:loginpass type:string require:1 default: desc:登录密码
     * @param name:quepass type:string require:1 default: desc:确认密码
     */
    public function changePass()
    {
        if ($this->request->isPost()) {
            $POST   = SafeFilter(input('post.'));
            $member = $this->memberInfo;
            $result = $this->validate($POST, 'MemberValidate.changeLogin');
            if (true !== $result) {
                return returnData(0, $result);
            }
            $oldPassword = htmlpurifier(stripslashes($POST['oldpass']));
            if (!Hash::check((string)$oldPassword, $member['login_pwd'])) {
                return json(array('code' => 0, 'msg' => '原密码错误!'));
            }
            $newPassword = htmlpurifier(stripslashes($POST['loginpass']));
            $newPassword = MemberModel::setPasswordAttr($newPassword);//加密
            $res         = $member->save(array('login_pwd' => $newPassword));
            if ($res) {
                return returnData(1, '修改成功!');
            } else {
                return returnData(0, '修改失败!');
            }
        }
    }

    /**
     * @title 提交意见反馈
     * @method POST
     * @url /member/personal/feedback
     * @header name:Authorization require:1 default:1455523e6d407a3c desc:token
     * @param name:content type:string require:1 default: desc:反馈内容
     */
    public function feedback()
    {
        if ($this->request->isPost()) {
            $POST      = SafeFilter(input('post.'));
            $member_id = $this->memberID;
            $validate  = new FeedbackValidate();
            if (!$validate->check($POST)) {
                return returnData(0, $validate->getError());
            }
            $POST['member_id'] = $member_id;
            $res               = FeedbackModel::create($POST);
            if ($res) {
                return returnData(1, '提交成功!');
            } else {
                return returnData(0, '提交失败!');
            }
        }
    }

    /**
     * @title 个人资料
     * @method POST
     * @url /member/personal/material
     * @header name:Authorization require:1 default:1455523e6d407a3c desc:token
     */
    public function material()
    {
        if ($this->request->isPost()) {
            $member           = $this->memberInfo;
            $member['avatar'] = getAvatar($member['avatar']);
            $my_expert        = SpExpertModel::where('user_id',$member['id'])->field('name,mobile,cover,cid,class_ids,desc,weichat_code,images,label,price,content,review_status')->find();
            $member['is_expert'] = 0;
            $member['expert_info'] = [];
            if(!empty($my_expert) && $my_expert['review_status'] == 1){
                $member['is_expert'] = 1;
                unset($my_expert['review_status']);
                $member['expert_info'] = $my_expert;
            }
            return returnData(1, '', ['data' => $member]);
        }
    }

    /**
     * @title 资料修改
     * @method POST
     * @url /member/personal/changeInfo
     * @header name:Authorization require:1 default:1455523e6d407a3c desc:token
     * @param name:avatar type:string require:0 default: desc:头像
     * @param name:name type:string require:0 default: desc:昵称
     */
    public function changeInfo()
    {
        if ($this->request->isPost()) {
            $member = $this->memberInfo;
            //$POST   = $this->request->post('', null, 'SafeFilterItem');
            $POST   = SafeFilter(input('post.'));
            if (empty($POST['avatar']) && empty($POST['name']) && empty($POST['sex']) && empty($POST['account'])) {
                return returnData(0, '请选择修改内容');
            }
            if (!empty($POST['avatar'])) {
                $arr['avatar'] = $POST['avatar'];
            }
            if (!empty($POST['name'])) {
                $arr['name'] = $POST['name'];
            }
            if(isset($POST['sex']) && $POST['sex']!=''){
                $arr['sex'] = $POST['sex'];
            }
            if(isset($POST['account']) && $POST['account']!=''){
                // 正则验证账号：只能包含字母、数字和下划线，不能有空格，长度大于6位
                // 注意：当前正则允许纯数字账号通过
                $account =  trim($POST['account']);
                if(!preg_match('/^(?=.*[a-zA-Z_])[a-zA-Z0-9_]{6,}$/', $account)){
                    return returnData(0,'账号格式不正确，只能包含字母、数字和下划线，不能有空格，长度必须大于6位');
                }
                $is_account = MemberModel::where('id','<>',$member['id'])->where('account',$account)->find();
                if(!empty($is_account)){
                        return returnData(0,'账号已存在');
                }
                $arr['account'] = $account;
            }
            $res = $member->save($arr);
            if ($res) {
                return returnData(1, '修改成功');
            } else {
                return returnData(0, '修改失败');
            }
        }
    }

    /**
     * @title 更换手机号
     * @author zwp
     * @method POST
     * @url member/personal/changeMobile
     * @param name:mobile type:string require:1 default: desc:手机号
     * @param name:code type:string require:1 default: desc:验证码
     */
    public function changeMobile()
    {
        if ($this->request->isPost()) {
            $post_data     = SafeFilter(input('post.'));
            $member   = $this->memberInfo;
            if(isset($post_data['mobile']) && $post_data['mobile']){
                $validate = $this->validate($post_data, 'MemberValidate.mobile_changeMobile');
                $field    = htmlpurifier(stripslashes($post_data['mobile']));
                $where[]    = ['mobile','=',$post_data['mobile']];
                $update     = ['mobile'=>$field];
            }
            if(isset($post_data['email']) && $post_data['email']){
                $validate = $this->validate($post_data, 'MemberValidate.email_changeMobile');
                $field    = htmlpurifier(stripslashes($post_data['email']));
                $where[]    = ['email','=',$post_data['email']];
                $update     = ['email'=>$field];
            }
            if ($validate !== true) {
                return returnData(0, $validate);
            }
            $code   = htmlpurifier(stripslashes($post_data['code']));
            $checkSmsRes  = app(MessageServer::class)->checkVerifyCode($field,'modify',$code);
            if($checkSmsRes == 0){
                return returnData(0,'验证码有误');
            }
            $has = MemberModel::where($where)->field('id')->find();
            if (!empty($has)) {
                return returnData(0, '信息已被绑定');
            }
            $member->isUpdate(true)->save($update);
            return returnData(1, '修改成功');
        }
    }

    /**
     * @title 绑定手机号
     * @author zwp
     * @url /member/personal/bindMobile
     * @method POST
     * @param name:mobile type:string require:1 default: desc:手机号
     * @param name:code type:string require:1 default: desc:验证码
     * @param name:loginpass type:string require:1 default: desc:登录密码
     * @param name:quepass type:string require:1 default: desc:确认密码
     */
    public function bindMobile(){
        if ($this->request->isPost()) {
            $post_data = SafeFilter(input('post.'));
            $member = $this->memberInfo;
            if (!empty($member['mobile'])) {
                return returnData(0, '该账号已绑定手机号');
            }
            $validate = $this->validate($post_data, 'MemberValidate.bindMobile');
            if ($validate !== true) {
                return returnData(0, $validate);
            }
            $mobile = htmlpurifier(stripslashes($post_data['mobile']));
            $code   = htmlpurifier(stripslashes($post_data['code']));
            $checkSmsRes  = app(MessageServer::class)->checkVerifyCode($mobile,'bind',$code);
            if($checkSmsRes == 0){
                return returnData(0,'短信验证码有误');
            }
            $hasUser = MemberModel::where(['mobile' => $mobile])->where('id','<>',$member['id'])->find();
            if (!empty($hasUser)) {
                // 账号合并
                if (!empty($hasUser['openid_wx']) && !empty($hasUser['openid_xcx'])){
                    return returnData(0, '该手机号已存在');
                }
                $update = [];
                if(empty($hasUser['openid_wx']) && !empty($member['openid_wx'])){
                    $update['openid_wx'] = $member['openid_wx'];
                }
                if(empty($hasUser['openid_xcx']) && !empty($member['openid_xcx'])){
                    $update['openid_xcx'] = $member['openid_xcx'];
                }
                if(empty($update)){
                    return returnData(0, '绑定失败');
                }

                if(empty($hasUser['unionid_wx']) && !empty($member['unionid_wx'])){
                    $update['unionid_wx'] = $member['unionid_wx'];
                }
                if(!empty($post_data['loginpass'])){
                    $newPassword = htmlpurifier(stripslashes($post_data['loginpass']));
                    $newPassword = MemberModel::setPasswordAttr($newPassword);//加密
                    $update['login_pwd'] = $newPassword;
                }
                $hasUser->save($update);
                MemberModel::destroy(['id' => $member['id']]);
                $member = $hasUser;
            } else {
                $update = [
                    'mobile' => $mobile,
                    'login_pwd' => MemberModel::setPasswordAttr($post_data['loginpass']),
                ];
                MemberModel::where(['id' => $member['id']])->update($update);
            }
            $return_data = $member->getLoginToken();
            return returnData(1, '绑定成功', $return_data);
        }
    }

    public function logout()
    { 
        if ($this->request->isPost()) {
            $post_data   = SafeFilter(input('post.'));
            $user_id     = $this->memberID;
            MemberModel::where(['id' => $user_id])->update(['update_time' => time()]);
            return returnData(1, '退出成功');
        }
    }
}