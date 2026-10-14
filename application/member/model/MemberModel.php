<?php
namespace app\member\model;

use app\member\facade\JwtAuth;
use Carbon\Carbon;
use think\Db;
use think\Model;
use think\helper\Hash;
use think\model\concern\SoftDelete;

/**
 * 个人会员
 * Class MemberModel
 * @package app\member\model
 */
class MemberModel extends Model
{
    use SoftDelete;

    // 设置当前模型对应的完整数据表名称
    protected $name = 'member';
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    public static function setPasswordAttr($value)
    {
        return Hash::make((string)$value);
    }

    public function retrieveById($member_id)
    {
        $info = $this->get($member_id);
        foreach (servicelist('member.getMemberInfo') as $object) {
            if (!is_object($object)) continue;

            if (method_exists($object, 'handle')) {
                $object->handle($member_id, $info);
            }
        }
        return $info;
    }

    public static function geMemberInfo($member_id, &$info = [])
    {
        if (empty($info)) {
            $user = $info = self::where('id', $member_id)->field('id,name,mobile,avatar,email,account')->find();
        } else {
            $user = $info;
        }
        $str = '';
        if (!empty($user)) {
//            $avatar = !empty($user['avatar']) ? $user['avatar'] : config('web_site_logo');
//            $avatar = get_file_path($avatar);
//            $str    = '<img src="' . $avatar . '" style="width:50px;height:50px;"/>' . 'ID:' . $user['id'] . '</br>姓名：' . $user['name'] . '<br/>手机号：' . $user['mobile'];
            $str  = 'ID:' . $user['id'] . '</br>姓名：' . $user['name'] . '<br/>手机号：' . $user['mobile'].'<br/>邮箱：' . $user['email'].'<br/>账号：' . $user['account'];
        }
        return $str;
    }

    public function getLoginToken()
    {
        $token        = JwtAuth::createToken($this->id);
        $res['token'] = [
            'access_token'       => $token,
            'expires_in'         => Carbon::now()->addMinutes(config('jwt.ttl'))->timestamp,
            'refresh_expires_in' => Carbon::now()->addMinutes(config('jwt.refresh_ttl'))->timestamp
        ];
        $avatar       = $this->avatar ? setUpImg($this->avatar) : setUpImg(config('web_site_logo'));
        $data         = array(
            'id'      => $this->id,
            'name'    => $this->name,
            'mobile'  => $this->mobile,
            'email'   => $this->email,
            'account' => $this->account,
            'regcode' => $this->regcode,
            'avatar'  => $avatar,
        );
        $res['member']  = $data;
        return $res;
    }

    /**
     * 修改推荐人
     */
    public function changePid($member,$newPid,$admin_id = UID){
        $member_id = $member['id'];
        $oldPid = $member['tid'];
        $old_line = $member['line'];
        $old_layer = $member['layer'];
        if(!empty($newPid)){
            $newParent = self::where('id',$newPid)->find();
            if(empty($newParent)){
                $this->error = '上级会员不存在';
                return false;
            }
            if ($newParent['lock'] == 1) {
                $this->error = '推荐人被锁定';
                return false;
            }
            if(in_array($member_id,explode(',',trim(',',$newParent['line'])))){
                $this->error = '新推荐人不能是会员的下级';
                return false;
            }
        }
        $new_line = empty($newParent) ? '0,' : $newParent['line'].$newPid.',';
        $new_layer = count(explode(',', trim($new_line, ",")));
        Db::startTrans();
        try{
            // 修改所有下级推荐关系
            self::where('line', 'like', '%,'.$member_id . ',%')->update([
                'line'  => Db::raw('REPLACE (line,"' . $old_line . '","' . $new_line . '")'),
                'layer' => Db::raw('layer+' . $new_layer . '-' . $old_layer)
            ]);
            // 修改自身推荐关系
            self::where('id',$member_id)->update(['tid'=>$newPid,'line'=>$new_line,'layer'=>$new_layer]);
            // 老上级直推人-1
            self::where('id',$oldPid)->setDec('num',1);
            // 新上级直推人+1
            self::where('id',$newPid)->setInc('num',1);
            // 记录变更
            $arr = array(
                'member_id'    => $member_id,
                'old_pid'      => $oldPid,
                'new_pid'      => $newPid,
                'create_time'  => time(),
                'admin_id'     => $admin_id,
            );
            Db::name('member_tid_records')->insert($arr);
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            $this->error = $e->getMessage();
            return false;
        }
        return true;
    }

    public function getImportExcelTitle(){
        return [
            'tid' => '推荐人id',
            'name'=>'姓名',
            'mobile'=>'手机号',
            'loginpass'=>'密码',
        ];
    }

    public function getImportTips(){
        return [
            '模板第一行title，不允许修改',
            '隐藏、空白行会造成导入报错',
            '每次最多操作1000行，数据多请分多次操作',
            '密码不输入的默认123456'
        ];
    }

    public function doImportItemAdd($POST,&$msg){
        $POST['mobile'] = trim($POST['mobile']);
        $hasMobile      = self::where('mobile', $POST['mobile'])->find();
        if (!empty($hasMobile)) {
            $msg = '该手机号已存在';
            return false;
        }
        $POST['tid'] = (isset($POST['tid']) && $POST['tid']) ? intval($POST['tid']) : 0;
        $m_line      = '0,';
        $m_tid       = 0;
        if ($POST['tid'] > 0) {
            $hasRegCode = self::where(array('id' => $POST['tid']))->field('id,lock,line,num,layer')->find();
            if (empty($hasRegCode)) {
                $msg = '推荐人不存在';
                return false;
            }
            if ($hasRegCode['lock'] == 1) {
                $msg = '推荐人被锁定';
                return false;
            }
            $m_tid  = $hasRegCode['id'];
            $m_line = $hasRegCode['line'].$hasRegCode['id'].',';
        }
        $m_layer = count(explode(',', trim($m_line, ",")));
        $password      = trim($POST['loginpass']);
        empty($password) && $password = '123456';
        $password      = $this->setPasswordAttr($password);//加密
        $member        = array(
            'tid'       => $m_tid,
            'line'      => $m_line,
            'layer'     => $m_layer,
            'name'      => (isset($POST['name']) && !empty($POST['name'])) ? trim($POST['name']) : '',
            'mobile'    => $POST['mobile'],
            'login_pwd' => $password,
            'regcode'   => createNO(3, 6, 'member', 'regcode'), //创建新用户的邀请码
            'lock'      => $POST['lock'] ?? 0,
        );
        Db::startTrans();
        $member = self::create($member);
        if (empty($member)) {
            Db::rollback();
            $msg = '操作失败';
            return false;
        }
        if ($m_tid) {
            $updateTuserNum = MemberModel::where('id', $m_tid)->setInc('num');
            if (empty($updateTuserNum)) {
                Db::rollback();
                $msg = '注册失败';
                return false;
            }
        }
        Db::commit();
        return true;
    }

    //2023-05-11还原昵称中包含Emoji表情的字符
    public function getNameAttr($value){
        return emojiDecode($value);
    }
    public function memberRegister($data,$identity){
        $regcode = (isset($data['regcode']) && $data['regcode']) ? htmlpurifier(stripslashes($data['regcode'])) : '';
        if (!empty($regcode)) {
            $hasRegCode = MemberModel::where(array('regcode' => $regcode))->field('id,lock,line,num,layer')->find();
            if (empty($hasRegCode)) {
                $this->error = '邀请码不存在';
                return false;
            }
            if ($hasRegCode['lock'] == 1) {
                $this->error = '邀请人被锁定';
                return false;
            }
        }

        $mobile = isset($data['mobile']) && $data['mobile'] ? htmlpurifier(stripslashes($data['mobile'])) : '';
        $email  = isset($data['email']) && $data['email'] ? htmlpurifier(stripslashes($data['email'])) : '';
        if(!$mobile && !$email){
             return false;
        }
        $m_line = '0,';
        $m_tid  = 0;
        if (!empty($hasRegCode)) {
            $m_tid  = $hasRegCode['id'];
            $m_line = $hasRegCode['line'].$hasRegCode['id'].',';
        }
        $m_layer       = count(explode(',', trim($m_line, ",")));
        $member_data        = array(
            'tid'         => $m_tid,
            'line'        => $m_line,
            'layer'       => $m_layer,
            'avatar'      => setUpImg(config('web_site_logo')),
            'lock'        => 0,
            'name'        => config('web_site_title').'_'.GetRandStr(4),
            'mobile'      => $mobile,
            'email'       => $email,
            'regcode'     => createNO(3, 6, 'member', 'regcode'),
            'identity'    => $identity,       //当前会员等级
            'create_time' => time(),  //当前注册时间
        );
        Db::startTrans();
        try {
            $member = MemberModel::Create($member_data);
            if ($member->isEmpty()) {
                Db::rollback();
                $this->error = '注册失败';
                return false;
            }
            if ($m_tid) {
                $updateTuserNumber = MemberModel::where('id', $m_tid)->setInc('num');
                if (empty($updateTuserNumber)) {
                    Db::rollback();
                    $this->error = '注册失败';
                    return false;
                }
            }

            Db::commit();
            return $member;
        } catch (\Exception $e) {
            Db::rollback();
            \think\facade\Log::error(var_export($e->getMessage(),true));
            $this->error = '注册失败';
            return false;

        }
    }
}
