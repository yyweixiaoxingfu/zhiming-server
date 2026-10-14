<?php
namespace app\member\home;

use app\index\controller\Api;
use app\member\library\WechatMiniAppServer;
use app\member\model\MemberModel;
use app\member\model\MemberCreditModel;
use PosterMaker\PosterMaker;
use think\facade\Env;

/**
 * @title 我的团队
 * Class Team
 * @package app\member\home
 */
class Team extends Api
{
    /**
     * @title 我的团队
     * @author zwp
     * @method POST
     * @url /member/team/team_list
     * @header name:Authorization require:1 default: desc:token
     * @param name:type type:string require:1 default: desc:zhi直推，team团队
     * @param name:keywords type:string require:1 default: desc:会员昵称或手机号
     * @param name:page type:string require:1 default: desc:分页
     */
    public function team_list()
    {
         if ($this->request->isPost()) {
            $member         = $this->memberInfo;
            $post_data      = SafeFilterItem(input('post.'));
            $page           = isset($post_data['page']) && $post_data['page'] ? (int)$post_data['page'] : 1;
            $limit          = isset($post_data['limit']) && $post_data['limit'] ? (int)$post_data['limit'] : 20;
            $team           = MemberModel::where('line','like','%,'.$member['id'].',%')->count();
            $where          = [];

            if(!empty($post_data['type']) && $post_data['type'] == 'zhi'){
                $where[] = ['tid','=',$member['id']];
            }else{
                $where[] = ['line','like','%,'.$member['id'].',%'];
            }
            if(!empty($post_data['keywords']) && $post_data['keywords'] != ''){
                if(preg_match("/^1\d{10}$/", $post_data['keywords'])){
                    $where[] = ['mobile','=',$post_data['keywords']];
                }elseif(preg_match("/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/", $post_data['keywords'])){
                    $where[] = ['email','=',$post_data['keywords']];
                }else{
                    $where[] = ['name','like','%'.$post_data['keywords'].'%'];
                }
            }
            $total     = MemberModel::where($where)->count();
            $data_list = MemberModel::where($where)
                ->field('id,name,tid,avatar,email,mobile')
                ->page($page,$limit)
                ->select()
                ->each(function ($item){
                    $item['avatar']      = getAvatar($item['avatar']);
                    $credit_data         = MemberCreditModel::where('member_id', $item['id'])->group('type')->column('credit','type');
                    $item['deposit']     = isset($credit_data['deposit']) ? $credit_data['deposit'] : "0.00";
                    $item['points']      = isset($credit_data['points']) ? $credit_data['points'] : "0.00";
                    $item['frequency']   = isset($credit_data['frequency']) ? $credit_data['frequency'] : "0.00";
                    return $item;
                });
            return returnData(1,'',['zhi' => $member['num'], 'team' => $team, 'total' => $total, 'data' => $data_list]);
        }
    }

    /**
     * @title 邀请好友
     * @author zwp
     * @method POST
     * @url /member/team/invite_friends
     * @param name:type type:string require:1 default:类型app，h5=>H5版，wx=>公众号，wxmini=>微信小程序
     * @param name:page_url type:string require:1 default:扫码跳转地址
     * @header name:Authorization require:1 default:1455523e6d407a3c desc:token
     */
    public function invite_friends()
    {
        if ($this->request->isPost()) {
            $member = $this->memberInfo;
            //$param  = $this->request->param('',null,'SafeFilterItem');
            $param  = SafeFilterItem(input('post.'));;
            $type   = isset($param['type']) && $param['type'] ? $param['type'] : 'app';
            $param['page_url'] = isset($param['page_url']) && !empty($param['page_url']) ? $param['page_url'] : config('web_url').'pages/login/login?regcode=';
            if($type != 'app' && empty($param['page_url'])){
                return returnData(0,'请填写跳转地址');
            }
            $invite_code = $member['regcode'];
            switch ($type){
                case 'wx':
                    $url = $param['page_url'].$invite_code;
                    $file_name = "wx_".$invite_code . '.png';
                    $qrcode = getQrcode($url,$file_name);
                    break;
                case 'h5':
                    $url = $param['page_url'].$invite_code;
                    $file_name = "h5_".$invite_code . '.png';
                    $qrcode = getQrcode($url,$file_name);
                    break;
                case 'wxmini':
                    $url = $param['page_url'].$invite_code;
                    $server = new WechatMiniAppServer();
                    $file_name = 'wxmini_'.$invite_code.".jpg";
                    $qrcode = $server->getQrcode($url,$file_name);
                    break;
                case 'app':
                default:
                    $url = splicingHttpSrc('register.html?regcode=' . $invite_code);
                    $file_name  = $invite_code . '.png';
                    $qrcode     = getQrcode($url,$file_name);
                    break;
            }
            $poster  = new  PosterMaker(750, 1334);
            if (!is_readable('./uploads/poster')) mkdir('./uploads/poster', 0755);
            $poster_path            = Env::get('root_path') . 'public/uploads/poster/'.$file_name;
            $poster_img_path        = splicingHttpSrc('uploads/poster/'.$file_name);
            $bg_logo                = './' . get_file_path(config('bg_logo'));
            $poster->addImg($bg_logo, [0, 0], [750, 1334])
                ->addImg('./uploads/qrcode/'.$file_name, [220, 620], [320, 300])
                ->addText('邀请码：' . $invite_code, 25, [240, 575], [0, 0, 0])
                ->render($poster_path);
            return returnData(1, '', ['poster' => $poster_img_path, 'url' => $url, 'qrcode' => $qrcode, 'regcode' => $invite_code]);
        }
    }
}