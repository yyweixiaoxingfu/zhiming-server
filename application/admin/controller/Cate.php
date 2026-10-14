<?php
//塔罗牌控制器
namespace app\admin\controller;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\member\model\MemberModel;
use app\admin\model\SpCateModel;
use app\admin\model\SpProjectModel;
use app\admin\model\SpNewsModel;
use app\admin\model\SpConfigModel;
use app\admin\model\SpExpertModel;
use think\Db;

class Cate extends Admin
{
    // 列表
    public function index($group='')
    {
        $parent_data    = SpCateModel::where('pid', 0)->where('is_del', 0)->order('sort asc')->column('cate_name', 'id');
        foreach ($parent_data as $key => &$value) {
            $list_tab[$key] = ['title' => $value, 'url' => url('index', ['group' => $key])];
        }
        if(!$group){
            $keys  = array_keys($parent_data);
            $group = isset($keys[0]) && $keys[0] ? $keys[0] : 1;
        }
        $map            = $this->getMap();
        $order          = $this->getOrder('pid asc,cate_show_type asc,sort asc,id dsec');
        $where          = "pid={$group} or id={$group}";
        foreach($map as $k=>$v){
            if(isset($v[0]) && $v[0]=='cate_show_type'){
                $where = "pid={$group}";
            }
        }
        $cate_type      = [1 => '测算', 2 => '文章', 3 => '日历', 4 => '专家'];
        $data_list      = SpCateModel::where($map)->where($where)->order($order)->paginate()->each(function($item){
            if($item['pid']==0){
                $item['cate_show_type_name'] = '<span style="color: #f0ad4e;">顶级分类</span>';
            }
            if($item['pid']>0){
                $item['cate_show_type_name'] = $item['cate_show_type'] == 0 ? '<span style="color: red;">金刚区</span>' : '<span style="color: #5cb85c;">分类筛选</span>';
            }
            return $item;
        });
        $topBtn     = [];
        $rightBtn = [
                'edit'      => ['class' => 'btn btn-sm btn-warning', 'href' => url('edit', ['id' => '__id__', 'group' => $group])],
                'delete'    => ['class' => 'btn btn-sm btn-danger ajax-get confirm', 'href' => url('del', ['id' => '__id__', 'group' => $group])],
        ];
        if($group<>3){
            $topBtn    = [
                'add'       => ['class' => 'btn btn-warning', 'href' => url('add', ['group' => $group])],
            ];
        }
        
        return ZBuilder::make('table')
            ->setTabNav($list_tab, $group)
            ->setPageTitle('模块分类管理')
            ->hideCheckbox()
              ->setTableName('sp_cate')
            ->setSearchArea([
                ['text:1', 'id', 'ID'],
                ['text:2', 'cate_name', '名称'],
                ['select:2', 'cate_show_type', '显示位置', '', '', ['0'=>'金刚区','1'=>'分类筛选']],
                //['select:2', 'cate_type', '属性', '', '', $cate_type],
            ])
            ->addColumns([
                ['id', 'ID'],
                ['cate_name', '名称','text.edit'],
                ['cate_show_type_name', '显示位置'],
                ['cate_title', '标题','text.edit'],
                ['cate_desc', '描述','popover',10],
                ['cate_img', '图标','picture'],
                ['cate_type', '属性', 'status', '',$cate_type],
                ['cate_status', '状态', 'switch'],
                ['sort', '排序', 'text.edit'],
                ['create_time', '创建时间', 'datetime'],
                ['update_time', '更新时间', 'datetime'],
                ['right_button', '操作', 'btn'],
            ])
            ->setRowList($data_list)
            ->setColumnWidth([
                'id'                    => 50,
                'sort'                  => 50,
                'cate_name'             => 80,
                'parent_name'           => 80,
                'cate_show_type_name'   => 80,
                'cate_img'              => 75,
                'cate_type'             => 75,
                'cate_status'           => 75,
                'cate_title'            => 135,
                'right_button'          => 100,
                'create_time'           => 120,
                'update_time'           => 120,
            ])
            ->addRightButtons($rightBtn)
            ->addTopButtons($topBtn)
            ->setExtraCss($this->css)
            ->fetch();
    }

    public function add()
    { 
        $group = input('group',1);
        if(!$group){
            $this->error('参数错误');
        }
        if($this->request->isPost()){
            $data = input('post.');
            $data['create_time'] = time();
            if (!$data['cate_name']) {
                $this->error('名称不能为空');
            }
            if (!$data['cate_title']) {
                $this->error('标题不能为空');
            }
            if (isset($data['pid']) && $data['pid'] == '') {
                $this->error('请选择父级分类');
            }
            if($data['pid']>0){
                $parent = SpCateModel::where('id',$data['pid'])->where('is_del',0)->find();
                if(empty($parent)){
                    $this->error('请选择正确的父级分类');
                }
                $data['cate_type'] = $parent['cate_type'];
            }
            $res = SpCateModel::insert($data);
            if($res){
                $this->success('添加成功',url('index',['group'=>$group]));
            }else{
                $this->error('添加失败');
            }
        }
      
        $SpCate    = SpCateModel::where('pid',0)->order('sort asc')->column('cate_name','id');
        $FormItems = [
            ['select', 'pid', '父级分类', '', $SpCate,$group,'disabled'],
            ['hidden', 'pid', $group],
            ['text', 'cate_name', '名称'],
            ['text', 'cate_title', '标题', '实际为分类补充描述'],
            ['image', 'cate_img', '图标', '非必填,头部分类需上传'],
            ['textarea', 'cate_desc', '描述'],
            ['textarea', 'cate_keyword', '关键词','SEO优化使用'],
            ['radio', 'cate_status', '是否隐藏', '', [0 => '是', 1 => '否'], 1],
            ['radio', 'cate_show_type', '展示位置', '分类功能为测算时生效,作为分类筛选无需关联文章', [0 => '首页分类区', 1 => '首页二级分类筛选'], 0],
        ];
        switch($group) {
            case 1:
                $SpProject = SpProjectModel::where('p_status',1)->column('p_title','id');
                $FormItems[] =  ['select', 'link_id', '关联项目', '显示位置为金刚区需要关联项目', $SpProject];
                break;
            case 2:
                $SpNews    = SpNewsModel::where('status',1)->order('id desc')->column('title','id');
                $FormItems[] =  ['select', 'link_id', '关联文章', '显示位置为金刚区需要关联文章', $SpNews];
                break;
            case 4:
                $SpExpert  = SpExpertModel::where('is_del', 0)->where('review_status',1)->where('status',0)->column('name','id');
                $FormItems[] =  ['select', 'link_id', '关联专家', '显示位置为金刚区需要关联专家', $SpExpert];
                break;
        }
        $FormItems[]  =  ['number', 'sort', '排序'];
        return ZBuilder::make('form')
            ->setPageTitle('添加分类')
            ->addFormItems($FormItems)
            ->layout([
                'pid'           => 4,
                'cate_name'     => 6,
                'cate_title'    => 6,
                'cate_keyword'  => 6,
                'cate_desc'     => 6,
                'cate_status'   => 7,
                'link_id'       => 6,
                'sort'          => 2
            ])
            ->fetch();
    }

    public function edit($id = 0)
    { 
        $group      = input('group',1);
        if(!$group){
            $this->error('参数错误');
        }
        if($this->request->isPost()){
            $data = input('post.');
            $data['update_time'] = time();
            if (!$data['cate_name']) {
                $this->error('名称不能为空');
            }
            if (!$data['cate_title']) {
                $this->error('标题不能为空');
            }
            if (isset($data['pid']) && $data['pid'] == '') {
                $this->error('请选择父级分类');
            }
            if($data['pid']>0){
                $parent = SpCateModel::where('id',$data['pid'])->where('is_del',0)->find();
                if(empty($parent)){
                    $this->error('请选择正确的父级分类');
                }
                $data['cate_type'] = $parent['cate_type'];
            }
            $res = SpCateModel::update($data);
            if($res){
                $this->success('编辑成功',url('index',['group'=>$group]));
            }else{
                $this->error('编辑失败');
            }
        }
        $id         = input('id');
        $from_data  = SpCateModel::where('id', $id)->where('is_del',0)->find();
        if (!$from_data) {
            $this->error('数据不存在', url('index'));
        }
    
        $SpCate    = SpCateModel::where('pid',0)->order('sort asc')->column('cate_name','id');
        $form_items = [
                ['text', 'cate_name', '名称'],
                ['text', 'cate_title', '标题', '实际为分类补充描述'],
                ['textarea', 'cate_desc', '描述'],
                ['textarea', 'cate_keyword', '关键词','SEO优化使用'],
                ['radio', 'cate_status', '是否隐藏', '', [0 => '是', 1 => '否'], 1],
                ['radio', 'cate_show_type', '展示位置', '分类功能为测算时生效', [0 => '首页分类区', 1 => '首页二级分类筛选'], 0],
                ['number', 'sort', '排序'],
                ['hidden', 'pid',0],
                ['hidden', 'id',$from_data['id']],
        ];
        if($from_data['pid']>0){
            $form_items = [
                        ['select', 'pid', '父级分类', '', $SpCate,$from_data['pid'],'disabled'],
                        ['hidden', 'pid',$from_data['pid']],
                        ['hidden', 'id',$from_data['id']],
                        ['text', 'cate_name', '名称'],
                        ['text', 'cate_title', '标题', '实际为分类补充描述'],
                        ['image', 'cate_img', '图标', '非必填,头部分类需上传'],
                        ['textarea', 'cate_desc', '描述'],
                        ['textarea', 'cate_keyword', '关键词','SEO优化使用'],
                        ['radio', 'cate_status', '是否隐藏', '', [0 => '是', 1 => '否'], 1],
                        ['radio', 'cate_show_type', '展示位置', '分类功能为测算时生效,作为分类筛选无需关联文章', [0 => '首页分类区', 1 => '首页二级分类筛选'], 0],
                ];
            switch($group) {
                case 1:
                    $SpProject = SpProjectModel::where('p_status',1)->column('p_title','id');
                    $form_items[] =  ['select', 'link_id', '关联项目', '显示位置为金刚区需要关联项目', $SpProject];
                    break;
                case 2:
                    $SpNews    = SpNewsModel::where('status',1)->order('id desc')->column('title','id');
                    $form_items[] =  ['select', 'link_id', '关联文章', '显示位置为金刚区需要关联文章', $SpNews];
                    break;
                case 4:
                     $SpExpert  = SpExpertModel::where('is_del', 0)->where('review_status',1)->where('status',0)->column('name','id');
                    $form_items[] =  ['select', 'link_id', '关联专家', '显示位置为金刚区需要关联专家', $SpExpert];
                    break;
            }
            $form_items[]  =  ['number', 'sort', '排序'];
        }
        return ZBuilder::make('form')
            ->setPageTitle('编辑分类')
            ->addFormItems($form_items)
            ->layout([
                'pid'           => 4,
                'cate_name'     => 6,
                'cate_title'    => 6,
                'cate_keyword'  => 6,
                'cate_desc'     => 6,
                'cate_status'   => 7,
                'sort'          => 2,
                'link_id'       => 6,
            ])
            ->setFormData($from_data)
            ->fetch();
    }

    public function del ($id = 0){

    }

    public function project_index(){
        $map            = $this->getMap();
        $order          = $this->getOrder('sort asc,id dsec');
        $parent_data    = SpCateModel::where('pid',1)->where('cate_show_type',1)->order('sort asc')->where('is_del',0)->column('cate_name','id');
        foreach ($parent_data as $key => $value) {
            $parent_data[$key] = $key.'-'.$value;
        }
        $data_list      = SpProjectModel::where($map)->order($order)->paginate()->each(function($item) use ($parent_data){
            $item['cid_name'] = isset($parent_data[$item['cid']])?$parent_data[$item['cid']]:'';
            return $item;
        });
         $topBtn        = [
            'add'       => ['class' => 'btn btn-warning', 'href' => url('project_add')],
        ];
        $rightBtn = [
            'edit'      => ['class' => 'btn btn-sm btn-warning', 'href' => url('project_edit', ['id' => '__id__'])],
            'save'      => ['title' => '页面内容编辑', 'icon' => 'fa fa-fw fa-save', 'class' => 'btn btn-sm btn-success', 'href' => url('project_web_edit', ['id' => '__id__'])],
            'delete'    => ['class' => 'btn btn-sm btn-danger ajax-get confirm', 'href' => url('project_del', ['id' => '__id__'])],
        ];
        return ZBuilder::make('table')
            ->setPageTitle('项目管理')
            ->hideCheckbox()
              ->setTableName('sp_project')
            ->setSearchArea([
                ['text:1', 'id', 'ID'],
                ['text:2', 'p_title', '项目名称'],
                ['text:2', 'p_model', '关联模型'],
                ['select:2', 'cid', '所属分类', '', '', $parent_data],
            ])
            ->addColumns([
                ['id', 'ID'],
                ['cid', '所属分类','status','', $parent_data],
                ['p_title', '名称','text'],
                ['p_desc', '描述','popover',8],
                ['p_model', '关联模型','text'],
                ['p_img', '封面','picture'],
                ['p_icon', '图标','picture'],
                ['p_price', '价格','text'],
                ['p_points', '积分','text'],
                ['p_ad_num', '体验次数','text'],
                ['p_cs_num', '使用人数','text'],
                ['p_status', '状态', 'switch'],
                ['sort', '排序', 'text.edit'],
                ['create_time', '创建时间', 'datetime'],
                ['update_time', '更新时间', 'datetime'],
                ['right_button', '操作', 'btn'],
            ])
            ->setRowList($data_list)
            ->setColumnWidth([
                'id'                 => 50,
                'sort'               => 50,
                'p_title'            => 80,
                'p_img'              => 75,
                'p_icon'             => 60,
                'p_price'            => 75,
                'p_points'           => 75,
                'p_ad_num'           => 75,
                'p_cs_num'           => 75,
                'p_status'           => 75,
                'right_button'       => 110,
                'create_time'        => 120,
                'update_time'        => 120,
            ])
            ->addRightButtons($rightBtn)
            ->addTopButtons($topBtn)
            ->setExtraCss($this->css)
            ->fetch();
    }


    public function project_add(){
        if($this->request->isPost()){
            $data = input('post.');
            $data['create_time'] = time();
            if (!$data['p_title']) {
                $this->error('项目标题不能为空');
            }
            if (!isset($data['cid']) && !$data['cid']) {
                $this->error('请选择项目分类');
            }
            if(!isset($data['p_model']) && !$data['p_model']){
                $this->error('请输入项目关联模型');
            }
            if(!isset($data['p_price'])){
                $this->error('请输入项目价格');
            }
            if(!isset($data['p_points'])){
                $this->error('请输入项目所需消耗的积分');
            }
            if(!isset($data['p_ad_num'])){
                $this->error('请输入体验次数');
            }
            if(!isset($data['p_cs_num'])){
                $this->error('请输入已测算人数');
            }
            if(!isset($data['vip_discount'])){
                $this->error('请输入会员折扣');
            }
            if(isset($data['rec_pro_ids']) && !empty($data['rec_pro_ids'])){
                $data['rec_pro_ids'] = implode(',',$data['rec_pro_ids']);
            }
             if(isset($data['rec_expert_ids']) && !empty($data['rec_expert_ids'])){
                $data['rec_expert_ids'] = implode(',',$data['rec_expert_ids']);
            }
            $res = SpProjectModel::insert($data);
            if($res){
                $this->success('添加成功',url('project_index'));
            }else{
                $this->error('添加失败');
            }
        }
        $SpCate    = SpCateModel::where('pid',1)->where('cate_show_type',1)->order('sort asc')->where('is_del',0)->column('cate_name','id');
        $SpProject = SpProjectModel::where('p_status',1)->column('p_title','id');
        $SpExpert  = SpExpertModel::where('is_del', 0)->where('review_status',1)->where('status',0)->column('name','id');
        return ZBuilder::make('form')
            ->setPageTitle('添加项目')
            ->addFormItems([
                ['hidden', 'cid', '项目分类', '', $SpCate],
                ['text', 'p_title', '项目标题'],
                ['text', 'p_model', '项目关联模型', '用于前台模块交互'],
                ['text', 'p_extra', '项目扩展信息', '用于后台方法和参数'],
                ['image', 'p_img', '封面图'],
                ['image', 'p_icon', '项目图标'],
                ['number', 'p_price', '项目价格'],
                ['number', 'p_points', '项目积分'],
                ['number', 'p_ad_num', '体验次数','消耗对应次数可以免费测算一次'],
                ['number', 'p_cs_num', '已测算人数'],
                ['number', 'vip_discount', '会员折扣'],
                ['text', 'p_url', '跳转地址'],
                ['textarea', 'p_desc', '项目描述'],
                ['textarea', 'p_keyword', '关键词','SEO优化使用'],
                ['radio', 'p_status', '是否隐藏', '', [0 => '是', 1 => '否'], 1],
                ['select', 'rec_pro_ids', '推荐项目', '', $SpProject,'', 'multiple'],
                ['select', 'rec_expert_ids', '推荐专家', '', $SpExpert,'', 'multiple'],
            ])
            ->layout([
                'cid' => 6,
                'p_title' => 8,
                'p_model' => 5,
                'p_price' => 2,
                'p_points' => 2,
                'p_ad_num' => 2,
                'p_cs_num' => 2,
                'vip_discount' => 2,
                'p_url' => 8,
                'p_desc' => 6,
                'p_extra' => 5,
                'p_keyword' => 6,
                'rec_pro_ids' => 6,
                'rec_expert_ids' => 6,
            ])
            ->fetch();
    }

    public function project_edit($id = 0){
        if($this->request->isPost()){
            $data = input('post.');
            $data['update_time'] = time();
            if (!$data['p_title']) {
                $this->error('项目标题不能为空');
            }
            if (!isset($data['cid']) && !$data['cid']) {
                $this->error('请选择项目分类');
            }
            if(!isset($data['p_model']) && !$data['p_model']){
                $this->error('请输入项目关联模型');
            }
            if(!isset($data['p_price'])){
                $this->error('请输入项目价格');
            }
            if(!isset($data['p_points'])){
                $this->error('请输入项目所需消耗的积分');
            }
            if(!isset($data['p_ad_num'])){
                $this->error('请输入体验次数');
            }
            if(!isset($data['p_cs_num'])){
                $this->error('请输入已测算人数');
            }
            if(!isset($data['vip_discount'])){
                $this->error('请输入会员折扣');
            }
            if(isset($data['rec_pro_ids']) && !empty($data['rec_pro_ids'])){
                $data['rec_pro_ids'] = implode(',',$data['rec_pro_ids']);
            }
             if(isset($data['rec_expert_ids']) && !empty($data['rec_expert_ids'])){
                $data['rec_expert_ids'] = implode(',',$data['rec_expert_ids']);
            }
            $res = SpProjectModel::update($data);
            if($res){
                $this->success('修改成功',url('project_index'));
            }else{
                $this->error('修改失败');
            }
        }
        $id         = input('id');
        $data       = SpProjectModel::where('id', $id)->find();
        if (!$data) {
            $this->error('数据不存在', url('project_index'));
        }

        $SpCate     = SpCateModel::where('pid',1)->where('cate_show_type',1)->order('sort asc')->where('is_del',0)->column('cate_name','id');
        $SpProject  = SpProjectModel::where('p_status',1)->where('id','neq',$id)->column('p_title','id');
        $SpExpert   = SpExpertModel::where('is_del', 0)->where('review_status',1)->where('status',0)->column('name','id');
        return ZBuilder::make('form')
            ->setPageTitle('添加项目')
            ->addFormItems([
                ['select', 'cid', '项目分类', '', $SpCate],
                ['text', 'p_title', '项目标题'],
                ['text', 'p_model', '项目关联模型', '用于前台模块交互'],
                ['text', 'p_extra', '项目扩展信息', '用于后台方法和参数'],
                ['image', 'p_img', '封面图'],
                ['image', 'p_icon', '项目图标'],
                ['number', 'p_price', '项目价格'],
                ['number', 'p_points', '项目积分'],
                ['number', 'p_ad_num', '体验次数','消耗对应次数可以免费测算一次'],
                ['number', 'p_cs_num', '已测算人数'],
                ['number', 'vip_discount', '会员折扣'],
                ['text', 'p_url', '跳转地址'],
                ['textarea', 'p_desc', '项目描述'],
                ['textarea', 'p_keyword', '关键词','SEO优化使用'],
                ['radio', 'p_status', '是否隐藏', '', [0 => '是', 1 => '否'], 1],
                ['select', 'rec_pro_ids', '推荐项目', '', $SpProject,'', 'multiple'],
                ['select', 'rec_expert_ids', '推荐专家', '', $SpExpert,'', 'multiple'],
                ['hidden', 'id',$data['id']],
            ])
            ->layout([
                'cid' => 6,
                'p_title' => 8,
                'p_model' => 5,
                'p_price' => 2,
                'p_points' => 2,
                'p_ad_num' => 2,
                'p_cs_num' => 2,
                'vip_discount' => 2,
                'p_url' => 8,
                'p_desc' => 6,
                'p_extra' => 5,
                'p_keyword' => 6,
                'rec_pro_ids' => 6,
                'rec_expert_ids' => 6,
            ])
            ->setFormData($data)
            ->fetch();
    }

    public function project_web_edit($id = 0)
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            unset($data['__token__']);
            // foreach ($data as $k => &$v) {
            //     if (mb_strpos($v, 'img') !== false && (mb_strpos($v, 'http') === false && mb_strpos($v, 'https') === false)) {
            //         print_r($v);die;
            //         $data[$k] = Img_url($v);
            //     }
            // }
            $json_data = json_encode($data);
            $res = SpProjectModel::where('id', $data['id'])->update(['p_content' => $json_data,'update_time'=>time()]);
            if ($res) {
                $this->success('保存成功', url('project_index'));
            } else {
                $this->error('保存失败');
            }
        }
        $id = input('id');
        $sm_data = SpProjectModel::where('id', $id)->find();
        if (!$sm_data) {
            $this->error('数据不存在', url('sm_class'));
        }
        $data = json_decode($sm_data['p_content'], true);
        $url  = $sm_data['p_url'];
        if (!$url) {
            $this->error('绑定链接不存在', url('project_index'));
        }
        $sm_cate = SpCateModel::where('id', $sm_data['cid'])->find();
        if(!$sm_cate){
            $this->error('分类不存在', url('project_index'));
        }
        $url = $url.'?cate_type='.$sm_cate['cate_type'].'&link_id='.$sm_data['id'];
        $js = <<<EOF
            <script>
                var html =  '<div class="iframe_model" style="float: left;padding-right:3rem;"><iframe src="$url" style="width: 400px; height:215rem;"></iframe></div>';
                $(function(){
                    $('#form_group_desc3').before(html);
                })
            </script>
EOF;
        unset($data['id']);
        return ZBuilder::make('form')
            ->setExtraJs($js)
            ->addFormItems([
                ['text', 'desc3', '顶部滚动公告', '页面顶部滚动公告内容'],
                ['image', 'img1', '素材1', '对应页面背景图素材 750*1200 px'],
                ['image', 'img2', '素材2', '对应运程图素材 750*320px'],
                ['image', 'img3', '素材3', '对应吐故纳新图素材750*630px'],
                ['image', 'img4', '素材4', '对应吐故纳新图素材750*630px'],
                ['array', 'fields', '字段内容', '用户需要输入输入的字段,非专业运维勿动'],
                ['image', 'button_img1', '按钮素材', '对应页面按钮的素材'],
                ['text', 'desc1', '按钮文字', '查看运势按钮'],
                ['text', 'desc2', '已有X人测算,满意率Y%', '隐私协议下方描述'],
                ['ckeditor', 'content1', '内容', '下方内容处素材，通常为多图'],
                ['text', 'comment_title', '评论标题'],
                ['text', 'cs_count', '测算人数'],
                ['text', 'g_comment_rate', '好评率'],
                ['tags', 'label_total', '好评标签合集'],
                ['array', 'comment_total', '页面评论合集','每条评论由“用户名:(英文下的冒号)内容“组成；如“张三:这是张三的评论”,每行一条评论'],
                ['text', 'desc4', '底部悬浮按钮文字', '页面最下方底部悬浮按钮文字'],
                ['hidden', 'id', $sm_data['id']],
            ])
            ->setFormData($data)
            ->layout([
                'img1' => 5, 'img2' => 5, 'img3' => 5, 'img4' => 5, 'button_img1' => 5, 'desc1' => 5, 
                'desc2' => 5, 'content1' => 6, 'desc2' => 5, 'desc3' => 6, 
                'comment_title' => 6, 'cs_count' => 3, 'g_comment_rate' => 3, 
                'label_total' => 7, 'comment_total' => 7,'fields' => 5, 'desc4' => 6,
            ])
            ->fetch();
    }


    //文章管理
    public function news_index(){
        $map              = $this->getMap();
        $order            = $this->getOrder('id dsec');
        $parent_data      = SpCateModel::where('pid',2)->where('cate_show_type',1)->order('sort asc')->where('is_del',0)->column('cate_name','id');
        foreach ($parent_data as $key => $value) {
            $parent_data[$key] = $key.'-'.$value;
        }
        $data_list        = SpNewsModel::where($map)->order($order)->paginate();
         $topBtn        = [
            'add'       => ['class' => 'btn btn-warning', 'href' => url('news_add')],
        ];
        $rightBtn = [
            'edit'      => ['class' => 'btn btn-sm btn-warning', 'href' => url('news_edit', ['id' => '__id__'])],
            'delete'    => ['class' => 'btn btn-sm btn-danger ajax-get confirm', 'href' => url('news_del', ['id' => '__id__'])],
        ];
        return ZBuilder::make('table')
            ->setPageTitle('百科文章管理')
            ->hideCheckbox()
              ->setTableName('sp_news')
            ->setSearchArea([
                ['text:1', 'id', 'ID'],
                ['text:2', 'title', '文章名称'],
                ['select:2', 'cid', '所属分类', '', '', $parent_data],
            ])
            ->addColumns([
                ['id', 'ID'],
                ['cid', '所属分类','status','', $parent_data],
                ['title', '名称','text'],
                ['desc', '描述','popover',8],
                ['cover', '封面','picture'],
                ['click_count', '点击次数','text'],
                ['status', '状态', 'switch'],
                ['create_time', '创建时间', 'datetime'],
                ['update_time', '更新时间', 'datetime'],
                ['right_button', '操作', 'btn'],
            ])
            ->setRowList($data_list)
            ->setColumnWidth([
                'id'                 => 50,
                'title'              => 80,
                'cover'              => 75,
                'click_count'        => 75,
                'status'             => 75,
                'right_button'       => 100,
                'create_time'        => 120,
                'update_time'        => 120,
            ])
            ->addRightButtons($rightBtn)
            ->addTopButtons($topBtn)
            ->setExtraCss($this->css)
            ->fetch();
    }

    public function news_add(){
        if($this->request->isPost()){
            $data = input('post.');
            $data['create_time'] = time();
            if (!$data['title']) {
                $this->error('标题不能为空');
            }
            if (!isset($data['cid']) && !$data['cid']) {
                $this->error('请选择项目分类');
            }
            $data['model'] = 'news';
            if(!isset($data['cover']) || !$data['cover']){
                $this->error('请上传封面图');
            }
            if(!isset($data['click_count'])){
                $this->error('请输入点击次数');
            }
            if(!isset($data['desc']) || !$data['desc']){
                $this->error('请输入文章描述');
            }
            if($data['content_type'] <> 'html' && (!isset($data['content_file']) || !$data['content_file'])){
                $this->error('请上传电子书文件');
            }
            if($data['content_type'] == 'html' && (!isset($data['content']) || !$data['content'])){
                $this->error('请输入文章内容');
            }
            
            if(isset($data['rec_pro_ids']) && !empty($data['rec_pro_ids'])){
                $data['rec_pro_ids'] = implode(',',$data['rec_pro_ids']);
            }
             if(isset($data['rec_expert_ids']) && !empty($data['rec_expert_ids'])){
                $data['rec_expert_ids'] = implode(',',$data['rec_expert_ids']);
            }
            $res = SpNewsModel::insert($data);
            if($res){
                $this->success('添加成功',url('news_index'));
            }else{
                $this->error('添加失败');
            }
        }
        $SpCate    = SpCateModel::where('pid',2)->where('cate_show_type',1)->order('sort asc')->where('is_del',0)->column('cate_name','id');
        $SpProject = SpProjectModel::where('p_status',1)->column('p_title','id');
        $SpExpert  = SpExpertModel::where('is_del', 0)->where('review_status',1)->where('status',0)->column('name','id');
        return ZBuilder::make('form')
            ->setPageTitle('添加百科文章')
            ->addFormItems([
                ['select', 'cid', '所属分类', '', $SpCate],
                ['text', 'title', '文章标题'],
                ['text', 'author', '文章作者'],
                ['image', 'cover', '封面图'],
                ['number', 'click_count', '点击次数'],
                ['text', 'url', '跳转地址'],
                ['textarea', 'desc', '文章描述'],
                ['textarea', 'keyword', '关键词','SEO优化使用'],
                ['radio', 'content_type', '文章类型', '目前电子书类型仅支持EPUB文件', ['html' => 'HTML内容', 'txt' => 'TXT','epub'=>'EPUB文件','pdf'=>'PDF文件'], 'html'],
                ['file', 'content_file', '附件'],
                ['ckeditor', 'content', '文章内容'],
                ['radio', 'status', '是否隐藏', '', [0 => '是', 1 => '否'], 1],
                ['select', 'rec_pro_ids', '推荐项目', '', $SpProject,'', 'multiple'],
                ['select', 'rec_expert_ids', '推荐专家', '', $SpExpert,'', 'multiple'],
            ])
            ->layout([
                'cid'           => 6,
                'title'         => 8,
                'author'        => 3,
                'click_count'   => 2,
                'url'           => 7,
                'desc'          => 6,
                'extra'         => 5,
                'keyword'       => 6,
                'rec_pro_ids'   => 6,
                'rec_expert_ids'=> 6,
            ])
            ->fetch();
    }

    public function news_edit(){
        if($this->request->isPost()){
            $data = input('post.');
            $data['create_time'] = time();
            if (!$data['title']) {
                $this->error('标题不能为空');
            }
            if (!isset($data['cid']) && !$data['cid']) {
                $this->error('请选择项目分类');
            }
            $data['p_model'] = 'news';
            if(!isset($data['cover']) || !$data['cover']){
                $this->error('请上传封面图');
            }
            if(!isset($data['click_count'])){
                $this->error('请输入点击次数');
            }
            if(!isset($data['desc']) || !$data['desc']){
                $this->error('请输入文章描述');
            }
            if($data['content_type'] <> 'html' && (!isset($data['content_file']) || !$data['content_file'])){
                $this->error('请上传电子书文件');
            }
            if($data['content_type'] == 'html' && (!isset($data['content']) || !$data['content'])){
                $this->error('请输入文章内容');
            }
            
            if(isset($data['rec_pro_ids']) && !empty($data['rec_pro_ids'])){
                $data['rec_pro_ids'] = implode(',',$data['rec_pro_ids']);
            }
             if(isset($data['rec_expert_ids']) && !empty($data['rec_expert_ids'])){
                $data['rec_expert_ids'] = implode(',',$data['rec_expert_ids']);
            }
            $res = SpNewsModel::update($data);
            if($res){
                $this->success('编辑成功',url('news_index'));
            }else{
                $this->error('编辑失败');
            }
        }
        $id         = input('id');
        $data       = SpNewsModel::where('id', $id)->find();
        if (!$data) {
            $this->error('数据不存在', url('news_index'));
        }
        $SpCate    = SpCateModel::where('pid',2)->where('cate_show_type',1)->order('sort asc')->where('is_del',0)->column('cate_name','id');
        $SpProject = SpProjectModel::where('p_status',1)->column('p_title','id');
        $SpExpert  = SpExpertModel::where('is_del', 0)->where('review_status',1)->where('status',0)->column('name','id');
        return ZBuilder::make('form')
            ->setPageTitle('添加百科文章')
            ->addFormItems([
                ['select', 'cid', '所属分类', '', $SpCate],
                ['text', 'title', '文章标题'],
                ['text', 'author', '文章作者'],
                ['image', 'cover', '封面图'],
                ['number', 'click_count', '点击次数'],
                ['text', 'url', '跳转地址'],
                ['textarea', 'desc', '文章描述'],
                ['textarea', 'keyword', '关键词','SEO优化使用'],
                ['radio', 'content_type', '文章类型', '目前电子书类型仅支持EPUB文件', ['html' => 'HTML内容', 'txt' => 'TXT','epub'=>'EPUB文件','pdf'=>'PDF文件'], 'html'],
                ['file', 'content_file', '附件'],
                ['ckeditor', 'content', '文章内容'],
                ['radio', 'status', '是否隐藏', '', [0 => '是', 1 => '否'], 1],
                ['select', 'rec_pro_ids', '推荐项目', '', $SpProject,'', 'multiple'],
                ['select', 'rec_expert_ids', '推荐专家', '', $SpExpert,'', 'multiple'],
                ['hidden', 'id'],
            ])
            ->setFormData($data)
            ->layout([
                'cid'           => 6,
                'title'         => 8,
                'author'        => 3,
                'click_count'   => 2,
                'url'           => 7,
                'desc'          => 6,
                'extra'         => 5,
                'keyword'       => 6,
                'rec_pro_ids'   => 6,
                'rec_expert_ids'=> 6,
            ])
            ->fetch();
    }

    public function news_del(){
        $id    = input('id');
        if (!$id) {
            $this->error('请选择要删除的记录');
        }
        $res = SpNewsModel::where('id', $id)->delete();
        if ($res) {
            $this->success('删除成功', 'news_index');
        } else {
            $this->error('删除失败');
        }
    }

    public function sp_config(){
        if($this->request->isPost()){
            $data = input('post.');
            $data['update_time'] = time();
            if(!isset($data['id']) || !$data['id']){
                $this->error('请选择要修改的记录');
            }
            if(!isset($data['hot_x']) || !$data['hot_x']){
                $this->error('请输入热门项目排');
            }
            if(!isset($data['hot_y']) || !$data['hot_y']){
                $this->error('请输入热门项目列');
            }
            if(!isset($data['button_num']) || !$data['button_num']){
                $this->error('请输入底部按钮数');
            }
            if(!isset($data['calendar_api_1']) || !$data['calendar_api_1']){
                $this->error('请输入日历API1');
            }
            if(!isset($data['calendar_api_2']) || !$data['calendar_api_2']){
                $this->error('请输入日历API2');
            }
            $res = SpConfigModel::update($data);
            if($res){
                $this->success('修改成功',url('sp_config'));
            }else{
                $this->error('修改失败');
            }
        }
        $data = SpConfigModel::where('id', 1)->find();
        return ZBuilder::make('form')
            ->setPageTitle('配置')
            ->addFormItems([
                ['number', 'hot_x', '排'],
                ['number', 'hot_y', '列'],
                ['text', 'system_year', '系统年份'],
                ['text', 'calendar_api_1', '日历API1'],
                ['text', 'calendar_api_2', '日历API2'],
                ['number', 'button_num', '底部按钮数'],
                ['radio', 'hot_zone_type', '首页金刚区', '', [1 => '显示', 0 => '隐藏']],
                ['radio', 'home_cate_type', '首页分类', '', [1 => '显示', 0 => '隐藏']],
                ['textarea', 'comment_data', '首页评分数据','内容之间以|隔开'],
                ['image', 'expert_image', '专家专题页素材'],
                ['hidden', 'id',1],
            ])
            ->layout([
                'hot_x'         => 5,
                'hot_y'         => 5,
                'system_year'   => 4,
                //'calendar_api_1'   => 6,
                //'calendar_api_2'   => 6,
                'button_num'    => 5,
                'comment_data'  => 8,
            ])
            ->setFormData($data)
            ->fetch();

    }

}