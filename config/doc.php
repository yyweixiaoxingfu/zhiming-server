<?php
return [
    'title' => "APi接口文档",  //文档title
    'version'=>'1.0.0', //文档版本
    'copyright'=>'Powered By Zhangweiwei', //版权信息
    'password' => '123456', //访问密码，为空不需要密码
    //静态资源路径--默认为云上路径，解决很多人nginx配置问题
    //可将assets目录拷贝到public下面，具体路径课自行配置
    'static_path' => '/doc_assets',
    'controller' => [
        //需要生成文档的类
        'app\system\home\Announce',
        'app\system\home\Basic',
        'app\system\home\Sms',
        'app\member\home\Login',
        'app\member\home\Personal',
        'app\member\home\Credit',
        'app\member\home\Team',
        'app\payment\home\Pay',
        'app\finance\home\Cash',
        'app\finance\home\MemberBank',
        'app\message\home\Index',
    ],
    'filter_method' => [
        //过滤 不解析的方法名称
        '_empty'
    ],
    'return_format' => [
        //数据格式
        'code' => "0/1/100/401",// 0失败1成功401跳转登录100绑定手机号
        'msg' => "提示信息",
    ],
    'public_header' => [
        //全局公共头部参数
        //如：['name'=>'version', 'require'=>1, 'default'=>'', 'desc'=>'版本号(全局)']
    ],
    'public_param' => [
        //全局公共请求参数，设置了所以的接口会自动增加次参数
        //如：['name'=>'token', 'type'=>'string', 'require'=>1, 'default'=>'', 'other'=>'' ,'desc'=>'验证（全局）')']
    ],
];
