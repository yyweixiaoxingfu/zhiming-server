<?php

use think\facade\Env;
return [
    'secret'      => Env::get('jwt.secret','123456'),
    //JWT time to live
    'ttl'         => Env::get('jwt.jwt_ttl',60),
    //Refresh time to live
    'refresh_ttl' => Env::get('jwt.jwt_refresh_ttl',20160),
    // 单点登录
    'single' => Env::get('jwt.single',false),

    'defaults' => [
        'guard' => 'users'
    ],

    'guards' => [
        'users' => [
            'model' => \app\member\model\MemberModel::class,
        ],
    ]
];
