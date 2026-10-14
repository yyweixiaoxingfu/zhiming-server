<?php
namespace app\payment\admin;

use app\common\library\ExcelServer;
use app\member\model\MemberModel;
use app\payment\model\PayModel;
use think\Db;
use app\admin\controller\Admin;
use app\common\builder\ZBuilder;

class Pay extends Admin
{
    public function index()
    {
        $order = $this->getOrder('p.create_time desc,p.id desc');
        $map = $this->getMap();
        $map[]=['status','=','pay'];
        $MemberModel = new MemberModel();
        $maps      = urlencode(json_encode($map));
        $result = Db::name('pay')->alias('p')
            ->join('member mm', 'mm.id=p.member_id')
            ->where($map)
            ->order($order)
            ->field('p.*,mm.name as membername,mm.mobile as member_mobile,mm.email as member_email,mm.account as member_account,mm.avatar')
            ->paginate('20', false, ['query' => request()->param()])->each(function ($item, $key) use ($MemberModel) {
                $member = [
                    'id' => $item['member_id'],
                    'name' => $item['membername'],
                    'mobile' => $item['member_mobile'],
                    'account' => $item['member_account'],
                    'email' => $item['member_email'],
                    'avatar' => $item['avatar'],
                ];
                $item['member'] = $MemberModel->geMemberInfo($item['member_id'],$member);
                return $item;
            });
        $total_money = Db::name('pay')->alias('p')
            ->join('member mm', 'mm.id=p.member_id')
            ->where($map)
            ->order($order)
            ->sum('pay_money');
        $html = <<<EOF
           <style>
    .tableReset{margin: 20px auto;border-collapse:collapse}
    .tableReset td, .tableReset th{border:1px solid #ddd !important;}
    .tableReset tr:last-child{background-color: #FFFCEF !important;}
</style>
<table class="tableReset table table-builder table-hover table-bordered table-striped js-table-checkable">
    <tr>
        <th>总金额</th>
    </tr>
    <tr>
        <td>{$total_money}(元)</td>
    </tr>
</table>
EOF;
        $column = [
            ['id', 'ID'],
            ['member', '会员'],
            ['target_type', '充值类型','status','',PayModel::getTargetTypes()],
            ['pay_money', '支付金额'],
            ['trade_no', '交易单号'],
            ['turn_no', '三方单号'],
            ['status', '支付状态','status','',PayModel::getStatus()],
            ['pay_type', '支付方式','status','',PayModel::getPayTypes()],
            ['create_time', '创建时间', 'datetime'],
            ['pay_time', '支付时间', 'datetime'],
        ];

        $builder = ZBuilder::make('table')
            ->setSearchArea([
                ['text', 'mm.id', '会员ID'],
                ['text', 'mm.name', '会员名称','like'],
                ['text', 'mm.mobile', '手机号'],
                ['text', 'mm.account', '账号'],
                ['text', 'mm.email', '邮箱'],
                ['text', 'trade_no', '交易单号'],
                ['text', 'turn_no', '三方单号'],
                ['text', 'pay_money', '支付金额'],
                ['select', 'target_type', '充值类型', '', '', PayModel::getTargetTypes()],
                ['select', 'status', '支付状态', '', '', PayModel::getStatus()],
                ['select', 'pay_type', '支付方式', '', '', PayModel::getPayTypes()],
                ['daterange', 'create_time', '创建时间', '', '', ['time-picker' => 'true', 'time-picker24-hour' => 'true', 'time-picker-seconds' => 'true']],
                ['daterange', 'pay_time', '支付时间', '', '', ['time-picker' => 'true', 'time-picker24-hour' => 'true', 'time-picker-seconds' => 'true']],
            ])
            ->setExtraHtml($html, 'block_top')
            ->setExtraCss($this->css)
            ->addColumns($column)
            ->setPrimaryKey('id')
            ->setColumnWidth(['id' => 60,'target_type' => 70,'member' => 195,'status' => 60,'pay_money'=>60])
            ->setColumnWidth('create_time,pay_time', 95)
            ->addTopButton('export', ['title' => '导出', 'class' => 'btn btn-danger confirm', 'href' => url('export', ['map' => $maps])])
            ->setRowList($result);
        return $builder->fetch(); // 渲染模板
    }


    public function export($map = [])
    {
        $map = json_decode(urldecode(urldecode($map)));
        $result = Db::name('pay')->alias('p')
            ->join('member mm', 'mm.id=p.member_id')
            ->where($map)
            ->order('id desc')
            ->field('p.*,mm.name as membername,mm.mobile as member_mobile')
            ->withAttr('target_type', function($value, $data) {
                return strtolower($value);
            })
            ->select();
        $target_types = PayModel::getTargetTypes();
        $status_list = PayModel::getStatus();
        $pay_types = PayModel::getPayTypes();
        foreach ($result as &$item){
            $item['target_type'] = $target_types[$item['target_type']] ?? '';
            $item['status'] = $status_list[$item['status']] ?? '';
            $item['pay_type'] = $pay_types[$item['pay_type']] ?? '';
            $item['create_time'] = empty($item['create_time']) ? '' : date('Y-m-d H:i',$item['create_time']);
            $item['pay_time'] = empty($item['pay_time']) ? '' : date('Y-m-d H:i',$item['pay_time']);
        }
        $title      = [
            ['id', 'ID'],
            ['membername', '会员'],
            ['member_mobile', '手机号'],
            ['target_type', '充值类型'],
            ['pay_money', '支付金额'],
            ['trade_no', '交易单号'],
            ['turn_no', '三方单号'],
            ['status', '支付状态'],
            ['pay_type', '支付方式'],
            ['create_time', '创建时间'],
            ['pay_time', '支付时间'],
        ];
        ExcelServer::export_old('支付记录',$title,$result);
    }


}
