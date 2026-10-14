<?php
namespace app\message\admin;


use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\message\model\MessageActionModel;
use app\message\model\MessageTemplateModel;
use think\facade\Cache;
use think\facade\Env;
use think\helper\Hash;

class Index extends Admin
{
    public function setting(){
        $model = new MessageActionModel();
        $data = $model->order('sort asc')->select();
        $drivers = $model->getDrivers();
        $columns = [
            ['action','场景','text'],
            ['keywords_desc','模板变量','text'],
            ['title','默认标题','text.edit'],
            ['content','默认模板','textarea.edit'],
        ];
        foreach ($drivers as $key => $value){
            $columns[] = [$key.'_template',$value.'模板'];
        }
        foreach ($data as &$item){
            foreach ($drivers as $key => $value){
                if($item[$key.'_enabled']){
                    $item[$key.'_template'] = '<div class="table-cell"><a class="pop btn btn-sm btn-success" target="_blank" href="'. url('template',['action'=>$item['action'],'driver'=>$key,'_pop'=>1]) .'">模板</a></div>';
                }else{
                    $item[$key.'_template'] = '--';
                }
            }
        }
        $columns[] = ['right_button', '操作', 'btn'];
        $debug = config('app_debug');
        $builder = ZBuilder::make('table')
            ->setTableName($model->getName())
            ->addColumns($columns)
            ->setColumnWidth(['keywords_desc'=>150,'title'=>'120','content'=>260])
            ->addTopButton('config',['title' => '参数配置', 'class' => 'btn btn-sm btn-warning','icon' => 'fa fa-edit', 'href' => url('config')],true)
            ->addRightButton('edit', ['class' => 'btn btn-sm btn-warning', 'href' => url('edit', ['id' => '__id__'])],true)
            ->setExtraCss($this->css)
            ->hideCheckbox(true)
            ->setRowList($data);
        if($debug){
            $builder ->addTopButton('add',[],true)
                ->addRightButton('delete', ['class' => 'btn btn-sm btn-danger ajax-get confirm']);
        }
        return $builder->fetch();
    }

    public function config($group = 'sms'){
        $config = config('message.');
        empty($config) && $config['driver'] = MessageActionModel::getDrivers();
        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();
            switch ($group){
                case 'sms':
                    $config['sms'] = [
                        'is_close' => $data['sms_is_close'] ? true : false, // 是否是测试不发送信息 true/false
                        'default' => $data['sms_default'], // 默认短信引擎
                        // 账号配置
                        'engine' => [
                            'aliyun' => [ // 阿里云短信
                                'sign' => $data['aliyun_sign'] ?? '', // 签名
                                'key' => $data['aliyun_key'] ?? '', // AccessKeyID
                                'secret' => $data['aliyun_secret'] ?? '',// AccessKeySecret
                            ],
                            'duanxinbao' => [ // 短信宝
                                'key' => $data['duanxinbao_key'] ?? '', // 账号
                                'secret' => $data['duanxinbao_secret'] ?? '',// 秘钥
                            ],
                            'juhe' => [ // 聚合数据
                                'app_key' => $data['juhe_app_key'] ?? '', // 您申请的APPKEY
                            ],
                            'kaixintong' => [ // 凯信通
                                'userid' => $data['kaixintong_userid'] ?? '', // 凯信通企业ID
                                'account' => $data['kaixintong_account'] ?? '',// 凯信通账户
                                'password' => $data['kaixintong_password'] ?? '', // 凯信通密码
                            ],
                            'qiyexinshi' => [ // 企业信使
                                'userid' => $data['qiyexinshi_userid'] ?? '', // 凯信通企业ID
                                'account' => $data['qiyexinshi_account'] ?? '',// 凯信通账户
                                'password' => $data['qiyexinshi_password'] ?? '', // 凯信通密码
                            ],
                            'yunxinshi' => [ // 云信使
                                'uid' => $data['yunxinshi_uid'] ?? '', // 账号
                                'password' => $data['yunxinshi_password'] ?? '', // 密码
                            ],
                            'huaweiyun' => [ // 华为云
                                'endpoint' => $data['huaweiyun_endpoint'] ?? '', // APP接入地址
                                'app_key' => $data['huaweiyun_app_key'] ?? '', // app_key
                                'app_secret' => $data['huaweiyun_app_secret'] ?? '', // app_secret
                                'sender' => $data['huaweiyun_sender'] ?? '', // 国内短信签名通道号或国际/港澳台短信通道号
                                'signature' => $data['huaweiyun_signature'] ?? '', // 签名名称
                            ],
                        ],
                    ];
                    break;
                case 'email':
                    $config['email'] = [
                        'is_close'      => $data['email_is_close'] ? true : false,
                        'host'          => $data['email_host'] ?? '',
                        'username'      => $data['email_username'] ?? '',
                        'password'      => $data['email_password'] ?? '',
                        'port'          => $data['email_port'] ?? '',
                        'fromEmail'     => $data['email_fromEmail'] ?? '',
                        'fromUserName'  => $data['email_fromUserName'] ?? '',
                    ];
                    break;
                case 'info':
                    $config['info'] = [
                        'is_close' => $data['info_is_close'] ? true : false,
                    ];
                    break;
                case 'notice':
                    $config['notice'] = [
                        'is_close' => $data['notice_is_close'] ? true : false,
                        'default' => $data['notice_default'] ?? 'jpush', // 默认推送引擎
                        // 账号配置
                        'engine' => [
                            'jpush' => [ // 极光推送
                                'app_key' => $data['notice_app_key'] ?? '', // app_key
                                'app_secret' => $data['notice_app_secret'] ?? '', // app_secret
                            ],
                        ],
                    ];
                    break;
                default:;
            }
            file_put_contents(Env::get('config_path').'message.php', "<?php \r\n return " . var_export($config, true) . ";");
            $this->success('更新成功', '','_parent_reload');
        } else {
            $table_list = [];
            foreach ($config['driver'] as $key => $value){
                $table_list[$key] = ['title' => $value, 'url' => url('config',['group' => $key])];
            }
            $data_list = [
                'sms' => [
                    ['radio','sms_is_close','是否关闭','',['开启','关闭'],$config['sms']['is_close']?? true],
                    ['radio','sms_default','默认短信引擎','',[
                        'aliyun' => '阿里云',
                        'huaweiyun' => '华为云',
                        'kaixintong' => '凯信通',
                        'juhe' => '聚合数据',
//                        'duanxinbao' => '短信宝',
//                        'qiyexinshi' => '企业信使',
//                        'yunxinshi' => '云信使',
                    ],$config['sms']['default']??'aliyun'],
                    ['text','aliyun_sign','签名','',$config['sms']['engine']['aliyun']['sign']??''],
                    ['text','aliyun_key','AccessKeyID','',$config['sms']['engine']['aliyun']['key']??''],
                    ['text','aliyun_secret','AccessKeySecret','',$config['sms']['engine']['aliyun']['secret']??''],
                    ['text','kaixintong_userid','凯信通企业ID','',$config['sms']['engine']['kaixintong']['userid']??''],
                    ['text','kaixintong_account','凯信通账户','',$config['sms']['engine']['kaixintong']['account']??''],
                    ['text','kaixintong_password','凯信通密码','',$config['sms']['engine']['kaixintong']['password']??''],
                    ['text','juhe_app_key','APPKEY','',$config['sms']['engine']['juhe']['app_key']??''],
                    ['text','duanxinbao_key','AccessKeyID','',$config['sms']['engine']['duanxinbao']['key']??''],
                    ['text','duanxinbao_secret','AccessKeySecret','',$config['sms']['engine']['duanxinbao']['secret']??''],
                    ['text','qiyexinshi_userid','企业信使企业ID','',$config['sms']['engine']['qiyexinshi']['userid']??''],
                    ['text','qiyexinshi_account','企业信使账户','',$config['sms']['engine']['qiyexinshi']['account']??''],
                    ['text','qiyexinshi_password','企业信使密码','',$config['sms']['engine']['qiyexinshi']['password']??''],
                    ['text','yunxinshi_uid','云信使账号','',$config['sms']['engine']['yunxinshi']['uid']??''],
                    ['text','yunxinshi_password','云信使密码','',$config['sms']['engine']['yunxinshi']['password']??''],
                    ['text','huaweiyun_endpoint','APP接入地址','',$config['sms']['engine']['huaweiyun']['endpoint']??''],
                    ['text','huaweiyun_app_key','app_key','',$config['sms']['engine']['huaweiyun']['app_key']??''],
                    ['text','huaweiyun_app_secret','app_secret','',$config['sms']['engine']['huaweiyun']['app_secret']??''],
                    ['text','huaweiyun_sender','通道号','国内短信签名通道号或国际/港澳台短信通道号',$config['sms']['engine']['huaweiyun']['sender']??''],
                    ['text','huaweiyun_signature','签名名称','使用国内短信通用模板时,必须填写签名名称',$config['sms']['engine']['huaweiyun']['signature']??''],
                ],
                'email' => [
                    ['radio','email_is_close','是否关闭','',['开启','关闭'],$config['email']['is_close']??true],
                    ['text','email_host','SMTP服务器','',$config['email']['host']??''],
                    ['text','email_username','SMTP服务器邮箱账号','',$config['email']['username']??''],
                    ['text','email_password','授权码','',$config['email']['password']??''],
                    ['text','email_port','服务器端口','',$config['email']['port']??''],
                    ['text','email_fromEmail','发件人邮箱','',$config['email']['fromEmail']??''],
                    ['text','email_fromUserName','发件人','',$config['email']['fromUserName']??''],
                ],
                'info' => [
                    ['radio','info_is_close','是否关闭','',['开启','关闭'],$config['info']['is_close']??true],
                ],
                'notice' => [
                    ['radio','notice_is_close','是否关闭','',['开启','关闭'],$config['notice']['is_close']??true],
                    ['radio','notice_default','默认推送引擎','',['jpush' => '极光推送'],$config['notice']['default']??'jpush'],
                    ['text','notice_app_key','app_key','',$config['notice']['engine']['jpush']['app_key']??''],
                    ['text','notice_app_secret','app_secret','',$config['notice']['engine']['jpush']['app_secret']??''],
                ]
            ];
            // 使用ZBuilder快速创建表单
            return ZBuilder::make('form')
                ->setPageTitle('系统设置')
                ->setTabNav($table_list, $group)
                ->addFormItems($data_list[$group])
                ->setTrigger('sms_default','aliyun','aliyun_sign,aliyun_key,aliyun_secret')
                ->setTrigger('sms_default','kaixintong','kaixintong_userid,kaixintong_account,kaixintong_password')
                ->setTrigger('sms_default','juhe','juhe_app_key')
                ->setTrigger('sms_default','duanxinbao','duanxinbao_key,duanxinbao_secret')
                ->setTrigger('sms_default','qiyexinshi','qiyexinshi_userid,qiyexinshi_account,qiyexinshi_password')
                ->setTrigger('sms_default','yunxinshi','yunxinshi_uid,yunxinshi_password')
                ->setTrigger('sms_default','huaweiyun','huaweiyun_endpoint,huaweiyun_app_key,huaweiyun_app_secret,huaweiyun_sender,huaweiyun_signature')
                ->setTrigger('notice_default','jpush','notice_app_key,notice_app_secret')
                ->fetch();

        }
    }

    public function template($action='',$driver = ''){
        if($this->request->isPost()){
            $POST = $this->request->param();
            $result = $this->validate($POST, 'MessageValidate.template');
            if ($result !== true) {
                $this->error($result);
            }
            $model = new MessageTemplateModel();
            $res = $model->setTemplate($POST);
            if(!$res){
                $this->error($model->getError() ?? '操作失败');
            }
            $cache_key = 'message_temp_'.$POST['driver'].'_'.$POST['action'];
            Cache::rm($cache_key);
            $this->success('操作成功');
        }
        $info = MessageTemplateModel::where(['action'=>$action,'driver'=>$driver])->find();
        $keywords_desc = MessageActionModel::where(['action'=>$action])->value('keywords_desc');
        $keywords_desc = '模板变量：'.$keywords_desc;
        $driver == 'sms' &&  $keywords_desc = '根据短信引擎，可配置短信模板ID或者自定义模板内容；'.$keywords_desc;
        return ZBuilder::make('form')
            ->addFormItems([
                ['text','title','标题'],
                ['textarea','template','模板',$keywords_desc]
            ])
            ->addHidden('action',$action)
            ->addHidden('driver',$driver)
            ->setFormData($info)
            ->fetch();
    }

    public function add(){
        if($this->request->isPost()){
            $POST = $this->request->param();
            $result = $this->validate($POST, 'MessageValidate.add');
            if ($result !== true) {
                $this->error($result);
            }
            foreach ($POST['driver'] as $driver){
                $POST[$driver.'_enabled'] = 1;
            }
            $model = new MessageActionModel();
            $has = $model->where(['action'=>$POST['action']])->find();
            if($has){
                $this->error('场景标识已存在');
            }
            $res = $model->create($POST);
            if(!$res){
                $this->error('操作失败');
            }
            $this->success('操作成功','','_parent_reload');
        }
        $drivers = MessageActionModel::getDrivers();
        return ZBuilder::make('form')
            ->addFormItems([
                ['text','action','场景标识','20位以内的字母数字下划线组成'],
                ['text','desc','场景描述'],
                ['text','title','默认标题'],
                ['textarea','content','默认模板'],
                ['textarea','keywords_desc','模板变量','在模板中可能会使用到的变量，需提前定义'],
                ['checkbox','driver','模板引擎','',$drivers],
                ['text','sort','排序','越小越靠前',100],
            ])
            ->fetch();
    }

    public function edit($id = 0){
        if($this->request->isPost()){
            $POST = $this->request->param();
            $result = $this->validate($POST, 'MessageValidate.edit');
            if ($result !== true) {
                $this->error($result);
            }
            $info = MessageActionModel::get($POST['id']);
            if(!$info){
                $this->error('场景不存在');
            }
            $res = $info->save($POST);
            if(!$res){
                $this->error('操作失败');
            }
            $this->success('操作成功','','_parent_reload');
        }
        $info = MessageActionModel::get($id);
        return ZBuilder::make('form')
            ->addFormItems([
                ['text','action','场景标识','20位以内的字母数字下划线组成','','','disabled'],
                ['text','desc','场景描述'],
                ['text','title','默认标题'],
                ['textarea','content','默认模板'],
                ['textarea','keywords_desc','模板变量','在模板中可能会使用到的变量，需提前定义'],
                ['text','sort','排序','越小越靠前',100],
            ])
            ->addHidden('id',$id)
            ->setFormData($info)
            ->fetch();
    }

}