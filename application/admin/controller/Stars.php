<?php
//星座控制器
namespace app\admin\controller;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class Stars extends Admin
{
    protected $css = <<<EOF
        <style>
            th,tr{
                text-align: center;
            }
        </style>
EOF;
    //星座基本属性
    public function star_sign_base(){
        $order      = $this->getOrder('sort asc');
        $map        = $this->getMap();
        $data_list  = Db::name('star_sign_base')
            ->where($map)
            ->order($order)
            ->paginate(12)->each(function($item){
                $item['base_info1'] = '个人特点:'.$item['td'].'<br>四象属性:'.$item['sixiang']
                .'<br>阴阳:'.$item['yinyang']
                .'<br>特征:'.$item['strong'].'<br>守护星:'.$item['shouhu'];
                $item['base_info2'] = '吉祥饰物:'.$item['jixiang'].'<br>开运金属:'.$item['jinshu']
                .'<br>幸运数字:'.$item['number'].'<br>幸运颜色:'.$item['yanse']
                .'<br>性格关键字:'.$item['keyword'];
                return $item;
            });
        $rightBtn = [
            'edit'      => ['class' => 'btn btn-sm btn-warning', 'href' => url('star_sign_base_edit', ['id' => '__id__'])],
        ];
        return ZBuilder::make('table')
            ->setPageTitle('基本属性')
            ->hideCheckbox()
            ->setTableName('star_sign_base')
            ->addOrder('id') // 添加排序
            ->setSearchArea([
                //['text:1', 'id', 'ID'],
                ['text:2', 'xz', '星座'],
                ['text:2', 'td', '个人特点'],
                ['select:2', 'yinyang', '阴阳','','',['阴性'=>'阴性','阳性'=>'阳性']],
            ])
            ->addColumns([
                //['id', 'ID'],
                ['xz', '星座', 'text'],
                ['se_date', '起止日期', 'text'],
                ['text_icon', '文字图标', 'picture'],
                ['icon', '图标素材', 'picture'],
                ['td', '个人特点', 'text'],
                ['gongwei', '宫位', 'text'],
                ['base_info1', '基本信息', 'text'],
                ['base_info2', '补充信息', 'text'],
                // ['sixiang', '四象属性', 'text'],
                // ['yinyang', '阴阳', 'text'],
                // ['strong', '特征', 'text'],
                // ['shouhu', '守护星', 'text'],
                // ['jixiang', '吉祥饰物', 'text'],
                // ['jinshu', '开运金属', 'text'],
                // ['number', '幸运数字', 'text'],
                // ['yanse', '幸运颜色', 'text'],
                // ['keyword', '性格关键字', 'popover'],
                ['xingge', '基础性格', 'popover'],
                ['update_time', '更新时间', 'date'],
                ['right_button', '操作', 'btn'],
            ])
            ->setRowList($data_list)
            ->addRightButtons($rightBtn)
            ->setColumnWidth([
                'id'            => 35,
                'xz'            => 50,
                'se_date'       => 65,
                'text_icon'     => 125,
                'icon'          => 65,
                'td'            => 65,
                'gongwei'       => 65,
                'right_button'  => 80,
            ])
            ->setExtraCss($this->css)
            ->fetch();
    }

    public function star_sign_base_edit($id=0){
        if ($this->request->isPost()) {
            $data = input('post.');
            $data['update_time'] = time();
            if(!$data['td']){
                $this->error('请输入个人特点');
            }
            if(!$data['sixiang']){
                $this->error('请输入四象属性');
            }
            if(!$data['gongwei']){
                $this->error('请输入宫位');
            }
            if(!$data['yinyang']){
                $this->error('请输入阴阳');
            }
            if(!$data['strong']){
                $this->error('请输入特征');
            }
            if(!$data['shouhu']){
                $this->error('请输入守护星');
            }
            if(!$data['jixiang']){
                $this->error('请输入吉祥饰物');
            }
            if(!$data['jinshu']){
                $this->error('请输入开运金属');
            }
            if(!$data['number']){
                $this->error('请输入幸运数字');
            }
            if(!$data['yanse']){
                $this->error('请输入幸运颜色');
            }
            if(!$data['keyword']){
                $this->error('请输入性格关键字');
            }
            if(!$data['xingge']){
                $this->error('请输入基础性格');
            }
            if(!$data['youquedian']){
                $this->error('请输入优缺点');
            }
            if(!$data['youquedian']){
                $this->error('请输入重要星象');
            }
            $res = Db::name('star_sign_base')->where('id', $data['id'])->update($data);
            if ($res) {
                $this->success('更新成功', url('star_sign_base'),);
            } else {
                $this->error('更新失败', url('star_sign_base'),);
            }
        }
        $id   = input('id');
        $data = Db::name('star_sign_base')->where('id', $id)->find();
        if (empty($data)) {
            $this->error('未查到当前数据', url('star_sign_base'),);
        }
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'xz', '星座', '', '', '', 'disabled'],
                ['text', 'se_date', '起止日期'],
                ['image', 'text_icon', '文字图标'],
                ['image', 'icon', '图标素材'],
                ['text', 'td', '个人特点'],
                ['text', 'sixiang', '四象属性'],
                ['text', 'gongwei', '宫位'],
                ['text', 'yinyang', '阴阳'],
                ['text', 'strong', '特征'],
                ['text', 'shouhu', '守护星'],
                ['text', 'jixiang', '吉祥饰物'],
                ['text', 'jinshu', '开运金属'],
                ['number', 'number', '幸运数字'],
                ['text', 'yanse', '幸运颜色'],
                ['textarea', 'keyword', '性格关键字'],
                ['textarea', 'xingge', '基础性格'],
                ['ckeditor', 'youquedian', '优缺点'],
                ['ckeditor', 'zhongyao', '重要星象'],
                ['hidden', 'id', $id],
            ])
            ->setFormData($data)
            ->layout(['xz' => 3, 'td' => 4,'se_date' => 4, 'sixiang' => 3, 'gongwei' => 3, 'yinyang' => 3, 'strong' => 3,
                     'shouhu' => 3, 'jixiang' => 3, 'jinshu' => 3, 'number' => 3, 'yanse' => 3, 'keyword' => 6,
                     'xingge' => 6, 'youquedian' => 6, 'zhongyao' => 6])
            ->fetch();
    }

    public function star_sign_data($group='star_sign_aq'){
        $list_tab = [
            'star_sign_aq'                  => ['title' => '12星座爱情数据', 'url' => url('star_sign_data', ['group' => 'star_sign_aq'])],
            'star_sign_cy'                  => ['title' => '12星座财富数据', 'url' => url('star_sign_data', ['group' => 'star_sign_cy'])],
            'star_sign_sy'                  => ['title' => '12星座事业数据', 'url' => url('star_sign_data', ['group' => 'star_sign_sy'])],
            'star_sign_zh'                  => ['title' => '12星座星级数据', 'url' => url('star_sign_data', ['group' => 'star_sign_zh'])],
            'star_sign_shangsheng_data'     => ['title' => '12星座上升数据', 'url' => url('star_sign_data', ['group' => 'star_sign_shangsheng_data'])],
        ];
        $order      = $this->getOrder('id asc');
        $map        = $this->getMap();
        $data_list  = Db::name($group)
            ->where($map)
            ->order($order)
            ->paginate(100);
        $Search = [
            ['text:1', 'id', 'ID'],
            ['text:2', 'xz', '星座', 'like'],
        ];
        $Columns = [
            ['id', 'ID'],
            ['xz', '星座', 'text'],
        ];
        switch($group){
            case 'star_sign_aq':
                $Columns[] = ['star', '星级', 'text.edit'];
                $Columns[] = ['peidui', '配对', 'text'];
                $Columns[] = ['master', '主被动值', 'text'];
                $Columns[] = ['txt', '简述', 'popover'];
                $Columns[] = ['line', '素材', 'img_url'];
                break;
            case 'star_sign_cy':
                $Columns[] = ['star', '星级', 'text.edit'];
                $Columns[] = ['zong', '总评', 'popover'];
                $Columns[] = ['txt', '简述', 'popover'];
                $Columns[] = ['line', '素材', 'img_url'];

                break;
            case 'star_sign_sy':
                $Columns[] = ['star', '星级', 'text.edit'];
                //$Columns[] = ['guiren', '贵人', 'popover'];
                $Columns[] = ['zong', '总评', 'popover'];
                $Columns[] = ['txt', '简述', 'popover'];
                $Columns[] = ['line', '素材', 'img_url'];
                break;
            case 'star_sign_zh':
                $Columns[] = ['love_on', '爱情', 'text.edit'];
                $Columns[] = ['cash_on', '财富', 'text.edit'];
                $Columns[] = ['job_on', '事业', 'text.edit'];
                break;
        }
        if($group=='star_sign_shangsheng_data'){
            $Search = [
                ['text:1', 'id', 'ID'],
                ['text:2', 'title', '星座', 'like'],
            ];
            $Columns = [
                ['id', 'ID'],
                ['title', '星座', 'text'],
                ['xingge', '性格', 'popover'],
                ['tezhi', '特质', 'popover'],
            ];
        }
        $Columns[] = ['update_time', '更新时间', 'datetime'];
        $Columns[] = ['right_button', '操作', 'btn'];
        $topBtn = [];
        $rightBtn = [
            'edit'      => ['class' => 'btn btn-sm btn-warning', 'href' => url('star_sign_data_edit', ['group' => $group, 'id' => '__id__'])],
        ];
        if($group!='star_sign_zh' && $group!='star_sign_shangsheng_data'){
            $topBtn    = [
                    'access'      => ['title' => '批量替换', 'icon' => 'fa fa-fw fa-key', 'class' => 'btn btn-warning', 'href' => url('sm/batch_update', ['table' => $group])],
                ];
        }
        return ZBuilder::make('table')
            ->setTabNav($list_tab, $group)
            ->hideCheckbox()
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

    public function star_sign_data_edit($group='star_sign_aq', $id=0){
        if ($this->request->isPost()) {
            $data                   = input('post.');
            $data['update_time']    = time();
            if(isset($data['star']) && !$data['star']){
               $this->error('星级数值不能为空');
            }
            if (isset($data['love_on']) && !$data['love_on']) {
                $this->error('爱情数值不能为空');
            }
            if (isset($data['cash_on']) && !$data['cash_on']) {
                $this->error('财富数值不能为空');
            }
            if (isset($data['job_on']) && !$data['job_on']) {
                $this->error('事业数值不能为空');
            }
            $group                  = $data['group'];
            $is_update = Db::name($group)->where('id', '=', $id)->update($data);
            if ($is_update) {
                $this->success('编辑成功', url('star_sign_data', ['group' => $group]));
            } else {
                $this->error('编辑失败', url('star_sign_data', ['group' => $group]));
            }
        }
        $id   = input('id');
        $data = Db::name($group)->where('id', '=', $id)->find();
        if (empty($data)) {
            $this->error('未查到当前数据', url('star_sign_data', ['group' => $group]),);
        }
        $FormItems = [
            ['text', 'xz', '星座', '', '', '', 'disabled'],
        ];
        switch ($group) {
            case 'star_sign_aq':
                $FormItems[] = ['number', 'star', '星级'];
                $FormItems[] = ['text', 'peidui', '配对'];
                $FormItems[] = ['text', 'master', '主被动值'];
                $FormItems[] = ['image', 'line', '素材'];
                $FormItems[] = ['ckeditor', 'txt', '简述'];
                $FormItems[] = ['ckeditor', 'jiyu', '爱情寄语'];
                $FormItems[] = ['ckeditor', 'taohua', '单身桃花'];
                $FormItems[] = ['ckeditor', 'ganqing', '感情运势'];
                break;
            case 'star_sign_cy':
                $FormItems[] = ['number', 'star', '星级'];
                $FormItems[] = ['image', 'line', '素材'];
                $FormItems[] = ['ckeditor', 'gaodian', '财富高点'];
                $FormItems[] = ['ckeditor', 'didian', '低谷时期'];
                $FormItems[] = ['ckeditor', 'txt', '简述'];
                $FormItems[] = ['ckeditor', 'zong', '总评'];
                $FormItems[] = ['ckeditor', 'touzi', '投资'];
                $FormItems[] = ['ckeditor', 'zhichu', '支出'];
                break;
            case 'star_sign_sy':
                $FormItems[] = ['number', 'star', '星级'];
                $FormItems[] = ['image', 'line', '素材'];
                $FormItems[] = ['ckeditor', 'guiren', '贵人'];
                $FormItems[] = ['ckeditor', 'didian', '低谷时期'];
                $FormItems[] = ['ckeditor', 'txt', '简述'];
                $FormItems[] = ['ckeditor', 'zong', '总评'];
                $FormItems[] = ['ckeditor', 'work', '学习工作'];
                $FormItems[] = ['ckeditor', 'zy', '职业发展'];
                break;
            case 'star_sign_zh':
                $FormItems = [
                    $FormItems[] = ['number', 'love_on', '爱情'],
                    $FormItems[] = ['number', 'cash_on', '财富'],
                    $FormItems[] = ['number', 'job_on', '事业'],
                ];
                break;
        }
        if($group=='star_sign_shangsheng_data'){
            $FormItems = [
               ['text', 'title', '星座', '', '', '', 'disabled'],
               ['text', 't', 'SEO标题'],
               ['text', 'k', 'SEO关键字'],
               ['text', 'd', 'SEO描述'],
               ['ckeditor', 'xingge', '性格特点'],
               ['ckeditor', 'jianjie', '简介'],
               ['ckeditor', 'tezhi', '特质'],
               ['ckeditor', 'fgmd', '行事风格（盲点）'],
            ];
        }
        $FormItems[]    = ['hidden', 'group', $group];
        $FormItems[]    = ['hidden', 'id', $id];
        return ZBuilder::make('form')
            ->addFormItems($FormItems)
            ->setFormData($data)
            ->layout(['xz' => 3, 'star' => 3,'title' => 9,'t' => 4,'k' => 4,'d' => 4,'peidui'=>3,'master'=>3])
            ->fetch();
    }

    public function peidui_data($tid=388){
          $list_tab = [
            388    => ['title' => '血型配对', 'url' => url('peidui_data', ['tid' => 388])],
            385    => ['title' => '星座配对', 'url' => url('peidui_data', ['tid' => 385])],
            386    => ['title' => '生肖配对', 'url' => url('peidui_data', ['tid' => 386])],
            461    => ['title' => '星座+血型测算', 'url' => url('peidui_data', ['tid' => 461])],
            462    => ['title' => '生肖+血型测算', 'url' => url('peidui_data', ['tid' => 462])],
            463    => ['title' => '星座+生肖测算', 'url' => url('peidui_data', ['tid' => 463])],
        ];
         $order      = $this->getOrder('id asc');
        $map        = $this->getMap();
        $data_list  = Db::name('peidui_data')
            ->where('tid',$tid)
            ->where($map)
            ->order($order)
            ->paginate(100);
         $Search = [
            ['text:1', 'id', 'ID'],
            ['text:2', 'title', '标题', 'like'],
        ];
        $Columns = [
            ['id', 'ID'],
            ['title', '标题', 'text'],
            ['t1', '目标A', 'text'],
            ['t2', '目标B', 'text'],
            ['shorttxt', '结果', 'popover'],
            ['update_time', '更新时间', 'date'],
            ['right_button', '操作', 'btn'],
        ];
        $rightBtn = [
            'edit'      => ['class' => 'btn btn-sm btn-warning', 'href' => url('peidui_data_edit', ['id' => '__id__','tid'=>$tid])],
        ];
        return ZBuilder::make('table')
            ->setTabNav($list_tab, $tid)
            ->hideCheckbox()
            ->setSearchArea($Search)
            ->addColumns($Columns)
            ->setRowList($data_list)
            ->addRightButtons($rightBtn)
            // ->setColumnWidth([
            //     'weight'        => 20,
            //     'update_time'   => 30,
            //     'right_button'  => 20,
            // ])
            ->setExtraCss($this->css)
            ->fetch();
    }

    public function peidui_data_edit($id=0,$tid=388){
        if ($this->request->isPost()) {
            $data = input('post.');
            $data['update_time'] = time();
            if (!$data['title']) {
                $this->error('请输入标题');
            }
            if (!$data['content']) {
                $this->error('请输入描述');
            }
            if (!$data['shorttxt']) {
                $this->error('请输入内容');
            }
            $res = Db::name('peidui_data')->where('id', $data['id'])->where('tid',$tid)->update($data);
            if ($res) {
                $this->success('更新成功', url('peidui_data', ['tid' => $tid]),);
            } else {
                $this->error('更新失败', url('peidui_data', ['tid' => $tid]),);
            }
        }
        $id   = input('id');
        $data = Db::name('peidui_data')->where('id', $id)->where('tid',$tid)->find();
        if (empty($data)) {
            $this->error('未查到当前数据', url('peidui_data', ['tid' => $tid]),);
        }
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 't1', '目标A', '', '', '', 'disabled'],
                ['text', 't2', '目标B', '', '', '', 'disabled'],
                ['text', 'title', '标题'],
                ['ckeditor', 'content', '内容'],
                ['ckeditor', 'shorttxt', '结果'],
                ['hidden', 'id', $id],
            ])
            ->setFormData($data)
            ->layout(['t1' => 3, 't2' => 3, 'title' => 7, 'content' => 6, 'shorttxt' => 6])
            ->fetch();
    }


}