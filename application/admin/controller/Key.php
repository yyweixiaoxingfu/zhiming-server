<?php
//兑换码
namespace app\admin\controller;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\member\model\MemberModel;
use think\Db;

class Key extends Admin
{
    //兑换码列表
    public function index()
    {
        $order     = $this->getOrder('id desc');
        $map       = $this->getMap();
        $data_list = Db::name('sp_key')->where($map)->order($order)->paginate()->each(function ($item) {
            $item['days'] = '';
            if($item['day']){
                $item['days'] = $item['day'].'天 ';
            }
            if($item['house']){
                $item['days'] .= $item['house'].'小时 ';
            }
            if($item['minutes']){
                $item['days'] .= $item['minutes'].'分钟';
            }
            if($item['user_id']){
                $item['user_name'] = MemberModel::where('id', $item['user_id'])->value('name');
            }
            return $item;
        });
        $topBtn    = [
            'add'       => ['class' => 'btn btn-warning', 'href' => url('add')],
            //'delete'    => ['href' => url('delete')],
        ];
        $rightBtn = [
            'edit'       => ['class' => 'btn btn-sm btn-warning', 'href' => url('edit', ['id' => '__id__'])],
            'delete'     => ['class' => 'btn btn-sm btn-danger ajax-get confirm', 'href' => url('del', ['id' => '__id__'])],
        ];
        return ZBuilder::make('table')
            ->hideCheckbox()
            ->setSearchArea([
                ['text:1', 'id', 'ID'],
                ['text:2', 'key', '兑换码'],
                ['text:2', 'user_id', '用户ID'],
            ])
            ->addColumns([
                ['id', 'ID'],
                ['key', '兑换码'],
                ['days', '有效期', 'text'],
                ['user_name', '使用用户'],
                ['star_time', '开始时间', 'datetime'],
                ['end_time', '结束时间', 'datetime'],
                ['status', '状态', 'status', '', [0=>'未使用', 1=>'使用中', 2=>'已过期']],
                ['create_time', '创建时间', 'datetime'],
                ['update_time', '更新时间', 'datetime'],
                ['right_button', '操作', 'btn'],
            ])
            ->setRowList($data_list)
            ->addTopButtons($topBtn)   // 添加授权按钮
            ->addRightButtons($rightBtn)
            ->setExtraCss($this->css)
            ->setColumnWidth([
                'id'            => 30,
                'key'           => 115,
                'status'        => 60,
                'right_button'  => 60,
            ])
            ->fetch();
    }

    public function add(){
        if ($this->request->isPost()) {
            $post_data = $this->request->post();
            if(!$post_data['day'] && !$post_data['house'] && !$post_data['minutes']){
                return returnData(0, '请设置有效期');
            }
            if(!$post_data['key_count']){
                return returnData(0, '请设置兑换码数量');
            }
            $data = [];
            for ($i=0; $i < $post_data['key_count']; $i++) { 
                $insert_data['key']         = generateCardKey ('REC',15);   
                $insert_data['create_time'] = time();
                $insert_data['day']         = $post_data['day'];
                $insert_data['house']       = $post_data['house'];
                $insert_data['minutes']     = $post_data['minutes'];
                $data[] = $insert_data;
            }
            $res                 = Db::name('sp_key')->insertAll($data);
            if ($res) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'key_count', '兑换码数量'],
                ['number', 'day', '有效期天数'],
                ['number', 'house', '有效期小时'],
                ['number', 'minutes', '有效期分钟'],
            ])
            ->layout(['key_count' => 5, 'day' => 2, 'house' => 2, 'minutes' => 2])
            ->fetch();
    }

    public function edit($id = null){
        if ($this->request->isPost()) {
            $post_data = $this->request->post();
            if(isset($post_data['day']) || isset($post_data['house']) || isset($post_data['minutes'])){
                if(!$post_data['day'] && !$post_data['house'] && !$post_data['minutes']){
                    return returnData(0, '请设置有效期');
                }
            }
            $key_data = Db::name('sp_key')->where('id', $id)->find();
            if(empty($key_data)){
                return returnData(0, '信息不存在');
            }
            if(isset($post_data['user_id']) && $post_data['user_id']){
                $user_info = MemberModel::where('id', $post_data['user_id'])->find();
                if(!$user_info){
                    return returnData(0, '用户不存在');
                }
                $post_data['status'] = 1;
                $time                   = time();
                $post_data['star_time'] = date('Y-m-d H:i:s', $time);   
                $post_data['end_time']  = date('Y-m-d H:i:s', $time+$post_data['day']*86400+$post_data['house']*3600+$post_data['minutes']*60);
            }
            if(isset($post_data['star_time']) || isset($post_data['end_time'])){
                if($post_data['status'] == 1){
                    if(!$post_data['star_time'] || !$post_data['end_time']){
                        return returnData(0, '请设置有效期');
                    }
                    $post_data['star_time'] = strtotime($post_data['star_time']);
                    $post_data['end_time']  = strtotime($post_data['end_time']);
                    if($post_data['star_time'] >= $post_data['end_time']){
                        return returnData(0, '开始时间不能大于结束时间');
                    }
                    $fast_time = abs($post_data['end_time']-$post_data['star_time']);
                    // 将秒数转换为天、小时、分钟、秒
                    $post_data['day']    = floor($fast_time / (60 * 60 * 24));
                    $remainingSeconds    = $fast_time % (60 * 60 * 24);
                    $post_data['house']  = floor($remainingSeconds / (60 * 60));
                    $remainingSeconds    %= (60 * 60);
                    $post_data['minutes']= floor($remainingSeconds / 60);
                }else{
                    $post_data['end_time'] = time();
                    $fs_time               = 0;
                    if($key_data['day']){
                        $fs_time = $key_data['day']*86400;
                    }
                    if($key_data['house']){
                        $fs_time += $key_data['house']*3600;
                    }
                    if($key_data['minutes']){
                        $fs_time += $key_data['minutes']*60;
                    }
                    $post_data['star_time'] = $post_data['end_time']-$fs_time;
                }
                $post_data['update_time'] = time();
                //$seconds            = $remainingSeconds % 60;   
            }
           
            $res = Db::name('sp_key')->where('id', $id)->update($post_data);
            if ($res) {
                $this->success('更新成功', 'index');
            } else {
                $this->error('更新失败');
            }
        }
        if (!$id) {
            $this->error('参数错误');
        }
        $info = Db::name('sp_key')->where('id', $id)->find();
        switch ($info['status']) {
            case 0:
                 $FormItems = [
                    ['text', 'key', '兑换码', '', $info['key']],
                    ['text', 'user_id', '用户ID', '', $info['user_id']],
                    ['number', 'day', '有效期天数', '', $info['day']],
                    ['number', 'house', '有效期小时', '', $info['house']],
                    ['number', 'minutes', '有效期分钟', '', $info['minutes']],
                ];
                break;
            case 1:
                $FormItems = [
                    ['datetime', 'star_time', '开始时间', '', $info['star_time']],
                    ['datetime', 'end_time', '结束时间', '', $info['end_time']],
                    ['radio', 'status', '状态',  '', [1 => '使用中', 2 => '已过期']],
                    ['hidden', 'user_id', $info['user_id']],
                ];
                break;
            case 2:
                $this->error('已过期，不能编辑');
                break;
        }
        $FormItems[] = ['hidden', 'id'];
        return ZBuilder::make('form')
            ->addFormItems($FormItems)
            ->setFormData($info)
            ->layout(['key_count' => 5, 'day' => 2,'star_time'=>4, 'end_time'=>4,'house' => 2, 'minutes' => 2])
            ->fetch();
    }

    public function del($id = null){
        if (!$id) {
            $this->error('参数错误');
        }
        $info = Db::name('sp_key')->where('id', $id)->find();
        if(empty($info)){
            $this->error('兑换码不存在');
        }
        if($info['status'] == 1){
            $this->error('该兑换码正在使用中,不能删除');
        }
        $res = Db::name('sp_key')->where('id', $id)->delete();
        if ($res) {
            $this->success('删除成功', 'index');
        } else {
            $this->error('删除失败');
        }
    }
}