<?php
//塔罗牌控制器
namespace app\admin\controller;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\admin\model\SpCateModel;
use app\member\model\MemberModel;
use app\admin\model\SpExpertModel;
use think\Db;

class Web extends Admin
{
    protected $css = <<<EOF
        <style>
            th,tr{
                text-align: center;
            }
        </style>
EOF;
    public function sm_class()
    {
        $mid_data   = [0 => '', 1 => '功能', 2 => '文章', 3 => '单页', 4 => '解梦', 5 => '星座运势', 6 => '生肖'];
        $mids_data  = [1 => '功能', 2 => '文章', 3 => '单页', 4 => '解梦', 5 => '星座运势', 6 => '生肖'];
        $order      = $this->getOrder('sort asc,id asc');
        $map        = $this->getMap();
        $data_list  = getTreeFlat('sm_class', 'parentid', 0, 0, $order, $map);
        foreach ($data_list as $k => &$v) {
            if ($v['parentid'] == 0) {
                $v['price'] = ' ';
                $v['points'] = ' ';
                $v['ad_num'] = ' ';
            }
        }
        $topBtn = [
            //'add' => ['class' => 'btn btn-sm btn-success', 'href' => url('sm_class_add')],
        ];
        $parent_data = Db::name('sm_class')->where('parentid', 0)->where('is_del', 0)->column('name', 'id');
        $rightBtn = [
            'edit'      => ['class' => 'btn btn-sm btn-warning', 'href' => url('sm_class_edit', ['id' => '__id__'])],
            'save'      => ['title' => '页面内容编辑', 'icon' => 'fa fa-fw fa-save', 'class' => 'btn btn-sm btn-success', 'href' => url('sm_class_save', ['id' => '__id__'])],
            'delete'    => ['class' => 'btn btn-sm btn-danger ajax-get confirm', 'href' => url('sm_class_del', ['id' => '__id__'])],
        ];
        return ZBuilder::make('table')
            ->setPageTitle('测算功能分类')
            ->hideCheckbox()
            ->setTableName('tarot_yunshi')
            ->addOrder('id,sort') // 添加排序
            ->setSearchArea([
                ['text:1', 'id', 'ID'],
                ['text:2', 'name', '分类名'],
                ['text:3', 'title', '标题', 'like'],
                ['select:2', 'parentid', '上级分类', '', '', $parent_data],
                ['select:2', 'mid', '功能类型', '', '', $mids_data],
                ['select:2', 'cate_type', '分类区展示', '', '', [0 => '隐藏', 1 => '显示']],
                ['select:2', 'home_type', '列表区展示', '', '', [0 => '隐藏', 1 => '显示']],
                ['select:2', 'status', '状态', '', '', [0 => '显示', 1 => '隐藏']],
                ['daterange:4', 'update_time', '更新时间'],
            ])
            ->addColumns([
                //['id', 'ID'],
                ['parent_name', '父级分类', 'text'],
                ['name', '分类', 'text'],
                ['mid', '功能类型', 'status', '', $mid_data],
                //['level_name', '级别', 'text'],
                ['title', '标题', 'text'],
                ['cate_img', '分类图标', 'picture'],
                ['home_img', '列表素材', 'picture'],
                ['cate_type', '分类展示', 'status', '', [0 => '否', 1 => '是']],
                ['home_type', '列表展示', 'status', '', [0 => '否', 1 => '是']],
                ['price', '价格', 'text'],
                ['points', '积分', 'text'],
                ['ad_num', '广告兑换次数', 'text'],
                //['keywords', '关键词', 'text'],
                ['status', '是否隐藏', 'status', '', [0 => '否', 1 => '是']],
                ['update_time', '更新时间', 'date'],
                ['right_button', '操作', 'btn'],
            ])
            ->setRowList($data_list)
            ->replaceRightButton(['parentid' => ['=', 0], 'mid' => ['<>', 1]], '', 'save')
            ->addRightButtons($rightBtn)
            ->addTopButtons($topBtn)
            ->setColumnWidth([
                'id'            => 50,
                'title'         => 180,
                'right_button'  => 100,
            ])
            ->setExtraCss($this->css)
            ->fetch();
    }

    public function sm_class_edit($id = 0)
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            $data['update_time'] = time();
            if (!$data['name']) {
                $this->error('名称不能为空');
            }
            if (!$data['title']) {
                $this->error('标题不能为空');
            }
            if (isset($data['parentid']) && $data['parentid'] == '') {
                $this->error('请选择父级分类');
            }
            if ($data['parentid'] > 0) {
                if (isset($data['price']) && $data['price'] == '') {
                    $this->error('请输入价格');
                }
                if (!$data['ad_num']) {
                    $this->error('请输入广告兑换次数');
                }
                if ($data['cate_type'] == 1 && !$data['cate_img']) {
                    $this->error('请上传分类图标');
                }
                if ($data['home_type'] == 1 && !$data['home_img']) {
                    $this->error('请上传列表展示图');
                }
                if (!$data['url']) {
                    $this->error('请输入链接');
                }
            }
            if (isset($data['vip_discount']) && $data['vip_discount'] > 1) {
                $this->error('会员折扣不能超过1');
            }
            if ($data['parentid'] == 0 && $data['status'] == 1) {
                Db::name('sm_class')->where('parentid', $data['id'])->update(['status' => 1]);
            }
            $res = Db::name('sm_class')->where('id', $data['id'])->update($data);
            if ($res) {
                $this->success('操作成功', url('sm_class'));
            } else {
                $this->error('操作失败');
            }
        }
        $id = input('id');
        $data = Db::name('sm_class')->where('id', $id)->find();
        if (!$data) {
            $this->error('数据不存在', url('sm_class'));
        }
        $parent_data = Db::name('sm_class')->where('parentid', 0)->where('is_del', 0)->column('name', 'id');
        $mid_data = [0 => '', 1 => '算命', 2 => '文章', 3 => '单页', 4 => '解梦', 5 => '星座运势', 6 => '生肖'];
        $title = '设置测算分类';
        $form_items = [
            ['text', 'name', '分类名'],
            ['text', 'title', '标题', '编辑顶级分类时实际为分类补充描述'],
            ['text', 'keywords', '关键词','like'],
            ['number', 'vip_discount', '会员折扣', '当前分类下的功能的会员可以享受的折扣 1表示不打折,0.8为八折'],
            ['radio', 'home_type', '首页类型', '首页默认展示四个分类', [0 => '否', 1 => '是'], 0],
            ['radio', 'status', '是否隐藏', '', [0 => '否', 1 => '是'], 0],
            ['number', 'sort', '排序'],
            ['textarea', 'description', '详细描述'],
            ['hidden', 'id', 'ID'],
            ['hidden', 'parentid', '父级分类'],
        ];
        if ($data['parentid'] > 0) {
            $title = '设置测算功能';
            $form_items = [
                ['select', 'parentid', '父级分类', '', $parent_data],
                ['select', 'mid', '功能类型', '', $mid_data, '', 'disabled'],
                ['text', 'name', '名称'],
                ['text', 'title', '标题', '编辑子分类时实际为功能补充描述'],
                ['number', 'price', '价格'],
                ['number', 'points', '积分'],
                ['number', 'ad_num', '广告兑换次数'],
                ['number', 'vip_discount', '当前功能的会员折扣', '1表示不打折,0.8为八折'],
                ['number', 'cs_num', '测算人数', '虚拟测算人数'],
                ['image', 'cate_img', '分类图标', '当前功能出现在金刚区时,需要设置的分类图标'],
                ['image', 'home_img', '列表展示图', '当前功能出现在列表展示时,需要设置的列表展示图'],
                ['text', 'desc', '列表描述', '当前功能出现在列表展示时,需要设置的文字描述'],
                ['text', 'url', '页面跳转的链接', '设置页面素材内容时需正确配置才能查看'],
                ['text', 'extra', '扩展信息', ''],
                ['text', 'model', '所在模型', ''],
                ['radio', 'cate_type', '金刚区展示', '金刚区处内容', [0 => '否', 1 => '是'], 0],
                ['radio', 'home_type', '首页展示', '首页默认展示四个分类,设置多个默认取前四个', [0 => '否', 1 => '是'], 0],
                ['radio', 'status', '是否隐藏', '', [0 => '否', 1 => '是'], 0],
                ['text', 'keywords', '关键词'],
                ['number', 'sort', '排序'],
                ['textarea', 'description', '详细描述'],
                ['hidden', 'id', 'ID'],
            ];
        }
        return ZBuilder::make('form')
            ->setPageTitle($title)
            ->addFormItems($form_items)
            ->layout([
                'parentid' => 6,
                'name' => 6,
                'title' => 6,
                'keywords' => 6,
                'desc' => 8,
                'status' => 7,
                'mid' => 6,
                'home_type' => 6,
                'cate_type' => 7,
                'price' => 2,
                'points' => 2,
                'ad_num' => 2,
                'cs_num' => 2,
                'vip_discount' => 2,
                'url' => 8,
                'extra' => 5,
                'model' => 5,
                'sort' => 2
            ])
            ->setFormData($data)
            ->fetch();
    }

    public function sm_class_del()
    {
        if ($this->request->isPost()) {
            // $POST = input('post.');
            // ###########################批量删除（开始）##############################
            // $ids  = $POST['ids'];
            // if (empty($ids)) {
            //     $this->error('请选择要删除的选项！');
            // }
            // //$res1  = Db::name('sm_class')->where('parentid', 'in', $ids)->update(['is_del'=>1]);
            // $res  = Db::name('sm_class')->where('id', 'in', $ids)->update(['is_del'=>1]);
            // if ($res) {
            //     $in_del_data = Db::name('sm_class')->where('parentid', 'in', $ids)->where('')->where('is_del',0)->select();
            //     if(!empty($in_del_data)){
            //         Db::name('sm_class')->where('parentid', 'in', $ids)->update(['is_del'=>1]);
            //     }
            //     Db::name('sm_class')->where('parentid', 'in', $ids)->update(['is_del'=>1]);
            //     $this->success('删除成功', url('sm_class'));
            // } else {
            //     $this->error('删除失败', url('sm_class'));
            // }
            // ###########################批量删除（结束）##############################
        } else {
            $id  = input('id');
            if (!$id) {
                $this->error('请选择要删除的数据');
            }
            $is_del_data = Db::name('sm_class')->where('parentid', $id)->where('is_del', 0)->select();
            if (!empty($is_del_data)) {
                Db::name('sm_class')->where('parentid', $id)->update(['is_del' => 1]);
            }
            $res = Db::name('sm_class')->where('id', $id)->update(['is_del' => 1]);
            if ($res) {
                $this->success('删除成功', url('sm_class'));
            } else {
                $this->error('删除失败', url('sm_class'));
            }
        }
    }

    public function sm_class_save($id = 0)
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            unset($data['__token__']);
            foreach ($data as $k => &$v) {
                if (mb_strpos($v, 'img') !== false) {
                    $data[$k] = Img_url($v);
                }
            }
            $json_data = json_encode($data);
            $res = Db::name('sm_class')->where('id', $data['id'])->update(['content' => $json_data]);
            if ($res) {
                $this->success('保存成功', url('sm_class'));
            } else {
                $this->error('保存失败');
            }
        }
        $id = input('id');
        $sm_class_data = Db::name('sm_class')->where('id', $id)->find();
        if (!$sm_class_data) {
            $this->error('数据不存在', url('sm_class'));
        }
        $data = json_decode($sm_class_data['content'], true);
        $url = $sm_class_data['url'];
        if (!$url) {
            $this->error('绑定链接不存在', url('sm_class'));
        }
        $js = <<<EOF
            <script>
                var html =  '<div class="iframe_model" style="float: left;padding-right:3rem;"><iframe src="$url" style="width: 400px; height:172rem;"></iframe></div>';
                $(function(){
                    $('#form_group_desc3').before(html);
                })
            </script>
EOF;
        return ZBuilder::make('form')
            ->setExtraJs($js)
            ->addFormItems([
                ['text', 'desc3', '顶部滚动公告', '页面顶部滚动公告内容'],
                ['image', 'img1', '素材1', '对应页面背景图素材 750*1200 px'],
                ['image', 'img2', '素材2', '对应运程图素材 750*320px'],
                ['image', 'img3', '素材3', '对应吐故纳新图素材750*630px'],
                ['text', 'desc1', '按钮文字', '查看运势按钮'],
                ['text', 'desc2', '已有X人测算,满意率Y%', '隐私协议下方描述'],
                ['ckeditor', 'content1', '内容', '下方内容处素材，通常为多图'],
                ['text', 'desc2', '底部悬浮按钮文字', '页面最下方底部悬浮按钮文字'],
                ['hidden', 'id', $sm_class_data['id']],
            ])
            ->setFormData($data)
            ->layout(['img1' => 5, 'img2' => 5, 'img3' => 5, 'desc1' => 5, 'desc2' => 5, 'content1' => 6, 'desc2' => 5, 'desc3' => 5])
            ->fetch();
    }

    public function expert_index()
    {
        $order      = $this->getOrder('id asc');
        $map        = $this->getMap();
        $data_list  = SpExpertModel::where('is_del', 0)
            ->where($map)
            ->order($order)
            ->paginate(50)->each(function($item){
                $item['user_info'] = '';
                if($item['user_id']){
                    $item['user_info'] = MemberModel::geMemberInfo($item['user_id']);
                }
                return $item;
            });
        $parent_data      = SpCateModel::where('pid',4)->where('cate_show_type',1)->order('sort asc')->where('is_del',0)->column('cate_name','id');    
        foreach ($parent_data as $key => $value) {
            $parent_data[$key] = $key.'-'.$value;
        }
        $topBtn    = [
            'add'       => ['class' => 'btn btn-warning', 'href' => url('expert_add')],
        ];
        $rightBtn = [

            'edit'      => ['class' => 'btn btn-sm btn-warning', 'href' => url('expert_edit', ['id' => '__id__'])],
            //'look'      => ['title' => '查看', 'icon' => 'fa fa-eye', 'class' => 'btn btn-sm btn-success', 'href' => url('expert_look', ['id' => '__id__'])],
            'delete'    => ['class' => 'btn btn-sm btn-danger ajax-get confirm', 'href' => url('expert_del', ['id' => '__id__'])],
        ];
        return ZBuilder::make('table')
            ->setPageTitle('专家列表')
            ->hideCheckbox()
            ->setTableName('expert')
            ->addOrder('id') // 添加排序
            //->addFilter('wuxing,level,star')
            ->setSearchArea([
                ['text:1', 'id', 'ID'],
                ['text:2', 'name', '名称'],
                ['select:2', 'cid', '所属分类', '', '', $parent_data],
                ['select:2', 'status', '状态', '', [0 => '正常', 1 => '下架']],
            ])
            ->addColumns([
                ['id', 'ID'],
                ['cid', '所属分类','status','', $parent_data],
                ['name', '名称', 'text'],
                ['user_info', '关联用户', 'text'],
                //['desc', '描述', 'popover'],
                ['mobile', '联系方式', 'text'],
                ['weichat_code', '微信二维码', 'picture'],
                ['cover', '封面图', 'picture'],
                //['images', '图册', 'pictures'],
                ['price', '价格', 'text'],
                ['rate', '佣金比例', 'text'],
                ['vip_discount', '会员折扣', 'text'],
                ['status', '状态', 'status','', [0 => '显示', 1 => '隐藏']],
                ['review_status', '审核状态', 'status','', [0 => '审核中', 1 => '通过',-1=>'拒绝']],
                ['add_time', '添加时间', 'date'],
                ['update_time', '更新时间', 'date'],
                ['right_button', '操作', 'btn'],
            ])
            ->setRowList($data_list)
            ->addTopButtons($topBtn)
            ->addRightButtons($rightBtn)
            ->setColumnWidth([
                'id'            => 45,
                'cid'           => 100,
                'user_info'     => 100,
                'price'         => 50,
                'add_time'      => 80,
                'status'        => 60,
                'review_status' => 80,
                'update_time'   => 80,
                'right_button'  => 70,
            ])
            ->setExtraCss($this->css)
            ->fetch();
    }

    //新增
    public function expert_add()
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            if (!$data['name']) {
                $this->error('名称不能为空');
            }
            if (!$data['mobile']) {
                $this->error('联系方式不能为空');
            }
            if (!$data['cover']) {
                $this->error('封面图不能为空');
            }
            if (!isset($data['cid']) && !$data['cid']) {
                $this->error('请选择项目分类');
            }
            if (isset($data['user_id']) && $data['user_id']) {
                $user = Db::name('member')->where('id', $data['user_id'])->find();
                if (!$user) {
                    $this->error('关联用户不存在');
                }
            }
            if (empty($data['class_ids'])) {
                $this->error('请选择可选领域');
            } else {
                $data['class_ids'] = implode(',', $data['class_ids']);
            }
            $data['review_status'] = 1;
            $data['add_time']      = time();
            $res = SpExpertModel::insert($data);
            if ($res) {
                $this->success('保存成功', url('expert_index'));
            } else {
                $this->error('保存失败');
            }
        }
        $SpCate    = SpCateModel::where('pid',4)->where('cate_show_type',1)->order('sort asc')->where('is_del',0)->column('cate_name','id');
        $filed = Db::name('sm_class')->where('mid', 1)->where('parentid', '<>', 0)->where('url', '<>', '')->where('is_del', 0)->column('name', 'id');
        return ZBuilder::make('form')
            ->addFormItems([
                ['select', 'cid', '所属分类', '', $SpCate],
                ['text', 'name', '名称'],
                ['text', 'user_id', '关联用户ID', '不关联用户可填0'],
                ['textarea', 'desc', '描述'],
                ['tags', 'label', '标签'],
                ['text', 'mobile', '联系方式'],
                ['image', 'weichat_code', '微信二维码'],
                ['image', 'cover', '封面图'],
                ['transfer', 'class_ids', '可选领域', '已选功能', '操作提示:默认导出所有字段。双击字段可移动该字段到另一侧选框;按住‘Ctrl”键并点击字段可选择多个字段;按住“Shift”键可连续选择字段;', $filed],
                ['images', 'images', '图册'],
                ['number', 'price', '价格'],
                ['number', 'rate', '佣金比例'],
                ['number', 'vip_discount', '会员折扣'],
                ['radio', 'status', '状态', '', [0 => '正常', 1 => '隐藏'], 0],
                ['ckeditor', 'content', '内容'],
            ])
            ->layout(['name' => 3, 'user_id' => 3, 'desc' => 5, 'label' => 5, 'transfer' => 6, 'mobile' => 3, 'price' => 2,'rate' => 2, 'vip_discount' => 2])
            ->fetch();
    }

    //编辑
    public function expert_edit($id = 0)
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            if (!$data['name']) {
                $this->error('名称不能为空');
            }
            if (!$data['mobile']) {
                $this->error('联系方式不能为空');
            }
            if (!$data['cover']) {
                $this->error('封面图不能为空');
            }
            if (isset($data['user_id']) && $data['user_id']) {
                $user = Db::name('member')->where('id', $data['user_id'])->find();
                if (!$user) {
                    $this->error('关联用户不存在');
                }
            }
            if (!empty($data['class_ids'])) { 
                $data['class_ids'] = implode(',', $data['class_ids']);
            }else{
                $data['class_ids'] = '';
            }
            $data['update_time'] = time();
            $res = SpExpertModel::where('id',$data['id'])->update($data);
            if ($res) {
                $this->success('保存成功', url('expert_index'));
            } else {
                $this->error('保存失败');
            }
        }

        $id   = input('id');
        $data = SpExpertModel::where('id', $id)->find();
        if (empty($data)) {
            $this->error('数据不存在');
        }
        if($data['class_ids']){
            $data['class_ids'] = explode(',',$data['class_ids']);
            //$class_ids = Db::name('sm_class')->where('mid', 1)->where('parentid', '<>', 0)->where('id','in',$data['class_ids'])->column('id', 'name');
        }
        $SpCate    = SpCateModel::where('pid',4)->where('cate_show_type',1)->order('sort asc')->where('is_del',0)->column('cate_name','id');
        $filed = Db::name('sm_class')->where('mid', 1)->where('parentid', '<>', 0)->where('url','<>','')->where('is_del', 0)->column('name', 'id');
        return ZBuilder::make('form')
            ->addFormItems([
                ['select', 'cid', '所属分类', '', $SpCate],
                ['text', 'name', '名称'],
                ['text', 'user_id', '关联用户ID', '不关联用户可填0'],
                ['textarea', 'desc', '描述'],
                ['tags', 'label', '标签'],
                ['text', 'mobile', '联系方式'],
                ['image', 'weichat_code', '微信二维码'],
                ['image', 'cover', '封面图'],
                ['transfer', 'class_ids', '可选领域', '已选功能', '操作提示:默认导出所有字段。双击字段可移动该字段到另一侧选框;按住‘Ctrl”键并点击字段可选择多个字段;按住“Shift”键可连续选择字段;', $filed, $data['class_ids']],
                ['images', 'images', '图册'],
                ['number', 'price', '价格'],
                ['number', 'rate', '佣金比例'],
                ['number', 'vip_discount', '会员折扣'],
                ['radio', 'status', '状态', '', [0 => '正常', 1 => '隐藏'], 0],
                ['radio', 'review_status', '审核状态', '', [0 => '审核中', 1 => '通过',-1=>'拒绝'], 0],
                ['ckeditor', 'content', '内容'],
                ['hidden', 'id'],
            ])
            ->setFormData($data)
            ->layout(['name' => 3, 'user_id' => 3, 'desc' => 5, 'label' => 5, 'transfer' => 6, 'mobile' => 3, 'price' => 2,'rate' => 2, 'vip_discount' => 2])
            ->fetch();
    }

    //删除
    public function expert_del() {}
}
