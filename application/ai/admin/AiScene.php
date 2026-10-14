<?php


namespace app\ai\admin;


use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\ai\model\AiSceneModel;
use think\Db;
use think\facade\Env;

class AiScene extends Admin
{
    public function index()
    {
        $order          = $this->getOrder('id asc');
        $map            = $this->getMap();
        $data_list      = AiSceneModel::where($map)
            ->order($order)
            ->paginate(50);
        $topBtn = [
            'add' => ['class' => 'btn btn-sm btn-success', 'href' => url('add')],
        ];
        $rightBtn = [
            'edit'      => ['class' => 'btn btn-sm btn-warning', 'href' => url('edit', ['id' => '__id__'])],
            'delete'    => ['class' => 'btn btn-sm btn-danger ajax-get confirm', 'href' => url('del', ['id' => '__id__'])],
        ];
        return ZBuilder::make('table')
            ->setPageTitle('AI场景配置')
            ->hideCheckbox()
            ->setTableName('ai_scene')
            ->addOrder('id') // 添加排序
            ->setSearchArea([
                ['text:1', 'id', 'ID'],
                ['text:2', 'scenename', '场景名称', 'like'],
                ['text:2', 'scenevalue', '场景参数描绘', 'like'],
                ['select:2', 'isvalid', '是否有效', '', '', ['1' => '是', '0' => '否']],
            ])
            ->addColumns([
                ['id', 'ID'],
                ['scenename', '场景名称', 'text.edit'],
                ['scenevalue', '场景参数描绘', 'textarea.edit'],
                ['isvalid', '有效', 'switch', ['1' => '是', '0' => '否']],
                ['memo', '备注', 'textarea.edit'],
                ['createtime', '创建时间', 'text'],
                ['right_button', '操作', 'btn'],
            ])
            ->setRowList($data_list)
            ->addRightButtons($rightBtn)
            ->addTopButtons($topBtn)
            ->setColumnWidth([
                'id'            => 35,
                'scenename'     => 60,
                'isvalid'       => 40,
                'createtime'    => 60,
                'right_button'  => 40,
            ])
            ->setExtraCss($this->css)
            ->fetch();
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $data   = input('post.');
            $data['createtime'] = date('Y-m-d H:i:s');
            if (!$data['scenename']) {
                $this->error('场景名称不能为空');
            }
            if (!$data['scenevalue']) {
                $this->error('场景参数值不能为空');
            }
            $res = AiSceneModel::create($data);
            if ($res) {
                $this->success('添加成功', url('index'));
            } else {
                $this->error('添加失败');
            }
        }
        return ZBuilder::make('form')
            ->setPageTitle('添加AI场景')
            ->addFormItems([
                ['text:2', 'scenename', '场景名称'],
                ['textarea:5', 'scenevalue', '场景参数值'],
                ['radio:2', 'isvalid', '是否有效', '', ['1' => '是', '0' => '否'], 1],
                ['textarea:6', 'scenecode', '场景代码', '场景代码为Array格式,不了解请勿乱动'],
                ['textarea:5', 'memo', '备注'],
            ])
            ->fetch();
    }

    public function edit($id = 0)
    {
        if ($this->request->isPost()) {
            $data   = input('post.');
            $data['createtime'] = date('Y-m-d H:i:s');
            if (!$data['scenename']) {
                $this->error('场景名称不能为空');
            }
            if (!$data['scenevalue']) {
                $this->error('场景参数值不能为空');
            }
            $res = AiSceneModel::update($data);
            if ($res) {
                $this->success('编辑成功', url('index'));
            } else {
                $this->error('编辑失败');
            }
        }
        $data = AiSceneModel::get($id);
        if (!$data) {
            $this->error('数据不存在');
        }
        return ZBuilder::make('form')
            ->setPageTitle('编辑AI场景')
            ->addFormItems([
                ['text:2', 'scenename', '场景名称'],
                ['textarea:5', 'scenevalue', '场景参数值'],
                ['radio:2', 'isvalid', '是否有效', '', ['1' => '是', '0' => '否'], 1],
                ['textarea:5', 'memo', '备注'],
                ['hidden', 'id'],
            ])
            ->setFormData($data)
            ->fetch();
    }

    public function del($id = 0)
    {
        if (!$id) {
            $this->error('参数错误');
        }
        $res = AiSceneModel::destroy($id);
        if ($res) {
            $this->success('删除成功', url('index'));
        } else {
            $this->error('删除失败');
        }
    }
}
