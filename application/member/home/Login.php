<?php

namespace app\member\home;

use app\member\library\WechatMiniAppServer;
use app\member\facade\JwtAuth;
use app\member\library\WechatServer;
use app\member\model\MemberModel;
use app\message\library\MessageServer;
use Carbon\Carbon;
use think\Controller;
use think\Db;
use think\facade\Log;
use think\helper\Hash;
use app\ai\model\AiMainModel;

/**
 * @title 登录注册
 */
class Login extends Controller
{
    /**
     * @title 手机号登录
     * @param name:mobile type:string require:1 default: desc:手机号
     * @param name:loginpass type:string require:1 default: desc:登录密码
     * @author BH
     * @url /member/login/login
     * @method POST
     */
    public function login()
    {
        if ($this->request->isPost()) {
            $post_data = SafeFilter(input('post.'));
            if(isset($post_data['mobile']) && $post_data['mobile']){
                $result     = $this->validate($post_data, 'MemberValidate.mobile_login');
                $filed      = htmlpurifier(stripslashes($post_data['mobile']));
                $where[]    = ['mobile','=',$post_data['mobile']];
            }
            if(isset($post_data['email']) && $post_data['email']){
                $result      = $this->validate($post_data, 'MemberValidate.email_login');
                $filed       = htmlpurifier(stripslashes($post_data['email']));
                $where[]     = ['email','=',$post_data['email']];
            }
            if ($result !== true) {
                return returnData(0, $result);
            }
            //$field = array('mobile' => $post_data['mobile']);
            $member  = MemberModel::where($where)
                // ->whereOr('account',$post_data['mobile'])
                // ->whereOr('email',$post_data['mobile'])
                ->find();
            //echo MemberModel::getLastSql();die;
            if (empty($member)) {
                return returnData(0, '账号或密码有误!');
            }
            if (!Hash::check((string)$post_data['loginpass'], $member['login_pwd'])) {
                return returnData(0, '账号或密码有误!');
            }
            if ($member['lock'] == 1) {
                return returnData(0, '账户已被锁定');
            }
            $res = $member->getLoginToken();
            return returnData(1, '登录成功', $res);
        }
    }

    /**
     * @title 短信验证码登录
     * @author zwp
     * @method POST
     * @url /member/login/code_login
     * @param name:mobile type:string require:1 default: desc:手机号
     * @param name:code type:string require:1 default: desc:短信验证码
     */
    public function code_login(){
        if ($this->request->isPost()) {
            $post_data = SafeFilter(input('post.'));
            if(isset($post_data['mobile']) && $post_data['mobile']){
                $result     = $this->validate($post_data, 'MemberValidate.mobile_codelogin');
                $filed      = htmlpurifier(stripslashes($post_data['mobile']));
                $where[]    = ['mobile','=',$post_data['mobile']];
            }
            if(isset($post_data['email']) && $post_data['email']){
                $result      = $this->validate($post_data, 'MemberValidate.email_codelogin');
                $filed       = htmlpurifier(stripslashes($post_data['email']));
                $where[]     = ['email','=',$post_data['email']];
            }
            //$result    = $this->validate($post_data, 'MemberValidate.codelogin');
            if ($result !== true) {
                return returnData(0, $result);
            }
            //$mobile = htmlpurifier(stripslashes($post_data['mobile']));
            $code   = htmlpurifier(stripslashes($post_data['code']));
            $checkSmsRes  = app(MessageServer::class)->checkVerifyCode($filed,'login',$code);
            if($checkSmsRes == 0){
                return returnData(0,'验证码有误');
            }
            $member  = MemberModel::where($where)->find();
            if (empty($member)) {
                $MemberModel = new MemberModel();
                $res = $MemberModel->memberRegister($post_data,'member');
                if(empty($res)){
                    return returnData(0, $MemberModel->getError());
                }
                $member  = MemberModel::where($where)->find();
            }
            if ($member['lock'] == 1) {
                return returnData(0, '账户已被锁定');
            }
            $res = $member->getLoginToken();
            return returnData(1, '登录成功', $res);
        }
    }

    /**
     * @title 刷新TOKEN
     * @author BH
     * @url /member/login/refreshToken
     * @method POST
     * @header name:Authorization require:1 default: desc:token
     */
    public function refreshToken()
    {
        $token = JWTAuth::refresh();
        if (!$token) return returnData(401, '操作失败');
        $data['token'] = [
            'access_token'       => $token,
            'expires_in'         => Carbon::now()->addMinutes(config('jwt.ttl'))->timestamp,
            'refresh_expires_in' => Carbon::now()->addMinutes(config('jwt.refresh_ttl'))->timestamp
        ];
        return returnData(1, '操作成功', $data);
    }

    /**
     * @title 注册
     * @param name:mobile type:string require:1 default: desc:手机号
     * @param name:code type:string require:1 default: desc:短信验证码
     * @param name:loginpass type:string require:1 default: desc:登录密码
     * @param name:quepass type:string require:1 default: desc:确认密码
     * @param name:regcode type:string require:0 default: desc:上级邀请码
     * @url /member/login/register
     * @method POST
     */
    public function register()
    {
        if ($this->request->isPost()) {
            $post_data = SafeFilter(input('post.'));
            if (intval(config('register_switch')) == 0) {
                return returnData(0, '会员注册端口未开通');
            }
           
            if (intval(config('invitation_code')) == 1) {
                if (!isset($post_data['regcode']) || empty($post_data['regcode'])) {
                    return returnData(0, '请输入上级邀请码');
                }
            }

            $regcode = (isset($post_data['regcode']) && $post_data['regcode']) ? htmlpurifier(stripslashes($post_data['regcode'])) : '';
            if (!empty($regcode)) {
                $hasRegCode = MemberModel::where(array('regcode' => $regcode))->field('id,lock,line,num,layer')->find();
                if (empty($hasRegCode)) {
                    return returnData(0, '邀请码不存在');
                }
                if ($hasRegCode['lock'] == 1) {
                    return returnData(0, '邀请人被锁定');
                }
            }

            $account = '';
            if(isset($post_data['account']) && $post_data['account'] != ''){
                $account    = htmlpurifier(stripslashes($post_data['account']));
                if(!preg_match('/^(?=.*[a-zA-Z_])[a-zA-Z0-9_]{6,}$/', $account)){
                    return returnData(0,'账号格式不正确，只能包含字母、数字和下划线，不能有空格，长度必须大于6位');
                }
                $hasAccount = MemberModel::where(array('account' => $account))->field('id')->find();
                if (!empty($hasAccount)) {
                    return returnData(0, '该账号已存在');
                }
            }

            $email = '';
            if(isset($post_data['email']) && $post_data['email']){
                $result = $this->validate($post_data, 'MemberValidate.email_register');
                if (true !== $result) {
                    return returnData(0, $result);
                }
                $email    = htmlpurifier(stripslashes($post_data['email']));
                $hasEmail = MemberModel::where(array('email' => $email))->field('id')->find();
                if (!empty($hasEmail)) {
                    return returnData(0, '该邮箱已存在');
                }
                $code         = htmlpurifier(stripslashes($post_data['code']));
                $checkSmsRes  = app(MessageServer::class)->checkVerifyCode($email,'register',$code);
                if($checkSmsRes == 0){
                    return returnData(0,'验证码有误');
                }
            }

            $mobile = '';
            if(isset($post_data['mobile']) && $post_data['mobile']){
                $result = $this->validate($post_data, 'MemberValidate.mobile_register');
                if (true !== $result) {
                    return returnData(0, $result);
                }
                $mobile     = htmlpurifier(stripslashes($post_data['mobile']));
                $hasMobile = MemberModel::where(array('mobile' => $mobile))->field('id')->find();
                if (!empty($hasMobile)) {
                    return returnData(0, '该手机号已存在');
                }
                $code         = htmlpurifier(stripslashes($post_data['code']));
                $checkSmsRes  = app(MessageServer::class)->checkVerifyCode($mobile,'register',$code);
                if($checkSmsRes == 0){
                    return returnData(0,'验证码有误');
                }
            }
            
            $m_line = '0,';
            $m_tid  = 0;
            if (!empty($hasRegCode)) {
                $m_tid  = $hasRegCode['id'];
                $m_line = $hasRegCode['line'].$hasRegCode['id'].',';
            }
            $m_layer       = count(explode(',', trim($m_line, ",")));
            $password      = htmlpurifier(stripslashes($post_data['loginpass']));
            $password      = MemberModel::setPasswordAttr($password);//加密
            $member        = array(
                'tid'         => $m_tid,
                'line'        => $m_line,
                'layer'       => $m_layer,
                'avatar'      => setUpImg(config('web_site_logo')),
                'name'        => config('web_site_title').'_'.GetRandStr(4),
                'mobile'      => $mobile,
                'email'       => $email,
                'account'     => $account,
                'login_pwd'   => $password,
                'regcode'     => createNO(3, 6, 'member', 'regcode'),
                'identity'    => 'member',       //当前会员等级
                'create_time' => time(),  //当前注册时间
            );
            Db::startTrans();
            try {
                $member = MemberModel::Create($member);
                if ($member->isEmpty()) {
                    Db::rollback();
                    return returnData(0, '注册失败');
                }
                if ($m_tid) {
                    $updateTuserNumber = MemberModel::where('id', $m_tid)->setInc('num');
                    if (empty($updateTuserNumber)) {
                        Db::rollback();
                        return returnData(0, '注册失败');
                    }
                }
                Db::commit();
                return returnData(1, '注册成功');
            } catch (\Exception $e) {
                Db::rollback();
                Log::error(var_export($e->getMessage(),true));
                return returnData(0, $e->getMessage());
            }
        }
    }

    /**
     * @title 忘记密码
     * @param name:mobile type:string require:1 default: desc:手机号
     * @param name:code type:string require:1 default: desc:短信验证码
     * @param name:loginpass type:string require:1 default: desc:登录密码
     * @param name:quepass type:string require:1 default: desc:确认密码
     * @url /member/login/forgetPass
     * @method POST
     */
    public function forgetPass()
    {
        if ($this->request->isPost()) {
            $post_data   = SafeFilter(input('post.'));
            if(isset($post_data['mobile']) && $post_data['mobile']){
                $result     = $this->validate($post_data, 'MemberValidate.mobile_forget');
                $filed      = htmlpurifier(stripslashes($post_data['mobile']));
                $where[]    = ['mobile','=',$post_data['mobile']];
                $msg        = '手机号';
            }
            if(isset($post_data['email']) && $post_data['email']){
                $result      = $this->validate($post_data, 'MemberValidate.email_forget');
                $filed       = htmlpurifier(stripslashes($post_data['email']));
                $where[]     = ['email','=',$post_data['email']];
                $msg        = '邮箱';
            }
            if (true !== $result) {
                return returnData(0, $result);
            }
            //$post_data['mobile'] = trim($post_data['mobile']);
            $code         = htmlpurifier(stripslashes($post_data['code']));
            $checkSmsRes  = app(MessageServer::class)->checkVerifyCode($filed,'forget',$code);
            if($checkSmsRes == 0){
                return returnData(0,'验证码有误');
            }
            $hasMember = MemberModel::where($where)->field('id,login_pwd')->find();
            if (empty($hasMember)) {
                return json(array('code' => 0, 'msg' => '该'.$msg.'不存在'));
            }
            $password = htmlpurifier(stripslashes($post_data['loginpass']));
            $password = MemberModel::setPasswordAttr($password);//加密
            if ($password == $hasMember['login_pwd']) {
                return json(array('code' => 0, 'msg' => '新密码与旧密码不能一致'));
            }
            //$mobile = htmlpurifier(stripslashes($post_data['mobile']));
            $res    = MemberModel::where('id','=',$hasMember['id'])->update(array('login_pwd' => $password));
            if ($res) {
                return returnData(1, '修改成功');
            } else {
                return returnData(0, '修改失败');
            }
        }
    }

    /**
     * @title 微信小程序登录
     * @author zwp
     * @url /member/login/wxapp_login
     * @method POST
     * @param name:code type:string require:1 default: desc:微信code
     * @param name:rawData type:string require:1 default: desc:getUserProfile数据
     * @param name:signature type:string require:1 default: desc:getUserProfile数据
     * @param name:encryptedData type:string require:1 default: desc:getUserProfile数据
     * @param name:iv type:string require:1 default: desc:getUserProfile数据
     * @param name:regcode type:string require:0 default: desc:上级邀请码
     */
    public function wxapp_login(){
        if ($this->request->isPost()) {
            $post_data      = SafeFilter(input('post.'));
            $result         = $this->validate($post_data, 'MemberValidate.wxapp_login');
            if (true !== $result) {
                return returnData(0, '参数错误');
            }
            $server = new WechatMiniAppServer();
            $info = $server->getAuthByCode($post_data['code']);
            if(!$info){
                return returnData(0,$server->getError() ?? '获取用户信息失败');
            }
            $post_data['rawData'] = str_replace('_\\',',',$post_data['rawData']);
            $post_data['rawData'] = stripslashes($post_data['rawData']);
            $signature = sha1($post_data['rawData'].$info['session_key']);
            if($signature != $post_data['signature']){
                return returnData(0,'验签失败');
            }
            $wxUserInfo = $server->decryptData($info['session_key'],$post_data['iv'],$post_data['encryptedData']);
            if(!$wxUserInfo){
                return returnData(0,$server->getError() ?? '获取用户信息失败');
            }
            $user  = MemberModel::where(['openid_xcx' => $info['openid']])->find();
            if(isset($info['unionid']) && empty($user)){
                $user  = MemberModel::where(['unionid_wx' => $info['unionid']])->find();
            }
            if (empty($user)) {
                $regcode = (isset($post_data['regcode']) && $post_data['regcode']) ? htmlpurifier(stripslashes($post_data['regcode'])) : '';
                if (!empty($regcode)) {
                    $hasRegCode = MemberModel::where(array('regcode' => $regcode))->field('id,lock,line,num,layer')->find();
                    if (empty($hasRegCode)) {
                        return returnData(0, '邀请码不存在');
                    }
                    if ($hasRegCode['lock'] == 1) {
                        return returnData(0, '邀请人被锁定');
                    }
                }
                $m_line = '0,';
                $m_tid  = 0;
                if (!empty($hasRegCode)) {
                    $m_tid  = $hasRegCode['id'];
                    $m_line = $hasRegCode['line'].$hasRegCode['id'].',';
                }
                $m_layer  = count(explode(',', trim($m_line, ",")));
                $currtime = time();
                $insert_member = [
                    'tid'           => $m_tid,
                    'line'          => $m_line,
                    'layer'         => $m_layer,
                    'name'          => empty($wxUserInfo['nickName']) ? config('web_site_title').'_'.GetRandStr(4):emojiEncode($wxUserInfo['nickName']),
                    'mobile'        => '',
                    'email'         => '',
                    'avatar'        => $wxUserInfo['avatarUrl'] ?? setUpImg(config('web_site_logo')),
                    'regcode'       => createNO(3, 6, 'member', 'regcode'),
                    'identity'      => 'member',
                    'openid_xcx'    => $info['openid'],
                    'unionid_wx'    => $info['unionid'] ?? '',
                    'lock'          => 0,
                    'create_time'   => $currtime,
                ];
                Db::startTrans();
                try {
                    $user = MemberModel::Create($insert_member);
                    if ($user->isEmpty()) {
                        Db::rollback();
                        return returnData(0, '注册失败');
                    }
                    if ($m_tid) {
                        $updateTuserNumber = MemberModel::where('id', $m_tid)->setInc('num');
                        if (empty($updateTuserNumber)) {
                            Db::rollback();
                            return returnData(0, '注册失败');
                        }
                    }
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                    Log::error(var_export($e->getMessage(),true));
                    return returnData(0, '注册失败');
                }
            }else{
                if($user['lock'] == 1){
                    return returnData(0, '您的账号已禁用!');
                }
                $update = [];
                if(empty($user['unionid_wx']) && !empty($info['unionid']))  $update['unionid_wx'] = $info['unionid'];
                if(empty($user['openid_xcx']) && !empty($info['openid']))  $update['openid_xcx'] = $info['openid'];
                if(empty($user['name']) && !empty($wxUserInfo['nickName']))  $update['name'] = $wxUserInfo['nickName'];
                if(empty($user['avatar']) && !empty($wxUserInfo['avatarUrl'])) $update['avatar'] = $wxUserInfo['avatarUrl'];
                if(!empty($update))  $user->save($update);
            }
            $return_data = $user->getLoginToken();
            return returnData(empty($user['mobile']) ? 100 : 1, '登录成功', $return_data);
        }
    }

    /**
     * @title 微信公众号登录-获取授权地址
     * @author zwp
     * @method POST
     * @url /member/login/getAuthUrl
     * @param name:redirect_url type:string require:1 default: desc:code接受地址
     * @return url:微信授权地址
     */
    public function getAuthUrl(){
        $server = new WechatServer();
        $redirect_url = $this->request->param('redirect_url');
        $res = $server->getAuthUrl($redirect_url);
        return returnData(1, '',['url'=>$res]);
    }

    /**
     * @title 微信授权登录
     * @author zwp
     * @method POST
     * @url /member/login/authByCode
     * @param name:code type:string require:1 default: desc:微信code
     * @param name:regcode type:string require:0 default: desc:上级邀请码
     */
    public function authByCode(){
        $server = new WechatServer();
        $wxinfo = $server->getUserInfo();
        if(!$wxinfo){
            return returnData(0, $server->getError());
        }
        $member = MemberModel::where(['openid_wx'=>$wxinfo['openid']])->find();
        if(isset($wxinfo['unionid']) && empty($member)){
            $member  = MemberModel::where(['unionid_wx' => $wxinfo['unionid']])->find();
        }
        if(empty($member)){
            $post_data = SafeFilter(input('post.'));
            $regcode = (isset($post_data['regcode']) && $post_data['regcode']) ? htmlpurifier(stripslashes($post_data['regcode'])) : '';
            if (!empty($regcode)) {
                $hasRegCode = MemberModel::where(array('regcode' => $regcode))->field('id,lock,line,num,layer')->find();
                if (empty($hasRegCode)) {
                    return returnData(0, '邀请码不存在');
                }
                if ($hasRegCode['lock'] == 1) {
                    return returnData(0, '邀请人被锁定');
                }
            }
            $m_line = '0,';
            $m_tid  = 0;
            if (!empty($hasRegCode)) {
                $m_tid  = $hasRegCode['id'];
                $m_line = $hasRegCode['line'].$hasRegCode['id'].',';
            }
            $m_layer = count(explode(',', trim($m_line, ",")));
            $insert_member = [
                'tid'           => $m_tid,
                'line'          => $m_line,
                'layer'         => $m_layer,
                'name'          => empty($wxinfo['nickname'])? config('web_site_title').'_'.GetRandStr(4):emojiEncode($wxinfo['nickname']),
                'mobile'        => '',
                'email'         => '',
                'openid_wx'     => $wxinfo['openid'],
                'unionid_wx'    => $wxinfo['unionid'] ?? '',
                'avatar'        => $wxinfo['headimgurl']??setUpimg(config('web_site_logo')),
                'regcode'       => createNO(3, 6, 'member', 'regcode'),
                'identity'      => 'member',
                'lock'          => 0,
                'create_time'   => time(),  
            ];
            Db::startTrans();
            try {
                $member = MemberModel::Create($insert_member);
                if ($member->isEmpty()) {
                    Db::rollback();
                    return returnData(0, '注册失败');
                }
                if ($m_tid) {
                    $updateTuserNumber = MemberModel::where('id', $m_tid)->setInc('num');
                    if (empty($updateTuserNumber)) {
                        Db::rollback();
                        return returnData(0, '注册失败');
                    }
                }
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                Log::error(var_export($e->getMessage(),true));
                return returnData(0, '注册失败');
            }
        }else{
            if($member['lock'] == 1){
                return returnData(0, '您的账号已禁用!');
            }
            $update = [];
            if(empty($member['unionid_wx']) && !empty($wxinfo['unionid']))  $update['unionid_wx'] = $wxinfo['unionid'];
            if(empty($member['openid_wx']) && !empty($wxinfo['openid']))  $update['openid_wx'] = $wxinfo['openid'];
            if(empty($member['name']) && !empty($wxinfo['nickname']))  $update['name'] = $wxinfo['nickname'];
            if(empty($member['avatar']) && !empty($wxinfo['headimgurl'])) $update['avatar'] = $wxinfo['headimgurl'];
            if(!empty($update))  $member->save($update);
        }
        $res = $member->getLoginToken();
        return returnData(empty($member['mobile']) ? 100 : 1, '登录成功', $res);
    }
    ######AI模块登录注册######
     public function user_login(){
        if ($this->request->isPost()) {
            $post_data = SafeFilter(input('post.'));
            $email     = $post_data['email'];
            if (empty($email)) {
                 return json(['success'=>false,'message'=>'请输入电子邮箱']);
            }
            $password   = $post_data['password'];
            if (empty($password)) {
                return json(['success'=>false,'message'=>'请输入密码']);
            }
            $captcha    = $post_data['captcha'];
            if (empty($captcha)) {
                return json(['success'=>false,'message'=>'请输入验证码']);
            }
            if (!captcha_check(strtolower($captcha))) {
                return json(['success'=>false,'message'=>'验证码错误']);
            }
            $userrndstr = $post_data['userrndstr'];
            $member  = MemberModel::where('email',$email)->find();
            if (empty($member)) {
                return json(['success'=>false,'message'=>'账号或密码有误']);
            }
            if (!Hash::check((string)$post_data['password'], $member['login_pwd'])) {
                return json(['success'=>false,'message'=>'账号或密码有误']);
            }
            if ($member['lock'] == 1) {
                return json(['success'=>false,'message'=>'账户已被锁定']);
            }
            if( $member->loginip){
                $loginip = explode(';',$member->loginip);
                if(!in_array($_SERVER["REMOTE_ADDR"],$loginip)){
                    $loginip[] = $_SERVER["REMOTE_ADDR"];
                    $member->loginip          = implode(';',$loginip); 
                }
            }else{
                 $member->loginip         = $_SERVER["REMOTE_ADDR"]; 
            }
            $member->rndstr           = $userrndstr;
            $member->last_login_time  = time();
            $member->save();
            $res = $member->getLoginToken();
            return json(['success'=>true,'message'=>'登录成功','data'=>$res]);
        }
    }


    public function check_user(){
        $userrndstr      = SafeFilter(input('userrndstr',''));
        if(!$userrndstr){
            return json(['success'=>false,'uid'=>'','openid'=>'']);
        }
        // $main_data       = AiMainModel::get(1);
        // $freetryeveryday = $main_data["freetryeveryday"];
        // $freetryshare    = $main_data["freetryshare"];
        $member          = MemberModel::where('rndstr',$userrndstr)->where('lock',0)->find();
        if (empty($member)) {
            return json(['success'=>false,'uid'=>'','openid'=>'']);
        } else{
            return json(['success'=>true,'uid'=>$member['id'],'openid'=>$member['openid_wx'],'email'=>$member['email']]);
        }
    }

    public function user_register(){
        if($this->request->isPost()){
            $post_data = SafeFilter(input('post.'));
            $userrndstr = $post_data['userrndstr'];
            $email      = $post_data['email'];
            if (empty($email)) {
                 return json(['success'=>false,'message'=>'请输入电子邮箱']);
            }
            $mobile      = $post_data['mobile'];
            if (empty($mobile)) {
                 return json(['success'=>false,'message'=>'请输入手机号']);
            }
            $password   = $post_data['password'];
            if (empty($password)) {
                return json(['success'=>false,'message'=>'请输入密码']);
            }
            $captcha    = $post_data['captcha'];
            if (empty($captcha)) {
                return json(['success'=>false,'message'=>'请输入验证码']);
            }
            if (!captcha_check(strtolower($captcha))) {
                return json(['success'=>false,'message'=>'验证码错误']);
            }
            $users = MemberModel::where('email',$email)->where('lock',0)->find();
            if(!empty($users)){
                return json(['success' => false, 'message' => '您输入的邮箱已被其他人绑定']);
            }
            $users = MemberModel::where('mobile',$mobile)->where('lock',0)->find();
            if(!empty($users)){
                return json(['success' => false, 'message' => '您输入的手机号已被其他人绑定']);
            }
            $m_line = '0,';
            $m_tid  = 0;
            // if (!empty($hasRegCode)) {
            //     $m_tid  = $hasRegCode['id'];
            //     $m_line = $hasRegCode['line'].$hasRegCode['id'].',';
            // }
            $m_layer       = count(explode(',', trim($m_line, ",")));
            $password      = htmlpurifier(stripslashes($password));
            $password      = MemberModel::setPasswordAttr($password);       //加密
            $member        = array(
                'tid'               => $m_tid,
                'email'             => $email,
                'line'              => $m_line,
                'layer'             => $m_layer,
                'name'              => empty($wxinfo['nickname'])? config('web_site_title').'_'.GetRandStr(4):emojiEncode($wxinfo['nickname']),
                'mobile'            => $mobile,
                'avatar'            => setUpImage(config('web_site_logo')),
                'login_pwd'         => $password,
                'regcode'           => createNO(3, 6, 'member', 'regcode'),
                'identity'          => 'member',       //当前会员等级
                'create_time'       => time(),         //当前注册时间
                'rndstr'            => $userrndstr,
                'last_login_time'   => time(),
                'loginip'           => $_SERVER["REMOTE_ADDR"],
            );
            Db::startTrans();
            try {
                $member = MemberModel::Create($member);
                if ($member->isEmpty()) {
                    Db::rollback();
                    return json(['success' => false, 'message' => '注册失败']);
                }
                if ($m_tid) {
                    $updateTuserNumber = MemberModel::where('id', $m_tid)->setInc('num');
                    if (empty($updateTuserNumber)) {
                        Db::rollback();
                        return json(['success' => false, 'message' => '注册失败']);
                    }
                }
                Db::commit();
                return json(['success' => true, 'message' => '注册成功']);
            } catch (\Exception $e) {
                Db::rollback();
                Log::error(var_export($e->getMessage(),true));
                return json(['success' => false, 'message' => '注册失败']);
            }
        }
    }
    ######AI模块登录注册######

}


