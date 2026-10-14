<?php


namespace app\ai\admin;


use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\ai\model\AiTtsModel;
use think\Db;
use think\facade\Env;

class AiTts extends Admin
{
    public function index($id=1){
        if($this->request->isPost()){
            $data   = input('post.');
            $data['updatetime'] = date('Y-m-d H:i:s');
            if(!$data['ttsmodel']){
                $this->error('朗读模型不能为空');
            }
            if($data['ttsmodel'] == 'openai'){
                if(!$data['openaimodel']){
                    $this->error('OpenAI模型不能为空');
                }
                if(!$data['openairole']){
                    $this->error('OpenAI角色不能为空');
                }
                if(!$data['openaiapikey']){
                    $this->error('OpenAI API Key不能为空');
                }
                if(!$data['openaiapiaddress']){
                    $this->error('OpenAI API 地址不能为空');
                }
                if(!$data['openaispeed']){
                    $this->error('OpenAI播放速度不能为空');
                }
            }
            if($data['ttsmodel'] == 'xunfei'){

                if(!$data['xunfeirole']){
                    $this->error('科大讯飞角色不能为空');
                }
                if(!$data['xunfeiapikey']){
                    $this->error('科大讯飞API Key不能为空');
                }
                if(!$data['xunfeiapiaddress']){
                    $this->error('科大讯飞API 地址不能为空');
                }
                if(!$data['xunfeispeed']){
                    $this->error('科大讯飞播放速度不能为空');
                }
                if(!$data['xunfeivolume']){
                    $this->error('科大讯飞播放音量不能为空');
                }
                if(!$data['xunfeipitch']){
                    $this->error('科大讯飞播放语调不能为空');
                }
            }
            if($data['ttsmodel'] == 'microsoft'){
                if(!$data['microsoftrole']){
                    $this->error('微软角色不能为空');
                }
                if(!$data['microsoftapiaddress']){
                    $this->error('微软API 地址不能为空');
                }
                if(!$data['microsoftspeed']){
                    $this->error('微软播放速度不能为空');
                }
                if(!$data['microsoftvolume']){
                    $this->error('微软播放音量不能为空');
                }
            }   
            $res    = AiTtsModel::where('id',$id)->update($data);
            if($res){
                $this->success('保存成功');
            }else{
                $this->error('保存失败');
            }
        }
        $data = AiTtsModel::get($id);
        if (!$data) {
            $this->error('数据不存在');
        }
        $ttsmodel = ['openai'=>'OpenAI TTS','xunfei'=>'科大讯飞TTS','microsoft'=>'微软TTS'];
        $microsoft_role = [
            "zh-CN-XiaoxiaoNeural"          =>'中国大陆-Xiaoxiao',
            "zh-CN-XiaoyiNeural"            =>'中国大陆-Xiaoyi',
            "zh-CN-YunjianNeural"           =>'中国大陆-Yunjian',
            "zh-CN-YunxiNeural"             =>'中国大陆-Yunxi',
            "zh-CN-YunxiaNeural"            =>'中国大陆-Yunxia',
            "zh-CN-YunyangNeural"           =>'中国大陆-Yunyang',
            "zh-CN-liaoning-XiaobeiNeural"  =>'中国辽宁-Xiaobei',
            "zh-CN-shaanxi-XiaoniNeural"    =>'中国陕西-Xiaoni',
            "zh-HK-HiuGaaiNeural"           =>'中国香港-HiuGaai',
            "zh-HK-HiuMaanNeural"           =>'中国香港-HiuMaan',
            "zh-HK-WanLungNeural"           =>'中国香港-WanLung',
            "zh-TW-HsiaoChenNeural"         =>'中国台湾-HsiaoChen',
            "zh-TW-HsiaoYuNeural"           =>'中国台湾-HsiaoYu',
            "zh-TW-YunJheNeural"            =>'中国台湾-YunJhe',
        ];
        $xunfei_role = [
            "xiaoyan"       => "讯飞-xiaoyan",
            "aisjiuxu"      => "讯飞-aisjiuxu",
            "aisxping"      => "讯飞-aisxping",
            "aisjinger"     => "讯飞-aisjinger",
            "aisbabyxu"     => "讯飞-aisbabyxu",
            "其他"           => "其他",
        ];
        $openai_model = [
            'tts-1'     =>'OpenAI TTS-1',
            'tts-1-hd'  =>'OpenAI TTS-1-HD',
        ];
        $openai_role = [
            'alloy'         =>'OpenAI TTS-Alloy',
            'echo'          =>'OpenAI TTS-echo',
            'fable'         =>'OpenAI TTS-fable',
            'nova'          =>'OpenAI TTS-nova',
            'onyx'          =>'OpenAI TTS-onyx',
            'shimmer'       =>'OpenAI TTS-shimmer',
        ];
        return ZBuilder::make('form')
            ->setPageTitle('朗读模型配置')
            ->addFormItems([
                ['select', 'ttsmodel', '朗读模型', '', $ttsmodel],
                //['text:5', 'microsoftapikey', 'ApiKey'],
                ['text:8', 'microsoftapiaddress', '微软TTS-Api地址'],
                ['select:6', 'microsoftrole', '微软TTS-发音人','',$microsoft_role],
                ['number:2', 'microsoftspeed', '微软TTS-语速',''],
                ['number:2', 'microsoftvolume', '微软TTS-音量',''],
                ['text:8', 'xunfeiapikey', '讯飞TTS-ApiKey'],
                ['text:5', 'xunfeiapiaddress', '讯飞TTS-Api地址'],
                ['select:6', 'xunfeirole', '讯飞TTS-发音人','',$xunfei_role],
                ['number:2', 'xunfeispeed', '讯飞TTS-语速',''],
                ['number:2', 'xunfeivolume', '讯飞TTS-音量',''],
                ['number:2', 'xunfeipitch', '讯飞TTS-音高',''],
                ['text:8', 'openaiapikey', 'OpenAI TTS-ApiKey'],
                ['text:8', 'openaiapiaddress', 'OpenAI TTS-Api地址'],
                ['select:6', 'openaimodel', 'OpenAI TTS-模型','',$openai_model],
                ['select:6', 'openairole', 'OpenAI TTS-发音人','',$openai_role],
                ['number:2', 'openaispeed', 'OpenAI TTS-语速',''],
                ['hidden', 'id'],
            ])
            ->setFormData($data)
            ->fetch();
    }
}