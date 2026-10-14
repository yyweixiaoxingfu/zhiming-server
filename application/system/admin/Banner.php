<?php
namespace app\system\admin;

use app\system\model\BannerModel;
use app\system\validate\BannerValidate;
use app\admin\controller\Admin;
use app\common\builder\ZBuilder;

class Banner extends Admin
{
    /**
     * 轮播图列表
     */
    public function index()
    {
        $order     = $this->getOrder('sort asc,create_time desc');
        $map       = $this->getMap();
        $data_list = BannerModel::where($map)->order($order)->paginate();
        $BannerModel = new BannerModel();
        return ZBuilder::make('table')
            ->setTableName($BannerModel->getName())
            ->addTopButton('add', ['href' => url('add')], ['area' => ['800px', '800px']])
            ->addTopButton('delete')
            ->addRightButton('edit', ['href' => url('edit', ['id' => '__id__'])], ['area' => ['800px', '800px']])
            ->addRightButton('delete')
            ->addColumns([
                ['id', 'ID'],
                ['pic', '图片', 'picture'],
                ['sort', '排序', 'text.edit'],
                ['pos', '位置', 'status','',BannerModel::getPos()],
                ['right_button', '操作', 'btn'],
            ])
            ->setExtraCss($this->css)
            ->setRowList($data_list)
            ->fetch();
    }
    /**
     * 新增轮播图
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $POST = $this->request->post();
            $validate = new BannerValidate();
            if (!$validate->check($POST)){
                $this->error($validate->getError());
            }
            $res = BannerModel::create($POST);
            if ($res) {
                $this->success('新增成功', '', '_parent_reload');
            } else {
                $this->error('新增失败');
            }
        }
        return ZBuilder::make('form')
            ->addFormItems([
                ['image', 'pic', '图片','尺寸:1125x666px'],
                ['select','pos','位置','',BannerModel::getPos()],
                ['select','link_type','跳转类型','',BannerModel::getLinkTypes()],
                ['text','link','跳转','网页地址/ID'],
                ['number', 'sort', '排序', '数字越小排序越靠前'],
            ])
            ->fetch();
    }

    /**
     * 编辑轮播图
     */
    public function edit($id=0)
    {
        if ($this->request->isPost()) {
            $POST = $this->request->post();
            $validate = new BannerValidate();
            if (!$validate->check($POST)){
                $this->error($validate->getError());
            }
            $res = BannerModel::where('id', intval($POST['id']))->update($POST);
            if ($res) {
                $this->success('修改成功', '', '_parent_reload');
            } else {
                $this->error('修改失败');
            }
        }
        $id   = intval(input('id'));
        $data = BannerModel::where('id', $id)->find();
        return ZBuilder::make('form')
            ->addFormItems([
                ['image', 'pic', '图片','尺寸:1125x666px'],
                ['select','pos','位置','',BannerModel::getPos()],
                ['select','link_type','跳转类型','',BannerModel::getLinkTypes()],
                ['text','link','跳转','网页地址/ID'],
                ['number', 'sort', '排序', '数字越小排序越靠前'],
            ])
            ->setFormData($data)
            ->addHidden('id', $id)
            ->fetch();
    }



}
