<?php
 

// [ 应用入口文件 ]
namespace think;

// [ PHP版本检查 ]
header("Content-type: text/html; charset=utf-8");
header('Access-Control-Allow-Origin: *'); // 允许所有域名访问
header('Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE'); // 允许的请求方法
header('Access-Control-Allow-Credentials: true'); // 是否允许发送 Cookies
header('Access-Control-Allow-Headers: Content-Type, Content-Length, Accept-Encoding,X-Requested-With, Origin,Authorization'); // 自定义请求头
if (version_compare(PHP_VERSION, '5.6', '<')) {
    die('PHP版本过低，最少需要PHP5.6，请升级PHP版本！');
}

// 定义后台入口文件
define('ADMIN_FILE', 'admin.php');

// 加载基础文件
require __DIR__ . '/../thinkphp/base.php';

// 支持事先使用静态方法设置Request对象和Config对象

// 检查是否安装
if(!is_file('../data/install.lock')){
    define('BIND_MODULE', 'install');
    Container::get('app')->bind('install')->run()->send();
} else {
    // 执行应用并响应
    Container::get('app')->run()->send();
}
