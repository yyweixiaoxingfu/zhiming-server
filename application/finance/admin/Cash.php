<?php


namespace app\finance\admin;


use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\common\library\ExcelServer;
use app\finance\library\DoCash;
use app\finance\model\MemberCashModel;
use app\member\model\MemberCreditModel;
use app\member\model\MemberModel;
use think\Db;

class Cash extends Admin
{
    public function index($member_id='') {
        // 查询
        $map = $this->getMap();
        if(!empty($member_id)){
            $map[]=['C.member_id','=',$member_id];
        }
        $maps = urlencode(json_encode($map));
        // 数据列表
        $current = MemberCreditModel::getCreditName();
        $data_list = $this->index_data($map,0);
        $data =   MemberCashModel::alias('C')
            ->leftJoin('w_member M','C.member_id=M.id')
            ->where($map)
            ->field('sum(C.amount) as total,sum(C.charge) as fee,sum(C.cash) as cash,count(C.id) as num')
            ->find();
        $total = $data['total'];  //总提现金额
        $fee = $data['fee'];  //手续费金额
        $cash = $data['cash'];  //实际打款金额
        $num = $data['num'];  //实际打款金额
        $btn_access = [
            'title' => '通过',
            'icon'  => 'fa fa-fw fa-key',
            'data-title' => '您正在通过用户提现审核，确认继续吗？',
            'class' => 'btn btn-primary ajax-post confirm',
            'href'  => url('agree')
        ];
        $btn_access_r = [
            'title' => '驳回',
            'icon'  => 'fa fa-fw fa-sliders',
            'class' => 'btn btn-danger ajax-post confirm',
            'href'  => url('reject')
        ];
        $html = <<<EOF
<style>
    .tableReset{margin: 20px auto;border-collapse:collapse}
    .tableReset td, .tableReset th{border:1px solid #ddd !important;}
    .tableReset tr:last-child{background-color: #FFFCEF !important;}
</style>
<table class="tableReset table table-builder table-hover table-bordered table-striped js-table-checkable">
    <tr>
        <th>申请提现总金额</th>
        <th>手续费金额</th>
        <th>实际打款金额</th>
        <th>提现次数</th>
    </tr>
    <tr>
        <td>{$total}(元)</td>
        <td>{$fee}(元)</td>
        <td>{$cash}(元)</td>
        <td>{$num}(次)</td>

    </tr>
</table>
EOF;
        $model = new MemberCashModel();
        return ZBuilder::make('table')
            ->setTableName($model->getName())
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['member', '会员信息', 'text'],
                ['amount', '提现金额', 'text'],
                ['charge', '手续费', 'text'],
                ['cash', '到账金额', 'text'],
                ['type', '提现钱包','status','',['deposit'=>$current['deposit']]],
                ['method', '提现方式','status','',MemberCashModel::getMethods()],
                ['name', '姓名', 'text'],
                ['account', '帐号', 'text'],
                ['bank', '银行', 'text'],
                ['mobile', '预留手机号', 'text'],
                ['receiving_code', '微信收款码', 'picture'],
                ['status', '状态','status','',MemberCashModel::getStatus()],
                ['create_time', '提现时间', 'datetime'],
                ['deal_time','审核时间','datetime'],
                ['right_button', '操作', 'btn']
            ])
            ->setPrimaryKey('id')
            ->addTopButton('agree', $btn_access) // 添加授权按钮
            ->addTopButton('reject', $btn_access_r) // 添加授权按钮
            ->addOrder('id')
            ->setSearchArea([
                // ['text:3', 'C.id', 'ID'],
                ['text:3', 'C.member_id', '会员ID', 'like'],
                ['text:3', 'M.name', '会员名称', 'like'],
                ['text:3', 'M.mobile', '会员手机号', 'like'],
                ['text:3', 'C.account', '账号', 'like'],
                ['text:3', 'C.name', '姓名', 'like'],
                ['select', 'status', '状态', '', '', MemberCashModel::getStatus()],
                ['daterange', 'C.create_time', '申请提现时间', '', '', ['time-picker' => 'true', 'time-picker24-hour' => 'true','format' => 'YYYY-MM-DD HH:mm']],
                ['daterange', 'C.deal_time', '审核时间', '', '', ['time-picker' => 'true', 'time-picker24-hour' => 'true','format' => 'YYYY-MM-DD HH:mm']],
            ])
            ->setExtraCss($this->css)
            ->setExtraHtml($html, 'block_top')
            ->setColumnWidth(['id'=>80,'member' => 180,'card_no'=>150,'create_time'=>150,'deal_time'=> 150,'right_button'=>150])
            // ->addTopButton('delete')
            ->addRightButton('agree',['title'=>'通过','icon'=>'fa fa-fw fa-check-circle-o','class' => 'btn btn-sm btn-success ajax-get confirm','data-title' => '您正在通过用户提现审核，确认继续吗？','href' => url('agree', ['id'=>'__id__'])])
            ->addRightButton('reject',['title'=>'驳回','icon'=>'fa fa-fw fa-times-circle-o','class' => 'btn btn-sm btn-danger ajax-get confirm','data-title' => '确定要驳回吗？','href' => url('reject', ['id'=>'__id__'])])
            ->addRightButton('mark_payed',['title'=>'标记已打款','icon'=>'fa fa-fw fa-hand-o-up','class' => 'btn btn-sm btn-info ajax-get confirm','data-title' => '此操作会员提现状态标记为已打款状态（不进行实际打款操作），确认继续吗？','href' => url('mark_payed', ['id'=>'__id__'])])
            ->addTopButton('export', ['title' => '导出', 'icon' => 'fa fa-fw fa-upload', 'class' => 'btn btn-info confirm', 'href' => url('export', ['map' => $maps])])
            // ->addRightButton('delete',['class' => 'btn btn-sm btn-danger ajax-get confirm'])
            ->replaceRightButton(['status'=>['<>','review']],'','agree')
            ->replaceRightButton(['status'=>['in',['agree','reject','payed']]],'','reject')
            ->replaceRightButton(['status'=>['in',['reject','payed']]],'','mark_payed')
            ->setRowList($data_list)
            ->fetch(); // 渲染模板
    }

    public function agree() {
        if ($this->request->isPost()) {
            $POST = input('post.');
            $ids =  $POST['ids'];
            if(empty($ids)){
                $this->error('请选择要操作的数据');
            }
            $info = MemberCashModel::where('id','in',$ids)->select();
        } else {
            $id  = input('id');
            if(empty($id)){
                $this->error('请选择要操作的数据');
            }
            $info = MemberCashModel::where('id','=',$id)->select();
        }
        if($info->isEmpty()){
            $this->error('数据有误');
        }
        $auto_pay = (bool) config('withdraw_auto_pay');
        foreach ($info as $v){
            if($v['status'] !='review'){
                continue;
            }
            Db::startTrans();
            $xx = MemberCashModel::where(['status'=>'review','id'=>$v['id']])->update(['status'=>'agree','deal_time'=>time()]);
            if(empty($xx)){
                Db::rollback();
                $this->error('审核失败');
            }
            // 在线打款
            if($auto_pay){
                $logs = DoCash::doCash($v, $v['method'],[],$msg);
                if(empty($logs)){
                    Db::rollback();
                    $this->error(empty($msg) ? '打款失败' : $msg);
                }
                if($logs['code'] == 'success'){
                    MemberCashModel::where(['id'=>$v['id']])->update(['status'=> $logs['status'],'pay_time'=>time(),'trans_id'=>$logs['trans_id']]);
                }else{
                    Db::rollback();
                    $this->error($logs['message']);
                }
            }
            // end
            Db::commit();
        }
        $this->success('操作成功');
    }


    public function reject() {
        if ($this->request->isPost()) {
            $POST = input('post.');
            $ids =  $POST['ids'];
            if(empty($ids)){
                $this->error('请选择要操作的数据');
            }
            $info = MemberCashModel::where('id','in',$ids)->select();
        } else {
            $id  = input('id');
            if(empty($id)){
                $this->error('请选择要操作的数据');
            }
            $info = MemberCashModel::where('id','=',$id)->select();
        }
        if($info->isEmpty()){
            $this->error('数据有误');
        }
        Db::startTrans();
        foreach($info as $k=>$v){
            if($v['status'] !='review' && $v['status'] != 'ongoing'){
                continue;
            }
            $xx = MemberCashModel::where(['id'=>$v['id']])->update(['status'=>'reject','deal_time'=>time()]);
            if(empty($xx)){
                Db::rollback();
                $this->error('审核失败');
            }
            $logs = MemberCreditModel::credit($v['member_id'], 'deposit', $v['amount'], '提现驳回退回', '', $v['id'], 'cash_reject', UID);
            if(empty($logs)){
                Db::rollback();
                $this->error('审核失败');
            }
        }
        Db::commit();
        $this->success('审核成功');
    }

    public function mark_payed() {
        if ($this->request->isPost()) {
            $POST = input('post.');
            $ids =  $POST['ids'];
            if(empty($ids)){
                $this->error('请选择要操作的数据');
            }
            $info = MemberCashModel::where('id','in',$ids)->select();
        } else {
            $id  = input('id');
            if(empty($id)){
                $this->error('请选择要操作的数据');
            }
            $info = MemberCashModel::where('id','=',$id)->select();
        }
        if($info->isEmpty()){
            $this->error('数据有误');
        }
        Db::startTrans();
        foreach($info as $k=>$v){
            if($v['status'] !='review' && $v['status'] != 'agree' && $v['status'] != 'ongoing'){
                continue;
            }
            $xx = MemberCashModel::where(['id'=>$v['id']])->update(['status'=>'payed','pay_time'=>time()]);
            if(empty($xx)){
                Db::rollback();
                $this->error('操作失败');
            }
        }
        Db::commit();
        $this->success('操作成功');
    }

    public function export($map=[])
    {
        $map = json_decode(urldecode(urldecode($map)));
        $data = $this->index_data($map,1);
        $title = [
            ['id', 'ID'],
            ['member_name', '会员'],
            ['member_mobile', '会员手机号'],
            ['amount', '提现金额'],
            ['charge', '手续费'],
            ['cash', '到账金额'],
            ['type', '提现钱包'],
            ['method', '提现方式'],
            ['name', '姓名'],
            ['account', '帐号'],
            ['bank', '银行'],
            ['mobile', '预留手机号'],
            ['status', '状态'],
            ['create_time', '提现时间'],
            ['deal_time','审核时间'],
        ];
        ExcelServer::export_old('提现记录',$title,$data);
    }

    protected function index_data($map,$is_export = 0){
        $order = $this->getOrder('C.id desc');
        $query = MemberCashModel::alias('C')
            ->leftJoin('w_member M','C.member_id=M.id')
            ->where($map)
            ->order($order)
            ->field('C.*,M.name as member_name,M.mobile as member_mobile,M.email as member_email,M.avatar as member_avatar');
        if($is_export){
            $status = MemberCashModel::getStatus();
            $wallet = MemberCreditModel::getCreditName();
            $method = MemberCashModel::getMethods();
            $data_list = $query->select()
                ->map(function ($item)use($status,$wallet,$method) {
                    $item['status'] = $status[$item['status']];
                    $item['type'] = $wallet[$item['type']];
                    $item['method'] = $method[$item['method']];
                    $item['create_time'] = format_time($item['create_time']);
                    $item['deal_time'] = format_time($item['deal_time']);
                    $item['charge'] = $item['charge'] . '%';
                    return $item;
                });
        }else{
            $MemberModel = new MemberModel();
            $data_list = $query->paginate(15)
                ->each(function ($item)use($MemberModel) {
                    $member = [
                        'id' => $item['member_id'],
                        'name' => $item['member_name'],
                        'email' => $item['member_email'],
                        'mobile' => $item['member_mobile'],
                        'avatar' => $item['member_avatar'],
                    ];
                    $item['member'] = $MemberModel->geMemberInfo($item['member_id'],$member);
                    $item['charge'] = $item['charge'] . '%';
                    return $item;
                });
        }
        return $data_list;
    }

}