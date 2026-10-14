<?php


namespace app\ai\admin;


use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\common\library\ExcelServer;
use app\ai\model\AiMoldsModel;
use think\Db;
use think\facade\Env;

class AiMolds extends Admin
{
    protected $html = <<<EOF
    <p>模型类型为“OpenAI官方”指的是官方接口及兼容的接口规范。如果使用类似<a target="_blank" href="https://github.com/songquanpeng/one-api">one-api</a>之类的聚合转发接口生成的APIKEY，则所有模型都选择OpenAI官方类型即可。</p>
    <p>模型类型为“免费国外接口”用的是免费的第三方模型。注册和使用方式请参考<a target="_blank" href="https://bbs.ipfei.cn/forum.php?mod=viewthread&tid=106">论坛相关教程</a>。</p>
    <p>模型类型为MidJourney和StableDiffusion时，模型参数值不能修改，模型名称可以随意写。环境配置请参考<a target="_blank" href="https://bbs.ipfei.cn/forum.php?mod=viewthread&tid=81">论坛相关教程</a></p>
    <p>模型参数值代表了每个模型调用接口时使用的模型名，区分大小写，注意不要写错。模型名称可以随意设置，不影响使用。</p>
    <p>目前已确认支持的模型参数值如下。（OpenAI、Claude账号可以在交流群<a target="_blank" href="https://ad.ipfei.cn">商户平台</a>购买，国内大模型申请地址请查看
        <a target="_blank" href="https://bbs.ipfei.cn/forum.php?mod=viewthread&tid=116">论坛相关教程</a>）<br>
        <b>国外模型：</b><br> OpenAI官方：gpt-3.5-turbo-0125、gpt-3.5-turbo-instruct（擅长执行具体指令，无上下文）、gpt-4-turbo-preview（最具性价比）、gpt-4-0125-preview等、dall-e-3（画图）、gpt-4-vision-preview（识图）、dall-e-2（改图）；<br>
        由于使用官方APIKEY成本和门槛都很高，大多数用户都是购买第三方接口的额度来调用gpt-4等模型，并且第三方接口可以支持某些官方没有的模型，如gpt-4-all，gpts上的应用gpt-4-gizmo-xxx等。推荐一个
        <a target="_blank" href="https://mvtw4457uos.feishu.cn/docx/HxB6dqzPDozjE8xribScGUtGnBh">群友运营的店铺</a>。<br>
        Claude官方：claude-3-opus-20240229、claude-3-sonnet-20240229、claude-2.1、claude-2.0、claude-instant-1.2（Claude的所有模型均包含文本对话及识图能力）；<br>
        <a target="_blank" href="https://ai.google.dev/?hl=zh-cn">谷歌Bard</a>：gemini-1.5-flash、gemini-1.5-pro、gemini-1.5-flash（识图）；<br>
        <b>国内模型：</b><br>
        <a target="_blank" href="https://cloud.baidu.com/survey/qianfan.html">文心千帆</a>：ERNIE-4.0-8K、ERNIE-3.5-8K、ERNIE-Speed-128K（免费）、ERNIE-Lite-8K（免费）等文本大模型，每个模型使用前需要先
        <a target="_blank" href="https://console.bce.baidu.com/qianfan/chargemanage/list">点击这里开通权限</a>、fuyu_8b（识图）、Stable-Diffusion-XL（画图）；<br>
        <a target="_blank" href="https://dashscope.console.aliyun.com/model">通义千问</a>：qwen-turbo、qwen-vl-plus（识图）、wanx-v1（画图）、stable-diffusion-xl（画图）、stable-diffusion-v1.5（画图）；<br>
        <a target="_blank" href="https://cloud.tencent.com/product/hunyuan">腾讯混元</a>：hunyuan-pro、hunyuan-standard、hunyuan-lite、hunyuan-code（写代码）、hunyuan-role、hunyuan-vision（识图）、TextToImageLite（画图））；<br>
        <a target="_blank" href="https://open.bigmodel.cn/overview">清华智谱</a>：glm-4-0520、glm-4、glm-4-air、glm-4-airx、glm-4-long、 glm-4-flash、codegeex-4（写代码）、glm-4v（识图）、cogview-3（画图）；<br>
        <a target="_blank" href="https://xinghuo.xfyun.cn/">讯飞星火</a>：Spark Lite、Spark Pro、Spark Max、图片理解、图片生成，参数值随便写，靠API地址区分；<br>
        <a target="_blank" href="https://ai.360.cn/open">360智脑</a>：360gpt2-pro、360gpt-turbo-responsibility-8k、360gpt-pro、360gpt-turbo、360CV_S0_V5（画图）；<br>
        <a target="_blank" href="https://platform.baichuan-ai.com/console/authentication">百川智能</a>：Baichuan4、Baichuan3-Turbo、Baichuan3-Turbo-128k、Baichuan2-Turbo、Baichuan2-Turbo-192k；<br>
        <a target="_blank" href="https://platform.moonshot.cn/">月之暗面</a>：moonshot-v1-8k、moonshot-v1-32k、moonshot-v1-128k；<br>
        <a target="_blank" href="https://www.volcengine.com/product/doubao">火山方舟（豆包）</a>：Doubao-lite-4k、Doubao-lite-32k、Doubao-lite-128k、Doubao-pro-4k、Doubao-pro-32k、Doubao-pro-128k；<br>
        <a target="_blank" href="https://platform.minimaxi.com/">MiniMax</a>：abab6.5s、abab5.5s、abab5.5；<br>
        <a target="_blank" href="https://platform.lingyiwanwu.com/">零一万物</a>：yi-large、yi-large-turbo、yi-medium、yi-spark、yi-vision（识图）；<br>
        <a target="_blank" href="https://www.deepseek.com/">DeepSeek</a>：deepseek-chat、deepseek-coder；<br>
        <a target="_blank" href="https://cloud.infini-ai.com/platform/ai">无问芯穹</a>：mt-infini-3b、baichuan2-13b-chat、qwen2-72b-chat、glm-4-9b-chat、yi-34b-chat；<br>
        <a target="_blank" href="https://siliconflow.cn/zh-cn/">硅基流动</a>：超多免费和收费的第三方模型，
        <a target="_blank" href="https://siliconflow.cn/zh-cn/maaspricing#siliconcloud">点击这里查看</a>。模型参数值样例：deepseek-ai/DeepSeek-V2-Chat<br>
        <a target="_blank"  href="https://docs.link-ai.tech">LinkAI</a>：这是一个第三方知识库网站，请将app_code写到模型参数后面，如gpt-3.5-turbo,cJslRJSo；<br>
        本地知识库：模型参数值填写知识库ID即可，如：asst_eduOuMQnER4nFKyDdCJo7Wur
    </p>
    <p>模型开启联网搜索后，每次请求该模型前会将搜索引擎（目前只支持duckduckgo）返回的前3条结果作为上下文提问，建议采用tokens较大的模型开启联网功能。</p>
    <p>模型开启提问翻译后，每次请求该模型前会先调用百度翻译将提问翻译成英文，多用于画图模型。此选项生效的前提是“提问翻译配置”已经配置好并开启。</p>
EOF;
    public function index()
    {
        $AiMoldsModel   = new AiMoldsModel();
        $order          = $this->getOrder('id asc');
        $map            = $this->getMap();
        $data_list      = AiMoldsModel::where($map)
            ->order($order)
            ->paginate(100);
        $topBtn = [
            'add' => ['class' => 'btn btn-sm btn-success', 'href' => url('add')],
        ];
        $rightBtn = [
            //'edit'      => ['class' => 'btn btn-sm btn-warning', 'href' => url('edit', ['id' => '__id__'])],
            'delete'    => ['class' => 'btn btn-sm btn-danger ajax-get confirm', 'href' => url('del', ['id' => '__id__'])],
        ];
        return ZBuilder::make('table')
            ->setPageTitle('模型配置')
            ->setPageTips($this->html)
            ->hideCheckbox()
            ->setTableName('ai_molds')
            ->addOrder('id') // 添加排序
            ->setSearchArea([
                ['text:1', 'id', 'ID'],
                ['text:2', 'modelname', '模型名称'],
                ['select:2', 'modeltype', '模型类型', '', '', $AiMoldsModel->model_type],
                ['select:2', 'isimage', '画图模型', '', '', $AiMoldsModel->image_type],
                ['select:2', 'isonline', '联网搜索', '', '', ['1' => '是', '0' => '否']],
                ['select:2', 'istranslate', '提问翻译', '', '', ['1' => '是', '0' => '否']],
                ['select:2', 'isvalid', '展示', '', '', ['1' => '是', '0' => '否']],
            ])
            ->addColumns([
                ['id', 'ID'],
                ['modeltype', '模型类型', 'select', $AiMoldsModel->model_type],
                ['modelname', '模型名称', 'text.edit'],
                ['modelvalue', '配置值', 'text.edit'],
                ['modelprice', '消耗次数', 'text.edit'],
                ['isvalid', '展示', 'switch', ['1' => '是', '0' => '否']],
                ['isimage', '画图模型', 'select', $AiMoldsModel->image_type],
                ['isonline', '联网搜索', 'switch', ['1' => '是', '0' => '否']],
                ['istranslate', '提问翻译', 'switch', ['1' => '是', '0' => '否']],
                ['memo', '备注', 'textarea.edit'],
                ['sequenceid', '排序', 'text.edit'],
                ['createtime', '创建时间', 'text'],
                ['right_button', '操作', 'btn'],
            ])
            ->setRowList($data_list)
            ->addRightButtons($rightBtn)
            ->addTopButtons($topBtn)
            ->setColumnWidth([
                'id'            => 35,
                'isvalid'       => 40,
                'modelprice'    => 60,
                'isimage'       => 60,
                'isonline'      => 60,
                'istranslate'   => 60,
                'modeltype'     => 75,
                'sequenceid'    => 35,
                'right_button'  => 40,
            ])
            ->setExtraCss($this->css)
            ->fetch();
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $data   = input('post.');
            $data['createtime'] = date('Y-m-d H:i:s');
            if(!$data['modeltype']){
                 $this->error('模型类型不能为空');
            }
            if(!$data['modelname']){
                $this->error('模型名称不能为空');
            }
            if(!$data['modelvalue']){
                $this->error('配置值不能为空');
            }
            if(!$data['modelprice']){
                $this->error('消耗次数不能为空');
            }
            $res = AiMoldsModel::create($data);
            if($res){
                $this->success('添加成功',url('index'));
            }else{
                $this->error('添加失败');
            }
        }
        $AiMoldsModel   = new AiMoldsModel();
        $model_type     = $AiMoldsModel->model_type;
        $image_type     = $AiMoldsModel->image_type;
        return ZBuilder::make('form')
            ->setPageTitle('添加模型')
            ->addFormItems([
                ['select:3', 'modeltype', '模型类型', '', $model_type],
                ['text:3', 'modelname', '模型名称'],
                ['text:3', 'modelvalue', '配置值'],
                ['number:2', 'modelprice', '消耗次数'],
                ['select:2', 'isimage', '画图模型', '', $image_type],
                ['radio:2', 'isvalid', '展示', '', ['1' => '是', '0' => '否'], 1],
                ['radio:2', 'isonline', '联网搜索', '', ['1' => '是', '0' => '否'], 0],
                ['radio:2', 'istranslate', '提问翻译', '', ['1' => '是', '0' => '否'], 0],
                ['number:2', 'sequenceid', '排序'],
                ['textarea:5', 'memo', '备注'],
            ])
            //->layout(['xz' => 3])
            ->fetch();
    }

    public function del($id=0){
        if($id){
            $res = AiMoldsModel::destroy($id);
            if($res){
                $this->success('删除成功',url('index'));
            }else{
                $this->error('删除失败');
            }
        }else{
            $this->error('参数错误');
        }
    }
}
