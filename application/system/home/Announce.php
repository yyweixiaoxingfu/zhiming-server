<?php
namespace app\system\home;
use app\index\controller\Home;
use app\system\model\AnnounceModel;
use app\system\model\HelpCenterModel;
use app\system\model\HelpSetModel;

/**
 * @title 公告&帮助中心
 * Class Announce
 * @package app\system\home
 */
class Announce extends Home
{
    /**
     * @title 公告列表
     * @param name:page type:number require:1 default: desc:当前页
     * @param name:page_size type:string require:1 default: desc:每页展示条数
     * @author zxn
     * @url /system/announce/announce_list
     * @method POST
     */
    public function announce_list()
    {
        $list =  AnnounceModel::field('id,name,create_time')
            ->order('sort asc,create_time desc')
            ->page($this->page, $this->pageSize)
            ->select()
            ->withAttr('create_time', function ($value, $data) {
            return format_time($value);
        });
        return returnData(1,'success',['data'=>$list]);

    }

    /**
     * @title 公告详情
     * @param name:announce_id type:number require:1 default: desc:公告id
     * @author zxn
     * @url /system/announce/announce_detail
     * @method POST
     */
    public function announce_detail()
    {
        if ($this->request->isPost()) {
            $post_data     = SafeFilter(input('post.'));
            $id            = isset($post_data['announce_id']) && $post_data['announce_id'] ? (int)$post_data['announce_id'] : '';
            $data = AnnounceModel::where('id',$id)
                ->field('id,name,detail,create_time')
                ->find()
                ->withAttr('detail', function ($value, $data) {
                    return Img_url($value);
                })
                ->withAttr('create_time', function ($value, $data) {
                return format_time($value);
            });
            return returnData(1,'success',['data'=>$data]);
        }
        

    }

    /**
     * @title 帮助中心
     * @param name:page type:number require:1 default: desc:当前页
     * @param name:page_size type:string require:1 default: desc:每页展示条数
     * @author zxn
     * @url /system/announce/help_center
     * @method POST
     */
    public function help_center()
    {
        $list = HelpSetModel::with('helpCenter')->order('sort asc,create_time desc')->field('id,name') ->page($this->page, $this->pageSize)->select();
        return returnData(1,'success',['data'=>$list]);

    }

    /**
     * @title 帮助中心详情
     * @param name:id type:number require:1 default: desc:帮助id
     * @author zxn
     * @url /system/announce/help_center_detail
     * @method POST
     */
    public function help_center_detail()
    {
        if ($this->request->isPost()) {
            $post_data     = SafeFilter(input('post.'));
            $id            = isset($post_data['id']) && $post_data['id'] ? (int)$post_data['id'] : '';
            if(!$id){
                 return returnData(0,'参数错误');
            }
            $data = HelpCenterModel::where('id',$id)
                ->field('id,name,content,create_time')
                    ->find()
                ->withAttr('content', function ($value, $data) {
                    return Img_url($value);
                })
                ->withAttr('create_time', function ($value, $data) {
                    return format_time($value);
                });

            return returnData(1,'success',['data'=>$data]);
        }
        

    }


}
