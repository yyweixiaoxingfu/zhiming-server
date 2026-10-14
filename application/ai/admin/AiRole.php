<?php


namespace app\ai\admin;


use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\ai\model\AiRoleModel;
use think\Db;
use think\facade\Env;

class AiRole extends Admin
{
    public function index()
    {
        $order          = $this->getOrder('id asc');
        $map            = $this->getMap();
        $data_list      = AiRoleModel::where($map)
            ->order($order)
            ->paginate(100);
        $topBtn = [
            'add' => ['class' => 'btn btn-sm btn-success', 'href' => url('add')],
        ];
        $rightBtn = [
            'edit'      => ['class' => 'btn btn-sm btn-warning', 'href' => url('edit', ['id' => '__id__'])],
            'delete'    => ['class' => 'btn btn-sm btn-danger ajax-get confirm', 'href' => url('del', ['id' => '__id__'])],
        ];
        return ZBuilder::make('table')
            ->setPageTitle('AI模拟角色配置')
            ->hideCheckbox()
            ->setTableName('ai_role')
            ->addOrder('id') // 添加排序
            ->setSearchArea([
                ['text:1', 'id', 'ID'],
                ['text:2', 'rolename', '角色名称', 'like'],
                ['select:2', 'isvalid', '是否有效', '', '', ['1' => '是', '0' => '否']],
            ])
            ->addColumns([
                ['id', 'ID'],
                ['rolename', '角色名称', 'text.edit'],
                ['rolevalue', '角色参数值', 'text.edit'],
                ['rolecode', '角色代码', 'text'],
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
                'isvalid'       => 40,
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
            if (!$data['rolename']) {
                $this->error('角色名称不能为空');
            }
            if (!$data['rolevalue']) {
                $this->error('角色参数值不能为空');
            }
            if (!$data['rolecode']) {
                $this->error('角色代码不能为空');
            }
            $res = AiRoleModel::create($data);
            if ($res) {
                $this->success('添加成功', url('index'));
            } else {
                $this->error('添加失败');
            }
        }
        return ZBuilder::make('form')
            ->setPageTitle('添加AI模拟角色')
            ->addFormItems([
                ['text:2', 'rolename', '角色名称'],
                ['text:2', 'rolevalue', '角色参数值'],
                ['radio:2', 'isvalid', '是否有效', '', ['1' => '是', '0' => '否'], 1],
                ['textarea:6', 'rolecode', '角色代码', '角色代码为Array格式,不了解请勿乱动'],
                ['textarea:5', 'memo', '备注'],
            ])
            ->fetch();
    }

    public function edit($id = 0)
    {
        if ($this->request->isPost()) {
            $data   = input('post.');
            $data['createtime'] = date('Y-m-d H:i:s');
            if (!$data['rolename']) {
                $this->error('角色名称不能为空');
            }
            if (!$data['rolevalue']) {
                $this->error('角色参数值不能为空');
            }
            if (!$data['rolecode']) {
                $this->error('角色代码不能为空');
            }
            $res = AiRoleModel::update($data);
            if ($res) {
                $this->success('编辑成功', url('index'));
            } else {
                $this->error('编辑失败');
            }
        }
        $data = AiRoleModel::get($id);
        if (!$data) {
            $this->error('数据不存在');
        }
        return ZBuilder::make('form')
            ->setPageTitle('编辑AI模拟角色')
            ->addFormItems([
                ['text:2', 'rolename', '角色名称'],
                ['text:2', 'rolevalue', '角色参数值'],
                ['radio:2', 'isvalid', '是否有效', '', ['1' => '是', '0' => '否'], 1],
                ['textarea:6', 'rolecode', '角色代码', '角色代码为Array格式,不了解请勿乱动'],
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
        $res = AiRoleModel::destroy($id);
        if ($res) {
            $this->success('删除成功', url('index'));
        } else {
            $this->error('删除失败');
        }
    }
}
