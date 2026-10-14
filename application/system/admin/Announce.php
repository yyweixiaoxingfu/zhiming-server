<?php
namespace app\system\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\system\model\AnnounceModel;
use app\system\validate\AnnounceValidate;

/**
 * 系统公告
 * @package app\admin\controller
 */
class Announce extends Admin
{
    //系统公告
    public function index() {
        $map = $this->getMap();
        $order = $this->getOrder('sort asc,id desc');
        $data_list = AnnounceModel::where($map)->order($order)->paginate();
        $Announce = new AnnounceModel();
        return ZBuilder::make('table')
            ->setTableName($Announce->getName())
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['name', '标题', 'text'],
                ['sort', '排序', 'text.edit'],
                ['create_time','时间','datetime'],
                ['right_button', '操作', 'btn']
            ])
            ->addOrder('id')
            ->setPrimaryKey('id')
            ->addOrder('id')
            ->setExtraCss($this->css)
            ->setColumnWidth(['id' =>60])
            ->setColumnWidth('m_title', 150)
            ->setColumnWidth('m_detail', 400)
            ->addTopButton('add',['href' => url('add')]) // 添加顶部按钮
            ->addTopButton('delete', ['href' => url('del')])
            ->addRightButton('edit',['class' => 'btn btn-sm btn-warning','href' => url('edit', ['id'=>'__id__'])])
            ->addRightButton('delete',['class' => 'btn btn-sm btn-danger ajax-get confirm'])
            ->setRowList($data_list)
            ->fetch(); // 渲染模板
    }

    // 系统公告 - 新增
    public function add() {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $validate = new AnnounceValidate();
            if (!$validate->check($data)){
                $this->error($validate->getError());
            }
            $res = AnnounceModel::create($data);
            if ($res) {
                $this->success('新增成功','index');
            } else {
                $this->error('新增失败');
            }
        }
        return ZBuilder::make('form')
            ->setPageTitle('新增') // 设置页面标题
            ->addFormItems([ // 批量添加表单项
                ['text', 'name', '标题'],
                ['ueditor', 'detail', '内容'],
                ['number', 'sort', '排序'],
            ])
            ->fetch();
    }

    // 系统公告 - 编辑
    public function edit($id='') {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $validate = new AnnounceValidate();
            if (!$validate->check($data)){
                $this->error($validate->getError());
            }
            $res = AnnounceModel::where('id',$data['id'])->update($data);
            if ($res) {
                $this->success('编辑成功','index');
            } else {
                $this->error('编辑失败');
            }
        }
        $info = AnnounceModel::where('id',$id)->find();
        return ZBuilder::make('form')
            ->setPageTitle('编辑')
            ->addFormItems([
                ['hidden','id'],
                ['text', 'name', '标题'],
                ['ueditor', 'detail', '内容'],
                ['number', 'sort', '排序'],
            ])
            ->setFormData($info)
            ->fetch();
    }


}
