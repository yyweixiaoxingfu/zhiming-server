 
===============

# 表单项扩展目录

# demo
```PHP
<?php
namespace app\admin\controller;

use app\common\builder\ZBuilder;
use app\common\controller\Common;
use think\Db;

class Test extends Common
{
    public function index(){
        if($this->request->isPost()){
            $data = $this->request->param();
            halt($data);
        }
        $apply_info = [
            ['会员','打款金额','备注'],
            ['13500135000','20','备注111'],
            ['13600136000','22','备注222'],
            ['13700137000','30','备注333'],
        ];
        $groups = [
            '广州市' => [
                '440111' => '白云区',
                '440106' => '天河区',
                '440104' => '越秀区'
            ],
            '深圳市' => [
                '440303' => '罗湖区',
                '440304' => '福田区',
                '440305' => '南山区',
            ]
        ];
        $columns = [
            'id' => 'ID',
            'name' => '姓名',
            'mobile' => '手机号',
        ];
        $fields = [
            'SFMC'   => '省份',
            'XSPCMC' => '批次',
            'XSKLMC' => '科类',
            'ZKZH'   => '准考证号',
            'XH'     => '学号',
            'XM'     => '姓名',
            'CYM'    => '曾用名',
            'XB'     => '性别',
            'CSNY'   => '出生年月',
        ];
        return ZBuilder::make('form')
            ->addFormItems([
//                ['complexTable', 'apply_info', '申请信息', $apply_info,true],
                ['dataTable', 'members', '添加打款会员数据', '备注信息', [
                    'member_id' => [
                        'type' => 'select2ajax',
                        'title' => '会员',
                        'options'=>['6'=>'会员1','7'=>'会员2'],
                        'ajax_url' => url('test_table'),
                        'extra_attr' => 'multiple', // 单选去掉此项
                    ],
                    'money' => ['type' => 'text', 'title' => '打款金额'],
                    'remark' => ['type' => 'text', 'title' => '备注'],
                    'status' => ['type' => 'select', 'title' => '状态','options'=>['否','是']],
                ],[
                    ['member_id'=>'6,7','money'=>20,'remark'=>'','status'=>0],
                    ['member_id'=>'7','money'=>30,'remark'=>'备注','status'=>1],
                ]],
//                ['select2ajax','member_ids', '选择会员','备注', ['6'=>'会员1','7'=>'会员2'], '', url('test_table'),'multiple'],
//                ['select2ajax','member_id', '选择会员','备注', ['6'=>'会员1','7'=>'会员2'], '', url('test_table')],
//                ['selectGroup', 'test', '测试', '', $groups, '440303,440305', 'multiple'],
//                ['selectGroup', 'test', '测试', '', $groups, '440303'],
//                ['selectTable', 'member_id', '选择用户', '', $columns, [], url('getUserList')],
//                ['transfer', 'fields', '可选字段', '导出字段', '默认导出所有字段', $fields],
            ])
            ->setFormData(['member_id'=>7,'member_ids'=>'6'])
            ->fetch();
    }

    public function test_table($search = '', $page = 1){
        // 静态模拟
//            return json([
//                'data'=>[
//                    ['text'=>'吉佳便利超市','id'=>2],
//                    ['text'=>'吉祥馄饨','id'=>880],
//                ]
//            ]);
        // 动态查询
        $where = [];
        if(!empty($search)){
            $where[] = ['name','like','%'.$search.'%'];
        }
        $shop_list = Db::name('member')->where($where)->field('name AS text,id')->paginate();
        return json($shop_list);
    }

    public function getUserList()
    {
        $data_list = Db::name('member')->paginate();
        return ZBuilder::make('table')
            ->addTopButton('select', [
                'title'     => '确定',
                'icon'      => 'fa fa-check',
                'class'     => 'btn btn-primary',
                'id'        => 'js-confirm',
                'data-name' => 'member_id'
            ])
            ->addColumns([
                ['id', 'ID'],
                ['name', '姓名'],
                ['mobile', '手机号']
            ])
            ->setRowList($data_list)
            ->js('../extend/form/selecttable/selecttable')
            ->fetch();
    }

    // selectAjax 使用方法
    public function selectAjax(){
        if($this->request->isPost()){
            $data = $this->request->param();
            halt($data);
        }
        return ZBuilder::make('form')
            ->addSelect('status','状态','',['锁定','正常'])
            ->addSelectAjax('member_id','选择会员','请选择会员',[
                'table'=>'member',
                'multiple' => true,
                'where' => [['lock', '=',0]],
                'order' => 'id asc',
                'list_rows' => 10,
                'id' => 'id',
                'name' => 'mobile',
                'search' => 'name'
            ],'','','','','')
            ->addFormItems([
                ['selectAjax','tid', '选择推荐人','', ['table' => 'member','search' => 'name','where' => [['tid','=',0]]], '','','','','uid'],
                ['selectAjax','uid', '选择会员','', ['table' => 'member','search' => 'name'], '','','','tid'],
            ])
            ->addFormItems([
                ['selectAjax','brand_id', '选择品牌','', ['table' => 'brand','search' => 'name', 'where' => [['status', '=',1],['delete_time','=',0]],'order'=>'sort'], '','','','','line_id,series_id'],
                ['selectAjax','line_id', '选择产品线','', ['table' => 'product_line','search' => 'name', 'where' => [['status', '=',1],['delete_time','=',0]],'order'=>'sort','list_rows'=>10], '','','','brand_id','series_id'],
                ['selectAjax','series_id', '选择产品系列','', ['table' => 'product_series','search' => 'name', 'where' => [['status', '=',1],['delete_time','=',0]],'order'=>'sort'], '','','','brand_id,line_id'],
            ])
            ->fetch();
    }

}
```