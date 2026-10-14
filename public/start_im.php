<?php
/**
 * IM GatewayWorker 启动脚本
 * 运行命令：php start_im.php [start|stop|restart|status|reload]
 */

ini_set('display_errors', 'on');
use Workerman\Worker;

// 定义项目根目录
$project_root = dirname(__DIR__);

// 显示启动信息
echo "=============================================\n";
echo "          IM GatewayWorker 启动脚本          \n";
echo "=============================================\n";
echo "当前操作: " . ($argv[1] ?? 'start') . "\n";
echo "项目路径: {$project_root}\n";
echo "PHP版本: " . PHP_VERSION . "\n";
echo "操作系统: " . PHP_OS . "\n";
echo "=============================================\n\n";

// 检查操作系统
if(strpos(strtolower(PHP_OS), 'win') === 0)
{
    exit("start_im.php 不支持 Windows 系统，请使用 start_im.bat\n");
}

// 检查扩展
$required_extensions = ['pcntl', 'posix'];
foreach($required_extensions as $ext)
{
    if(!extension_loaded($ext))
    {
        exit("请安装 {$ext} 扩展。参考：http://doc3.workerman.net/appendices/install-extension.html\n");
    }
}

// 检查命令行参数
$action = $argv[1] ?? 'start';
$valid_actions = ['start', 'stop', 'restart', 'status', 'reload'];
if(!in_array($action, $valid_actions))
{
    exit("无效的命令参数！\n用法：php start_im.php [start|stop|restart|status|reload]\n例如：php start_im.php start\n");
}

// 检查 vendor 目录是否存在
if(!is_dir($project_root . '/vendor'))
{
    exit("未找到 vendor 目录，请先执行 composer install\n");
}

// 检查 ThinkPHP 框架是否存在
if(!is_file($project_root . '/thinkphp/base.php'))
{
    exit("未找到 ThinkPHP 框架文件，请检查项目结构\n");
}

// 标记是全局启动
define('GLOBAL_START', 1);

// 设置应用路径
define('APP_PATH', $project_root . '/application/');

// 自动加载
require_once $project_root . '/vendor/autoload.php';

// 加载ThinkPHP框架
require $project_root . '/thinkphp/base.php';

// 加载IM网关启动文件
$gateway_start_file = $project_root . '/application/im/gateway/start.php';
if(!is_file($gateway_start_file))
{
    exit("未找到 IM 网关启动文件：{$gateway_start_file}\n");
}
require_once $gateway_start_file;

// 运行所有服务
echo "正在执行 {$action} 操作...\n\n";
Worker::runAll();

echo "\n=============================================\n";
echo "操作完成！\n";
echo "=============================================\n";