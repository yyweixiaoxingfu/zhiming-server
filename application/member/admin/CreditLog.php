<?php


namespace app\member\admin;


use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\common\library\ExcelServer;
use app\member\model\MemberCreditLogsModel;
use app\member\model\MemberCreditModel;
use app\member\transformer\CreditLogsTransformer;

class CreditLog extends Admin
{
    /**
     * 会员佣金明细
     */
    public function index($uid = '', $m_type = '', $cate = '')
    {
        $map = $this->getMap();
        if ($uid != '') {
            $map[] = ['member_id', '=', $uid];
        }
        if ($m_type != '') {
            $map[] = ['type', '=', $m_type];
        }
        if ($cate == 1) {
            $map[] = ['cate', 'in', ['direct', 'win_com']];
        } elseif ($cate == 2) {
            $map[] = ['cate', 'in', ['recharge', 'recharge_offline']];
        }
        $order = $this->getOrder('id desc');
        $data_list = $this->credit_logs_data($map, $order);
        $maps = urlencode(json_encode($map));
        return ZBuilder::make('table')
            ->setTableName('logs')
            ->setSearchArea([
                ['text', 'MemberModel.mobile', '会员手机号'],
                ['text', 'MemberModel.name', '会员姓名'],
                ['text', 'member_id', '会员ID'],
                ['text', 'info', '描述', 'like'],
                ['select', 'type', '金额类型', '', '',  MemberCreditModel::getCreditName()],
                ['daterange', 'MemberCreditLogsModel.create_time', '选择时间'],

            ])
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['member', '会员', 'text'],
                ['num', '金额', 'text'],
                ['type', '金额类型', 'status', '', MemberCreditModel::getCreditName()],
                ['info', '描述', 'text'],
                ['create_time', '创建时间'],
            ])
            ->hideCheckbox()
            ->addTopButton('export', ['title' => '导出', 'class' => 'btn btn-danger confirm', 'href' => url('export', ['map' => $maps, 'order' => urlencode($order)])])
            ->setPrimaryKey('id')
            ->setExtraCss($this->css)
            ->addOrder('id') //添加排序
            ->setColumnWidth(['id' => 60, 'info' => 150])
            ->setRowList($data_list)

            ->fetch();
    }

    private function credit_logs_data($map = [], $order = '', $paginate = true)
    {

        $data_list = MemberCreditLogsModel::haswhere('member')
            ->where($map)
            ->order($order);
        if ($paginate) {
            $data_list = $data_list->paginate();
        } else {
            $data_list = $data_list->select();
        }
        $data_list = transformer($data_list, CreditLogsTransformer::class);
        return $data_list;
    }

    public function export($map = '', $order = '')
    {
        $map = json_decode(urldecode(urldecode($map)));
        $order = urldecode(urldecode($order));
        $data_list = $this->credit_logs_data($map, $order, false);
        $currency = MemberCreditModel::getCreditName();
        $title      = [
            ['id', 'ID'],
            ['memberInfo.name', '会员名称'],
            ['memberInfo.mobile', '会员手机号'],
            ['num', '金额'],
            ['type', '金额类型'],
            ['info', '描述'],
            ['create_time', '创建时间'],
        ];
        $callback = function ($item) use ($currency) {
            $item['type'] = $currency[$item['type']] ?? '';
            return $item;
        };
        $data_list = $data_list->map($callback);
        ExcelServer::export_old('会员账户流水', $title, $data_list);
    }
}
