<?php

namespace app\member\home;


use app\member\model\FeedbackModel;
use app\member\model\MemberModel;
use app\index\controller\Api;
use app\member\validate\FeedbackValidate;
use app\message\library\MessageServer;
use app\admin\model\SpCateModel;
use app\admin\model\SpProjectModel;
use app\admin\model\SpExpertModel;
use app\order\model\SpOrderModel;
use think\helper\Hash;
use app\member\model\MemberCreditModel;
use think\Exception;
use think\Db;

/**
 * @title 专家模块
 */
class Expert extends Api
{
    //获取专家入驻数据
    public function get_expert_base_data(){
        if ($this->request->isPost()) {
            $member         = $this->memberInfo;
            $SpCate         = SpCateModel::where('pid',4)->where('cate_show_type',1)->order('sort asc')->where('is_del',0)->column('cate_name','id');
            $filed          = Db::name('sm_class')->where('mid', 1)->where('parentid', '<>', 0)->where('url', '<>', '')->where('is_del', 0)->column('name', 'id');
            $my_expert      = SpExpertModel::where('user_id',$member['id'])->field('name,mobile,cover,cid,class_ids,desc,weichat_code,images,label,price,content,review_status')->find();
            $expert_status  = 0;//未发起申请
            if(!empty($my_expert)){
                $expert_status = 1; //审核中
                if($my_expert['review_status'] == 1){
                    $expert_status = 2; //已通过
                }
                if($my_expert['review_status'] == -1){
                    $expert_status = -1;//拒绝
                }
                unset($my_expert['review_status']);
            }
            return returnData(1, '获取成功!',['class_ids'=>$filed,'sp_cate'=>$SpCate,'my_expert'=>$my_expert,'expert_status'=>$expert_status]);
        }
    }

    //专家入驻
    public function expert_add(){ 
          if ($this->request->isPost()) {
            $post_data      = SafeFilter(input('post.'));
            $member         = $this->memberInfo;
            try{
                $my_expert      = SpExpertModel::where('user_id',$member['id'])->find();
                if (isset($post_data['name']) && !$post_data['name']) {
                    return returnData(0, '名称不能为空!');
                }
                if (isset($post_data['mobile']) && !$post_data['mobile']) {
                    return returnData(0, '联系方式不能为空!');
                }
                if (isset($post_data['cover']) && !$post_data['cover']) {
                    return returnData(0, '封面图不能为空!');
                }
                if (isset($post_data['cid']) && !$post_data['cid']) {
                    return returnData(0, '请选择项目分类!');
                }
                if (isset($post_data['class_ids']) && empty($post_data['class_ids'])) {
                    return returnData(0, '请选择擅长领域!');
                }
                //$data['class_ids'] = implode(',', $post_data['class_ids']);
                if(isset($post_data['desc']) && !$post_data['desc']){
                    return returnData(0, '请输入您的介绍!');
                }
                if(isset($post_data['price']) && !$post_data['price']){
                    return returnData(0, '请输入您的咨询价格!');
                }
                Db::startTrans();
                if(empty($my_expert)){
                    $post_data['user_id']       = $member['id'];
                    $post_data['add_time']      = time();
                    $res = SpExpertModel::insert($post_data);
                }else{
                    $post_data['update_time']  = time();
                    if($my_expert['review_status'] == 1){
                        if($post_data['name'] != $my_expert['name'] || 
                            $post_data['mobile'] != $my_expert['mobile'] || 
                            $post_data['cid'] != $my_expert['cid'] || 
                            $post_data['class_ids'] != $my_expert['class_ids'] || 
                            $post_data['price'] != $my_expert['price']){
                            $post_data['review_status'] = 0;
                        }
                    }
                    foreach ($post_data as $key => $value) {
                        if(!$value){
                            unset($post_data[$key]);
                        }
                    }
                    $res = SpExpertModel::where('id',$my_expert['id'])->update($post_data);
                }
                if ($res) {
                    Db::commit();
                    return returnData(1, '提交成功,请等待审核!');
                } else {
                    Db::rollback();
                    return returnData(0, '提交失败!');
                }
            }catch(\Exception $e){
                return returnData(0, $e->getMessage());
            }
        }
    }

    //专家咨询订单列表
    public function expert_order_list(){ 
        if ($this->request->isPost()) {
            $post_data      = SafeFilter(input('post.'));
            $page           = isset($post_data['page']) && $post_data['page']!=''?intval($post_data['page']):1;
            $limit          = isset($post_data['limit']) && $post_data['limit']!=''?intval($post_data['limit']):10;
            $member         = $this->memberInfo;
            $expert_info    = SpExpertModel::where('user_id',$member['id'])->field('id,cid,name,desc,cover,images,price,label,mobile,weichat_code')->find();
            if(!empty($expert_info)){
                $expert_info['cover']         = setUpImg($expert_info['cover']);
                $expert_info['images']        = setUpImg($expert_info['images']);
                $expert_info['weichat_code']  = setUpImg($expert_info['weichat_code']);
            }
            $order_list     = SpOrderModel::where('expert_user_id',$member['id'])->where('cate_type',4)->where('status','>=',1)->select();
            $total          = SpOrderModel::where('expert_user_id',$member['id'])->where('cate_type',4)->where('status','>=',1)->count();
            if(!empty($order_list)){
                foreach ($order_list as $key => $value) {
                    $order_list[$key]['create_time'] = isset($value['create_time']) && $value['create_time']?date('Y-m-d H:i',$value['create_time']):'';
                    $order_list[$key]['update_time'] = isset($value['update_time']) && $value['update_time']?date('Y-m-d H:i',$value['update_time']):'';
                    $order_list[$key]['pay_time']    = isset($value['pay_time']) && $value['pay_time']?date('Y-m-d H:i',$value['pay_time']):'';
                    $order_list[$key]['submit_data'] = json_decode($value['submit_data'],true);
                }
            }
            return returnData(1, '获取成功!',['expert_info'=>$expert_info,'order_list'=>$order_list,'total'=>$total]);
        }
    }
    
    //专家咨询订单详情
    public function expert_order_detail(){ 
        if ($this->request->isPost()) {
            $post_data      = SafeFilter(input('post.'));
            $order_no       = isset($post_data['order_no']) && $post_data['order_no']!=''?$post_data['order_no']:'';
            if(!$order_no){
                return returnData(0, '参数错误!');
            }
            $member         = $this->memberInfo;
            $expert_info    = SpExpertModel::where('user_id',$member['id'])->field('id,cid,name,desc,cover,images,price,label,mobile,weichat_code')->find();
            if(!empty($expert_info)){
                $expert_info['cover']         = setUpImg($expert_info['cover']);
                $expert_info['images']        = setUpImg($expert_info['images']);
                $expert_info['weichat_code']  = setUpImg($expert_info['weichat_code']);
            }
            $order_detail     = SpOrderModel::where('expert_user_id',$member['id'])->where('cate_type',4)->where('order_no',$order_no)->find();
            if(!empty($order_detail)){
                $order_detail['create_time'] = isset($order_detail['create_time']) && $order_detail['create_time']?date('Y-m-d H:i',$order_detail['create_time']):'';
                $order_detail['update_time'] = isset($order_detail['update_time']) && $order_detail['update_time']?date('Y-m-d H:i',$order_detail['update_time']):'';
                $order_detail['pay_time']    = isset($order_detail['pay_time']) && $order_detail['pay_time']?date('Y-m-d H:i',$order_detail['pay_time']):'';
                $order_detail['submit_data'] = json_decode($order_detail['submit_data'],true);
            }
            return returnData(1, '获取成功!',['expert_info'=>$expert_info,'order_detail'=>$order_detail]);
        }
    }

    //上传解惑信息
    public function expert_answer(){ 
        if ($this->request->isPost()) {
            $post_data      = SafeFilter(input('post.'));
            $member         = $this->memberInfo;
            $order_no       = isset($post_data['order_no']) && $post_data['order_no']!=''?$post_data['order_no']:'';
            if(!$order_no){
                return returnData(0, '参数错误!');
            }
            $order_detail     = SpOrderModel::where('expert_user_id',$member['id'])->where('status','in',[1,2])->where('cate_type',4)->where('order_no',$order_no)->find();
            if(empty($order_detail)){
                return returnData(0, '订单不存在!');
            }
            $answer         = isset($post_data['answer']) && $post_data['answer']!=''?$post_data['answer']:'';
            $notes          = isset($post_data['notes']) && $post_data['notes']!=''?$post_data['notes']:'';
            if(!$answer){
                return returnData(0, '参数错误!');
            }
            try {
                Db::startTrans();
                $return_data = json_encode([
                    'return_answer'=>$answer,
                    'return_notes' =>$notes,
                ]);
                $update_data = [
                    'update_time' =>time(),
                    'return_data' =>$return_data,
                    'status'      =>2,
                ];
                $order_result = SpOrderModel::where('id',$order_detail['id'])->update($update_data);
                if($order_result === false){
                    Db::rollback();
                    return returnData(0, '操作失败');
                }
                Db::commit();
                return returnData(1, '提交成功,请等待用户确认!');
            } catch (\Exception $e) {
                return returnData(0, $e->getMessage());
            }
        }
    }

    //用户确认专家订单
    public function confirm_expert_order(){
         if ($this->request->isPost()) {
            $post_data    = SafeFilter(input('post.'));
            $member       = $this->memberInfo;
            $order_no     = isset($post_data['order_no']) && $post_data['order_no']!=''?$post_data['order_no']:'';
            if(!$order_no){
                return returnData(0, '参数错误!');
            }
            $order_detail     = SpOrderModel::where('user_id',$member['id'])->where('status',2)->where('cate_type',4)->where('order_no',$order_no)->find();
            if(empty($order_detail)){
                return returnData(0, '订单不存在!');
            }
            $expert_rate    = SpExpertModel::where('id',$order_detail['expert_user_id'])->value('rate');
            if($expert_rate<=0){
                $expert_rate = config('expert_fee');
            }
            try {
                Db::startTrans();
                $update_data = [
                    'update_time' =>time(),
                    'status'      =>3,
                ];
                $order_result = SpOrderModel::where('id',$order_detail['id'])->update($update_data);
                if($order_result === false){
                    Db::rollback();
                    return returnData(0, '操作失败');
                }
                if($expert_rate){
                    $fast_price = round(bcmul($order_detail['pay_price'],$expert_rate,3),2);
                    if($fast_price>=0.01){
                        $credit_result = MemberCreditModel::credit(
                                $order_detail['expert_user_id'],
                                'deposit',
                                $fast_price,
                                $member['name'].'用户咨询佣金到账',
                                '',
                                $order_no,
                                'expert_commission'
                            );
                        if($credit_result === false){
                            Db::rollback();
                            return returnData(0, '操作失败');
                        }
                    }
                }
                Db::commit();
                return returnData(1, '确认成功,感谢您的认可!');
            } catch (\Exception $e) {
                return returnData(0, $e->getMessage());
            }
         }
    }

}