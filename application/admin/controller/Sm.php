<?php
//算命控制器
namespace app\admin\controller;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class Sm extends Admin
{
    protected $css = <<<EOF
        <style>
            th,tr{
                text-align: center;
            }
        </style>
EOF;
    //命格列表
    public function sm_81($group = 'sm_81')
    {
        $list_tab = [
            'sm_81'  => ['title' => '命格数据', 'url' => url('sm_81', ['group' => 'sm_81'])],
            'sm_gua_ci' => ['title' => '卦辞数据', 'url' => url('sm_gua_ci', ['group' => 'sm_gua_ci'])],
            'sm_ziwei' => ['title' => '紫薇-宫位数据', 'url' => url('sm_ziwei', ['group' => 'sm_ziwei'])],
        ];
        $order     = $this->getOrder('id asc');
        $map       = $this->getMap();
        $data_list = Db::name('Sm_81')
            ->where('is_del', '<>', 1)
            ->where($map)
            ->order($order)
            ->paginate('20');
        $topBtn    = [
            'add'       => ['class' => 'btn btn-warning', 'href' => url('sm_81_add')],
        ];
        $rightBtn = [
            'edit'      => ['class' => 'btn btn-sm btn-warning', 'href' => url('sm_81_edit', ['id' => '__id__'])],
            'delete'    => ['class' => 'btn btn-sm btn-danger ajax-get confirm', 'href' => url('sm_81_del', ['id' => '__id__'])],
        ];
        return ZBuilder::make('table')
            ->setTabNav($list_tab,  $group)
            ->hideCheckbox()
            ->setSearchArea([
                ['text:1', 'id', 'ID'],
                ['text:2', 'num', '命格数值'],
                ['text:2', 'jx', '吉凶'],
                ['text:3', 'as', '运势', 'like'],
                ['text:4', 'yy', '运势谶语', 'like']
            ])
            ->addColumns([
                ['id', 'ID'],
                ['num', '命格数值'],
                ['jx', '吉凶', 'text.edit'],
                ['as', '运势', 'text.edit'],
                ['yy', '运势谶语', 'text.edit'],
                ['update_time', '更新时间', 'datetime'],
                ['right_button', '操作', 'btn'],
            ])
            ->setRowList($data_list)
            ->addTopButtons($topBtn)   // 添加授权按钮
            ->addRightButtons($rightBtn)
            ->setExtraCss($this->css)
            ->setColumnWidth([
                'id'  => 15,
                'num' => 15,
                'jx'  => 20,
                'update_time'   => 28,
                'right_button'  => 20,
            ])
            ->fetch();
    }
    //命格数值81增加
    public function sm_81_add()
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            $num        = $data['num'];
            if ($num <= 0 || $num == '') {
                $this->error('命格数值不能小于0');
            }
            $is_data_num = Db::name('Sm_81')->where('num', '=', $num)->find();
            if (!empty($is_data_num)) {
                $this->error('命格数值不能重复');
            }
            $jx         = $data['jx'];
            if (isset($jx) && $jx == '') {
                $this->error('吉凶值不能为空');
            }
            $as         = $data['as'];
            if (isset($as) && $as == '') {
                $this->error('说明值不能为空');
            }
            $yy         = $data['yy'];
            if (isset($yy) && $yy == '') {
                $this->error('运势谶语不能为空');
            }
            $content    = $data['content'];
            if (isset($content) && $content == '') {
                $this->error('内容谶语不能为空');
            }
            $data['update_time'] = time();
            $res = Db::name('Sm_81')->insert($data);
            if ($res) {
                $this->success('新增成功', 'sm_81');
            } else {
                $this->error('新增失败');
            }
        }
        return ZBuilder::make('form')
            ->addFormItems([
                ['number', 'num', '命格数值'],
                ['text', 'jx', '吉凶'],
                ['textarea', 'as', '说明'],
                ['text', 'yy', '运势谶语'],
                ['textarea', 'content', '内容'],
            ])
            ->layout(['num' => 2, 'jx' => 3, 'as' => 5, 'yy' => 5, 'content' => 6])
            ->fetch();
    }
    //命格数值81编辑
    public function sm_81_edit($id = 0)
    {
        if ($this->request->isPost()) {
            $post_data       = input('post.');
            $id              = $post_data['id'];
            $num             = $post_data['num'];
            if ($num <= 0 || $num == '') {
                $this->error('命格数值不能小于0');
            }
            $is_data_num = Db::name('Sm_81')->where([['num', '=', $num], ['id', '<>', $id]])->find();
            if (!empty($is_data_num)) {
                $this->error('命格数值不能重复');
            }
            $jx         = $post_data['jx'];
            if (isset($jx) && $jx == '') {
                $this->error('吉凶值不能为空');
            }
            $as         = $post_data['as'];
            if (isset($as) && $as == '') {
                $this->error('说明值不能为空');
            }
            $yy         = $post_data['yy'];
            if (isset($yy) && $yy == '') {
                $this->error('运势谶语不能为空');
            }
            $content    = $post_data['content'];
            if (isset($content) && $content == '') {
                $this->error('内容谶语不能为空');
            }
            $post_data['update_time'] = time();
            $res = Db::name('Sm_81')->update($post_data);
            if ($res) {
                $this->success('编辑成功', 'sm_81');
            } else {
                $this->error('编辑失败');
            }
        }
        $id = intval($id);
        $data = Db::name('Sm_81')->where('id', '=', $id)->find();
        if (empty($data)) {
            $this->error('未查到当前数据', 'sm_81');
        }
        return ZBuilder::make('form')
            ->addFormItems([
                ['number', 'num', '命格数值'],
                ['text', 'jx', '吉凶'],
                ['textarea', 'as', '说明'],
                ['text', 'yy', '运势谶语'],
                ['textarea', 'content', '内容'],
            ])
            ->setFormData($data)
            ->addHidden('id')
            ->layout(['num' => 2, 'jx' => 3, 'as' => 5, 'yy' => 5, 'content' => 6])
            ->fetch();
    }
    //命格数值81删除
    public function sm_81_del($id = 0)
    {
        $id     = input('id');
        $data   = Db::name('Sm_81')->where([['id', '=', $id], ['is_del', '<>', 1]])->find();
        if (empty($data)) {
            $this->error('未查到当前数据', 'sm_81');
        }
        $post_data['update_time'] = time();
        $post_data['is_del']      = 1;
        $post_data['id']          = $data['id'];
        $res = Db::name('Sm_81')->update($post_data);
        if ($res) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    //卦辞数据
    public function sm_gua_ci($group = 'sm_gua_ci')
    {
        $list_tab = [
            'sm_81'  => ['title' => '命格数据', 'url' => url('sm_81', ['group' => 'sm_81'])],
            'sm_gua_ci' => ['title' => '卦辞数据', 'url' => url('sm_gua_ci', ['group' => 'sm_gua_ci'])],
            'sm_ziwei' => ['title' => '紫薇-宫位数据', 'url' => url('sm_ziwei', ['group' => 'sm_ziwei'])],
        ];
        $order     = $this->getOrder('id asc');
        $map       = $this->getMap();
        $data_list = Db::name('Sm_gua_ci')
            ->where($map)
            ->order($order)
            ->paginate('64');
        $rightBtn = [
            'edit'      => ['class' => 'btn btn-sm btn-warning', 'href' => url('sm_gua_ci_edit', ['id' => '__id__'])],
        ];
        return ZBuilder::make('table')
            ->hideCheckbox()
            ->setTabNav($list_tab,  $group)
            ->setSearchArea([
                ['text:1', 'id', 'ID'],
                ['text:3', 'gua_name', '卦名'],
                ['text:3', 'gua_xiang', '卦相'],
                ['text:2', 'yinyang', '阴阳'],
            ])
            ->addColumns([
                ['id', 'ID'],
                ['gua_name', '卦名'],
                ['yinyang', '阴阳', 'text'],
                ['gua_xiang', '卦相', 'text'],
                ['original_text', '原文', 'text.edit'],
                ['baihua_text', '译文', 'text.edit'],
                ['update_time', '更新时间', 'datetime'],
                ['right_button', '操作', 'btn'],
            ])
            ->setRowList($data_list)
            //->addTopButtons($topBtn)   // 添加授权按钮
            ->addRightButtons($rightBtn)
            ->setExtraCss($this->css)
            ->setColumnWidth([
                'id'            => 15,
                'gua_name'      => 15,
                'yinyang'       => 15,
                'gua_xiang'     => 25,
                'original_text' => 35,
                'update_time'   => 28,
                'right_button'  => 20,
            ])
            ->fetch();
    }

    public function sm_gua_ci_edit()
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            $data['update_time'] = time();
            if (!$data['original_text']) {
                $this->error('原文不能为空');
            }
            if (!$data['baihua_text']) {
                $this->error('译文不能为空');
            }
            if (!$data['xiang_yue']) {
                $this->error('卦相曰不能为空');
            }
            $is_update = Db::name('Sm_gua_ci')->where('id', $data['id'])->update($data);
            if ($is_update) {
                $this->success('编辑成功', 'sm_gua_ci');
            } else {
                $this->error('编辑失败');
            }
        }
        $id   = input('id');
        $data = Db::name('Sm_gua_ci')->where('id', '=', $id)->find();
        if (empty($data)) {
            $this->error('未查到当前数据', 'sm_gua_ci');
        }
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'gua_name', '卦名', '', '', '', 'disabled'],
                ['text', 'yinyang', '阴阳', '', '', '', 'disabled'],
                ['text', 'gua_xiang', '卦相', '', '', '', 'disabled'],
                ['textarea', 'xiang_yue', '卦相曰'],
                ['textarea', 'original_text', '原文'],
                ['ckeditor', 'baihua_text', '译文'],
                ['ckeditor', 'duanyi_text', '《断易天机》解卦'],
                ['ckeditor', 'shaoyong_text', '北宋易学家邵雍解卦'],
                ['ckeditor', 'fupeirong_text', '台湾国学大儒傅佩荣解卦'],
                ['ckeditor', 'traditional_text', '传统解卦'],
                ['ckeditor', 'zhangmingren_text', '台湾张铭仁解卦'],
                ['hidden', 'id', $id],
            ])
            ->setFormData($data)
            ->addHidden('id')
            ->layout(['gua_name' => 3, 'yinyang' => 3, 'gua_xiang' => 3, 'original_text' => 6, 'baihua_text' => 7, 'xiang_yue' => 6, 'duanyi_text' => 7, 'shaoyong_text' => 7, 'fupeirong_text' => 7, 'traditional_text' => 7, 'zhangmingren_text' => 7])
            ->fetch();
    }

    //紫薇斗数-宫位数据
    public function sm_ziwei($group = 'sm_ziwei')
    { 
         $list_tab = [
            'sm_81'  => ['title' => '命格数据', 'url' => url('sm_81', ['group' => 'sm_81'])],
            'sm_gua_ci' => ['title' => '卦辞数据', 'url' => url('sm_gua_ci', ['group' => 'sm_gua_ci'])],
            'sm_ziwei' => ['title' => '紫薇-宫位数据', 'url' => url('sm_ziwei', ['group' => 'sm_ziwei'])],
        ];
        $order     = $this->getOrder('id asc');
        $map       = $this->getMap();
        $data_list = Db::name('Sm_ziwei')
            ->where($map)
            ->order($order)
            ->paginate('50');
        $topBtn    = [
            'add'       => ['class' => 'btn btn-warning', 'href' => url('sm_ziwei_add')],
        ];
        $rightBtn = [
            'edit'      => ['class' => 'btn btn-sm btn-warning', 'href' => url('sm_ziwei_edit', ['id' => '__id__'])],
            'delete'    => ['class' => 'btn btn-sm btn-danger ajax-get confirm', 'href' => url('sm_ziwei_del', ['id' => '__id__'])],
        ];   
        return ZBuilder::make('table')
            ->hideCheckbox()
            ->setTabNav($list_tab,  $group)
            ->setSearchArea([
                ['text:1', 'id', 'ID'],
                ['text:3', 'ziwei_name', '宫名'],
                ['text:3', 'ziwei_title', '标题'],
                ['text:2', 'wuxing', '五行'],
                ['text:2', 'xingqing', '星情'],
            ])
            ->addColumns([
                ['id', 'ID'],
                ['ziwei_name', '宫名'],
                ['ziwei_title', '宫位星'],
                ['wuxing', '五行', 'text'],
                ['xingqing', '星情', 'text'],
                ['zonghe', '综合', 'popover',30],
                ['ming_g', '命宫', 'popover',30],
                ['update_time', '更新时间', 'datetime'],
                ['right_button', '操作', 'btn'],
            ])
            ->setRowList($data_list)
            ->addTopButtons($topBtn)   // 添加授权按钮
            ->addRightButtons($rightBtn)
            ->setExtraCss($this->css)
            ->setColumnWidth([
                'id'            => 15,
                'ziwei_name'    => 20,
                'ziwei_title'   => 25,
                'wuxing'        => 20,
                'xingqing'      => 35,
                'update_time'   => 40,
                'right_button'  => 20,
            ])
            ->fetch();
    }

    //新增
    public function sm_ziwei_add()
    { 
        if ($this->request->isPost()) {
            $data = input('post.');
            $data['update_time'] = time();
            if (!$data['ziwei_name']) {
                $this->error('宫名不能为空');
            }
            if (!$data['ziwei_title']) {
                $this->error('宫位星不能为空');
            }
            if (!$data['wuxing']) {
                $this->error('五行不能为空');
            }
            if (!$data['xingqing']) {
                $this->error('星情不能为空');
            }
            if (!$data['xiangyi']) {
                $this->error('相义不能为空');
            }
            if (!$data['desc']) {
                $this->error('描述不能为空');
            }
            if (!$data['zonghe']) {
                $this->error('描述不能为空');
            }
            $is_insert = Db::name('Sm_ziwei')->insert($data);
            if ($is_insert) {
                $this->success('新增成功', 'sm_ziwei');
            } else {
                $this->error('新增失败');
            }
        }
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'ziwei_name', '宫名'],
                ['text', 'ziwei_title', '宫位星'],
                ['text', 'wuxing', '五行'],
                ['text', 'xingqing', '星情'],
                ['ckeditor', 'xiangyi', '相义'],
                ['ckeditor', 'desc', '描述'],
                ['ckeditor', 'zonghe', '综合'],
                ['ckeditor', 'ming_g', '命宫'],
                ['ckeditor', 'xiongdi_g', '兄弟宫(奴仆宫)'],
                ['ckeditor', 'fuqi_g', '夫妻宫'],
                ['ckeditor', 'zinv_g', '子女宫'],
                ['ckeditor', 'caibo_g', '财帛宫'],
                ['ckeditor', 'jibing_g', '疾病宫(疾厄宫)'],
                ['ckeditor', 'qianyi_g', '迁移宫'],
                ['ckeditor', 'jiaoyou_g', '交友宫'],
                ['ckeditor', 'guanlu_g', '官禄宫'],
                ['ckeditor', 'tianzhai_g', '田宅宫'],
                ['ckeditor', 'defu_g', '福德宫'],
                ['ckeditor', 'fuwu_g', '父母宫'],
            ])
            ->layout(['ziwei_name' => 2, 'ziwei_title' => 3, 'wuxing'=>2,'xingqing' => 4, 'xiangyi' => 6, 'desc' => 6, 
                'zonghe' => 7, 'ming_g' => 7, 'xiongdi_g' => 7, 'fuqi_g' => 7, 'zinv_g' => 7, 'caibo_g' => 7, 'jibing_g' => 7, 'qianyi_g' => 7, 
                'jiaoyou_g' => 7,'guanlu_g' => 7, 'tianzhai_g' => 7, 'defu_g' => 7, 'fuwu_g' => 7])
            ->fetch();
    }

    //编辑
    public function sm_ziwei_edit($id=0)
    { 
        if ($this->request->isPost()) {
            $data = input('post.');
            $data['update_time'] = time();
            if (!$data['ziwei_name']) {
                $this->error('宫名不能为空');
            }
            if (!$data['ziwei_title']) {
                $this->error('宫位星不能为空');
            }
            if (!$data['wuxing']) {
                $this->error('五行不能为空');
            }
            if (!$data['xingqing']) {
                $this->error('星情不能为空');
            }
            if (!$data['xiangyi']) {
                $this->error('相义不能为空');
            }
            if (!$data['desc']) {
                $this->error('描述不能为空');
            }
            if (!$data['zonghe']) {
                $this->error('描述不能为空');
            }
            $is_update = Db::name('Sm_ziwei')->where('id', $data['id'])->update($data);
            if ($is_update) {
                $this->success('更新成功', 'sm_ziwei');
            } else {
                $this->error('更新失败');
            }
        }
        $id   = input('id');
        $data = Db::name('Sm_ziwei')->where('id', '=', $id)->find();
        if (empty($data)) {
            $this->error('未查到当前数据', 'sm_ziwei');
        }
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'ziwei_name', '宫名'],
                ['text', 'ziwei_title', '宫位星'],
                ['text', 'wuxing', '五行'],
                ['text', 'xingqing', '星情'],
                ['ckeditor', 'xiangyi', '相义'],
                ['ckeditor', 'desc', '描述'],
                ['ckeditor', 'zonghe', '综合'],
                ['ckeditor', 'ming_g', '命宫'],
                ['ckeditor', 'xiongdi_g', '兄弟宫(奴仆宫)'],
                ['ckeditor', 'fuqi_g', '夫妻宫'],
                ['ckeditor', 'zinv_g', '子女宫'],
                ['ckeditor', 'caibo_g', '财帛宫'],
                ['ckeditor', 'jibing_g', '疾病宫(疾厄宫)'],
                ['ckeditor', 'qianyi_g', '迁移宫'],
                ['ckeditor', 'jiaoyou_g', '交友宫'],
                ['ckeditor', 'guanlu_g', '官禄宫'],
                ['ckeditor', 'tianzhai_g', '田宅宫'],
                ['ckeditor', 'defu_g', '福德宫'],
                ['hidden', 'id', $id],
            ])
            ->setFormData($data)
            ->layout(['ziwei_name' => 2, 'ziwei_title' => 3, 'wuxing'=>2,'xingqing' => 4, 'xiangyi' => 6, 'desc' => 6, 
                'zonghe' => 7, 'ming_g' => 7, 'xiongdi_g' => 7, 'fuqi_g' => 7, 'zinv_g' => 7, 'caibo_g' => 7, 'jibing_g' => 7, 'qianyi_g' => 7, 
                'jiaoyou_g' => 7,'guanlu_g' => 7, 'tianzhai_g' => 7, 'defu_g' => 7, 'fuwu_g' => 7])
            ->fetch();
    }

    //删除
    public function sm_ziwei_del($id=0) 
    { 
        $id     = input('id');
        $data   = Db::name('Sm_ziwei')->where('id', '=', $id)->find();
        if (empty($data)) {
            $this->error('未查到当前数据', 'sm_ziwei');
        }
        $res = Db::name('Sm_ziwei')->where('id', '=', $data['id'])->delete();
        if ($res) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }


    
    //笔画列表
    public function sm_bihua($group = 'sm_bihua')
    {
        $list_tab = [
            'sm_bihua'  => ['title' => '汉字笔画-A', 'url' => url('sm_bihua', ['group' => 'sm_bihua'])],
            'sm_bihuaj' => ['title' => '汉字笔画-B', 'url' => url('sm_bihuaj', ['group' => 'sm_bihuaj'])],
            'sm_hzwh'   => ['title' => '汉字五行', 'url' => url('sm_hzwh', ['group' => 'sm_hzwh'])],
            'sm_jiazi'  => ['title' => '甲子纳音', 'url' => url('sm_jiazi', ['group' => 'sm_jiazi'])],

        ];
        $order     = $this->getOrder('id asc');
        $map       = $this->getMap();
        $data_list = Db::name('Sm_bihua')
            ->where($map)
            ->order($order)
            ->paginate('20');
        return ZBuilder::make('table')
            ->setTabNav($list_tab,  $group)
            ->hideCheckbox()
            ->setSearchArea([
                ['text:1', 'id', 'ID'],
                ['text:2', 'num', '笔画'],
                ['text:2', 'hanzi', '汉字', 'like'],
            ])
            ->addColumns([
                ['id', 'ID'],
                ['num', '笔画', 'text'],
                ['hanzi', '汉字', 'text'],
                ['right_button', '操作', 'btn'],
            ])
            ->setRowList($data_list)
            ->setExtraCss($this->css)
            ->setColumnWidth([
                'id'  => 15,
                'num' => 15,
                'right_button'  => 20,
            ])
            ->addRightButton('edit', ['class' => 'btn btn-sm btn-warning', 'href' => url('sm_bihua_edit', ['id' => '__id__'])])
            ->fetch();
    }

    public function sm_bihua_edit()
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            $num  = isset($data['num']) && !$data['num'] ? 0 : (int)$data['num'];
            if ($num <= 0 || $num == '') {
                $this->error('笔画不能小于0');
            }
            $hanzi = isset($data['hanzi']) && !$data['hanzi'] ? '' : $data['hanzi'];
            if (!$hanzi) {
                $this->error('汉字不能为空');
            }
            $is_update = Db::name('Sm_bihua')->where('id', $data['id'])->update($data);
            if ($is_update) {
                $this->success('编辑成功', 'sm_bihua');
            } else {
                $this->error('编辑失败');
            }
        }
        $id = input('id');
        $data = Db::name('Sm_bihua')->where('id', '=', $id)->find();
        if (empty($data)) {
            $this->error('未查到当前数据', 'sm_bihua');
        }
        return ZBuilder::make('form')
            ->addFormItems([
                ['number', 'num', '笔画'],
                ['textarea', 'hanzi', '汉字'],
                ['hidden', 'id', $id],
            ])
            ->setFormData($data)
            ->addHidden('id')
            //->layout(['num' => 2, 'jx' => 3, 'as' => 5, 'yy' => 5, 'content' => 6])
            ->fetch();
    }
    // //笔画列表
    public function sm_bihuaj($group = 'sm_bihuaj')
    {
        $list_tab = [
            'sm_bihua'  => ['title' => '汉字笔画-A', 'url' => url('sm_bihua', ['group' => 'sm_bihua'])],
            'sm_bihuaj' => ['title' => '汉字笔画-B', 'url' => url('sm_bihuaj', ['group' => 'sm_bihuaj'])],
            'sm_hzwh'   => ['title' => '汉字五行', 'url' => url('sm_hzwh', ['group' => 'sm_hzwh'])],
            'sm_jiazi'  => ['title' => '甲子纳音', 'url' => url('sm_jiazi', ['group' => 'sm_jiazi'])],
        ];
        $order     = $this->getOrder('id asc');
        $map       = $this->getMap();
        $data_list = Db::name('Sm_bihuaj')
            ->where($map)
            ->order($order)
            ->paginate('20');
        $css = <<<EOF
            <style>
                th,tr{
                    text-align: center;
                }
            </style>
EOF;
        return ZBuilder::make('table')
            ->setTabNav($list_tab,  $group)
            ->hideCheckbox()
            ->setSearchArea([
                ['text:1', 'id', 'ID'],
                ['text:2', 'numj', '笔画'],
                ['text:2', 'hanzij', '汉字', 'like'],

            ])
            ->addColumns([
                ['id', 'ID'],
                ['numj', '笔画', 'text'],
                ['hanzij', '汉字', 'text'],
                ['right_button', '操作', 'btn'],
            ])
            ->setRowList($data_list)
            ->setExtraCss($css)
            ->setColumnWidth([
                'id'            => 15,
                'numj'          => 15,
                'right_button'  => 20,
            ])
            ->addRightButton('edit', ['class' => 'btn btn-sm btn-warning', 'href' => url('sm_bihuaj_edit', ['id' => '__id__'])])
            ->fetch();
    }

    public function sm_bihuaj_edit()
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            $num  = isset($data['numj']) && !$data['numj'] ? 0 : (int)$data['numj'];
            if ($num <= 0 || $num == '') {
                $this->error('笔画不能小于0');
            }
            $hanzi = isset($data['hanzij']) && !$data['hanzij'] ? '' : $data['hanzij'];
            if (!$hanzi) {
                $this->error('汉字不能为空');
            }
            $is_update = Db::name('Sm_bihuaj')->where('id', $data['id'])->update($data);
            if ($is_update) {
                $this->success('编辑成功', 'sm_bihuaj');
            } else {
                $this->error('编辑失败');
            }
        }
        $id = input('id');
        $data = Db::name('Sm_bihuaj')->where('id', '=', $id)->find();
        if (empty($data)) {
            $this->error('未查到当前数据', 'sm_bihuaj');
        }
        return ZBuilder::make('form')
            ->addFormItems([
                ['number', 'numj', '笔画'],
                ['textarea', 'hanzij', '汉字'],
                ['hidden', 'id', $id],
            ])
            ->setFormData($data)
            ->addHidden('id')
            //->layout(['num' => 2, 'jx' => 3, 'as' => 5, 'yy' => 5, 'content' => 6])
            ->fetch();
    }

    //汉字五行
    public function sm_hzwh($group = 'sm_hzwh')
    {
        $list_tab = [
            'sm_bihua'  => ['title' => '汉字笔画-A', 'url' => url('sm_bihua', ['group' => 'sm_bihua'])],
            'sm_bihuaj' => ['title' => '汉字笔画-B', 'url' => url('sm_bihuaj', ['group' => 'sm_bihuaj'])],
            'sm_hzwh'   => ['title' => '汉字五行', 'url' => url('sm_hzwh', ['group' => 'sm_hzwh'])],
            'sm_jiazi'  => ['title' => '甲子纳音', 'url' => url('sm_jiazi', ['group' => 'sm_jiazi'])],
        ];
        $order     = $this->getOrder('id asc');
        $map       = $this->getMap();
        $data_list = Db::name('sm_hzwh')
            ->where($map)
            ->order($order)
            ->paginate('20');
        $css = <<<EOF
            <style>
                th,tr{
                    text-align: center;
                }
            </style>
EOF;
        return ZBuilder::make('table')
            ->setTabNav($list_tab,  $group)
            ->hideCheckbox()
            ->setSearchArea([
                ['text:1', 'id', 'ID'],
                ['text:2', 'wh', '五行'],
                ['text:2', 'hz', '汉字', 'like'],

            ])
            ->addColumns([
                ['id', 'ID'],
                ['wh', '五行', 'text'],
                ['hz', '汉字', 'text'],
                ['update_time', '更新时间', 'datetime'],
                ['right_button', '操作', 'btn'],
            ])
            ->setRowList($data_list)
            ->setExtraCss($css)
            ->setColumnWidth([
                'id'            => 15,
                'wh'            => 15,
                'update_time'   => 30,
                'right_button'  => 20,
            ])
            ->addRightButton('edit', ['class' => 'btn btn-sm btn-warning', 'href' => url('sm_hzwh_edit', ['id' => '__id__'])])
            ->fetch();
    }

    public function sm_hzwh_edit($id = 0)
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            $data['update_time'] = time();
            if (!$data['hz']) {
                $this->error('汉字不能为空');
            }
            $is_update = Db::name('sm_hzwh')->where('id', $data['id'])->update($data);
            if ($is_update) {
                $this->success('编辑成功', 'sm_hzwh');
            } else {
                $this->error('编辑失败');
            }
        }
        $id = input('id');
        $data = Db::name('sm_hzwh')->where('id', '=', $id)->find();
        if (empty($data)) {
            $this->error('未查到当前数据', 'sm_hzwh');
        }
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'wh', '五行', '', '', '', 'disabled'],
                ['textarea', 'hz', '汉字'],
                ['hidden', 'id', $id],
            ])
            ->setFormData($data)
            ->addHidden('id')
            ->layout(['wh' => 2])
            ->fetch();
    }

    //甲子纳音
    public function sm_jiazi($group = 'sm_jiazi')
    {
        $list_tab = [
            'sm_bihua'  => ['title' => '汉字笔画-A', 'url' => url('sm_bihua', ['group' => 'sm_bihua'])],
            'sm_bihuaj' => ['title' => '汉字笔画-B', 'url' => url('sm_bihuaj', ['group' => 'sm_bihuaj'])],
            'sm_hzwh'   => ['title' => '汉字五行', 'url' => url('sm_hzwh', ['group' => 'sm_hzwh'])],
            'sm_jiazi'  => ['title' => '甲子纳音', 'url' => url('sm_jiazi', ['group' => 'sm_jiazi'])],
        ];

        $order     = $this->getOrder('id asc');
        $map       = $this->getMap();
        $data_list = Db::name('sm_jiazi')
            ->where($map)
            ->order($order)
            ->paginate(60);
        $css = <<<EOF
            <style>
                th,tr{
                    text-align: center;
                }
            </style>
EOF;
        return ZBuilder::make('table')
            ->setTabNav($list_tab,  $group)
            ->hideCheckbox()
            ->setSearchArea([
                ['text:1', 'id', 'ID'],
                ['text:2', 'jiazi', '甲子(年)'],
                ['text:2', 'layin', '纳音'],

            ])
            ->addColumns([
                ['id', 'ID'],
                ['jiazi', '甲子(年)', 'text'],
                ['layin', '纳音', 'text.edit'],
                //['update_time', '更新时间', 'datetime'],
                //['right_button', '操作', 'btn'],
            ])
            ->setRowList($data_list)
            ->setExtraCss($css)
            // ->setColumnWidth([
            //     'id'            => 15,
            //     'jiazi'         => 20,
            //     'layin'         => 30,
            //     'right_button'  => 20,
            // ])
            ->addRightButton('edit', ['class' => 'btn btn-sm btn-warning', 'href' => url('sm_jiazi_edit', ['id' => '__id__'])])
            ->fetch();
    }

    //穷通宝鉴
    public function sm_qtbj($group = 'sm_qtbj')
    {
        $list_tab = [
            'sm_qtbj'  => ['title' => '穷通宝鉴数据', 'url' => url('sm_qtbj', ['group' => 'sm_qtbj'])],
            'sm_smtf'  => ['title' => '三命通会数据', 'url' => url('sm_smtf', ['group' => 'sm_smtf'])],
            'sm_rgnm'  => ['title' => '日干心性数据', 'url' => url('sm_rgnm', ['group' => 'sm_rgnm'])],
            'sm_sjrs'  => ['title' => '四季用神数据', 'url' => url('sm_sjrs', ['group' => 'sm_sjrs'])],
            'sm_ssjs'  => ['title' => '十神数据', 'url' => url('sm_ssjs', ['group' => 'sm_ssjs'])],
            'sm_ssjs2'  => ['title' => '十神数据2', 'url' => url('sm_ssjs2', ['group' => 'sm_ssjs2'])],
            'sm_ssyq'  => ['title' => '十神运势数据', 'url' => url('sm_ssyq', ['group' => 'sm_ssyq'])],
        ];
        $order     = $this->getOrder('id asc');
        $map       = $this->getMap();
        $data_list = Db::name('Sm_qtbj')
            ->where($map)
            ->order($order)
            ->paginate(10);
        $topBtn    = [
            //'add'       => ['class' => 'btn btn-warning','href' => url('sm_81_add')],
        ];
        $rightBtn = [
            'edit'      => ['class' => 'btn btn-sm btn-warning', 'href' => url('sm_qtbj_edit', ['id' => '__id__'])],
            //'delete'    => ['class' => 'btn btn-sm btn-danger ajax-get confirm','href' => url('sm_81_del', ['id'=>'__id__'])],
        ];
        return ZBuilder::make('table')
            ->setTabNav($list_tab, $group)
            ->setPageTitle('穷通宝鉴数据')
            ->hideCheckbox()
            ->setSearchArea([
                ['text:1', 'id', 'ID'],
                ['text:2', 'tg', '天干'],
                ['text:2', 'dz', '地支'],
                ['text:4', 'content', '测算内容', 'like']
            ])
            ->addColumns([
                ['id', 'ID'],
                ['tg', '天干'],
                ['dz', '地支', 'text'],
                ['content', '测算内容', 'text.edit'],
                ['update_time', '更新时间', 'datetime'],
                ['right_button', '操作', 'btn'],
            ])
            ->setRowList($data_list)
            ->addTopButtons($topBtn)   // 添加授权按钮
            ->addRightButtons($rightBtn)
            ->setExtraCss($this->css)
            ->setColumnWidth([
                'id'            => 15,
                'tg'            => 15,
                'dz'            => 15,
                'update_time'   => 28,
                'right_button'  => 20,
            ])
            ->fetch();
    }

    public function sm_qtbj_edit()
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            $data['update_time'] = time();
            if (!$data['content']) {
                $this->error('测算内容不能为空');
            }
            $is_update = Db::name('Sm_qtbj')->where('id', $data['id'])->update($data);
            if ($is_update) {
                $this->success('编辑成功', 'sm_qtbj');
            } else {
                $this->error('编辑失败');
            }
        }
        $id   = input('id');
        $data = Db::name('Sm_qtbj')->where('id', '=', $id)->find();
        if (empty($data)) {
            $this->error('未查到当前数据', 'sm_qtbj');
        }
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'tg', '天干', '', '', '', 'disabled'],
                ['text', 'dz', '地支', '', '', '', 'disabled'],
                ['textarea', 'content', '测算内容'],
                ['hidden', 'id', $id],
            ])
            ->setFormData($data)
            ->addHidden('id')
            ->layout(['tg' => 3, 'dz' => 3, 'content' => 7])
            ->fetch();
    }


    //三命通会
    public function sm_smtf($group = 'sm_smtf')
    {
        $list_tab = [
            'sm_qtbj'  => ['title' => '穷通宝鉴数据', 'url' => url('sm_qtbj', ['group' => 'sm_qtbj'])],
            'sm_smtf'  => ['title' => '三命通会数据', 'url' => url('sm_smtf', ['group' => 'sm_smtf'])],
            'sm_rgnm'  => ['title' => '日干心性数据', 'url' => url('sm_rgnm', ['group' => 'sm_rgnm'])],
            'sm_sjrs'  => ['title' => '四季用神数据', 'url' => url('sm_sjrs', ['group' => 'sm_sjrs'])],
            'sm_ssjs'  => ['title' => '十神数据', 'url' => url('sm_ssjs', ['group' => 'sm_ssjs'])],
            'sm_ssjs2'  => ['title' => '十神数据2', 'url' => url('sm_ssjs2', ['group' => 'sm_ssjs2'])],
            'sm_ssyq'  => ['title' => '十神运势数据', 'url' => url('sm_ssyq', ['group' => 'sm_ssyq'])],
        ];
        $order     = $this->getOrder('id asc');
        $map       = $this->getMap();
        $data_list = Db::name('sm_smtf')
            ->where($map)
            ->order($order)
            ->paginate(12);
        $topBtn    = [
            //'add'       => ['class' => 'btn btn-warning','href' => url('sm_81_add')],
        ];
        $rightBtn = [
            'edit'      => ['class' => 'btn btn-sm btn-warning', 'href' => url('sm_smtf_edit', ['id' => '__id__'])],
            //'delete'    => ['class' => 'btn btn-sm btn-danger ajax-get confirm','href' => url('sm_81_del', ['id'=>'__id__'])],
        ];
        return ZBuilder::make('table')
            ->setTabNav($list_tab, $group)
            ->setPageTitle('三命通会数据')
            ->hideCheckbox()
            ->setSearchArea([
                ['text:1', 'id', 'ID'],
                ['text:2', 'ri', '日干支'],
                ['text:2', 'shi', '时干支'],
                ['text:4', 'tf1', '谶语1', 'like'],
                ['text:4', 'tf2', '谶语2', 'like'],
            ])
            ->addColumns([
                ['id', 'ID'],
                ['ri', '日干支'],
                ['shi', '时干支'],
                ['tf1', '谶语1', 'text.edit'],
                ['tf2', '谶语2', 'text.edit'],
                ['update_time', '更新时间', 'datetime'],
                ['right_button', '操作', 'btn'],
            ])
            ->setRowList($data_list)
            ->addTopButtons($topBtn)   // 添加授权按钮
            ->addRightButtons($rightBtn)
            ->setExtraCss($this->css)
            ->setColumnWidth([
                'id'            => 15,
                'ri'            => 15,
                'shi'           => 15,
                'update_time'   => 28,
                'right_button'  => 20,
            ])
            ->fetch();
    }

    public function sm_smtf_edit()
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            $data['update_time'] = time();
            if (!$data['tf1']) {
                $this->error('谶语1不能为空');
            }
            if (!$data['tf2']) {
                $this->error('谶语2不能为空');
            }
            $is_update = Db::name('Sm_smtf')->where('id', $data['id'])->update($data);
            if ($is_update) {
                $this->success('编辑成功', 'sm_smtf');
            } else {
                $this->error('编辑失败');
            }
        }
        $id   = input('id');
        $data = Db::name('Sm_smtf')->where('id', '=', $id)->find();
        if (empty($data)) {
            $this->error('未查到当前数据', 'sm_smtf');
        }
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'ri', '日干支', '', '', '', 'disabled'],
                ['text', 'shi', '时干支', '', '', '', 'disabled'],
                ['textarea', 'tf1', '谶语1'],
                ['textarea', 'tf2', '谶语2'],
                ['hidden', 'id', $id],
            ])
            ->setFormData($data)
            ->addHidden('id')
            ->layout(['ri' => 3, 'shi' => 3, 'tf1' => 7, 'tf2' => 7])
            ->fetch();
    }
    //日干心性
    public function sm_rgnm($group = 'sm_rgnm')
    {
        $list_tab = [
            'sm_qtbj'  => ['title' => '穷通宝鉴数据', 'url' => url('sm_qtbj', ['group' => 'sm_qtbj'])],
            'sm_smtf'  => ['title' => '三命通会数据', 'url' => url('sm_smtf', ['group' => 'sm_smtf'])],
            'sm_rgnm'  => ['title' => '日干心性数据', 'url' => url('sm_rgnm', ['group' => 'sm_rgnm'])],
            'sm_sjrs'  => ['title' => '四季用神数据', 'url' => url('sm_sjrs', ['group' => 'sm_sjrs'])],
            'sm_ssjs'  => ['title' => '十神数据', 'url' => url('sm_ssjs', ['group' => 'sm_ssjs'])],
            'sm_ssjs2'  => ['title' => '十神数据2', 'url' => url('sm_ssjs2', ['group' => 'sm_ssjs2'])],
            'sm_ssyq'  => ['title' => '十神运势数据', 'url' => url('sm_ssyq', ['group' => 'sm_ssyq'])],
        ];
        $order    = $this->getOrder('id asc');
        $map       = $this->getMap();
        $data_list = Db::name('sm_rgnm')
            ->where($map)
            ->order($order)
            ->paginate(60);
        $topBtn    = [
            //'add'       => ['class' => 'btn btn-warning','href' => url('sm_81_add')],
        ];
        $rightBtn = [
            'edit'      => ['class' => 'btn btn-sm btn-warning', 'href' => url('sm_rgnm_edit', ['id' => '__id__'])],
            //'delete'    => ['class' => 'btn btn-sm btn-danger ajax-get confirm','href' => url('sm_81_del', ['id'=>'__id__'])],
        ];
        return ZBuilder::make('table')
            ->setTabNav($list_tab, $group)
            ->setPageTitle('日干心性数据')
            ->hideCheckbox()
            ->setSearchArea([
                ['text:1', 'id', 'ID'],
                ['text:2', 'rgz', '日干支'],
            ])
            ->addColumns([
                ['id', 'ID'],
                ['rgz', '日干支'],
                ['rgcz', '日干支说明', 'text.edit'],
                ['rgzfx', '日干支分析', 'text.edit'],
                //['xgfx', '性格分析', 'text.edit'],
                //['aqfx', '爱情分析', 'text.edit'],
                //['syfx', '事业分析', 'text.edit'],
                //['cyfx', '财运分析', 'text.edit'],
                //['jkfx', '健康分析', 'text.edit'],
                ['update_time', '更新时间', 'datetime'],
                ['right_button', '操作', 'btn'],
            ])
            ->setRowList($data_list)
            ->addTopButtons($topBtn)   // 添加授权按钮
            ->addRightButtons($rightBtn)
            ->setExtraCss($this->css)
            ->setColumnWidth([
                'id'            => 15,
                'rgz'           => 20,
                'update_time'   => 28,
                'right_button'  => 20,
            ])
            ->fetch();
    }


    public function sm_rgnm_edit()
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            $data['update_time'] = time();
            if (!$data['rgcz']) {
                $this->error('日干支说明不能为空');
            }
            if (!$data['rgzfx']) {
                $this->error('日干支分析不能为空');
            }
            if (!$data['xgfx']) {
                $this->error('性格分析不能为空');
            }
            if (!$data['aqfx']) {
                $this->error('爱情分析不能为空');
            }
            if (!$data['syfx']) {
                $this->error('事业分析不能为空');
            }
            if (!$data['cyfx']) {
                $this->error('财运分析不能为空');
            }
            if (!$data['jkfx']) {
                $this->error('健康分析不能为空');
            }
            $is_update = Db::name('Sm_rgnm')->where('id', $data['id'])->update($data);
            if ($is_update) {
                $this->success('编辑成功', 'sm_rgnm');
            } else {
                $this->error('编辑失败');
            }
        }
        $id   = input('id');
        $data = Db::name('Sm_rgnm')->where('id', '=', $id)->find();
        if (empty($data)) {
            $this->error('未查到当前数据', 'sm_rgnm');
        }
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'rgz', '日干支', '', '', '', 'disabled'],
                ['ckeditor', 'rgcz', '日干支说明'],
                ['ckeditor', 'rgzfx', '日干支分析'],
                ['ckeditor', 'xgfx', '性格分析'],
                ['ckeditor', 'aqfx', '爱情分析'],
                ['ckeditor', 'syfx', '事业分析'],
                ['ckeditor', 'cyfx', '财运分析'],
                ['ckeditor', 'jkfx', '健康分析'],
                ['hidden', 'id', $id],
            ])
            ->setFormData($data)
            ->addHidden('id')
            ->layout(['rgz' => 7, 'rgcz' => 6, 'rgzfx' => 6, 'xgfx' => 6, 'aqfx' => 6, 'syfx' => 6, 'cyfx' => 6, 'jkfx' => 6])
            ->fetch();
    }

    //四季用神
    public function sm_sjrs($group = 'sm_sjrs')
    {
        $list_tab = [
            'sm_qtbj'  => ['title' => '穷通宝鉴数据', 'url' => url('sm_qtbj', ['group' => 'sm_qtbj'])],
            'sm_smtf'  => ['title' => '三命通会数据', 'url' => url('sm_smtf', ['group' => 'sm_smtf'])],
            'sm_rgnm'  => ['title' => '日干心性数据', 'url' => url('sm_rgnm', ['group' => 'sm_rgnm'])],
            'sm_sjrs'  => ['title' => '四季用神数据', 'url' => url('sm_sjrs', ['group' => 'sm_sjrs'])],
            'sm_ssjs'  => ['title' => '十神数据', 'url' => url('sm_ssjs', ['group' => 'sm_ssjs'])],
            'sm_ssjs2'  => ['title' => '十神数据2', 'url' => url('sm_ssjs2', ['group' => 'sm_ssjs2'])],
            'sm_ssyq'  => ['title' => '十神运势数据', 'url' => url('sm_ssyq', ['group' => 'sm_ssyq'])],
        ];
        $order     = $this->getOrder('id asc');
        $map       = $this->getMap();
        $data_list = Db::name('sm_sjrs')
            ->where($map)
            ->order($order)
            ->paginate(60);
        $topBtn    = [
            //'add'       => ['class' => 'btn btn-warning','href' => url('sm_81_add')],
        ];
        $rightBtn = [
            'edit'      => ['class' => 'btn btn-sm btn-warning', 'href' => url('sm_sjrs_edit', ['id' => '__id__'])],
            //'delete'    => ['class' => 'btn btn-sm btn-danger ajax-get confirm','href' => url('sm_81_del', ['id'=>'__id__'])],
        ];
        return ZBuilder::make('table')
            ->setTabNav($list_tab, $group)
            ->setPageTitle('四季用神数据')
            ->hideCheckbox()
            ->setSearchArea([
                ['text:1', 'id', 'ID'],
                ['text:2', 'wh', '五行'],
                ['text:2', 'sj', '四季'],
            ])
            ->addColumns([
                ['id', 'ID'],
                ['wh', '五行'],
                ['sj', '四季', 'text'],
                ['sjrs', '四季用神', 'text.edit'],
                ['update_time', '更新时间', 'datetime'],
                ['right_button', '操作', 'btn'],
            ])
            ->setRowList($data_list)
            ->addTopButtons($topBtn)   // 添加授权按钮
            ->addRightButtons($rightBtn)
            //->addRightButton('edit',['class' => 'btn btn-sm btn-warning','href' => url('sm_81_edit', ['id'=>'__id__'])])
            //->addRightButton('delete',['class' => 'btn btn-sm btn-danger ajax-get confirm','href' => url('sm_81_del', ['id'=>'__id__'])])
            ->setExtraCss($this->css)
            ->setColumnWidth([
                'id'            => 15,
                'wh'            => 20,
                'sj'            => 20,
                'update_time'   => 28,
                'right_button'  => 20,
            ])
            ->fetch();
    }

    public function sm_sjrs_edit()
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            $data['update_time'] = time();
            if (!$data['sjrs']) {
                $this->error('四季用神不能为空');
            }
            $is_update = Db::name('Sm_sjrs')->where('id', $data['id'])->update($data);
            if ($is_update) {
                $this->success('编辑成功', 'sm_sjrs');
            } else {
                $this->error('编辑失败');
            }
        }
        $id   = input('id');
        $data = Db::name('Sm_sjrs')->where('id', '=', $id)->find();
        if (empty($data)) {
            $this->error('未查到当前数据', 'sm_sjrs');
        }
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'wh', '五行', '', '', '', 'disabled'],
                ['text', 'sj', '四季', '', '', '', 'disabled'],
                ['textarea', 'sjrs', '四季用神'],
                ['hidden', 'id', $id],
            ])
            ->setFormData($data)
            ->addHidden('id')
            ->layout(['wh' => 5, 'sj' => 6, 'sjrs' => 7])
            ->fetch();
    }

    //十神数据
    public function sm_ssjs($group = 'sm_ssjs')
    {
        $list_tab = [
            'sm_qtbj'  => ['title' => '穷通宝鉴数据', 'url' => url('sm_qtbj', ['group' => 'sm_qtbj'])],
            'sm_smtf'  => ['title' => '三命通会数据', 'url' => url('sm_smtf', ['group' => 'sm_smtf'])],
            'sm_rgnm'  => ['title' => '日干心性数据', 'url' => url('sm_rgnm', ['group' => 'sm_rgnm'])],
            'sm_sjrs'  => ['title' => '四季用神数据', 'url' => url('sm_sjrs', ['group' => 'sm_sjrs'])],
            'sm_ssjs'  => ['title' => '十神数据', 'url' => url('sm_ssjs', ['group' => 'sm_ssjs'])],
            'sm_ssjs2'  => ['title' => '十神数据2', 'url' => url('sm_ssjs2', ['group' => 'sm_ssjs2'])],
            'sm_ssyq'  => ['title' => '十神运势数据', 'url' => url('sm_ssyq', ['group' => 'sm_ssyq'])],
        ];
        $order     = $this->getOrder('id asc');
        $map       = $this->getMap();
        $data_list = Db::name('sm_ssjs')
            ->where($map)
            ->order($order)
            ->paginate(60);
        $topBtn    = [
            //'add'       => ['class' => 'btn btn-warning','href' => url('sm_81_add')],
        ];
        $rightBtn = [
            'edit'      => ['class' => 'btn btn-sm btn-warning', 'href' => url('sm_ssjs_edit', ['id' => '__id__'])],
        ];
        return ZBuilder::make('table')
            ->setTabNav($list_tab, $group)
            ->setPageTitle('十神数据')
            ->hideCheckbox()
            ->setSearchArea([
                ['text:1', 'id', 'ID'],
                ['text:4', 'ssjs_title', '五行'],
            ])
            ->addColumns([
                ['id', 'ID'],
                ['ssjs_title', '十神'],
                ['ssjs', '描述', 'text'],
                ['create_time', '创建时间', 'datetime'],
                ['update_time', '更新时间', 'datetime'],
                ['right_button', '操作', 'btn'],
            ])
            ->setRowList($data_list)
            ->addTopButtons($topBtn)   // 添加授权按钮
            ->addRightButtons($rightBtn)
            ->setExtraCss($this->css)
            ->setColumnWidth([
                'id'            => 15,
                'ssjs_title'    => 25,
                'create_time'   => 28,
                'update_time'   => 28,
                'right_button'  => 20,
            ])
            ->fetch();
    }

    public function sm_ssjs_edit()
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            $data['update_time'] = time();
            if (!$data['ssjs']) {
                $this->error('十神描述不能为空');
            }
            $is_update = Db::name('Sm_ssjs')->where('id', $data['id'])->update($data);
            if ($is_update) {
                $this->success('编辑成功', 'sm_ssjs');
            } else {
                $this->error('编辑失败');
            }
        }
        $id   = input('id');
        $data = Db::name('Sm_ssjs')->where('id', '=', $id)->find();
        if (empty($data)) {
            $this->error('未查到当前数据', 'sm_ssjs');
        }
        return ZBuilder::make('form')
            ->addFormItems([
                ['ckeditor', 'ssjs', '十神'],
                ['hidden', 'id', $id],
            ])
            ->setFormData($data)
            ->addHidden('id')
            ->layout(['ssjs' => 7])
            ->fetch();
    }

     //十神数据
    public function sm_ssjs2($group = 'sm_ssjs2')
    {
        $list_tab = [
            'sm_qtbj'  => ['title' => '穷通宝鉴数据', 'url' => url('sm_qtbj', ['group' => 'sm_qtbj'])],
            'sm_smtf'  => ['title' => '三命通会数据', 'url' => url('sm_smtf', ['group' => 'sm_smtf'])],
            'sm_rgnm'  => ['title' => '日干心性数据', 'url' => url('sm_rgnm', ['group' => 'sm_rgnm'])],
            'sm_sjrs'  => ['title' => '四季用神数据', 'url' => url('sm_sjrs', ['group' => 'sm_sjrs'])],
            'sm_ssjs'  => ['title' => '十神数据', 'url' => url('sm_ssjs', ['group' => 'sm_ssjs'])],
            'sm_ssjs2'  => ['title' => '十神数据2', 'url' => url('sm_ssjs2', ['group' => 'sm_ssjs2'])],
            'sm_ssyq'  => ['title' => '十神运势数据', 'url' => url('sm_ssyq', ['group' => 'sm_ssyq'])],
        ];
        $order     = $this->getOrder('id asc');
        $map       = $this->getMap();
        $data_list = Db::name('sm_ssjs2')
            ->where($map)
            ->order($order)
            ->paginate(60);
        $topBtn    = [
            //'add'       => ['class' => 'btn btn-warning','href' => url('sm_81_add')],
        ];
        $rightBtn = [
            'edit'      => ['class' => 'btn btn-sm btn-warning', 'href' => url('sm_ssjs_edit2', ['id' => '__id__'])],
        ];
        return ZBuilder::make('table')
            ->setTabNav($list_tab, $group)
            ->setPageTitle('十神数据2') 
            ->hideCheckbox()
            ->setSearchArea([
                ['text:1', 'id', 'ID'],
                ['text:4', 'shishen', '十神'],
            ])
            ->addColumns([
                ['id', 'ID'],
                ['shishen', '十神'],
                ['sanzhu', '三柱'],
                ['content', '解释', 'text'],
                ['update_time', '更新时间', 'datetime'],
                ['right_button', '操作', 'btn'],
            ])
            ->setRowList($data_list)
            ->addTopButtons($topBtn)   // 添加授权按钮
            ->addRightButtons($rightBtn)
            ->setExtraCss($this->css)
            ->setColumnWidth([
                'id'            => 15,
                'shishen'       => 25,
                'sanzhu'        => 15,
                'update_time'   => 28,
                'right_button'  => 20,
            ])
            ->fetch();
    }

    public function sm_ssjs_edit2()
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            $data['update_time'] = time();
            if ($data['shishen'] == '') {
                $this->error('十神不能为空');
            }
            if ($data['sanzhu'] == '') {
                $this->error('三柱不能为空');
            }
            if (!$data['content']) {
                $this->error('解释不能为空');
            }
            $is_update = Db::name('Sm_ssjs2')->where('id', $data['id'])->update($data);
            if ($is_update) {
                $this->success('编辑成功', 'sm_ssjs2');
            } else {
                $this->error('编辑失败');
            }
        }
        $id   = input('id');
        $data = Db::name('Sm_ssjs2')->where('id', '=', $id)->find();
        if (empty($data)) {
            $this->error('未查到当前数据', 'sm_ssjs2');
        }
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'shishen', '十神'],
                ['number', 'sanzhu', '三柱'],
                ['ckeditor', 'content', '解释'],
                ['hidden', 'id', $id],
            ])
            ->setFormData($data)
            ->addHidden('id')
            ->layout(['shishen' => 3,'sanzhu' => 2,'content' => 8])
            ->fetch();
    }

    //十神运势
    public function sm_ssyq($group = 'sm_ssyq')
    {
        $list_tab = [
            'sm_qtbj'  => ['title' => '穷通宝鉴数据', 'url' => url('sm_qtbj', ['group' => 'sm_qtbj'])],
            'sm_smtf'  => ['title' => '三命通会数据', 'url' => url('sm_smtf', ['group' => 'sm_smtf'])],
            'sm_rgnm'  => ['title' => '日干心性数据', 'url' => url('sm_rgnm', ['group' => 'sm_rgnm'])],
            'sm_sjrs'  => ['title' => '四季用神数据', 'url' => url('sm_sjrs', ['group' => 'sm_sjrs'])],
            'sm_ssjs'  => ['title' => '十神数据', 'url' => url('sm_ssjs', ['group' => 'sm_ssjs'])],
            'sm_ssjs2'  => ['title' => '十神数据2', 'url' => url('sm_ssjs2', ['group' => 'sm_ssjs2'])],
            'sm_ssyq'  => ['title' => '十神运势数据', 'url' => url('sm_ssyq', ['group' => 'sm_ssyq'])],
        ];
        $order     = $this->getOrder('id asc');
        $map       = $this->getMap();
        $data_list = Db::name('sm_ssyq')
            ->where($map)
            ->order($order)
            ->paginate(100);
        $rightBtn = [
            'edit'      => ['class' => 'btn btn-sm btn-warning', 'href' => url('sm_ssyq_edit', ['id' => '__id__'])],
        ];
        return ZBuilder::make('table')
            ->setTabNav($list_tab, $group)
            ->setPageTitle('十神运势数据')
            ->hideCheckbox()
            ->setSearchArea([
                ['text:1', 'id', 'ID'],
                ['text:4', 'ssyq', '描述','like'],
            ])
            ->addColumns([
                ['id', 'ID'],
                ['keys', 'Key'],
                ['ssyq', '描述', 'text'],
                ['create_time', '创建时间', 'datetime'],
                ['update_time', '更新时间', 'datetime'],
                ['right_button', '操作', 'btn'],
            ])
            ->setRowList($data_list) 
            ->addRightButtons($rightBtn)
            ->setExtraCss($this->css)
            ->setColumnWidth([
                'id'            => 15,
                'keys'          => 15,
                'create_time'   => 28,
                'update_time'   => 28,
                'right_button'  => 20,
            ])
            ->fetch();
    }

    public function sm_ssyq_edit()
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            $data['update_time'] = time();
            if (!$data['ssyq']) {
                $this->error('十神运势描述不能为空');
            }
            $is_update = Db::name('Sm_ssyq')->where('id', $data['id'])->update($data);
            if ($is_update) {
                $this->success('编辑成功', 'sm_ssyq');
            } else {
                $this->error('编辑失败');
            }
        }
        $id   = input('id');
        $data = Db::name('Sm_ssyq')->where('id', '=', $id)->find();
        if (empty($data)) {
            $this->error('未查到当前数据', 'sm_ssyq');
        }
        return ZBuilder::make('form')
            ->addFormItems([
                ['ckeditor', 'ssyq', '十神运势描述'],
                ['hidden', 'id', $id],
            ])
            ->setFormData($data)
            ->addHidden('id')
            ->layout(['ssyq' => 7])
            ->fetch();
    }

    //生肖个性
    public function sm_sxgx($group = 'sm_sxgx')
    {
        $list_tab = [
            'sm_sxgx'       => ['title' => '生肖个性数据', 'url' => url('sm_sxgx', ['group' => 'sm_sxgx'])],
            'zodiac_ztys'   => ['title' => '生肖整体运势数据', 'url' => url('zodiac_ztys', ['group' => 'zodiac_ztys'])],
            'zodiac_m'      => ['title' => '生肖月运势数据', 'url' => url('zodiac_m', ['group' => 'zodiac_m'])],
        ];
        $order     = $this->getOrder('id asc');
        $map       = $this->getMap();
        $data_list = Db::name('sm_sxgx')
            ->where($map)
            ->order($order)
            ->paginate(12);
        $topBtn    = [
            //'add'       => ['class' => 'btn btn-warning','href' => url('sm_81_add')],
            'access'      => ['title' => '批量替换', 'icon' => 'fa fa-fw fa-key', 'class' => 'btn btn-warning', 'href' => url('batch_update', ['table' => 'sm_sxgx'])],
        ];
        $rightBtn = [
            'edit'      => ['class' => 'btn btn-sm btn-warning', 'href' => url('sm_sxgx_edit', ['id' => '__id__'])],
            //'delete'    => ['class' => 'btn btn-sm btn-danger ajax-get confirm','href' => url('sm_81_del', ['id'=>'__id__'])],
        ];
        return ZBuilder::make('table')
            ->setTabNav($list_tab, $group)
            //->setPageTitle('生肖个性数据')
            ->hideCheckbox()
            ->setSearchArea([
                ['text:1', 'id', 'ID'],
                ['text:2', 'sx', '生肖'],
                ['text:2', 'sxgx', '生肖个性'],
            ])
            ->addColumns([
                ['id', 'ID'],
                ['sx', '生肖'],
                ['sxgx', '生肖个性', 'text'],
                ['update_time', '更新时间', 'datetime'],
                ['right_button', '操作', 'btn'],
            ])
            ->setRowList($data_list)
            ->addTopButtons($topBtn)   // 添加授权按钮
            ->addRightButtons($rightBtn)
            ->setExtraCss($this->css)
            ->setColumnWidth([
                'id'            => 15,
                'sx'            => 20,
                'update_time'   => 28,
                'right_button'  => 20,
            ])
            ->fetch();
    }

    public function sm_sxgx_edit()
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            $data['update_time'] = time();
            if (!$data['sxgx']) {
                $this->error('生肖个性不能为空');
            }
            $is_update = Db::name('sm_sxgx')->where('id', $data['id'])->update($data);
            if ($is_update) {
                $this->success('编辑成功', 'sm_sxgx');
            } else {
                $this->error('编辑失败');
            }
        }
        $id   = input('id');
        $data = Db::name('sm_sxgx')->where('id', '=', $id)->find();
        if (empty($data)) {
            $this->error('未查到当前数据', 'sm_sxgx');
        }
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'sx', '生肖', '', '', '', 'disabled'],
                ['ckeditor', 'sxgx', '生肖个性'],
                ['ckeditor', 'yinyuana', '姻缘补充说明1'],
                ['ckeditor', 'yinyuanb', '姻缘补充说明2'],
                ['ckeditor', 'yinyuanc', '姻缘补充说明3'],
                ['ckeditor', 'yinyuand', '姻缘补充说明4'],
                ['ckeditor', 'yinyuane', '姻缘补充说明5'],
                ['ckeditor', 'yinyuanf', '姻缘补充说明6'],
                ['hidden', 'id', $id],
            ])
            ->setFormData($data)
            ->addHidden('id')
            ->layout(['sx' => 7, 'sxgx' => 7, 'yinyuana' => 6, 'yinyuanb' => 6, 'yinyuanc' => 6, 'yinyuand' => 6, 'yinyuane' => 6, 'yinyuanf' => 6])
            ->fetch();
    }

    //生肖整体运势
    public function zodiac_ztys($group = 'zodiac_ztys')
    {
        $list_tab = [
            'sm_sxgx'       => ['title' => '生肖个性数据', 'url' => url('sm_sxgx', ['group' => 'sm_sxgx'])],
            'zodiac_ztys'   => ['title' => '生肖整体运势数据', 'url' => url('zodiac_ztys', ['group' => 'zodiac_ztys'])],
            'zodiac_m'      => ['title' => '生肖月运势数据', 'url' => url('zodiac_m', ['group' => 'zodiac_m'])],
        ];
        $order     = $this->getOrder('id asc');
        $map       = $this->getMap();
        $data_list = Db::name('zodiac_ztys')
            ->where($map)
            ->order($order)
            ->paginate(12);
        $topBtn    = [
            //'add'       => ['class' => 'btn btn-warning','href' => url('sm_81_add')],
            //'access'      => ['title' => '批量替换', 'icon' => 'fa fa-fw fa-key', 'class' => 'btn btn-warning', 'href' => url('batch_update', ['table' => 'sm_sxgx'])],
        ];
        $rightBtn = [
            'edit'      => ['class' => 'btn btn-sm btn-warning', 'href' => url('zodiac_ztys_edit', ['id' => '__id__'])],
            //'delete'    => ['class' => 'btn btn-sm btn-danger ajax-get confirm','href' => url('sm_81_del', ['id'=>'__id__'])],
        ];
        return ZBuilder::make('table')
            ->setTabNav($list_tab, $group)
            //->setPageTitle('生肖整体运势')
            ->hideCheckbox()
            ->setSearchArea([
                ['text:1', 'id', 'ID'],
                ['text:2', 'sx', '生肖'],
                ['text:2', 'yx', '生肖优点', 'like'],
                ['text:2', 'qd', '生肖缺点', 'like'],
            ])
            ->addColumns([
                ['id', 'ID'],
                ['sx', '生肖'],
                ['yx', '生肖优点', 'popover', 8],
                ['qd', '生肖缺点', 'popover', 6],
                ['lncy', '流年财运', 'popover', 5],
                ['xtcy', '先天财运', 'popover', 6],
                ['zhuyi', '注意事项', 'popover', 7],
                ['aqfx', '爱情分析', 'popover', 8],
                ['th', '桃花', 'text'],
                ['update_time', '更新时间', 'date'],
                ['right_button', '操作', 'btn'],
            ])
            ->setRowList($data_list)
            ->addTopButtons($topBtn)   // 添加授权按钮
            ->addRightButtons($rightBtn)
            ->setExtraCss($this->css)
            ->setColumnWidth([
                'id'            => 25,
                'sx'            => 25,
                'th'            => 25,
                'update_time'   => 60,
                'right_button'  => 35,
            ])
            ->fetch();
    }

    public function zodiac_ztys_edit()
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            $data['update_time'] = time();
            if (!$data['yx']) {
                $this->error('请输入生肖优点');
            }
            if (!$data['qd']) {
                $this->error('请输入生肖缺点');
            }
            if (!$data['lncy']) {
                $this->error('请输入流年财运');
            }
            if (!$data['xtcy']) {
                $this->error('请输入先天财运');
            }
            if (!$data['zhuyi']) {
                $this->error('请输入注意事项');
            }
            if (!$data['aqfx']) {
                $this->error('请输入爱情分析');
            }
            if (!$data['th']) {
                $this->error('请输入桃花运数值');
            }
            $is_update = Db::name('zodiac_ztys')->where('id', $data['id'])->update($data);
            if ($is_update) {
                $this->success('编辑成功', url('zodiac_ztys', ['group' => 'zodiac_ztys']));
            } else {
                $this->error('编辑失败');
            }
        }
        $id   = input('id');
        $data = Db::name('zodiac_ztys')->where('id', '=', $id)->find();
        if (empty($data)) {
            $this->error('未查到当前数据', url('zodiac_ztys', ['group' => 'zodiac_ztys']));
        }
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'sx', '生肖', '', '', '', 'disabled'],
                ['textarea', 'yx', '生肖优点'],
                ['textarea', 'qd', '生肖缺点'],
                ['ckeditor', 'lncy', '流年财运'],
                ['textarea', 'xtcy', '先天财运'],
                ['textarea', 'zhuyi', '注意事项'],
                ['textarea', 'aqfx', '爱情分析'],
                ['number', 'th', '桃花运（数值）'],
                ['hidden', 'id', $id],
            ])
            ->setFormData($data)
            ->addHidden('id')
            ->layout(['sx' => 6, 'yx' => 7, 'qd' => 7, 'lncy' => 7, 'xtcy' => 7, 'zhuyi' => 7, 'aqfx' => 7, 'th' => 7])
            ->fetch();
    }

    //生肖月运势
    public function zodiac_m($group = 'zodiac_m')
    {
        $list_tab = [
            'sm_sxgx'       => ['title' => '生肖个性数据', 'url' => url('sm_sxgx', ['group' => 'sm_sxgx'])],
            'zodiac_ztys'   => ['title' => '生肖整体运势数据', 'url' => url('zodiac_ztys', ['group' => 'zodiac_ztys'])],
            'zodiac_m'      => ['title' => '生肖月运势数据', 'url' => url('zodiac_m', ['group' => 'zodiac_m'])],
        ];
        $order     = $this->getOrder('id asc');
        $map        = $this->getMap();
        $data_list  = Db::name('zodiac_m')
            ->where($map)
            ->order($order)
            ->paginate(12);
        $topBtn    = [ //需增加导入导出
            //'add'       => ['class' => 'btn btn-warning','href' => url('sm_81_add')],
            'access'      => ['title' => '批量替换', 'icon' => 'fa fa-fw fa-key', 'class' => 'btn btn-warning', 'href' => url('batch_update', ['table' => 'zodiac_m'])],
        ];
        $rightBtn = [
            'edit'      => ['class' => 'btn btn-sm btn-warning', 'href' => url('zodiac_m_edit', ['id' => '__id__'])],
            //'delete'    => ['class' => 'btn btn-sm btn-danger ajax-get confirm','href' => url('sm_81_del', ['id'=>'__id__'])],
        ];
        return ZBuilder::make('table')
            ->setTabNav($list_tab, $group)
            ->hideCheckbox()
            ->setSearchArea([
                ['text:1', 'id', 'ID'],
                ['text:2', 'sx', '生肖'],
                ['text:2', 'title', '标题', 'like'],
                ['text:2', 'neirong', '测算内容', 'like'],
            ])
            ->addColumns([
                ['id', 'ID'],
                ['sx', '生肖'],
                ['m', '月份', 'text'],
                ['mm', 'mm', 'text'],
                ['toubu', '起止时间', 'text'],
                ['title', '标题', 'text'],
                ['toubu2', '头部', 'text'],
                ['content', '测算介绍', 'popover', 8],
                ['neirong', '测算内容', 'popover', 6],
                ['liunian', '流年运势', 'popover', 5],
                ['update_time', '更新时间', 'date'],
                ['right_button', '操作', 'btn'],
            ])
            ->setRowList($data_list)
            ->addTopButtons($topBtn)   // 添加授权按钮
            ->addRightButtons($rightBtn)
            ->setExtraCss($this->css)
            ->setColumnWidth([
                'id'            => 25,
                'sx'            => 25,
                'm'             => 25,
                'mm'            => 25,
                'update_time'   => 60,
                'right_button'  => 35,
            ])
            ->fetch();
    }

    public function zodiac_m_edit($id = 0)
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            $data['update_time'] = time();
            if (!$data['toubu']) {
                $this->error('起止时间不能为空');
            }
            if (!$data['title']) {
                $this->error('标题不能为空');
            }
            if (!$data['toubu2']) {
                $this->error('头部不能为空');
            }
            if (!$data['content']) {
                $this->error('测算介绍不能为空');
            }
            if (!$data['neirong']) {
                $this->error('测算内容不能为空');
            }
            if (!$data['liunian']) {
                $this->error('测算内容不能为空');
            }
            $res = Db::name('zodiac_m')->where('id', '=', $id)->update($data);
            if ($res) {
                $this->success('更新成功', url('zodiac_m', ['group' => 'zodiac_m']));
            } else {
                $this->error('更新失败');
            }
        }
        $id   = input('id');
        $data = Db::name('zodiac_m')->where('id', '=', $id)->find();
        if (empty($data)) {
            $this->error('未查到当前数据', url('zodiac_m', ['group' => 'zodiac_m']));
        }
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'sx', '生肖', '', '', '', 'disabled'],
                ['text', 'm', '月份', '', '', '', 'disabled'],
                ['text', 'mm', 'mm', '', '', '', 'disabled'],
                ['text', 'title', '标题'],
                ['text', 'toubu', '起止时间'],
                ['text', 'toubu2', '头部'],
                ['textarea', 'content', '测算介绍'],
                ['textarea', 'neirong', '测算内容'],
                ['textarea', 'liunian', '流年运势'],
                ['hidden', 'id', $id],
            ])
            ->setFormData($data)
            ->addHidden('id')
            ->layout(['sx' => 2, 'm' => 2, 'mm' => 2, 'title' => 7, 'toubu' => 7, 'toubu2' => 7, 'content' => 7, 'neirong' => 7, 'liunian' => 7])
            ->fetch();
    }

    //百家姓
    public function give_bjxing()
    {
        $order     = $this->getOrder('id asc');
        $map        = $this->getMap();
        $data_list  = Db::name('give_bjxing')
            ->where($map)
            ->order($order)
            ->paginate(50);
        $rightBtn = [
            'edit'      => ['class' => 'btn btn-sm btn-warning', 'href' => url('give_bjxing_edit', ['id' => '__id__'])],
        ];
        return ZBuilder::make('table')
            ->setPageTitle('百家姓数据')
            ->hideCheckbox()
            ->setSearchArea([
                ['text:1', 'id', 'ID'],
                ['text:2', 'xing', '姓氏'],
                ['text:2', 'url', '拼音'],
            ])
            ->addColumns([
                ['id', 'ID'],
                ['xing', '姓氏'],
                ['url', '拼音', 'text'],
                ['update_time', '更新时间', 'datetime'],
                ['right_button', '操作', 'btn'],
            ])
            ->setRowList($data_list)
            ->addRightButtons($rightBtn)
            ->setExtraCss($this->css)
            ->setColumnWidth([
                'id'            => 15,
                'xing'          => 20,
                'url'           => 20,
                'update_time'   => 28,
                'right_button'  => 20,
            ])
            ->fetch();
    }

    //百家姓数据修改
    public function give_bjxing_edit()
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            $data['update_time'] = time();
            if (!$data['xing']) {
                $this->error('姓氏不能为空');
            }
            if (!$data['url']) {
                $this->error('拼音不能为空');
            }
            $is_update = Db::name('give_bjxing')->where('id', $data['id'])->update($data);
            if ($is_update) {
                $this->success('编辑成功', 'give_bjxing');
            } else {
                $this->error('编辑失败');
            }
        }
        $id   = input('id');
        $data = Db::name('give_bjxing')->where('id', '=', $id)->find();
        if (empty($data)) {
            $this->error('未查到当前数据', 'give_bjxing');
        }
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'xing', '姓氏'],
                ['text', 'url', '拼音'],
                ['hidden', 'id', $id],
            ])
            ->setFormData($data)
            ->addHidden('id')
            ->layout(['xing' => 5, 'url' => 5])
            ->fetch();
    }

    //宝宝起名
    public function give_bjxingming()
    {
        $order      = $this->getOrder('a.id asc');
        $map        = $this->getMap();
        $data_list  = Db::name('give_bjxingming')
            ->alias('a')
            ->join('give_bjxing b', 'a.xid=b.id')
            ->field('a.*,b.xing')
            ->where($map)
            ->order($order)
            ->paginate(100);
        $topBtn    = [
            'add'       => ['class' => 'btn btn-warning', 'href' => url('give_bjxingming_add')],
            'delete'    => ['href' => url('give_bjxingming_del')],
        ];
        $rightBtn = [
            'edit'      => ['class' => 'btn btn-sm btn-warning', 'href' => url('give_bjxingming_edit', ['id' => '__id__'])],
            'delete'    => ['class' => 'btn btn-sm btn-danger ajax-get confirm', 'href' => url('give_bjxingming_del', ['id' => '__id__'])],
        ];
        return ZBuilder::make('table')
            ->setPageTitle('宝宝起名数据')
            //->hideCheckbox()
            ->setSearchArea([
                ['text:1', 'id', 'ID'],
                ['text:2', 'b.xing', '姓氏'],
                ['text:2', 'a.name', '姓名'],
                ['select:2', 'a.sex', '性别', '', '', ['男', '女']],
                ['select:2', 'a.geshu', '字数', '', '', [2 => '单字', 3 => '双字']],
            ])
            ->addColumns([
                ['id', 'ID'],
                ['xing', '姓氏'],
                ['name', '推荐姓名'],
                ['sex', '性别', 'status', '', ['男', '女']],
                ['geshu', '字数', 'status', '', [2 => '单字', 3 => '双字']],
                ['update_time', '更新时间', 'datetime'],
                ['right_button', '操作', 'btn'],
            ])
            ->setRowList($data_list)
            ->addTopButtons($topBtn)   // 添加授权按钮
            ->addRightButtons($rightBtn)
            ->setExtraCss($this->css)
            //->setColumnWidth([
            //     'id'            => 30,
            //     'xing'          => 15,
            //     'name'          => 20,
            //     'sex'           => 20,
            //     'geshu'           => 20,
            //     //'update_time'   => 35,
            //     'right_button'  => 30,
            // ])
            ->fetch();
    }

    public function give_bjxingming_add()
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            $data['update_time'] = time();
            if (!$data['xid']) {
                $this->error('姓氏不能为空');
            }
            if (!$data['name']) {
                $this->error('姓名不能为空');
            }
            $is_update = Db::name('give_bjxingming')->insert($data);
            if ($is_update) {
                $this->success('新增成功', 'give_bjxingming');
            } else {
                $this->error('新增失败');
            }
        }
        $baijiaxing = Db::name('give_bjxing')->where('id', '<>', 0)->column('xing', 'id');
        return ZBuilder::make('form')
            ->addFormItems([
                ['select', 'xid', '姓氏', '', $baijiaxing],
                ['text', 'name', '姓名'],
                ['radio', 'sex', '性别',  '', [0 => '男', 1 => '女'], 0],
                ['radio', 'geshu', '字数', '', [2 => '单字', 3 => '双字'], 2],
            ])
            ->layout(['xid' => 5, 'name' => 5])
            ->fetch();
    }

    public function give_bjxingming_edit()
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            $data['update_time'] = time();
            if (!$data['xid']) {
                $this->error('姓氏不能为空');
            }
            if (!$data['name']) {
                $this->error('姓名不能为空');
            }
            $is_update = Db::name('give_bjxingming')->where('id', $data['id'])->update($data);
            if ($is_update) {
                $this->success('编辑成功', 'give_bjxingming');
            } else {
                $this->error('编辑失败');
            }
        }
        $id = input('id');
        $data = Db::name('give_bjxingming')->where('id', $id)->find();
        if (!$data) {
            $this->error('数据不存在');
        }
        $baijiaxing = Db::name('give_bjxing')->where('id', '<>', 0)->column('xing', 'id');
        return ZBuilder::make('form')
            ->addFormItems([
                ['select', 'xid', '姓氏', '', $baijiaxing],
                ['text', 'name', '姓名'],
                ['radio', 'sex', '性别',  '', [0 => '男', 1 => '女']],
                ['radio', 'geshu', '字数', '', [2 => '单字', 3 => '双字']],
                ['hidden', 'id', $id]
            ])
            ->setFormData($data)
            ->layout(['xid' => 5, 'name' => 5])
            ->fetch();
    }

    public function give_bjxingming_del()
    {
        if ($this->request->isPost()) {
            $POST = input('post.');
            ###########################批量删除（开始）##############################
            $ids  = $POST['ids'];
            if (empty($ids)) {
                $this->error('请选择要删除的选项！');
            }
            $res  = Db::name('give_bjxingming')->where('id', 'in', $ids)->delete();
            if ($res) {
                $this->success('删除成功', 'give_bjxingming');
            } else {
                $this->error('删除失败');
            }
            ###########################批量删除（结束）##############################
        } else {
            $id  = input('id');
            if (!$id) {
                $this->error('请选择要删除的记录');
            }
            $res = Db::name('give_bjxingming')->where('id', $id)->delete();
            if ($res) {
                $this->success('删除成功', 'give_bjxingming');
            } else {
                $this->error('删除失败');
            }
        }
    }

    //姓名配对（64卦）
    public function give_xmpd_gua($group = 'give_xmpd_gua')
    {
        $list_tab = [
            'give_xmpd_gua'  => ['title' => '姓名配对（64卦）', 'url' => url('give_xmpd_gua', ['group' => 'give_xmpd_gua'])],
            'give_xmpd_yuanshen' => ['title' => '姓名配对（原神数据）', 'url' => url('give_xmpd_yuanshen', ['group' => 'give_xmpd_yuanshen'])],
        ];
        $order      = $this->getOrder('gua asc');
        $map        = $this->getMap();
        $data_list  = Db::name('give_xmpd_gua')
            ->where($map)
            ->order($order)
            ->paginate(64);
        $rightBtn = [
            'edit'      => ['class' => 'btn btn-sm btn-warning', 'href' => url('give_xmpd_gua_edit', ['id' => '__id__'])],
        ];
        return ZBuilder::make('table')
            ->setTabNav($list_tab,  $group)
            ->setPageTitle('姓名配对（周易64卦）')
            ->hideCheckbox()
            ->setSearchArea([
                ['text:1', 'id', 'ID'],
                ['text:2', 'title_txt', '卦名'],
                ['text:2', 'gua', '卦数'],
            ])
            ->addColumns([
                ['id', 'ID'],
                ['title_txt', '卦名'],
                ['title', '卦词', 'text'],
                ['gua', '卦数', 'text'],
                ['guayi', '卦义', 'text'],
                ['lv', '卦象', 'text'],
                ['update_time', '更新时间', 'datetime'],
                ['right_button', '操作', 'btn'],
            ])
            ->setRowList($data_list)
            ->addRightButtons($rightBtn)
            ->setExtraCss($this->css)
            ->fetch();
    }

    public function give_xmpd_gua_edit()
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            $data['update_time'] = time();
            if (!$data['title']) {
                $this->error('卦词不能为空');
            }
            if (!$data['guayi']) {
                $this->error('卦义不能为空');
            }
            if (!$data['lv']) {
                $this->error('卦象不能为空');
            }
            if (!$data['msg']) {
                $this->error('卦象详解不能为空');
            }
            $is_update = Db::name('give_xmpd_gua')->where('id', $data['id'])->update($data);
            if ($is_update) {
                $this->success('编辑成功', 'give_xmpd_gua');
            } else {
                $this->error('编辑失败');
            }
        }
        $id   = input('id');
        $data = Db::name('give_xmpd_gua')->where('id', '=', $id)->find();
        if (empty($data)) {
            $this->error('未查到当前数据', 'give_xmpd_gua');
        }
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'title_txt', '卦名', '', '', '', 'disabled'],
                ['text', 'gua', '卦数', '', '', '', 'disabled'],
                ['text', 'title', '卦词'],
                ['text', 'guayi', '卦义'],
                ['text', 'lv', '卦象'],
                ['textarea', 'msg', '卦象详解'],
                ['textarea', 'pd', '配对解析'],
                ['textarea', 'weiji', '爱情危机'],
                ['textarea', 'jianyi', '爱情建议'],
                ['hidden', 'id', $id],
            ])
            ->setFormData($data)
            ->layout(['title_txt' => 3, 'title' => 3, 'gua' => 3, 'guayi' => 3, 'lv' => 3])
            ->fetch();
    }

    //姓名配对（原神）
    public function give_xmpd_yuanshen($group = 'give_xmpd_yuanshen')
    {
        $list_tab = [
            'give_xmpd_gua'  => ['title' => '姓名配对（64卦）', 'url' => url('give_xmpd_gua', ['group' => 'give_xmpd_gua'])],
            'give_xmpd_yuanshen' => ['title' => '姓名配对（原神数据）', 'url' => url('give_xmpd_yuanshen', ['group' => 'give_xmpd_yuanshen'])],
        ];
        $order      = $this->getOrder('id asc');
        $map        = $this->getMap();
        $data_list  = Db::name('give_xmpd_yuanshen')
            ->where($map)
            ->order($order)
            ->paginate(64);
        $rightBtn = [
            'edit'      => ['class' => 'btn btn-sm btn-warning', 'href' => url('give_xmpd_yuanshen_edit', ['id' => '__id__'])],
        ];
        return ZBuilder::make('table')
            ->setTabNav($list_tab, $group)
            ->setPageTitle('姓名配对（原神数据）')
            ->hideCheckbox()
            ->setSearchArea([
                ['text:1', 'id', 'ID'],
                ['text:2', 'yuanshen', '原神', 'like'],
                ['text:2', 'wuhang', '五行'],
            ])
            ->addColumns([
                ['id', 'ID'],
                ['num', '数', 'text'],
                ['wuhang', '五行'],
                ['yuanshen', '原神', 'text'],
                ['xg', '性格内容', 'text'],
                ['update_time', '更新时间', 'datetime'],
                ['right_button', '操作', 'btn'],
            ])
            ->setRowList($data_list)
            ->addRightButtons($rightBtn)
            ->setExtraCss($this->css)
            ->fetch();
    }

    public function give_xmpd_yuanshen_edit()
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            $data['update_time'] = time();
            if (!$data['yuanshen']) {
                $this->error('原神不能为空');
            }
            if (!$data['xg']) {
                $this->error('性格内容不能为空');
            }
            $is_update = Db::name('give_xmpd_yuanshen')->where('id', $data['id'])->update($data);
            if ($is_update) {
                $this->success('编辑成功', 'give_xmpd_yuanshen');
            } else {
                $this->error('编辑失败');
            }
        }
        $id   = input('id');
        $data = Db::name('give_xmpd_yuanshen')->where('id', '=', $id)->find();
        if (empty($data)) {
            $this->error('未查到当前数据', 'give_xmpd_yuanshen');
        }
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'num', '数', '', '', '', 'disabled'],
                ['text', 'wuhang', '五行', '', '', '', 'disabled'],
                ['text', 'yuanshen', '原神'],
                ['textarea', 'xg', '性格内容'],
                ['hidden', 'id', $id],
            ])
            ->setFormData($data)
            ->layout(['num' => 3, 'wuhang' => 3, 'yuanshen' => 3])
            ->fetch();
    }

    //合婚数据
    public function match_hehun($group = 'match_hehun')
    {
        $list_tab = [
            'match_hehun'   => ['title' => '合婚数据1', 'url' => url('match_hehun', ['group' => 'match_hehun'])],
            'match_hehun1'  => ['title' => '合婚数据2', 'url' => url('match_hehun', ['group' => 'match_hehun1'])],
            'match_hehun2'  => ['title' => '合婚数据3', 'url' => url('match_hehun', ['group' => 'match_hehun2'])],
            'match_hehun3'  => ['title' => '合婚数据4', 'url' => url('match_hehun', ['group' => 'match_hehun3'])],
        ];
        $order      = $this->getOrder('id asc');
        $map        = $this->getMap();
        $data_list  = Db::name($group)
            ->where($map)
            ->order($order)
            ->paginate(100);
        $rightBtn = [
            'edit'      => ['class' => 'btn btn-sm btn-warning', 'href' => url('match_hehun_edit', ['group' => $group, 'id' => '__id__'])],
        ];
        return ZBuilder::make('table')
            ->setTabNav($list_tab, $group)
            ->hideCheckbox()
            ->setSearchArea([
                ['text:1', 'id', 'ID'],
                ['text:2', 'name', '天干地支', 'like'],
                ['text:5', 'content', '内容', 'like'],
            ])
            ->addColumns([
                ['id', 'ID'],
                ['name', '天干地支', 'text'],
                ['content', '内容', 'text'],
                ['update_time', '更新时间', 'datetime'],
                ['right_button', '操作', 'btn'],
            ])
            ->setRowList($data_list)
            ->addRightButtons($rightBtn)
            ->setColumnWidth([
                'id'  => 15,
                'name' => 25,
                'update_time' => 30,
                'right_button'  => 20,
            ])
            ->setExtraCss($this->css)
            ->fetch();
    }

    public function match_hehun_edit($group = 'match_hehun', $id = 0)
    {

        if ($this->request->isPost()) {
            $data = input('post.');
            $data['update_time'] = time();
            // if (!$data['name']) {
            //     $this->error('天干地支不能为空');
            // }
            if (!$data['content']) {
                $this->error('内容不能为空');
            }
            $is_update = Db::name($group)->where('id', $data['id'])->update($data);
            if ($is_update) {
                $this->success('编辑成功', url('match_hehun', ['group' => $group]));
            } else {
                $this->error('编辑失败', url('match_hehun', ['group' => $group]));
            }
        }
        $id   = input('id');
        $data = Db::name($group)->where('id', '=', $id)->find();
        if (empty($data)) {
            $this->error('未查到当前数据', url('match_hehun', ['group' => $group]),);
        }
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'name', '天干地支', '', '', '', 'disabled'],
                ['textarea', 'content', '内容'],
                ['hidden', 'id', $id],
            ])
            ->setFormData($data)
            ->layout(['name' => 3])
            ->fetch();
    }

    //称骨算命
    public function sm_chenggu($group = 'sm_chenggu')
    {
        $list_tab = [
            'sm_chenggu'   => ['title' => '称骨算命（总数据）', 'url' => url('sm_chenggu', ['group' => 'sm_chenggu'])],
            'sm_chenggu1'  => ['title' => '称骨算命（年数据）', 'url' => url('sm_chenggu', ['group' => 'sm_chenggu1'])],
            'sm_chenggu2'  => ['title' => '称骨算命（月数据）', 'url' => url('sm_chenggu', ['group' => 'sm_chenggu2'])],
            'sm_chenggu3'  => ['title' => '称骨算命（日数据）', 'url' => url('sm_chenggu', ['group' => 'sm_chenggu3'])],
            'sm_chenggu4'  => ['title' => '称骨算命（时数据）', 'url' => url('sm_chenggu', ['group' => 'sm_chenggu4'])],
        ];
        $order      = $this->getOrder('id asc');
        $map        = $this->getMap();
        $data_list  = Db::name($group)
            ->where($map)
            ->order($order)
            ->paginate(100);
        $Search = [
            ['text:1', 'id', 'ID'],
            ['text:2', 'weight', '称重'],
            ['text:5', 'content', '内容', 'like'],
        ];
        $Columns = [
            ['id', 'ID'],
            ['weight', '称重', 'text.edit'],
            ['content', '内容', 'text'],
            ['update_time', '更新时间', 'datetime'],
            ['right_button', '操作', 'btn'],
        ];
        $topBtn = [];
        $rightBtn = [
            'edit'      => ['class' => 'btn btn-sm btn-warning', 'href' => url('sm_chenggu_edit', ['group' => $group, 'id' => '__id__'])],
        ];
        if ($group == 'sm_chenggu' || $group == 'sm_chenggu1') {
            $topBtn = [
                'add'    => ['href' => url('sm_chenggu_add', ['group' => $group])],
                'delete' => ['href' => url('sm_chenggu_del', ['group' => $group, 'id' => '__id__'])],
            ];
            $rightBtn = [
                'edit'      => ['class' => 'btn btn-sm btn-warning', 'href' => url('sm_chenggu_edit', ['group' => $group, 'id' => '__id__'])],
                'delete'    => ['class' => 'btn btn-sm btn-danger ajax-get confirm', 'href' => url('sm_chenggu_del', ['group' => $group, 'id' => '__id__'])],
            ];
        }
        if ($group == 'sm_chenggu1') {
            $Search = [
                ['text:1', 'id', 'ID'],
                ['text:2', 'year', '年份'],
                ['text:2', 'weight', '称重'],

            ];
            $Columns = [
                ['id', 'ID'],
                ['year', '年份', 'text.edit'],
                ['weight', '称重', 'text'],
                ['update_time', '更新时间', 'datetime'],
                ['right_button', '操作', 'btn'],
            ];
        }
        if ($group == 'sm_chenggu2') {
            $Search = [
                ['text:1', 'id', 'ID'],
                ['text:2', 'month', '月份'],
                ['text:2', 'weight', '称重'],

            ];
            $Columns = [
                ['id', 'ID'],
                ['month', '月份', 'text'],
                ['weight', '称重', 'text.edit'],
                ['update_time', '更新时间', 'datetime'],
                ['right_button', '操作', 'btn'],
            ];
        }
        if ($group == 'sm_chenggu3') {
            $Search = [
                ['text:1', 'id', 'ID'],
                ['text:2', 'day', '日期'],
                ['text:2', 'weight', '称重'],

            ];
            $Columns = [
                ['id', 'ID'],
                ['day', '日期', 'text'],
                ['weight', '称重', 'text.edit'],
                ['update_time', '更新时间', 'datetime'],
                ['right_button', '操作', 'btn'],
            ];
        }
        if ($group == 'sm_chenggu4') {
            $Search = [
                ['text:1', 'id', 'ID'],
                ['text:2', 'time', '时辰'],
                ['text:2', 'weight', '称重'],

            ];
            $Columns = [
                ['id', 'ID'],
                ['time', '时辰', 'text'],
                ['weight', '称重', 'text.edit'],
                ['update_time', '更新时间', 'datetime'],
                ['right_button', '操作', 'btn'],
            ];
        }

        return ZBuilder::make('table')
            ->setTabNav($list_tab, $group)
            //->hideCheckbox()
            ->setSearchArea($Search)
            ->addColumns($Columns)
            ->setRowList($data_list)
            ->addTopButtons($topBtn)
            ->addRightButtons($rightBtn)
            // ->setColumnWidth([
            //     'weight'        => 20,
            //     'update_time'   => 30,
            //     'right_button'  => 20,
            // ])
            ->setExtraCss($this->css)
            ->fetch();
    }

    public function sm_chenggu_add($group = 'sm_chenggu')
    {
        if ($this->request->isPost()) {
            $data                   = input('post.');
            $data['update_time']    = time();
            $group                  = $data['group'];
            if (!$data['weight']) {
                $this->error('称重不能为空');
            }
            if (isset($data['content']) && !$data['content']) {
                $this->error('内容不能为空');
            }
            if (isset($data['year']) && !$data['year']) {
                $this->error('年份不能为空');
            }
            if (isset($data['month']) && !$data['month']) {
                $this->error('月份不能为空');
            }
            if (isset($data['day']) && !$data['day']) {
                $this->error('日期不能为空');
            }
            if (isset($data['time']) && !$data['time']) {
                $this->error('时辰不能为空');
            }
            $is_update = Db::name($group)->insert($data);
            if ($is_update) {
                $this->success('新增成功', url('sm_chenggu', ['group' => $group]));
            } else {
                $this->error('新增失败', url('sm_chenggu', ['group' => $group]));
            }
        }
        //$id   = input('id');
        //$data = Db::name($group)->where('id', '=', $id)->find();
        // if(empty($data)) {
        //     $this->error('未查到当前数据', url('match_hehun', ['group' => $group]),);
        //}
        switch ($group) {
            case 'sm_chenggu1':
                $FormItems = [
                    ['number', 'weight', '称重'],
                    ['text', 'year', '年份'],
                ];
                break;
            default:
                $FormItems = [
                    ['number', 'weight', '称重'],
                    ['textarea', 'content', '内容'],
                    ['textarea', 'intro', '介绍'],
                ];
                break;
        }
        $FormItems[count($FormItems)] = ['hidden', 'group', $group];
        return ZBuilder::make('form')
            ->addFormItems($FormItems)
            ->layout(['weight' => 6, 'content' => 7, 'intro' => 7, 'year' => 6, 'month' => 6, 'day' => 6, 'time' => 6])
            ->fetch();
    }

    public function sm_chenggu_edit($group = 'sm_chenggu', $id = 0)
    {
        if ($this->request->isPost()) {
            $data                   = input('post.');
            $data['update_time']    = time();
            $group                  = $data['group'];
            if (!$data['weight']) {
                $this->error('称重不能为空');
            }
            if (isset($data['content']) && !$data['content']) {
                $this->error('内容不能为空');
            }
            if (isset($data['year']) && !$data['year']) {
                $this->error('年份不能为空');
            }
            if (isset($data['month']) && !$data['month']) {
                $this->error('月份不能为空');
            }
            if (isset($data['day']) && !$data['day']) {
                $this->error('日期不能为空');
            }
            if (isset($data['time']) && !$data['time']) {
                $this->error('时辰不能为空');
            }
            $is_update = Db::name($group)->where('id', '=', $id)->update($data);
            if ($is_update) {
                $this->success('编辑成功', url('sm_chenggu', ['group' => $group]));
            } else {
                $this->error('编辑失败', url('sm_chenggu', ['group' => $group]));
            }
        }
        $id   = input('id');
        $data = Db::name($group)->where('id', '=', $id)->find();
        if (empty($data)) {
            $this->error('未查到当前数据', url('sm_chenggu', ['group' => $group]),);
        }
        switch ($group) {
            case 'sm_chenggu1':
                $FormItems = [
                    ['number', 'weight', '称重'],
                    ['text', 'year', '年份'],
                ];
                break;
            case 'sm_chenggu2':
                $FormItems = [
                    ['text', 'month', '月份', '', '', '', 'disabled'],
                    ['number', 'weight', '称重'],
                ];
                break;
            case 'sm_chenggu3':
                $FormItems = [
                    ['text', 'day', '日期', '', '', '', 'disabled'],
                    ['number', 'weight', '称重'],
                ];
                break;
            case 'sm_chenggu4':
                $FormItems = [
                    ['text', 'time', '时辰', '', '', '', 'disabled'],
                    ['number', 'weight', '称重'],
                ];
                break;
            default:
                $FormItems = [
                    ['number', 'weight', '称重'],
                    ['textarea', 'content', '内容'],
                    ['textarea', 'intro', '介绍'],
                ];
                break;
        }
        $count = count($FormItems);
        $FormItems[$count]      = ['hidden', 'group', $group];
        $FormItems[$count + 1]    = ['hidden', 'id', $id];
        return ZBuilder::make('form')
            ->addFormItems($FormItems)
            ->setFormData($data)
            ->layout(['weight' => 6, 'content' => 7, 'intro' => 7, 'year' => 6, 'month' => 6, 'day' => 6, 'time' => 6])
            ->fetch();
    }

    public function sm_chenggu_del($group = 'sm_chenggu', $id = 0)
    {
        if ($this->request->isPost()) {
            $POST = input('post.');
            ###########################批量删除（开始）##############################
            $ids  = $POST['ids'];
            if (empty($ids)) {
                $this->error('请选择要删除的选项！');
            }
            $res  = Db::name($group)->where('id', 'in', $ids)->delete();
            if ($res) {
                $this->success('删除成功', url('sm_chenggu', ['group' => $group]));
            } else {
                $this->error('删除失败', url('sm_chenggu', ['group' => $group]));
            }
            ###########################批量删除（结束）##############################
        } else {
            $id  = input('id');
            if (!$id) {
                $this->error('请选择要删除的数据');
            }
            $res = Db::name($group)->where('id', $id)->delete();
            if ($res) {
                $this->success('删除成功', url('sm_chenggu', ['group' => $group]));
            } else {
                $this->error('删除失败', url('sm_chenggu', ['group' => $group]));
            }
        }
    }


    //同异五行
    public function sm_wh($group = 'sm_wh')
    {
        $list_tab = [
            'sm_wh'    => ['title' => '同异五行', 'url' => url('sm_wh', ['group' => 'sm_wh'])],
            'sm_whjk'  => ['title' => '五行健康', 'url' => url('sm_whjk', ['group' => 'sm_whjk'])],
            'sm_xishensy'  => ['title' => '喜神五行', 'url' => url('sm_xishensy', ['group' => 'sm_xishensy'])],
        ];
        $order      = $this->getOrder('id asc');
        $map        = $this->getMap();
        $data_list  = Db::name('sm_wh')
            ->where($map)
            ->order($order)
            ->paginate(10);
        $rightBtn = [
            'edit'      => ['class' => 'btn btn-sm btn-warning', 'href' => url('sm_wh_edit', ['id' => '__id__'])],
        ];
        return ZBuilder::make('table')
            ->setTabNav($list_tab, $group)
            ->hideCheckbox()
            ->setSearchArea([
                ['text:1', 'id', 'ID'],
                ['text:2', 'wh', '五行'],
                ['text:3', 'tnwh', '同命五行', 'like'],
                ['text:3', 'ynwh', '纳音五行', 'like'],
            ])
            ->addColumns([
                ['id', 'ID'],
                ['wh', '五行', 'text'],
                ['tnwh', '同命五行', 'text'],
                ['ynwh', '纳音五行', 'text'],
                ['skzhyj', '盛衰宜忌', 'text'],
                ['whzx', '五行心性', 'text'],
                ['szwh', '脏腑五行', 'text'],
                ['hyhw', '方位喜好', 'text'],
                ['update_time', '更新时间', 'datetime'],
                ['right_button', '操作', 'btn'],
            ])
            ->setRowList($data_list)
            ->addRightButtons($rightBtn)
            ->setColumnWidth([
                'id'     => 15,
                'wh'     => 20,
                'tnwh'   => 30,
                'ynwh'   => 30,
                'update_time' => 60,
                'right_button'  => 25,
            ])
            ->setExtraCss($this->css)
            ->fetch();
    }

    public function sm_wh_edit($id = 0)
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            $data['update_time'] = time();
            if (!$data['tnwh']) {
                $this->error('同命五行不能为空');
            }
            if (!$data['ynwh']) {
                $this->error('纳音五行不能为空');
            }
            if (!$data['skzhyj']) {
                $this->error('吉凶宜忌不能为空');
            }
            if (!$data['whzx']) {
                $this->error('五行心性不能为空');
            }
            if (!$data['szwh']) {
                $this->error('脏腑五行不能为空');
            }
            if (!$data['hyhw']) {
                $this->error('方位喜好不能为空');
            }
            $is_update = Db::name('sm_wh')->where('id', $data['id'])->update($data);
            if ($is_update) {
                $this->success('编辑成功', url('sm_wh', ['group' => 'sm_wh']));
            } else {
                $this->error('编辑失败', url('sm_wh', ['group' => 'sm_wh']));
            }
        }
        $id   = input('id');
        $data = Db::name('sm_wh')->where('id', '=', $id)->find();
        if (empty($data)) {
            $this->error('未查到当前数据', url('sm_wh'),);
        }
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'wh', '五行', '', '', '', 'disabled'],
                ['text', 'tnwh', '同命五行'],
                ['text', 'ynwh', '纳音五行'],
                ['ckeditor', 'skzhyj', '盛衰宜忌'],
                ['textarea', 'whzx', '五行心性'],
                ['textarea', 'szwh', '脏腑五行'],
                ['textarea', 'hyhw', '方位喜好'],
                ['textarea', 'whw', '五行旺'],
                ['textarea', 'whq', '五行缺'],
                ['hidden', 'id', $id],
            ])
            ->setFormData($data)
            ->layout(['wh' => 3])
            ->fetch();
    }

    //五行健康
    public function sm_whjk($group = 'sm_whjk')
    {
        $list_tab = [
            'sm_wh'         => ['title' => '同异五行', 'url' => url('sm_wh', ['group' => 'sm_wh'])],
            'sm_whjk'       => ['title' => '五行健康', 'url' => url('sm_whjk', ['group' => 'sm_whjk'])],
            'sm_xishensy'   => ['title' => '喜神五行', 'url' => url('sm_xishensy', ['group' => 'sm_xishensy'])],
        ];
        $order      = $this->getOrder('id asc');
        $map        = $this->getMap();
        $data_list  = Db::name('sm_whjk')
            ->where($map)
            ->order($order)
            ->paginate(10);
        $rightBtn = [
            'edit'      => ['class' => 'btn btn-sm btn-warning', 'href' => url('sm_whjk_edit', ['id' => '__id__'])],
        ];
        return ZBuilder::make('table')
            ->setTabNav($list_tab, $group)
            ->hideCheckbox()
            ->setSearchArea([
                ['text:1', 'id', 'ID'],
                ['text:2', 'wh', '五行'],
            ])
            ->addColumns([
                ['id', 'ID'],
                ['wh', '五行', 'text'],
                ['jb', '易发疾病', 'text'],
                ['zz', '易发症状', 'text'],
                ['yd', '养生要点', 'text'],
                ['sh', '生活起居', 'text'],
                ['ys', '饮食调理', 'text'],
                ['bj', '保健膳食', 'text'],
                ['update_time', '更新时间', 'datetime'],
                ['right_button', '操作', 'btn'],
            ])
            ->setRowList($data_list)
            ->addRightButtons($rightBtn)
            ->setColumnWidth([
                'id'     => 15,
                'wh'     => 25,
                'update_time' => 70,
                'right_button'  => 25,
            ])
            ->setExtraCss($this->css)
            ->fetch();
    }

    public function sm_whjk_edit($id = 0)
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            $data['update_time'] = time();
            if (!$data['jb']) {
                $this->error('易发疾病不能为空');
            }
            if (!$data['zz']) {
                $this->error('易发症状不能为空');
            }
            if (!$data['yd']) {
                $this->error('养生要点不能为空');
            }
            if (!$data['sh']) {
                $this->error('生活起居不能为空');
            }
            if (!$data['ys']) {
                $this->error('饮食调理不能为空');
            }
            if (!$data['bj']) {
                $this->error('保健膳食不能为空');
            }
            $is_update = Db::name('sm_whjk')->where('id', $data['id'])->update($data);
            if ($is_update) {
                $this->success('编辑成功', url('sm_whjk', ['group' => 'sm_whjk']));
            } else {
                $this->error('编辑失败', url('sm_whjk', ['group' => 'sm_whjk']));
            }
        }
        $id   = input('id');
        $data = Db::name('sm_whjk')->where('id', '=', $id)->find();
        if (empty($data)) {
            $this->error('未查到当前数据', url('sm_whjk'),);
        }
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'wh', '五行', '', '', '', 'disabled'],
                ['textarea', 'jb', '易发疾病'],
                ['textarea', 'zz', '易发症状'],
                ['textarea', 'yd', '养生要点'],
                ['textarea', 'sh', '生活起居'],
                ['textarea', 'ys', '饮食调理'],
                ['textarea', 'bj', '保健膳食'],
                ['hidden', 'id', $id],
            ])
            ->setFormData($data)
            ->layout(['wh' => 3])
            ->fetch();
    }


    //喜神五行
    public function sm_xishensy($group = 'sm_xishensy')
    {
        $list_tab = [
            'sm_wh'    => ['title' => '同异五行', 'url' => url('sm_wh', ['group' => 'sm_wh'])],
            'sm_whjk'  => ['title' => '五行健康', 'url' => url('sm_whjk', ['group' => 'sm_whjk'])],
            'sm_xishensy'  => ['title' => '喜神五行', 'url' => url('sm_xishensy', ['group' => 'sm_xishensy'])],
        ];
        $order      = $this->getOrder('id asc');
        $map        = $this->getMap();
        $data_list  = Db::name('sm_xishensy')
            ->where($map)
            ->order($order)
            ->paginate(10);
        $rightBtn = [
            'edit'      => ['class' => 'btn btn-sm btn-warning', 'href' => url('sm_xishensy_edit', ['id' => '__id__'])],
        ];
        return ZBuilder::make('table')
            ->setTabNav($list_tab, $group)
            ->hideCheckbox()
            ->setSearchArea([
                ['text:1', 'id', 'ID'],
                ['text:2', 'xishen', '五行'],
            ])
            ->addColumns([
                ['id', 'ID'],
                ['xishen', '五行', 'text'],
                ['sy', '说明', 'text'],
                ['create_time', '创建时间', 'datetime'],
                ['update_time', '更新时间', 'datetime'],
                ['right_button', '操作', 'btn'],
            ])
            ->setRowList($data_list)
            ->addRightButtons($rightBtn)
            ->setColumnWidth([
                'id'            => 15,
                'xishen'        => 25,
                'create_time'   => 40,
                'update_time'   => 40,
                'right_button'  => 25,
            ])
            ->setExtraCss($this->css)
            ->fetch();
    }
    
    public function sm_xishensy_edit($id = 0)
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            $data['update_time'] = time();
            if (!$data['sy']) {
                $this->error('说明不能为空');
            }
            $is_update = Db::name('sm_xishensy')->where('id', $data['id'])->update($data);
            if ($is_update) {
                $this->success('编辑成功', url('sm_xishensy', ['group' => 'sm_xishensy']));
            } else {
                $this->error('编辑失败', url('sm_xishensy', ['group' => 'sm_xishensy']));
            }
        }
        $id   = input('id');
        $data = Db::name('sm_xishensy')->where('id', '=', $id)->find();
        if (empty($data)) {
            $this->error('未查到当前数据', url('sm_xishensy'),);
        }
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'xishen', '五行', '', '', '', 'disabled'],
                ['textarea', 'sy', '说明'],
                ['hidden', 'id', $id],
            ])
            ->setFormData($data)
            ->layout(['xishen' => 3])
            ->fetch();
    }

    //企业测吉凶
    public function sm_company()
    {
        $order      = $this->getOrder('id asc');
        $map        = $this->getMap();
        $data_list  = Db::name('sm_company')
            ->where($map)
            ->order($order)
            ->paginate(20);
        $rightBtn = [
            'edit'      => ['class' => 'btn btn-sm btn-warning', 'href' => url('sm_company_edit', ['id' => '__id__'])],
        ];
        return ZBuilder::make('table')
            ->setPageTitle('企业名称测吉凶（算法共提供数值到81,超过无效）')
            ->hideCheckbox()
            ->setTableName('sm_company')
            ->addOrder('id,num') // 添加排序
            ->addFilter('j1,j2')
            ->setSearchArea([
                ['text:1', 'id', 'ID'],
                ['text:2', 'j1', '运势'],
                ['text:2', 'j2', '运势说明'],
            ])
            ->addColumns([
                ['id', 'ID'],
                ['num', '数值', 'text'],
                ['j1', '运势', 'text'],
                ['j2', '运势', 'text'],
                ['t1', '描述1', 'text'],
                ['t2', '描述2', 'text'],
                ['c1', '解析1', 'text'],
                ['c2', '解析2', 'text'],
                ['update_time', '更新时间', 'datetime'],
                ['right_button', '操作', 'btn'],
            ])
            ->setRowList($data_list)
            ->addRightButtons($rightBtn)
            ->setColumnWidth([
                'id'            => 30,
                'num'           => 30,
                'update_time'   => 70,
                'right_button'  => 25,
            ])
            ->setExtraCss($this->css)
            ->fetch();
    }

    public function sm_company_edit($id = 0)
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            $data['update_time'] = time();
            // if (!$data['num']) {
            //     $this->error('数值不能为空');
            // }
            if (!$data['j1']) {
                $this->error('运势不能为空');
            }
            if (!$data['j2']) {
                $this->error('运势说明不能为空');
            }
            if (!$data['t1']) {
                $this->error('描述1不能为空');
            }
            if (!$data['t2']) {
                $this->error('描述2不能为空');
            }
            if (!$data['c1']) {
                $this->error('解析1不能为空');
            }
            if (!$data['c2']) {
                $this->error('解析2不能为空');
            }
            $is_update = Db::name('sm_company')->where('id', $data['id'])->update($data);
            if ($is_update) {
                $this->success('编辑成功', url('sm_company'));
            } else {
                $this->error('编辑失败', url('sm_company'));
            }
        }
        $id   = input('id');
        $data = Db::name('sm_company')->where('id', '=', $id)->find();
        if (empty($data)) {
            $this->error('未查到当前数据', url('sm_company'),);
        }
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'num', '数值', '', '', '', 'disabled'],
                ['text', 'j1', '运势'],
                ['text', 'j2', '运势说明'],
                ['textarea', 't1', '描述1'],
                ['textarea', 't2', '描述2'],
                ['textarea', 'c1', '解析1'],
                ['textarea', 'c2', '解析2'],
                ['hidden', 'id', $id],
            ])
            ->setFormData($data)
            ->layout(['num' => 3, 'j1' => 3, 'j2' => 3])
            ->fetch();
    }

    //号码测吉凶
    public function sm_tel()
    {
        $order      = $this->getOrder('id asc');
        $map        = $this->getMap();
        $data_list  = Db::name('sm_tel')
            ->where($map)
            ->order($order)
            ->paginate(20);
        $rightBtn = [
            'edit'      => ['class' => 'btn btn-sm btn-warning', 'href' => url('sm_tel_edit', ['id' => '__id__'])],
        ];
        return ZBuilder::make('table')
            ->setPageTitle('号码测吉凶（算法共提供数值到81,超过无效）')
            ->hideCheckbox()
            ->setTableName('sm_tel')
            ->addOrder('id,num') // 添加排序
            ->addFilter('jx')
            ->setSearchArea([
                ['text:1', 'id', 'ID'],
                ['text:2', 'jx', '吉凶'],
                ['text:2', 'title', '标题'],
            ])
            ->addColumns([
                ['id', 'ID'],
                ['num', '数值', 'text'],
                ['jx', '吉凶', 'text'],
                ['title', '标题', 'text'],
                ['content', '内容', 'text'],
                ['update_time', '更新时间', 'datetime'],
                ['right_button', '操作', 'btn'],
            ])
            ->setRowList($data_list)
            ->addRightButtons($rightBtn)
            ->setColumnWidth([
                'id'            => 20,
                'num'           => 25,
                'update_time'   => 70,
                'right_button'  => 25,
            ])
            ->setExtraCss($this->css)
            ->fetch();
    }

    public function sm_tel_edit($id = 0)
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            $data['update_time'] = time();
            // if (!$data['num']) {
            //     $this->error('数值不能为空');
            // }
            if (!$data['jx']) {
                $this->error('吉凶不能为空');
            }
            if (!$data['title']) {
                $this->error('标题不能为空');
            }
            if (!$data['content']) {
                $this->error('内容不能为空');
            }
            $is_update = Db::name('sm_tel')->where('id', $data['id'])->update($data);
            if ($is_update) {
                $this->success('编辑成功', url('sm_tel'));
            } else {
                $this->error('编辑失败', url('sm_tel'));
            }
        }
        $id   = input('id');
        $data = Db::name('sm_tel')->where('id', '=', $id)->find();
        if (empty($data)) {
            $this->error('未查到当前数据', url('sm_tel'),);
        }
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'num', '数值', '', '', '', 'disabled'],
                ['text', 'jx', '吉凶'],
                ['text', 'title', '标题'],
                ['textarea', 'content', '内容'],
                ['hidden', 'id', $id],
            ])
            ->setFormData($data)
            ->layout(['num' => 3, 'jx' => 3, 'title' => 7])
            ->fetch();
    }

    //中华字库
    public function qm_zidians()
    {
        $order      = $this->getOrder('id asc');
        $map        = $this->getMap();
        $data_list  = Db::name('qm_zidians')
            ->where('is_del', 1)
            ->where($map)
            ->order($order)
            ->paginate(100);
        $topBtn    = [
            //'add'       => ['class' => 'btn btn-warning', 'href' => url('qm_zidians_add')],
        ];
        $rightBtn = [

            'edit'      => ['class' => 'btn btn-sm btn-warning', 'href' => url('qm_zidians_edit', ['id' => '__id__'])],
            'look'      => ['title' => '查看', 'icon' => 'fa fa-eye', 'class' => 'btn btn-sm btn-success', 'href' => url('qm_zidians_look', ['id' => '__id__'])],
            //'delete'    => ['class' => 'btn btn-sm btn-danger ajax-get confirm', 'href' => url('qm_zidians_del', ['id' => '__id__'])],
        ];
        return ZBuilder::make('table')
            ->setPageTitle('中华字库,根据国标实时更新')
            ->hideCheckbox()
            ->setTableName('qm_zidians')
            ->addOrder('id,bihua') // 添加排序
            ->addFilter('wuxing,level,star')
            ->setSearchArea([
                ['text:1', 'id', 'ID'],
                ['text:2', 'zi', '字'],
                ['text:2', 'pinyin', '拼音'],
                ['select:2', 'tone_value', '声调', '', '', [1 => '一声', 2 => '二声', 3 => '三声', 4 => '四声']],
                ['select:2', 'is_bug', '生僻字', '', '', [0 => '否', 1 => '是']],
                ['text:3', 'ziyi', '字义', 'like'],
            ])
            ->addColumns([
                ['id', 'ID'],
                ['pinyin2', '拼音', 'text'],
                ['tone', '声调', 'text'],
                ['zi', '字', 'text'],
                ['wuxing', '五行', 'text'],
                ['level', '等级', 'text'],
                ['bihua', '笔画', 'text'],
                ['fan_bihua', '繁笔画', 'text'],
                ['shu_bihua', '康熙笔画', 'text'],
                ['star', '星级', 'text'],
                ['is_bug', '生僻字', 'status', '', [0 => '否', 1 => '是']],
                ['ziyi', '字义', 'popover', 7],
                ['update_time', '更新时间', 'datetime'],
                ['right_button', '操作', 'btn'],
            ])
            ->setRowList($data_list)
            ->addTopButtons($topBtn)
            ->addRightButtons($rightBtn)
            ->setColumnWidth([
                'id'            => 45,
                'pinyin2'       => 35,
                'tone'          => 35,
                'zi'            => 30,
                'wuxing'        => 40,
                'level'         => 40,
                'bihua'         => 40,
                'fan_bihua'     => 45,
                'shu_bihua'     => 45,
                'star'          => 50,
                'is_bug'        => 40,
                'update_time'   => 70,
                'right_button'  => 60,
            ])
            ->setExtraCss($this->css)
            ->fetch();
    }

    public function qm_zidians_edit($id = 0)
    {
        $tone    = [0 => '轻声', 1 => '一声', 2 => '二声', 3 => '三声', 4 => '四声'];
        $wuxing  = [1 => '金', 2 => '木', 3 => '水', 4 => '火', 5 => '土'];
        $level   = [1 => '一级', 2 => '二级', 3 => '三级', 4 => '四级'];
        $star    = [1 => '★', 2 => '★★', 3 => '★★★', 4 => '★★★★', 5 => '★★★★★'];
        if ($this->request->isPost()) {
            $data = input('post.');
            $data['update_time'] = time();
            if (!isset($tone[$data['tone_value']])) {
                $this->error('请选择声调');
            }
            $data['tone']   = $tone[$data['tone_value']];
            if (!isset($wuxing[$data['wuxing_value']])) {
                $this->error('请选择五行');
            }
            $data['wuxing'] = $wuxing[$data['wuxing_value']];
            if (!isset($level[$data['level_value']])) {
                $this->error('请选择等级');
            }
            $data['level']  = $level[$data['level_value']];
            if (!isset($star[$data['star_value']])) {
                $this->error('请选择星级');
            }
            $data['star']   = $star[$data['star_value']];
            if (!$data['zi']) {
                $this->error('请输入字');
            }
            if (!$data['pinyin']) {
                $this->error('请输入拼音');
            }
            if (!$data['pinyin2']) {
                $this->error('请输入拼音+声调');
            }
            if (!$data['bihua']) {
                $this->error('请输入笔画');
            }
            if (!$data['star']) {
                $this->error('请输入星级');
            }
            if (!$data['ziyi']) {
                $this->error('请输入字义');
            }
            $is_update = Db::name('qm_zidians')->where('id', $data['id'])->update($data);
            if ($is_update) {
                $this->success('编辑成功', url('qm_zidians'));
            } else {
                $this->error('编辑失败', url('qm_zidians'));
            }
        }
        $id   = input('id');
        $data = Db::name('qm_zidians')->where('id', '=', $id)->find();
        if (empty($data)) {
            $this->error('未查到当前数据', url('qm_zidians'),);
        }

        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'zi', '字'],
                ['text', 'zi_fan', '繁体字'],
                ['text', 'pinyin', '拼音'],
                ['text', 'pinyin2', '拼音+声调'],
                ['radio', 'tone_value', '声调', '', $tone],
                ['radio', 'wuxing_value', '五行', '', $wuxing],
                ['radio', 'level_value', '等级', '', $level],
                ['number', 'bihua', '笔画'],
                ['number', 'fan_bihua', '繁体笔画'],
                ['number', 'shu_bihua', '康熙字典笔画'],
                ['radio', 'star_value', '星级', '', $star],
                ['radio', 'is_bug', '生僻字', '', [0 => '否', 1 => '是']],
                ['textarea', 'ziyi', '字义'],
                ['hidden', 'id', $id],
            ])
            ->setFormData($data)
            ->layout(['zi' => 3, 'zi_fan' => 3, 'pinyin' => 3, 'pinyin2' => 3])
            ->fetch();
    }

    //查看
    public function qm_zidians_look($id = 0)
    {
        $id = input('id');
        $data = Db::name('qm_zidians')->where('id', $id)->find();
        if (empty($data)) {
            $this->error('未查到当前数据', url('qm_zidians'),);
        }
        $zidians_data = [
            ['字[2]', '拼音+声调[3]', '声母', '韵母'], //,'等级','笔画','繁体笔画','康熙字典笔画','星级','字义'
            [
                '简体字：&nbsp;' . $data['zi'],
                '繁体字：&nbsp;' . $data['zi_fan'],
                '拼音：&nbsp;' . $data['pinyin2'] . '<br/>' .
                    '拼音2：&nbsp;' . $data['pinyin'] . '<br/>' .
                    '声调：&nbsp;' . $data['tone'] . '[3]',
                $data['shengmu'],
                $data['yumu']
            ],
            [
                '笔画：&nbsp;' . $data['bihua'] . '[2]',
                '繁体笔画：&nbsp;' . $data['fan_bihua'] . '[2]',
                '康熙字典笔画：&nbsp;' . $data['shu_bihua'] . '[3]'
            ],
            [
                '五行：&nbsp;' . $data['wuxing'] . '[2]',
                '等级：&nbsp;' . $data['level'] . '[2]',
                '星级：&nbsp;' . $data['level'] . '[3]'
            ],
            [
                '推荐星级(星级越高越推荐人名用)：&nbsp;' . $data['star'] . '[2]',
                '字义：&nbsp;' . $data['ziyi'] . '[5]'
            ],
        ];
        return ZBuilder::make('form')
            ->addFormItems([
                ['complexTable', 'list_user', '字典内容', $zidians_data]
            ])
            ->hideBtn(['submit'])
            //->setFormData($zidians_data)
            ->fetch();
    }

    //小六壬
    public function sm_xliuren()
    {
        $order      = $this->getOrder('id asc');
        $map        = $this->getMap();
        $data_list  = Db::name('sm_xliuren')
            ->where($map)
            ->order($order)
            ->paginate(6);
        $rightBtn = [
            'edit'      => ['class' => 'btn btn-sm btn-warning', 'href' => url('sm_xliuren_edit', ['id' => '__id__'])],
        ];
        return ZBuilder::make('table')
            ->setPageTitle('小六壬（遇事不决，可速断）')
            ->hideCheckbox()
            ->setTableName('sm_xliuren')
            ->addOrder('id') // 添加排序
            ->setSearchArea([
                ['text:1', 'id', 'ID'],
                ['text:2', 'f_name', '卦名'],
                ['text:2', 'f_value', '吉凶'],
            ])
            ->addColumns([
                ['id', 'ID'],
                ['f_name', '卦名', 'text'],
                ['f_value', '吉凶', 'text'],
                ['f_exp_title', '标题', 'text'],
                ['f_exp_desc', '描述', 'text'],
                ['f_exp_note', '内容', 'text'],
                ['update_time', '更新时间', 'datetime'],
                ['right_button', '操作', 'btn'],
            ])
            ->setRowList($data_list)
            ->addRightButtons($rightBtn)
            ->setColumnWidth([
                'id'            => 20,
                'f_name'        => 30,
                'f_value'       => 25,
                'update_time'       => 45,
                'right_button'  => 25,
            ])
            ->setExtraCss($this->css)
            ->fetch();
    }

    public function sm_xliuren_edit()
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            $data['update_time'] = time();
            if (!$data['f_exp_title']) {
                $this->error('请输入标题');
            }
            if (!$data['f_exp_desc']) {
                $this->error('请输入描述');
            }
            if (!$data['f_exp_note']) {
                $this->error('请输入内容');
            }
            $res = Db::name('sm_xliuren')->where('id', $data['id'])->update($data);
            if ($res) {
                $this->success('更新成功', url('sm_xliuren'),);
            } else {
                $this->error('更新失败', url('sm_xliuren'),);
            }
        }
        $id   = input('id');
        $data = Db::name('sm_xliuren')->where('id', $id)->find();
        if (empty($data)) {
            $this->error('未查到当前数据', url('sm_xliuren'),);
        }
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'f_name', '卦名', '', '', '', 'disabled'],
                ['text', 'f_value', '吉凶', '', '', '', 'disabled'],
                ['text', 'f_exp_title', '标题'],
                ['textarea', 'f_exp_desc', '描述'],
                ['textarea', 'f_exp_note', '内容'],
                ['textarea', 'f_exp_content', '补充说明'],
                ['hidden', 'id', $id],
            ])
            ->setFormData($data)
            ->layout(['f_name' => 3, 'f_value' => 3, 'f_exp_title' => 7, 'f_exp_desc' => 6, 'f_exp_note' => 6, 'f_exp_content' => 6])
            ->fetch();
    }


    public function batch_update()
    {
        $table_data = ['sm_sxgx' => '生肖个性', 'zodiac_m' => '生肖月运势','star_sign_aq'=>'12星座爱情数据','star_sign_cy'=>'12星座财富数据','star_sign_sy'=>'12星座事业数据'];
        
        if ($this->request->isPost()) {
            $data           = SafeFilterItem(input('post.'));
            $table          = $data['table'];
            $search_field   = $data['search_field'];
            $update_field   = $data['update_field'];
            if (!$table || !$search_field || !$update_field) {
                $this->error('请填写完整数据');
            }
            if (!array_key_exists($table, $table_data)) {
                $this->error('数据选择有误');
            }
            $base_table    =  'w_' . $table;
            // 获取表的所有字段
            $fields = db()->query("SHOW COLUMNS FROM `{$base_table}`");
            $fieldNames = [];
            foreach ($fields as $field) {
                $fieldNames[] = $field['Field'];
            }

            // 构建更新条件和数据
            $map = [];
            // 对每个字段创建OR条件（包含搜索内容）
            foreach ($fieldNames as $field) {
                if ($field == 'id' || $field == 'create_time' || $field == 'update_time') {
                    continue;
                }
                $map[] = "`{$field}` LIKE '%{$search_field}%'";
            }
            // 使用OR关系连接所有字段条件
            $where      = implode(' OR ', $map);
            $result     = Db::name($table)->where($where)->select();
            $updateData = [];
            if (!empty($result)) {
                foreach ($result as $key => $val) {
                    foreach ($val as $k => $v) {
                        if (mb_strpos($v, $search_field) !== false && $k != 'id') {
                            $newValue = str_replace($search_field, $update_field, $v);
                            $updateData[] = [
                                'id' => $val['id'],
                                'field' => $k,
                                'new_value' => $newValue
                            ];
                        }
                    }
                }
            }
            // 执行批量更新
            $updateCount = 0;
            if (!empty($updateData)) {
                // 按字段分组处理
                $fieldGroups = [];
                foreach ($updateData as $item) {
                    $fieldGroups[$item['field']][] = $item;
                }

                // 开始事务
                Db::startTrans();
                foreach ($fieldGroups as $field => $items) {
                    $caseSql = [];
                    $ids = [];
                    foreach ($items as $item) {
                        $caseSql[] = "WHEN id = '{$item['id']}' THEN '{$item['new_value']}'";
                        $ids[] = $item['id'];
                    }
                    $idsStr = implode(',', array_unique($ids));
                    $sql    = "UPDATE `{$base_table}` SET `{$field}` = CASE " . implode(' ', $caseSql) . " ELSE `{$field}` END WHERE id IN ({$idsStr})";
                    $result = Db::execute($sql);
                    if ($result) {
                        $updateCount += count(array_unique($ids));
                    }
                }
                if ($updateCount) {
                    Db::commit();
                    $this->success('更新成功，共修改 ' . $updateCount . ' 条记录');
                } else {
                    Db::rollback();
                    $this->error('更新失败：未知原因');
                }
            } else {
                $this->success('没有找到需要更新的记录');
            }
        }
        $table      = input('table');
        return ZBuilder::make('form')
            ->setPageTitle('批量更新数据（通常用于更新年月信息数据,请勿盲目使用)')
            ->addFormItems([
                ['select', 'table', '模块', '', $table_data, $table],
                ['text', 'search_field', '搜素的内容'],
                ['text', 'update_field', '要替换的内容'],
            ])
            ->addHidden('id')
            ->layout(['table' => 4, 'search_field' => 4, 'update_field' => 4])
            ->fetch();
    }


    /**
     * 快速编辑
     * @param array

     * @return mixed
     */
    public function quickEdit($record = [])
    {
        if ($this->request->isPost()) {
            $table_token = input('post._t');
            $table_data  = session($table_token);
            if (!isset($table_data['action']) || $table_data['action'] == '') {
                $this->error('找不到当前数据');
            }
            $table       = $table_data['action'];
            $id          = input('post.pk', '');
            $field       = input('post.name', '');
            $value       = input('post.value', '');
            $find_data   = Db::name($table)->where('id', '=', $id)->find();
            if (empty($find_data)) {
                $this->error('找不到当前数据');
            }
            $config      = Db::name($table)->where('id', $id)->value($field);
            if ($value == $config) {
                $this->error('未检测需要修改的数据');
            }
            $res = Db::name($table)->where('id', '=', $id)->update([$field => $value]);
            if ($res) {
                $this->success('设置成功');
            } else {
                $this->error('设置失败');
            }
        }
    }
}
