<?php


namespace app\ai\admin;


use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\ai\model\AiMyfilterModel;
use app\ai\model\AiMainModel;
use think\Db;
use think\facade\Env;

class AiMyfilter extends Admin
{
    public function index($id = 1)
    {
        if ($this->request->isPost()) {
            $data  = input('post.');
            $data['updatetime'] = date('Y-m-d H:i:s');
            if($data['keywords']){
                // $data['keywords'] = str_replace("，",',',$data['keywords']);
                // $data['keywords'] = str_replace("\n",',',$data['keywords']);
               $keywords = str_replace('，',',',str_replace("\r", "", str_replace("\n", ",", $_POST["keywords"])));
                
            }
            unset($data['keywords']);
            if($keywords){
                AiMyfilterModel::where('id','<>',0)->delete();
                $keywords = explode(',',$keywords);
                if(!empty($keywords)){
                    foreach($keywords as $key=>$val){
                        if($val){
                            AiMyfilterModel::create(['keyword' => trim($val)]);
                        }
                    }
                }
                foreach($keywords as $key=>$val){
                    
                }
            }
            $res    = AiMainModel::where('id', $data['id'])->update($data);
            if ($res) {
                $this->success('保存成功');
            } else {
                $this->error('保存失败');
            }
        }
        $data = AiMainModel::get($id);
        if (!$data) {
            $this->error('数据不存在');
        }
        $MyfilterData = AiMyfilterModel::where('id', '<>', 0)->column('keyword');
        $data['keywords'] = implode("\n", $MyfilterData);
        return ZBuilder::make('form')
            ->setPageTitle('关键词过滤配置')
            ->addFormItems([
                ['radio', 'isquestionfilter', '开启用户提问内容审核', '', [0 => '否', 1 => '是'], 0],
                ['radio', 'isanswerfilter', '开启AI回复内容审核', '', [0 => '否', 1 => '是'], 0],
                ['textarea:7', 'keywords', '自定义过滤关键词', '请用英文逗号或回车分隔关键词'],
                ['hidden', 'id'],
            ])
            ->setFormData($data)
            ->fetch();
    }
}
