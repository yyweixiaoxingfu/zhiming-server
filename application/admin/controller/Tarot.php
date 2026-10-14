<?php
//塔罗牌控制器
namespace app\admin\controller;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class Tarot extends Admin
{
    protected $css = <<<EOF
        <style>
            th,tr{
                text-align: center;
            }
        </style>
EOF;

    public function tarot_yunshi()
    {
        $order      = $this->getOrder('pid asc');
        $map        = $this->getMap();
        $data_list  = Db::name('tarot_yunshi')
            ->where($map)
            ->order($order)
            ->paginate(50)
            ->each(function ($item) {
                if (!$item['img']) {
                    $item['img'] = '/uploads/images/taluo/images/' . $item['pid'] . '_' . $item['zf'] . '.jpg';
                }
                return $item;
            });
        $topBtn = [
            'add' => ['class' => 'btn btn-sm btn-success', 'href' => url('tarot_yunshi_add')],
        ];
        $rightBtn = [
            'edit'      => ['class' => 'btn btn-sm btn-warning', 'href' => url('tarot_yunshi_edit', ['id' => '__id__'])],
            'delete'    => ['class' => 'btn btn-sm btn-danger ajax-get confirm', 'href' => url('tarot_yunshi_del', ['id' => '__id__'])],
        ];
        $zf_data  = [0 => '逆位', 1 => '正位'];
        $lie_data = [1 => '爱情', 2 => '财运', 3 => '事业'];
        return ZBuilder::make('table')
            ->setPageTitle('塔罗牌占卜-未来运势大揭秘？')
            ->hideCheckbox()
            ->setTableName('tarot_yunshi')
            ->addOrder('id') // 添加排序
            ->setSearchArea([
                ['text:1', 'id', 'ID'],
                ['text:2', 'title', '牌名'],
                ['select:2', 'zf', '正逆位', '', '', $zf_data],
                ['select:2', 'lie', '列', '', '', $lie_data],
            ])
            ->addColumns([
                ['id', 'ID'],
                ['title', '牌名', 'text'],
                ['pid', '牌序', 'text'],
                ['img', '素材', 'img_url'],
                ['zf', '正逆位', 'status', '', $zf_data],
                ['lie', '列', 'status', '', $lie_data],
                ['update_time', '更新时间', 'date'],
                ['right_button', '操作', 'btn'],
            ])
            ->setRowList($data_list)
            ->addRightButtons($rightBtn)
            ->addTopButtons($topBtn)
            ->setColumnWidth([
                'id'            => 50,
            ])
            ->setExtraCss($this->css)
            ->fetch();
    }
    //新增
    public function tarot_yunshi_add()
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            $data['update_time'] = time();
            if (!$data['title']) {
                $this->error('牌名不能为空');
            }
            if (!$data['pid']) {
                $this->error('牌的序号不能为空');
            }
            if (!in_array($data['zf'], [0, 1])) {
                $this->error('正逆位不能为空');
            }
            $res = Db::name('tarot_yunshi')->where('pid', $data['pid'])->where('zf', $data['zf'])->find();
            if ($res) {
                $this->error('该牌已存在');
            }
            if (!in_array($data['lie'], [1, 2, 3])) {
                $this->error('列不能为空');
            }
            // if (!$data['img']) {
            //     $this->error('素材不能为空');
            // }
            if (!$data['des']) {
                $this->error('牌面翻译不能为空');
            }
            $is_insert = Db::name('tarot_yunshi')->insert($data);
            if ($is_insert) {
                $this->success('新增成功', 'tarot_yunshi');
            } else {
                $this->error('新增失败');
            }
        }
        $zf_data  = [0 => '逆位', 1 => '正位'];
        $lie_data = [1 => '爱情', 2 => '财运', 3 => '事业'];
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'title', '牌名'],
                ['number', 'pid', '牌的序号（1-22）', '不能重复，否则影响测算结果'],
                ['radio', 'zf', '正逆位', '', $zf_data, 0],
                ['radio', 'lie', '列', '', $lie_data, 1],
                ['image', 'img', '素材'],
                ['ckeditor', 'des', '牌面翻译'],
                ['ckeditor', 'c1', '爱情观、正财和偏财、事业发展'],
                ['ckeditor', 'c2', '新恋情、未来财运、未来事业发展'],
                ['ckeditor', 'c3', '命定之人'],
                ['ckeditor', 'c4', '未来感情'],
            ])
            ->layout(['title' => 5, 'pid' => 5, 'zf' => 3, 'lie' => 3, 'des' => 6, 'c1' => 6, 'c2' => 6, 'c3' => 6, 'c4' => 6])
            ->fetch();
    }
    //编辑
    public function tarot_yunshi_edit($id = 0)
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            $data['update_time'] = time();
            if (!$data['title']) {
                $this->error('牌名不能为空');
            }
            if (!$data['pid']) {
                $this->error('牌的序号不能为空');
            }
            if (!in_array($data['zf'], [0, 1])) {
                $this->error('正逆位数据有误');
            }
            $res = Db::name('tarot_yunshi')->where('pid', $data['pid'])->where('zf', $data['zf'])->where('id', '<>', $id)->find();
            if ($res) {
                $this->error('该牌已存在');
            }
            if (!in_array($data['lie'], [1, 2, 3])) {
                $this->error('列不能为空');
            }
            // if (!$data['img']) {
            //     $this->error('素材不能为空');
            //}
            if (!$data['des']) {
                $this->error('牌面翻译不能为空');
            }
            $is_update = Db::name('tarot_yunshi')->where('id', $id)->update($data);
            if ($is_update) {
                $this->success('编辑成功', 'tarot_yunshi');
            } else {
                $this->error('编辑失败');
            }
        }
        $id = input('id');
        $data = Db::name('tarot_yunshi')->where('id', $id)->find();
        if (!$data) {
            $this->error('数据不存在');
        }
        $zf_data  = [0 => '逆位', 1 => '正位'];
        $lie_data = [1 => '爱情', 2 => '财运', 3 => '事业'];
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'title', '牌名'],
                ['number', 'pid', '牌的序号（1-22）', '不能重复，否则影响测算结果'],
                ['radio', 'zf', '正逆位', '', $zf_data],
                ['radio', 'lie', '列', '', $lie_data],
                ['image', 'img', '素材'],
                ['ckeditor', 'des', '牌面翻译'],
                ['ckeditor', 'c1', '爱情观、正财和偏财、事业发展'],
                ['ckeditor', 'c2', '新恋情、未来财运、未来事业发展'],
                ['ckeditor', 'c3', '命定之人'],
                ['ckeditor', 'c4', '未来感情'],
                ['hidden', 'id', $id]
            ])
            ->setFormData($data)
            ->layout(['title' => 5, 'pid' => 5, 'zf' => 3, 'lie' => 3, 'des' => 6, 'c1' => 6, 'c2' => 6, 'c3' => 6, 'c4' => 6])
            ->fetch();
    }
    //删除
    public function tarot_yunshi_del($id = 0)
    {
        $id = input('id');
        $data = Db::name('tarot_yunshi')->where('id', $id)->find();
        if (!$data) {
            $this->error('数据不存在');
        }
        $res = Db::name('tarot_yunshi')->where('id', $id)->delete();
        if ($res) {
            $this->success('删除成功', 'tarot_yunshi');
        } else {
            $this->error('删除失败');
        }
    }

    //趣味塔罗牌
    public function tarot_aiqing($group = 'tarot_aiqing')
    {
        $list_tab = [
            'tarot_aiqing'                  => ['Tips'=>'塔罗牌占卜-爱情运势大揭秘，塔罗占卜你的爱情运势？','title' => '爱情运势', 'url' => url('tarot_aiqing', ['group' => 'tarot_aiqing'])],
            'tarot_anlian'                  => ['Tips'=>'塔罗牌占卜-你暗恋的人也喜欢你吗？','title' => '暗恋的人', 'url' => url('tarot_aiqing', ['group' => 'tarot_anlian'])],
            'tarot_bieren'                  => ['Tips'=>'塔罗牌占卜-你喜欢的人心里有想着别人吗？','title' => '是否有别人', 'url' => url('tarot_aiqing', ['group' => 'tarot_bieren'])],
            'tarot_fenshou'                 => ['Tips'=>'塔罗牌占卜-你和ta该不该分手？','title' => '是否分手', 'url' => url('tarot_aiqing', ['group' => 'tarot_fenshou'])],
            'tarot_fuhe'                    => ['Tips'=>'塔罗牌占卜-你们有机会复合吗？','title' => '复合', 'url' => url('tarot_aiqing', ['group' => 'tarot_fuhe'])], 
            'tarot_jixu'                    => ['Tips'=>'塔罗牌占卜-你和TA该继续下去吗？','title' => '是否继续', 'url' => url('tarot_aiqing', ['group' => 'tarot_jixu'])], 
            'tarot_tuodan'                  => ['Tips'=>'塔罗牌占卜-3个月内你会脱单吗？','title' => '是否脱单', 'url' => url('tarot_aiqing', ['group' => 'tarot_tuodan'])], 
            'tarot_xinli'                   => ['Tips'=>'塔罗牌占卜-ta的心里有没有你？','title' => '心中是否有你', 'url' => url('tarot_aiqing', ['group' => 'tarot_xinli'])], 
            'tarot_zuihou'                  => ['Tips'=>'塔罗牌占卜-你和ta能走到最后吗？','title' => '走到最后?', 'url' => url('tarot_aiqing', ['group' => 'tarot_zuihou'])], 
        ];
        $zf_data  = [0 => '逆位', 1 => '正位'];
        $lie_data = [1=>1, 2=>2, 3=>3, 4=>4, 5=>5, 6=>6];
        $order      = $this->getOrder('id asc');
        $map        = $this->getMap();
        $data_list  = Db::name($group)
            ->where($map)
            ->order($order)
            ->paginate(100)
            ->each(function ($item) {
                if (!$item['img']) {
                    $item['img'] = '/uploads/images/taluo/images/' . $item['pid'] . '_' . $item['zf'] . '.jpg';
                }
                return $item;
            });
        $Search = [
            ['text:1', 'id', 'ID'],
            ['text:2', 'title', '牌名', 'like'],
            ['select:2', 'zf', '正逆位', '', '', $zf_data],
            ['select:2', 'lie', '列', '', '', $lie_data],
        ];
        $Columns = [
            ['id', 'ID'],
            ['title', '牌名', 'text'],
            ['pid', '牌序', 'text'],
            ['img', '素材', 'img_url'],
            ['zf', '正逆位', 'status', '', $zf_data],
            ['lie', '列', 'status', '', $lie_data],
        ];
        $Columns[] = ['update_time', '更新时间', 'datetime'];
        $Columns[] = ['right_button', '操作', 'btn'];
       $topBtn = [
            'add' => ['class' => 'btn btn-sm btn-success', 'href' => url('tarot_aiqing_add', ['group' => $group])],
        ];
        $rightBtn = [
            'edit'      => ['class' => 'btn btn-sm btn-warning', 'href' => url('tarot_aiqing_edit', ['group' => $group, 'id' => '__id__'])],
            'delete'    => ['class' => 'btn btn-sm btn-danger ajax-get confirm', 'href' => url('tarot_aiqing_del', ['group' => $group, 'id' => '__id__'])],
        ];
        return ZBuilder::make('table')
            ->setPageTips($list_tab[$group]['Tips'])
            ->setTabNav($list_tab, $group)
            ->hideCheckbox()
            ->setSearchArea($Search)
            ->addColumns($Columns)
            ->setRowList($data_list)
            ->addTopButtons($topBtn)
            ->addRightButtons($rightBtn)
            ->setExtraCss($this->css)
            ->fetch();
    }

    public function tarot_aiqing_add($group = 'tarot_aiqing'){
        if ($this->request->isPost()) {
            $data                   = input('post.');
            $data['update_time']    = time();
            if(isset($data['title']) && !$data['title']){
               $this->error('牌名不能为空');
            }
            if (isset($data['pid']) && !$data['pid']) {
                $this->error('牌序数值不能为空');
            }
            if (!in_array($data['zf'], [0, 1])) {
                $this->error('正逆位不能为空');
            }
            // $res = Db::name($group)->where('pid', $data['pid'])->where('zf', $data['zf'])->find();
            // if ($res) {
            //     $this->error('该牌已存在');
            // }
            if (!in_array($data['lie'], [1, 2, 3, 4, 5, 6])) {
                $this->error('列不能为空');
            }
            if(isset($data['des']) && !$data['des']){
                $this->error('描述不能为空');
            }
            if(isset($data['content']) && !$data['content']){
                $this->error('内容不能为空');
            }
            $group     = $data['group'];
            $is_insert = Db::name($group)->insert($data);
            if ($is_insert) {
                $this->success('新增成功', url('tarot_aiqing', ['group' => $group]));
            } else {
                $this->error('新增失败', url('tarot_aiqing', ['group' => $group]));
            }
        }
        $zf_data  = [0 => '逆位', 1 => '正位'];
        $lie_data = [1=>1, 2=>2, 3=>3, 4=>4, 5=>5, 6=>6];
        $FormItems = [
            ['text', 'title', '牌名'],
            ['number', 'pid', '牌的序号（1-22）', '不能重复，否则影响测算结果'],
            ['radio', 'zf', '正逆位', '', $zf_data,0],
            ['radio', 'lie', '列', '',$lie_data,1],
            ['image', 'img', '素材'],
        ];
        $title = '塔罗牌占卜-爱情运势大揭秘，塔罗占卜你的爱情运势？';
        switch ($group) {
            case 'tarot_aiqing':
                $title = '塔罗牌占卜-爱情运势大揭秘，塔罗占卜你的爱情运势？';
                $FormItems[] = ['text', 'des', '指数'];
                $FormItems[] = ['ckeditor', 'content', '内容'];
                break;
            case 'tarot_anlian':
                $title = '塔罗牌占卜-塔罗牌占卜-你暗恋的人也喜欢你吗？';
                $FormItems[] = ['ckeditor', 'content', '内容'];
                break;
            case 'tarot_bieren':
                $title = '塔罗牌占卜-你喜欢的人心里有想着别人吗？';
                $FormItems[] = ['ckeditor', 'des', '描述'];
                $FormItems[] = ['ckeditor', 'content', '内容'];
                break;
            case 'tarot_fenshou':
                $title = '塔罗牌占卜-你和ta该不该分手？';
                $FormItems[] = ['ckeditor', 'c1', '内容'];
                break;
            case 'tarot_fuhe':
                $title = '塔罗牌占卜-你们有机会复合吗？';
                $FormItems[] = ['ckeditor', 'des', '描述'];
                $FormItems[] = ['ckeditor', 'c1', '内容1'];
                $FormItems[] = ['ckeditor', 'c2', '内容2'];
                break;
            case 'tarot_jixu':
                $title = '塔罗牌占卜-你和TA该继续下去吗？';
                $FormItems[] = ['text', 'des', '指数'];
                $FormItems[] = ['ckeditor', 'content', '内容'];
                break;
            case 'tarot_tuodan':
                $title = '塔罗牌占卜-3个月内你会脱单吗？';
                $FormItems[] = ['number', 'bili', '脱单比例'];
                $FormItems[] = ['text', 'day', '脱单幸运日'];
                $FormItems[] = ['ckeditor', 'des', '描述'];
                $FormItems[] = ['ckeditor', 'content', '内容'];
                break;
            case 'tarot_xinli':
                $title = '塔罗牌占卜-ta的心里有没有你？';
                $FormItems[] = ['ckeditor', 'content', '内容'];
                break;
            case 'tarot_zuihou':
                $title = '塔罗牌占卜-你和ta能走到最后吗？';
                $FormItems[] = ['ckeditor', 'des', '描述'];
                $FormItems[] = ['ckeditor', 'c1', '内容1'];
                $FormItems[] = ['ckeditor', 'c2', '内容2'];
                break;
            // default:
            //     $title = '未知';
            //     break;
        }
        $FormItems[]  = ['hidden', 'group', $group];
        return ZBuilder::make('form')
        ->setPageTitle($title)
        ->addFormItems($FormItems)
        ->layout(['title' => 3, 'pid' => 3,'zf' => 3,'lie' => 3])
        ->fetch();
    }

    public function tarot_aiqing_edit($group = 'tarot_aiqing', $id = 0){
        if ($this->request->isPost()) {
            $data                   = input('post.');
            $data['update_time']    = time();
            if(isset($data['title']) && !$data['title']){
               $this->error('牌名不能为空');
            }
            if (isset($data['pid']) && !$data['pid']) {
                $this->error('牌序数值不能为空');
            }
            if (!in_array($data['zf'], [0, 1])) {
                $this->error('正逆位不能为空');
            }
            // $res = Db::name($group)->where('pid', $data['pid'])->where('zf', $data['zf'])->where('id','<>',$data['id'])->find();
            // if ($res) {
            //     $this->error('该牌已存在');
            // }
            if (!in_array($data['lie'], [1, 2, 3, 4, 5, 6])) {
                $this->error('列不能为空');
            }
            if(isset($data['des']) && !$data['des']){
                $this->error('描述不能为空');
            }
            if(isset($data['content']) && !$data['content']){
                $this->error('内容不能为空');
            }
            $group     = $data['group'];
            $is_update = Db::name($group)->where('id', $data['id'])->update($data);
            if ($is_update) {
                $this->success('更新成功', url('tarot_aiqing', ['group' => $group]));
            } else {
                $this->error('更新失败', url('tarot_aiqing', ['group' => $group]));
            }
        }
        $zf_data  = [0 => '逆位', 1 => '正位'];
        $lie_data = [1=>1, 2=>2, 3=>3, 4=>4, 5=>5, 6=>6];
        $FormItems = [
            ['text', 'title', '牌名'],
            ['number', 'pid', '牌的序号（1-22）', '不能重复，否则影响测算结果'],
            ['radio', 'zf', '正逆位', '', $zf_data,0],
            ['radio', 'lie', '列', '',$lie_data,1],
            ['image', 'img', '素材'],
        ];
        $title = '塔罗牌占卜-爱情运势大揭秘，塔罗占卜你的爱情运势？';
        switch ($group) {
            case 'tarot_aiqing':
                $title = '塔罗牌占卜-爱情运势大揭秘，塔罗占卜你的爱情运势？';
                $FormItems[] = ['text', 'des', '桃花月份'];
                $FormItems[] = ['ckeditor', 'content', '内容'];
                break;
            case 'tarot_anlian':
                $title = '塔罗牌占卜-塔罗牌占卜-你暗恋的人也喜欢你吗？';
                $FormItems[] = ['ckeditor', 'content', '内容'];
                break;
            case 'tarot_bieren':
                $title = '塔罗牌占卜-你喜欢的人心里有想着别人吗？';
                $FormItems[] = ['ckeditor', 'des', '描述'];
                $FormItems[] = ['ckeditor', 'content', '内容'];
                break;
            case 'tarot_fenshou':
                $title = '塔罗牌占卜-你和ta该不该分手？';
                $FormItems[] = ['ckeditor', 'c1', '内容'];
                break;
            case 'tarot_fuhe':
                $title = '塔罗牌占卜-你们有机会复合吗？';
                $FormItems[] = ['ckeditor', 'des', '描述'];
                $FormItems[] = ['ckeditor', 'c1', '内容1'];
                $FormItems[] = ['ckeditor', 'c2', '内容2'];
                break;
            case 'tarot_jixu':
                $title = '塔罗牌占卜-你和TA该继续下去吗？';
                $FormItems[] = ['text', 'des', '指数'];
                $FormItems[] = ['ckeditor', 'content', '内容'];
                break;
            case 'tarot_tuodan':
                $title = '塔罗牌占卜-3个月内你会脱单吗？';
                $FormItems[] = ['number', 'bili', '脱单比例'];
                $FormItems[] = ['text', 'day', '脱单幸运日'];
                $FormItems[] = ['ckeditor', 'des', '描述'];
                $FormItems[] = ['ckeditor', 'content', '内容'];
                break;
            case 'tarot_xinli':
                $title = '塔罗牌占卜-ta的心里有没有你？';
                $FormItems[] = ['ckeditor', 'content', '内容'];
                break;
            case 'tarot_zuihou':
                $title = '塔罗牌占卜-你和ta能走到最后吗？';
                $FormItems[] = ['ckeditor', 'des', '描述'];
                $FormItems[] = ['ckeditor', 'c1', '内容1'];
                $FormItems[] = ['ckeditor', 'c2', '内容2'];
                break;
            // default:
            //     $title = '未知';
            //     break;
        }
        $id     = input('id');
        $data   = Db::name($group)->where('id', $id)->find();
        if (empty($data)) {
            $this->error('未查到当前数据', url('tarot_aiqing', ['group' => $group]),);
        }
        $FormItems[]  = ['hidden', 'group', $group];
        $FormItems[]  = ['hidden', 'id', $id];
        return ZBuilder::make('form')
        ->setPageTitle($title)
        ->addFormItems($FormItems)
        ->setFormData($data)
        ->layout(['title' => 3, 'pid' => 3,'zf' => 3,'lie' => 3])
        ->fetch();
    }

    public function tarot_aiqing_del($group = 'tarot_aiqing', $id = 0){
        if($id == 0){
            $this->error('参数错误');
        }
        $data = Db::name($group)->where('id', $id)->find();
        if(empty($data)){
            $this->error('未查到当前数据');
        }
        $res = Db::name($group)->where('id', $id)->delete();
        if($res){
            $this->success('删除成功', url('tarot_aiqing', ['group' => $group]));
        }else{
            $this->error('删除失败', url('tarot_aiqing', ['group' => $group]));
        }
    }

}
