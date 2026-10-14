<?php

namespace app\order\admin;

use app\admin\controller\Admin;
use app\order\home\SpOrder as HomeSpOrder;
use app\common\builder\ZBuilder;
use app\common\library\ExcelServer;
use app\member\model\MemberCreditModel;
use app\member\model\MemberModel;
use app\order\model\SpOrderModel;
use app\admin\model\SpProjectModel;
use app\admin\model\SpCateModel;
use app\admin\model\SpExpertModel;
use app\payment\model\PayModel;
use app\member\transformer\MemberTransformer;
use think\Db;
use think\facade\Env;
use think\Exception;

/**
 * 用户默认控制器
 * @package app\user\admin
 */
class SpOrder extends Admin
{
    //订单列表
    public function index($group='cs_index',$cate_type=1)
    {
        $order      = $this->getOrder('create_time desc,id desc');
        $map        = $this->getMap();
        $maps       = urlencode(json_encode($map));
        $list_tab = [
            'cs_index' => ['title' => '功能测算订单', 'url' => url('index', ['group' => 'cs_index','cate_type' => 1])],
            'ex_index' => ['title' => '专家咨询订单', 'url' => url('index', ['group' => 'ex_index','cate_type' => 4])], 
        ];
        $js = <<<EOF
            <script type="text/javascript">
                $(function(){
                    $('.pop').find("a").attr("data-layer",'{"area":["500px","70%"]}')
                });
            </script>
EOF;
        $data_list  = SpOrderModel::where($map)
            ->where('cate_type',$cate_type)
            ->order($order)
            ->paginate(50)
            ->each(function ($item) {
                $item['ex_user_info'] = '';
                if($item['cate_type']==4 && $item['expert_user_id']){
                   $item['ex_user_info'] = MemberModel::geMemberInfo($item['expert_user_id']);
                }
                if($item['cate_type']==1 ){
                    $item['cs_result'] = '未出结果';
                    if($item['status']==1){
                          $item['cs_result'] = '查看结果';
                    }

                }
                $item['member'] = MemberModel::geMemberInfo($item['user_id']);
                return $item;
            });
        $order_status = [
            -1 => '已失效',
            0  => '未支付',
            1  => '已支付',
            2  => '已回复',
            3  => '已确认',
        ];
        if($cate_type==1){
            $Columns = [
                ['id', 'ID'],
                ['order_no', '订单号', 'text'],
                ['member', '用户信息', 'text'],
                ['pay_price', '支付金额', 'text'],
                ['status', '支付状态', 'status', '', $order_status],
                ['pay_type', '支付方式', 'status', '', PayModel::getPayTypes()],
                ['cs_title', '测算内容', 'link',url('look_job',['link_id'=>'__link_id__','cate_type'=>$cate_type]), '_blank', 'pop', true],
                ['create_time', '下单时间', 'datetime'],
                ['pay_time', '支付时间', 'datetime'],
                ['update_time', '更新时间', 'datetime'],
                ['right_button', '操作', 'btn'],
            ];
        }elseif($cate_type==4){ 
             $Columns = [
                ['id', 'ID'],
                ['order_no', '订单号', 'text'],
                ['member', '用户信息', 'text'],
                ['pay_price', '支付金额', 'text'],
                ['status', '支付状态', 'status', '', $order_status],
                ['pay_type', '支付方式', 'status', '', PayModel::getPayTypes()],
                ['cs_title', '咨询服务', 'link',url('look_job',['link_id'=>'__link_id__','cate_type'=>$cate_type]), '_blank', 'pop', true],
                ['ex_user_info', '专家信息', 'text'],
                ['create_time', '下单时间', 'datetime'],
                ['pay_time', '支付时间', 'datetime'],
                ['update_time', '更新时间', 'datetime'],
                ['right_button', '操作', 'btn'],
             ];
        }
         return ZBuilder::make('table')
            ->setTabNav($list_tab,  $group)
            ->hideCheckbox()
            ->setTableName('sp_order')
            ->addOrder('id') // 添加排序
            ->setSearchArea([
                ['text:1', 'id', 'ID'],
                ['text:2', 'user_id', '用户ID'],
                ['text:2', 'order_no', '订单号'],
                ['text:3', 'cs_title', '测算内容', 'like'],
                ['select:3', 'pay_type','支付方式','','',PayModel::getPayTypes()],
                ['select:2', 'status','支付状态','','',$order_status],
            ])
            ->addColumns($Columns)
            ->setRowList($data_list)
          
            ->addRightButton('cs_edit',[ 'title' => '测算编辑','class' => 'btn btn-sm btn-warning','icon'=>"fa fa-pencil", 'href' => url('cs_order_edit', ['id' => '__id__'])], ['area' => ['800px', '800px']])
            ->addRightButton('ex_edit',['title' => '咨询编辑','class' => 'btn btn-sm btn-warning','icon'=>"fa fa-pencil", 'href' => url('ex_order_edit', ['id' => '__id__'])], ['area' => ['800px', '800px']])
            ->addRightButton('look', ['class' => 'btn btn-sm btn-info', 'title' => '查看测算结果','icon' => 'fa fa-fw fa-eye', 'href' => url('look_result', ['id' => '__id__'])], ['area' => ['500px', '70%']])
            //->addRightButton('delete',['class' => 'btn btn-sm btn-danger ajax-get confirm', 'href' => url('order_del', ['id' => '__id__'])])
            //->addTopButton('export', ['title' => '导出', 'class' => 'btn btn-danger confirm', 'href' => url('export', ['map' => $maps])])
            ->setColumnWidth([
                'id'            => 50,
            ])
            ->replaceRightButton(['cate_type' => 1], '', 'ex_edit')
            ->replaceRightButton(['cate_type' => 4], '', 'cs_edit')
            ->replaceRightButton(['status' => 1,'cate_type' => 1], '', 'cs_edit,delete')
            ->replaceRightButton(['status' => 0,'cate_type' => 1], '', 'look')
            ->replaceRightButton(['status' => ['in','2,3'],'cate_type' => 4], '', 'delete')
            ->replaceRightButton(['status' => ['in','0,1'],'cate_type' => 4], '', 'look')
            ->setExtraJs($js)
            ->setExtraCss($this->css)
            ->fetch();
    }

    public function look_job(){
        $id         = input('link_id');
        $cate_type  = input('cate_type');
        if($cate_type==1){
            $sm_data = SpProjectModel::where('id', $id)->find();
            if (!$sm_data) {
                $this->error('数据不存在', url('index'));
            }
            $data = json_decode($sm_data['p_content'], true);
            $url  = $sm_data['p_url'];
            if (!$url) {
                $this->error('绑定链接不存在', url('index'));
            }
            $sm_cate = SpCateModel::where('id', $sm_data['cid'])->find();
            if(!$sm_cate){
                $this->error('分类不存在', url('index'));
            }
            $url = $url.'?cate_type='.$sm_cate['cate_type'].'&link_id='.$sm_data['id'];
        }elseif($cate_type==4){ 
            $sm_data = SpExpertModel::where('id', $id)->find();
            if (!$sm_data) {
                $this->error('数据不存在', url('index'));
            }
            $url  = config('web_url').'pages/index/expert'.'?cate_type='.$cate_type.'&link_id='.$sm_data['id'];
            $data['id']    = $sm_data['id'];
            $data['desc3'] = '';
        }
        $js = <<<EOF
            <script>
                var html =  '<div class="iframe_model" style="float: left;padding-right:2rem;padding-left:2rem;"><iframe src="$url" style="width: 400px;height:215rem;"></iframe></div>';
                $(function(){
                    $('#form_group_desc3').before(html);
                })
            </script>
EOF;
        unset($data['id']);
        return ZBuilder::make('form')
            ->addFormItems([
                ['hidden', 'desc3', $data['desc3']],
                ['hidden', 'id', $sm_data['id']],
            ])
            ->setExtraJs($js)
            ->hideBtn('submit,back')
            ->setFormData($data)
            ->fetch();

    }

    public function look_result(){
        $id         = input('id');
        $order_data = SpOrderModel::where('id', $id)->find();
        if(!$order_data){
            $this->error('数据不存在', url('index'));
        }
        if($order_data['status']<=0){
              $this->error('订单状态有误', url('index'));
        }
        $order_on     = $order_data['order_no'];
        $cate_type    = $order_data['cate_type'];
        $link_id      = $order_data['link_id'];
        $data['id']   = $order_data['id'];
        $data['desc'] = '';
        if($cate_type==1){
            $p_model = SpProjectModel::where('id', $link_id)->value('p_model');;
            if($link_id==19 || $p_model=='zidian'){
                $return_data = json_decode($order_data['return_data'], true);
                return ZBuilder::make('form')
                    ->addFormItems([
                        ['text', 'key', 'Key','', $return_data['key'],'','disabled'],
                        ['hidden', 'id', $id],
                    ])
                    ->hideBtn('submit,back')
                    ->setFormData($data)
                    ->fetch();
            }else{
                $url     = config('web_url').'pages/result/'.$p_model.'?order_no='.$order_on;
                $js = <<<EOF
                <script>
                    var html =  '<div class="iframe_model" style="float: left;padding-right:2rem;padding-left:2rem;"><iframe src="$url" style="width: 400px;height:215rem;"></iframe></div>';
                    $(function(){
                        $('#form_group_desc').before(html);
                    })
                </script>
EOF;    
                unset($data['id']);
                return ZBuilder::make('form')
                    ->addFormItems([
                        ['hidden', 'desc', $data['desc']],
                        ['hidden', 'id', $id],
                    ])
                    ->setExtraJs($js)
                    ->hideBtn('submit,back')
                    ->setFormData($data)
                    ->fetch();
            }
           
        }elseif($cate_type==4){
            $return_data = json_decode($order_data['return_data'], true);
            return ZBuilder::make('form')
                ->addFormItems([
                    ['gallery', 'return_answer', '答案素材','',$return_data['return_answer']],
                    ['textarea', 'return_notes', '备注留言','大师对用户的留言',$return_data['return_notes'], 'disabled'],
                    ['hidden', 'id', $id],
                ])
                ->hideBtn('submit,back')
                ->setFormData($order_data)
                ->fetch();
        }
    }

    public function cs_order_edit($id=0){
        if ($this->request->isPost()) {
            $post_data = SafeFilterItem(input('post.'));
            $order_find = SpOrderModel::where('id', $post_data['id'])->find();
            if($order_find['status']>=1){
                $this->error('订单状态有误');
            }
            if($post_data['status']==1 && $order_find['cate_type']==1){
                $find_data = SpProjectModel::where('id',$order_find['link_id'])->find();
                $time = time();
                Db::startTrans();
                $res = SpOrderModel::where('order_no',$order_find['order_no'])->update([
                    'status'        =>1,
                    'pay_time'      =>$time,
                    'update_time'   =>$time,
                    'pay_type'      =>'frequency',
                ]);
                if($res === false){
                    Db::rollback();
                    $this->error('操作失败');
                }
                $key = ''; 
                // 调用独立的测算处理方法
                $HomeSpOrder       = new HomeSpOrder;
                $calculationResult = $HomeSpOrder->processCalculation($order_find, $find_data, $order_find['user_id'], $time, $order_find['order_no']);
                if(!$calculationResult['success']){
                    Db::rollback();  
                    $this->error($calculationResult['message']);
                }
                Db::commit();
                $this->success('修改成功', '', '_parent_reload');
            }
        }
        $order_data = SpOrderModel::where('id', $id)->find();
        if(!$order_data){
            $this->error('数据不存在');
        }
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'order_no', '订单号','', $order_data['order_no'],'','disabled'],
                ['textarea', 'submit_data', '提交数据','文本框内为原始提交数据',$order_data['submit_data'], 'disabled'],
                ['textarea', 'format_data', '格式化数据','文本框内为格式化后用于测算的数据',$order_data['format_data'], 'disabled'],
                ['radio', 'status', '状态', '', [0 => '未付款', 1 => '已付款'], 0],
                ['hidden', 'id', $id],
            ])
            ->setFormData($order_data)
            ->fetch();

    }

    public function ex_order_edit($id=0){
        if ($this->request->isPost()) {
            $post_data   = SafeFilterItem(input('post.'));
            $order_find  = SpOrderModel::where('id', $post_data['id'])->find();
            $return_data = json_decode($order_find['return_data'], true);
            $time        = time();
            if($post_data['status']<$order_find['status']){
                $this->error('订单状态有误');
            }
            $update_data = [
                'update_time'   =>$time,
            ];
            $expert_rate    = SpExpertModel::where('id',$order_find['expert_user_id'])->value('rate');
            if($expert_rate<=0){
                $expert_rate = config('expert_fee');
            }
            $member         = MemberModel::where('id',$order_find['user_id'])->find();
            Db::startTrans();
            switch($post_data['status']){
                case 1:
                    $update_data['status'] = 1;
                    $update_data['pay_type'] = 'frequency';
                    $update_data['pay_time'] = $time;
                    break;
                case 2:
                    if(!$post_data['return_answer']){
                        $this->error('请上传回复的图片');
                    }
                    $return_data['return_answer'] = setUpImg($post_data['return_answer']);
                    if($post_data['return_notes']){
                        $return_data['return_notes'] = $post_data['return_notes'];
                    }
                    if(!$order_find['pay_type']){
                        $update_data['pay_type'] = 'frequency';
                    }
                    if(!$order_find['pay_time']){
                        $update_data['pay_time'] = $time;
                    }
                    $update_data['status']      = 2;
                    $update_data['return_data'] = json_encode($return_data);
                    break;
                case 3:
                    if((empty($return_data) || isset($return_data['return_answer'])) && !$post_data['return_answer']){
                         $this->error('请上传回复的图片');
                    }
                    if($post_data['return_answer']){
                        $return_data['return_answer'] = setUpImg($post_data['return_answer']);
                    }
                    if($post_data['return_notes']){
                        $return_data['return_notes'] = $post_data['return_notes'];
                    }
                    if(!empty($return_data)){
                        $update_data['return_data'] = json_encode($return_data);
                    }
                    if($order_find['status']<3){
                        $update_data['status'] = 3;
                        $credit_log = Db::name('member_credit_logs')->where('member_id',$order_find['expert_user_id'])->where('cate','expert_commission')->where('target_id',$order_find['order_no'])->find();
                        if($order_find['expert_user_id'] && $order_find['pay_type'] && $order_find['pay_type']!='frequency' && empty($credit_log)){
                            if($expert_rate){
                                $fast_price = round(bcmul($order_find['pay_price'],$expert_rate,3),2);
                                if($fast_price>=0.01){
                                    $credit_result = MemberCreditModel::credit(
                                            $order_find['expert_user_id'],
                                            'deposit',
                                            $fast_price,
                                            $member['name'].'用户咨询佣金到账',
                                            '',
                                            $order_find['order_no'],
                                            'expert_commission'
                                        );
                                    if($credit_result === false){
                                        Db::rollback();
                                        $this->error('操作失败');
                                    }
                                }
                            }
                        }
                    }
                    break;
            }
            $res = SpOrderModel::where('order_no',$order_find['order_no'])->update($update_data);
            if($res === false){
                Db::rollback();
                $this->error('操作失败');
            }
            Db::commit();
            $this->success('修改成功', '', '_parent_reload');
        }
        $order_data = SpOrderModel::where('id', $id)->find();
        if(!$order_data){
            $this->error('数据不存在');
        }
        $submit_data  = json_decode($order_data['submit_data'], true);
        $return_data  = json_decode($order_data['return_data'], true);
        if(empty($return_data)){
            $return_data['return_answer'] = '';
            $return_data['return_notes'] = '';
        }
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'order_no', '订单号','', $order_data['order_no'],'','disabled'],
                ['text', 'name', '姓名','', $submit_data['name'],'','disabled'],
                ['radio', 'sex', '性别','',[ 0 => '女',1 => '男'],$submit_data['sex'],'','disabled'],
                ['text', 'birthday', '生日','', $submit_data['birthday'],'','disabled'],
                ['textarea', 'seek_content', '咨询内容','用户咨询的内容',$submit_data['seek_content'], 'disabled'],
                ['radio', 'status', '状态', '', [0 => '未付款', 1 => '已付款',2=>'已解惑',3=>'已确认'], 0],
                ['image', 'return_answer', '回复内容','请上传回复的图片',$return_data['return_answer']],
                ['textarea', 'return_notes', '备注','给用户的备注信息',$return_data['return_notes']],
                ['hidden', 'id', $id],
            ])
            ->setFormData($order_data)
            ->fetch();
    }

}