<?php


namespace app\ai\admin;


use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\ai\model\AiAsrModel;
use think\Db;
use think\facade\Env;

class AiAsr extends Admin
{
    public function index($id=1){
        if($this->request->isPost()){
            $data   = input('post.');
            $data['updatetime'] = date('Y-m-d H:i:s');
              
            $res    = AiAsrModel::where('id',$id)->update($data);
            if($res){
                $this->success('保存成功');
            }else{
                $this->error('保存失败');
            }
        }
        $data = AiAsrModel::get($id);
        if (!$data) {
            $this->error('数据不存在');
        }
        return ZBuilder::make('form')
            ->setPageTitle('OpenAI语音识别（whisper模型）')
            ->addFormItems([
                //['text:8', 'asrmodel', '智能模型'],
                ['text:5', 'openaiapikey', 'APIKEY'],
                ['text:5', 'openaiapiaddress', 'API地址'],
                ['hidden', 'id'],
            ])
            ->setFormData($data)
            ->fetch();
    }
}