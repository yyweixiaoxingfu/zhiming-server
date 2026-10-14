<?php


namespace app\ai\admin;


use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\ai\model\AiMainModel;
use think\Db;
use think\facade\Env;

class AiMain extends Admin
{
    protected $html = <<<EOF
      <p>开启提问翻译功能后，用户使用开启了提问翻译的模型时，输入的中文提示词将自动翻译成英文（一般用于画图，也可以用于仅支持英文的问答模型）。</p>
      <p style="margin-bottom:20px;">百度翻译资源领取和应用注册指南详见论坛文章，请 <a href="https://bbs.ipfei.cn/forum.php?mod=viewthread&tid=96" target="_blank">点击这里</a> 查看</p>
                            
EOF;
    protected $html2 = <<<EOF
        <p>百度内容审核功能开启后，违规问题无法提问，违规答案打字效果输出完成后会变成内容违规提示。</p>
        <p style="margin-bottom:20px;">百度内容审核平台注册指南详见论坛文章，请 <a href="https://bbs.ipfei.cn/forum.php?mod=viewthread&tid=60" target="_blank">点击这里</a> 查看</p>
                            
EOF;
    //翻译
    public function translate_index($id=1){
        if($this->request->isPost()){
            $data   = input('post.');
            $data['updatetime'] = date('Y-m-d H:i:s');
            $res    = AiMainModel::where('id',$data['id'])->update($data);
            if($res){
                $this->success('保存成功');
            }else{
                $this->error('保存失败');
            }
        }
        $data = AiMainModel::get($id);
        if (!$data) {
            $this->error('数据不存在');
        }
        return ZBuilder::make('form')
            ->setPageTitle('朗读模型配置')
            ->setPageTips($this->html)
            ->addFormItems([
                ['text:7', 'baidutranslateapikey', '百度翻译应用API Key'],
                ['text:7', 'baidutranslatesecretkey', '百度翻译应用Secret Key'],
                ['radio', 'isdrawtranslate', '开启提问翻译','',[0=>'否',1=>'是'],0],
                ['hidden', 'id'],
            ])
            ->setFormData($data)
            ->fetch();
    }

    //内容审核
    public function content_sensor_index($id=1){
         if($this->request->isPost()){
            $data   = input('post.');
            $data['updatetime'] = date('Y-m-d H:i:s');
            $res    = AiMainModel::where('id',$data['id'])->update($data);
            if($res){
                $this->success('保存成功');
            }else{
                $this->error('保存失败');
            }
        }
        $data = AiMainModel::get($id);
        if (!$data) {
            $this->error('数据不存在');
        }
        return ZBuilder::make('form')
            ->setPageTitle('内容审核配置（百度）')
            ->setPageTips($this->html2)
            ->addFormItems([
                ['text:7', 'baiduapikey', '百度应用API Key'],
                ['text:7', 'baidusecretkey', '百度应用Secret Key'],
                ['radio', 'isquestionsensor', '开启用户提问内容审核','',[0=>'否',1=>'是'],0],
                ['radio', 'isanswersensor', '开启AI回复内容审核','',[0=>'否',1=>'是'],0],
                ['hidden', 'id'],
            ])
            ->setFormData($data)
            ->fetch();
    }
}