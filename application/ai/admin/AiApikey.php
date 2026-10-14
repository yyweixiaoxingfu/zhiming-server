<?php


namespace app\ai\admin;


use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\ai\model\AiApikeyModel;
use app\ai\model\AiMoldsModel;
use think\Db;
use think\facade\Env;

class AiApikey extends Admin
{
    protected $html = <<<EOF
        <p>每个API_KEY单独配置API地址，请注意API地址最后不要加“/”，目前API_KEY和API地址都已支持修改。</p>
        <p>对于兼容OpenAI标准的接口，只写域名部分即可，不要写后面的/v1等。MJ画图和SD画图的环境搭建和配置方法请 <a target="_blank"
                href="https://bbs.ipfei.cn/forum.php?mod=viewthread&tid=81">参考这里</a>。</p>
        <p>国内大模型目前基本都提供免费试用额度，可以 <a target="_blank"
                href="https://bbs.ipfei.cn/forum.php?mod=viewthread&tid=116">点击这里</a> 了解和申请。</p>
        <p>所有已支持的模型配置API_KEY时将自动修改API地址，一般情况下无需修改即可使用。</p>
        <p>微软Azure类型的模型需要修改下API地址，请修改为您自己的resource-url和模型名称。</p>
        <p>OpenAI的o1系列模型不支持stream模式，第三方接口一般会做预处理，所以不需要特殊对待，API地址还是设置成第三方接口地址即可。如果您用的是OpenAI或微软azure官方的APIKEY，需要用本地的o1api.php中转和处理一下请求。调用OpenAI官方接口请将API地址配置成“<script>document.write(window.location.href.split("/admin")[0]);</script>/o1api.php?type=openai&url=https://api.openai.com/v1/chat/completions”；调用微软azure接口请将API地址配置成：“<script>document.write(window.location.href.split("/admin")[0]);</script>/o1api.php?type=azure&url=https://resource-url.openai.azure.com/openai/deployments/o1-mini/chat/completions?api-version=2024-08-01-preview”。</p>
        <p>百度文心千帆模型请将应用的API Key和Secret
            Key用逗号分隔组成字符串，即为API_KEY。如：Fq1FfkOQIGtMtZqRFxzrvz6T,cbzsoFAUSl8UGV1GkGSCSjENtqsjrOTd。用户使用百度文心千帆提问后，API_KEY会增加一项access_token，并用逗号分隔合并到原API_KEY。
        </p>
        <p>百度文心千帆因为对话大模型种类很多，并且每个接口地址都不同，因此需要自行设置每个模型的接口地址。请 <a target="_blank"
                href="https://cloud.baidu.com/doc/WENXINWORKSHOP/s/clntwmv7t#http%E8%B0%83%E7%94%A8">访问这里</a>
            查看每个模型的接口地址，在左侧选择对应的模型，再点击右侧的“HTTP调用”，然后复制“请求地址”即可。
        </p>
        <p>讯飞星火大模型请将讯飞开放平台应用的APPID、APISecret和APIKey用逗号分隔组成字符串，即为API_KEY。如：adz9zf8b,MTdzZjg0N2QwY2ZmZzZjNGYyMjzjNjzw,e522c8936z3fec69f6dfzd32azaa40z5。
        </p>
        <p>讯飞星火大模型API地址设置规则：Spark Lite（wss://spark-api.xf-yun.com/v1.1/chat）、Spark
            Pro（wss://spark-api.xf-yun.com/v3.1/chat）、Spark
            Max（wss://spark-api.xf-yun.com/v3.5/chat）、图片理解（wss://spark-api.cn-huabei-1.xf-yun.com/v2.1/image）、图片生成（https://spark-api.xf-yun.com/v2.1/tti）。
        </p>
        <p>腾讯混元大模型，请先到腾讯云 <a target="_blank" href="https://console.cloud.tencent.com/cam/capi">访问密钥</a>
            栏目申请API密钥，将SecretId和SecretKey用逗号分隔组成字符串，即为API_KEY。</p>
        <p>MiniMax仅支持ChatCompletion
            Pro接口，可以使用abab6.5s-chat、abab6.5t-chat、abab6.5g-chat、abab5.5-chat、abab5.5s-chat模型。注意要在默认的api地址最后填写自己的GroupId。
        </p>
        <p>本地知识库目前不需要验证APIKEY，可以随便填写。</p>
        <p>如果您的服务器在国内，则访问国外大模型官方接口时需要使用反代地址，第三方接口提供的地址一般都可以在国内直接访问。</p>
        <p>如果您的服务器在国外，则访问以下国内大模型接口时最好也用国内服务器反代一下，否则可能会出现卡顿或无法访问的情况：讯飞星火、360智脑、火山方舟</p>
        <!--<p>对于已失效的API_KEY，可以单击红色的“否”查看错误提示，鼠标右键点击可以重新激活该API_KEY。</p>-->
EOF;
    public function index()
    {
        $order          = $this->getOrder('a.id asc');
        $map            = $this->getMap();
        $data_list      = AiApikeyModel::where($map)
            ->alias('a')
            ->join('ai_molds b','a.keytype = b.id')
            ->order($order)
            ->field('a.*,b.modelname')
            ->paginate(100);
        $topBtn = [
            'add' => ['class' => 'btn btn-sm btn-success', 'href' => url('add')],
        ];
        $rightBtn = [
            'delete'    => ['class' => 'btn btn-sm btn-danger ajax-get confirm', 'href' => url('del', ['id' => '__id__'])],
        ];
        return ZBuilder::make('table')
            ->setPageTitle('ApiKey配置')
            ->setPageTips($this->html)
            ->hideCheckbox()
            ->setTableName('ai_apikey')
            ->addOrder('id') // 添加排序
            ->setSearchArea([
                ['text:1', 'a.id', 'ID'],
                ['text:2', 'b.modelname', '模型名称','like'],
                ['select:2', 'a.isvalid', '是否有效', '', '', ['1' => '是', '0' => '否']],
            ])
            ->addColumns([
                ['id', 'ID'],
                ['modelname', '模型名称', 'text'],
                ['apikey', 'ApiKey', 'text.edit'],
                ['apiaddress', 'Api地址', 'text.edit'],
                ['isvalid', '有效', 'switch', ['1' => '是', '0' => '否']],
                ['memo', '备注', 'textarea.edit'],
                ['createtime', '创建时间', 'text'],
                ['right_button', '操作', 'btn'],
            ])
            ->setRowList($data_list)
            ->addRightButtons($rightBtn)
            ->addTopButtons($topBtn)
            ->setColumnWidth([
                'id'            => 35,
                'isvalid'       => 40,
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
            if(!$data['keytype']){
                 $this->error('模型不能为空');
            }
            if(!$data['apikey']|| $data['apikey']=='请用回车分隔多个APIKEY'){
                $this->error('ApiKey不能为空');
            }
            if(!$data['apiaddress']){
                $this->error('Api地址不能为空');
            }
            if($data['apiaddress']=='请参考页面上方的配置说明'){
                $this->error('请参考列表页上方的配置说明,填写Api地址');
            }
            $keyarray = explode("\n", $data["apikey"]);
            if(count($keyarray)){
                unset($data['apikey']);
                $i = 0;
                while ($i < count($keyarray)) {
                    if (trim($keyarray[$i]) !== "") {
                        $data['apikey'] = $keyarray[$i];
                        $res = AiApikeyModel::create($data);
                    }
                    $i++;
                }
            }else{
                $res = AiApikeyModel::create($data);
            }
            if($res){
                $this->success('添加成功',url('index'));
            }else{
                $this->error('添加失败');
            }
        }
        $model_type     = AiMoldsModel::where('isvalid',1)->order('sequenceid asc')->column('modelname','id');
        return ZBuilder::make('form')
            ->setPageTitle('添加模型')
            ->addFormItems([
                ['linkage:3', 'keytype', '模型选择', '', $model_type,'',url('getModelApi'),'apiaddress'],
                ['text:5', 'apiaddress', 'Api地址',''],
                ['radio:2', 'isvalid', '是否有效', '', ['1' => '是', '0' => '否'], 1],
                ['textarea:6', 'apikey', 'ApiKey','支持一次填写多个ApiKey','请用回车分隔多个APIKEY'],
                ['textarea:5', 'memo', '备注'],
            ])
            //->layout(['xz' => 3])
            ->fetch();
    }

    public function getModelApi($keytype=''){
        if(!$keytype){
            $this->error('请选择模型');
        }
        $modelData       = AiMoldsModel::where('id',$keytype)->field('id,modelname,modeltype,modelvalue,api_address')->find();
        if(!$modelData){
            $this->error('模型不存在');
        }
        if($modelData['api_address']){
            $modelApiAddress = $modelData['api_address'];
        }else{
            $modelApiMap    = [
                'midjourney'            => 'http://mj.10ns.cn/api/mj-fast/mj',
                'chimeragpt'            => 'https://api.naga.ac',
                'stablediffusion'       => 'https://api.ipfei.com/sd',
                '文心千帆'               => '请参考页面上方的配置说明',
                '通义千问'               => 'https://dashscope.aliyuncs.com',
                'alistablediffusion'    => 'https://dashscope.aliyuncs.com',
                '腾讯混元'               => 'https://hunyuan.tencentcloudapi.com',
                '清华智谱'               => 'https://open.bigmodel.cn',
                '讯飞星火'               => '请参考页面上方的配置说明',
                '360智脑'               => 'https://api.360.cn',
                '百川智能'               => 'https://api.baichuan-ai.com',
                '月之暗面'               => 'https://api.moonshot.cn',
                '火山方舟'               => 'https://ark.cn-beijing.volces.com/api/v3/chat/completions',
                'MiniMax'               => 'https://api.minimax.chat/v1/text/chatcompletion_pro?GroupId=',
                '零一万物'               => 'https://api.lingyiwanwu.com',
                'DeepSeek'              => 'https://api.deepseek.com/chat/completions',
                '无问芯穹'                => 'https://cloud.infini-ai.com/maas/',                  // 特殊处理，需要拼接modelvalue
                '硅基流动'                => 'https://api.siliconflow.cn',
                'LinkAI'                => 'https://api.link-ai.chat',
                '本地知识库'              => 'dynamic',                 // 特殊处理，需要获取当前域名
                'bard'                  => 'https://generativelanguage.googleapis.com',
                'claude'                => 'https://api.anthropic.com',
                'azure'                 => 'https://resource-url.openai.azure.com/openai/deployments/gpt-35-turbo-16k/chat/completions?api-version=2023-05-15',
            ];
            $modelApiAddress = isset($modelApiMap[$modelData['modeltype']])?$modelApiMap[$modelData['modeltype']]:'https://api.openai.com';
            if($modelData['modeltype']=='无问芯穹'){
                $modelApiAddress = $modelApiAddress.$modelData['modelvalue'].'/nvidia/chat/completions';
            }
        }
        return json([
            'code'  => 1,
            'msg'   => '请求成功',
            'text'  => $modelApiAddress,
        ]);
    }

    public function del($id=0){
        if($id){
            $res = AiApikeyModel::destroy($id);
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
