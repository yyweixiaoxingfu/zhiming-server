<?php
namespace app\system\home;
use app\index\controller\Home;
use app\message\library\MessageServer;
use app\system\library\CaptchaMy;
use app\member\model\MemberModel;
use think\facade\Config;

/**
 * @title 发送短信
 */
class Sms extends Home
{
    /**
     * @title 发送短信
     * @param name:mobile type:number require:1 default: desc:手机号
     * @param name:type type:string require:1 default: desc:login:短信验证码登录，绑定手机号：bind，注册：register，设置登录密码：setlogin,设置支付密码：settrade，忘记密码：forget，修改手机号：modify，绑定银行卡：bank
     * @param name:uuid type:string require:1 default: desc:二维码表示ID
     * @param name:captcha type:captcha require:1 default: desc:图形验证码
     * @author bh
     * @url /system/sms/send_sms
     * @method POST
     */
    public function send_sms()
    {
        $post_data = SafeFilter(input('post.'));
        $result    = '未知错误';
        if(isset($post_data['mobile']) && $post_data['mobile']){
            $result     = $this->validate($post_data, 'SmsValidate.send_mobile');
            $filed      = $post_data['mobile'];
            $where[]    = ['mobile','=',$post_data['mobile']];
            $msg        = '手机号';
        }
        if(isset($post_data['email']) && $post_data['email']){
            $result      = $this->validate($post_data, 'SmsValidate.send_email');
            $filed       = $post_data['email'];
            $where[]     = ['email','=',$post_data['email']];
            $msg         = '邮箱';
        }
        if(true !== $result) {
            return returnData(0,$result);
        }
        
        $type       = $post_data['type'];
        $uuid       = $post_data['uuid'];
        $captcha    = $post_data['captcha'];
        $driver     = isset($post_data['driver']) && $post_data['driver'] ? $post_data['driver'] : 'sms';

        // $captchaObj = new CaptchaMy((array) Config::pull('captcha'));
        // if(!$res = $captchaObj->check($captcha, $uuid)){
        //     return returnData(0,'图形验证码错误');
        // }
        switch ($type) { //短信发送场景
            case 'login': //短信验证码登录
                break;
            case 'bind': //绑定手机号
                 break;
            case 'register':  //注册
                $hasMobile = MemberModel::where($where)->field('id')->find();
                if($hasMobile){
                    return returnData(0,$msg.'已存在');
                }
                break;
            case 'settrade': //设置支付密码
                 break;
            case 'setlogin': //设置登录密码
                 break;
            case 'forget': //忘记密码
                 break;
            case 'modify': //修改手机号
                 break;
            case 'trade': //修改交易密码
                $hasMobile = MemberModel::where($where)->field('id')->find();
                if(empty($hasMobile)){
                    return returnData(0,$msg.'不存在');
                }
                break;
            default:
                return returnData(0,'信息有误');
        }
        $server = new MessageServer();
        $info = $server->sendVerifyCode($type,$filed,$driver);
        if (!$info)  return returnData(0,$server->getError() ?? '操作失败');
        return returnData(1,'操作成功');

    }

    /**
     * @title 获取图形验证码
     * @author bh
     * @url /system/sms/get_img_code
     */
    public function get_img_code(){
        $uuid = uniqid();
        //['data' => $figure_data, 'uuid' => $uuid]
        $image_code = [
            'uuid' => $uuid,
            'img_src'=> splicingHttpSrc('index.php/system/sms/captcha/id/'.$uuid.'.html'),
        ];
        return returnData(1,'',$image_code);
    }

    public function captcha($id = "")
    {
        $captcha = new CaptchaMy((array) Config::pull('captcha'));
        return $captcha->entry($id);
    }

}
