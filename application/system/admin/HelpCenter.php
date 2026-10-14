<?php
namespace app\system\admin;

use app\system\model\HelpCenterModel;
use app\system\model\HelpSetModel;
use app\admin\controller\Admin;
use app\common\builder\ZBuilder;

class HelpCenter extends Admin
{
    /**
     * 帮助分类
     */
    public function set()
    {
        $order     = $this->getOrder('sort asc,create_time desc');
        $map       = $this->getMap();
        $data_list = HelpSetModel::where($map)->order($order)->paginate();
        $page      = $data_list->render();
        $HelpSetModel = new HelpSetModel();
        return ZBuilder::make('table')
            ->setTableName($HelpSetModel->getName())
            ->addTopButton('add', ['href' => url('set_add')])
            ->addTopButton('delete')
            ->addRightButton('edit', ['href' => url('set_edit', ['id' => '__id__'])])
            ->addRightButton('delete')
            ->setExtraCss($this->css)
            ->addColumns([
                ['id', 'ID'],
                ['name', '分类名称', 'text.edit'],
                ['sort', '排序', 'text.edit'],
                ['right_button', '操作', 'btn'],
            ])
             ->setColumnWidth([
                'id'            => 25,
                'right_button'  => 30,
            ])
            ->setRowList($data_list)
            ->fetch();
    }
    public function set_add()
    {
        if ($this->request->isPost()) {
            $POST = $this->request->post();
            $result = $this->validate($POST, 'HelpCenterValidate.set');
            if(true !== $result) {
                return returnData(0,$result);
            }
            $res = HelpSetModel::create($POST);
            if ($res) {
                $this->success('新增成功', 'set');
            } else {
                $this->error('新增失败');
            }
        }
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'name', '名称'],
                ['number', 'sort', '排序', '数字越小排序越靠前'],
            ])
            ->fetch();
    }
    public function set_edit($id=0)
    {
        if ($this->request->isPost()) {
            $POST = $this->request->post();
            $result = $this->validate($POST, 'HelpCenterValidate.set');
            if(true !== $result) {
                return returnData(0,$result);
            }
            $res = HelpSetModel::where('id', intval($POST['id']))->update($POST);
            if ($res) {
                $this->success('修改成功', 'set');
            } else {
                $this->error('修改失败');
            }
        }
        $id   = intval(input('id'));
        $data = HelpSetModel::where('id', $id)->find();
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'name', '名称'],
                ['number', 'sort', '排序', '数字越小排序越靠前'],
            ])
            ->setFormData($data)
            ->addHidden('id', $id)
            ->fetch();
    }

    /**
     * 帮助
     */
    public function index()
    {
        $order     = $this->getOrder('sort asc,create_time desc');
        $map       = $this->getMap();
        $data_list = HelpCenterModel::where($map)->order($order)->paginate();
        $page      = $data_list->render();
        $HelpCenterModel = new HelpCenterModel();
        return ZBuilder::make('table')
            ->setTableName($HelpCenterModel->getName())
            ->addTopButton('add', ['href' => url('add')])
            ->addTopButton('delete')
            ->addRightButton('edit', ['href' => url('edit', ['id' => '__id__'])])
            ->addRightButton('delete')
            ->addColumns([
                ['id', 'ID'],
                ['name', '标题', 'text.edit'],
                ['sort', '排序', 'text.edit'],
                ['set_id', '分类', 'status','',HelpSetModel::getSetList()],
                ['right_button', '操作', 'btn'],
            ])
            ->setRowList($data_list)
            ->setExtraCss($this->css)
            ->setSearchArea([
                ['text', 'name', '标题'],
                ['select', 'set_id', '分类','','',HelpSetModel::getSetList()]
                ]
            )
            ->fetch();
    }
    /**
     * 新增
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $POST = $this->request->post();
            $result = $this->validate($POST, 'HelpCenterValidate.help');
            if(true !== $result) {
                return returnData(0,$result);
            }
            $res = HelpCenterModel::create($POST);
            if ($res) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }
        return ZBuilder::make('form')
            ->addSelect('set_id', '分类', '', HelpSetModel::getSetList())
            ->addFormItems([
                ['text', 'name', '标题'],
                ['number', 'sort', '排序', '数字越小排序越靠前'],
                ['ueditor', 'content', '详情'],
            ])
            ->fetch();
    }

    /**
     * 编辑
     */
    public function edit($id=0)
    {
        if ($this->request->isPost()) {
            $POST = $this->request->post();
            $result = $this->validate($POST, 'HelpCenterValidate.help');
            if(true !== $result) {
                return returnData(0,$result);
            }
            $res = HelpCenterModel::where('id', intval($POST['id']))->update($POST);
            if ($res) {
                $this->success('修改成功', 'index');
            } else {
                $this->error('修改失败');
            }
        }
        $id   = intval(input('id'));
        $data = HelpCenterModel::where('id', $id)->find();
        return ZBuilder::make('form')
            ->addSelect('set_id', '分类', '', HelpSetModel::getSetList())
            ->addFormItems([
                ['text', 'name', '标题'],
                ['number', 'sort', '排序', '数字越小排序越靠前'],
                ['ueditor', 'content', '详情'],
            ])
            ->setFormData($data)
            ->addHidden('id', $id)
            ->fetch();
    }



}
