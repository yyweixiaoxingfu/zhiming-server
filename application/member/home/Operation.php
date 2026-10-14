<?php
namespace app\member\home;

use app\admin\model\SpCateModel;
use app\order\model\SpOrderModel;
use app\index\controller\Api;
use app\admin\model\SpProjectModel;
use app\admin\model\SpExpertModel;
use app\common\library\sm\cls_ancientCalendarData;
use think\Db;

class Operation extends Api
{
    //点赞
    public function click_like()
    {
        if ($this->request->isPost()) {
            $post        = SafeFilterItem(input('post.'));
            $user_id     = $this->memberID;
            $cate_type   = isset($post['cate_type']) && $post['cate_type'] ? $post['cate_type'] : 0;
            if(!$cate_type){
                return returnData(0, '关键参数为空');
            }
            $cid = isset($post['cid']) && $post['cid'] ? $post['cid'] : 0;
            if(!$cid){
                return returnData(0, '关键参数为空');
            }
            $link_id = isset($post['link_id']) && $post['link_id'] ? $post['link_id'] : 0;
            if(!$link_id){
                return returnData(0, '关键参数为空');
            }
            $ls_type   = isset($post['ls_type']) && $post['ls_type'] ? $post['ls_type'] : 0;
            if(!$ls_type){
                return returnData(0, '关键参数为空');
            }
            $info = Db::name('sp_operation')->where([
                ['user_id','=',$user_id],
                ['link_id','=',$link_id],
                ['ls_type','=',$ls_type],
            ])->find();
            $ls_type_title = '点赞';
            if($ls_type == 2){
                $ls_type_title = '收藏';
            }
            $time  = time();
            if(!empty($info)){
                $is_del = 0;
                $msg    = $ls_type_title.'成功';
                if($info['is_del'] == 0){
                    $is_del = 1;
                    $msg    = '取消'.$ls_type_title.'成功';
                }
                $res = Db::name('sp_operation')->where([
                    ['id','=',$info['id']],
                ])->update([
                    'is_del' => $is_del,
                    'update_time' => $time,
                ]);
                if($res){
                    return returnData(1, $msg);
                }else{
                    return returnData(0, '操作失败');
                }
            }else{
                $res = Db::name('sp_operation')->insert([
                    'user_id'       => $user_id,
                    'cate_type'     => $cate_type,
                    'cid'           => $cid,
                    'link_id'       => $link_id,
                    'ls_type'       => $ls_type,
                    'create_time'   => $time,
                ]);
                if($res){
                    return returnData(1, $ls_type_title.'成功');
                }else{
                    return returnData(0, '操作失败');
                }
            }
        }
    }

    public function click_like_list()
    { 
        if ($this->request->isPost()) {
            $post    = SafeFilterItem(input('post.'));
            $user_id = $this->memberID;
            $ls_type = isset($post['ls_type']) && $post['ls_type'] ? $post['ls_type'] : 0;
            if(!$ls_type){
                return returnData(0, '关键参数为空');
            }
            $ls_type_title = '点赞';
            if($ls_type == 2){
                $ls_type_title = '收藏';
            }
            $operation_list = Db::name('sp_operation')->where([
                ['user_id','=',$user_id],
                ['ls_type','=',$ls_type],
                ['is_del','=',0],
            ])->select();
            foreach ($operation_list as $k => &$val) {
                $content_info = [];
                switch ($val['cate_type']) {
                    case 1: 
                        $content_info = Db::name('sp_project')->where([
                            ['id','=',$val['link_id']],
                        ])->field('id,p_title,p_model,p_ad_num,p_img,cid,p_keyword')->find();
                        if(!empty($content_info)){
                            $content_info['p_img'] = setUpImg($content_info['p_img']);
                        }
                        break;
                    case 2: 
                        $content_info = Db::name('sp_news')->where([
                            ['id','=',$val['link_id']],
                        ])->field('id,title,desc,cover,keyword,click_count,author,model,url')->find();
                        if(!empty($content_info)){
                            $content_info['cover'] = setUpImg($content_info['cover']);
                        }
                        break;
                    case 4: 
                        $content_info = Db::name('sp_expert')->where([
                            ['id','=',$val['link_id']],
                        ])->field('id,name,desc,cover,images,price,vip_discount,label')->find();
                        if(!empty($content_info)){
                            $content_info['cover'] = setUpImg($content_info['cover']);
                            $content_info['images'] = setUpImg($content_info['images']);
                        }
                        break;
                }
                $val['create_time'] = isset($val['create_time']) && $val['create_time'] ? date('Y-m-d H:i:s',$val['create_time']) : '';
                $val['update_time'] = isset($val['update_time']) && $val['update_time'] ? date('Y-m-d H:i:s',$val['update_time']) : '';
                $val['content_info'] = $content_info;
            }
            if($operation_list){
                return returnData(1, $ls_type_title.'列表获取成功', $operation_list);
            }else{
                return returnData(0, '获取失败');
            }
        }
    }

    


}