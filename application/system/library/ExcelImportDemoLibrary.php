<?php


namespace app\system\library;


use app\common\library\ExcelServer;
use app\member\model\MemberModel;

class ExcelImportDemoLibrary
{
    public function getImportExcelTitle(){
        return [
            'name'=>'姓名',
            'mobile'=>'手机号',
            'password'=>'密码',
        ];
    }

    public function doImportItemAdd($data,&$msg){
        $status = rand(0,1);
        if($status){
            return true;
        }
        $msg = '测试一下错误信息吧';
        return false;
    }

    public function getImportTips(){
        return [
            '模板第一行title，不允许修改',
            '隐藏、空白行会造成导入报错',
            '每次最多操作1000行，数据多请分多次操作',
            '密码不输入的默认123456'
        ];
    }

    public function import_template(){
        $title = $this->getImportExcelTitle();
        $all[] = [
            'sheet' => '会员导入模板',
            'data' => [array_values($title)]
        ];
        $data = MemberModel::where(true)->field('name,mobile')->select();
        $data = getExportDataByTitle($title, $data);
        $all[] = [
            'sheet' => '参考数据',
            'data' => $data
        ];
        ExcelServer::export('会员模板',$all);
    }
}