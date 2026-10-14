<?php

namespace app\member\admin;

use app\member\model\FeedbackModel;
use app\member\model\MemberModel;
use app\common\builder\ZBuilder;
use app\admin\controller\Admin;

class Feedback extends Admin
{
    public function index()
    {
        $order     = $this->getOrder('create_time desc');
        $map       = $this->getMap();
        $MemberModel = new MemberModel();
        $data_list = FeedbackModel::hasWhere('Member')
            ->where($map)
            ->order($order)
            ->field('FeedbackModel.*,MemberModel.name as username,MemberModel.mobile as user_mobile')
            ->paginate('20', false, ['query' => request()->param()])->each(function ($item, $key) use ($MemberModel) {
                $item['member'] = $MemberModel->geMemberInfo($item['member_id']);
                return $item;
            });
        $btn_access = [
            'title' => '批量处理',
            'icon'  => 'fa fa-fw fa-key',
            'class' => 'btn btn-primary ajax-post',
            'href'  => url('feedback_deal')
        ];
        $page      = $data_list->render();
        $FeedbackModel = new FeedbackModel();
        return ZBuilder::make('table')
            ->setTableName($FeedbackModel->getName())
            ->addTopButton('delete')
            ->addRightButton('delete')
            ->addColumns([
                ['id', 'ID'],
                ['member', '会员'],
                ['content', '内容'],
                ['create_time', '反馈时间', 'datetime'],
                ['status', '是否处理', 'switch', [0 => '待处理', '1' => '已处理']],
                ['right_button', '操作', 'btn'],
            ])
            ->setExtraCss($this->css)
            ->addTopButton('custom', $btn_access) // 添加授权按钮
            ->setRowList($data_list)
            ->fetch();
    }

    public function feedback_deal()
    {
        $data = input('');
        if (empty($data)) {
            $this->error('请选择需要处理的数据');
        }
        $ids = $data['ids'];
        $xx = FeedbackModel::where('id', 'in', $ids)->update(array('status' => 1, 'deal_time' => time()));
        if ($xx) {
            $this->success('设置成功');
        } else {
            $this->error('设置失败');
        }
    }
}
