<?php
/**
 * GatewayWorker启动文件 - 仅用于Linux系统
 * Windows系统请使用单独的启动文件
 */

// 预定义必要的类，避免think-captcha扩展加载时出错
class Validate {
    public static function extend() {}
    public static function rule() {}
    public static function message() {}
    public static function setTypeMsg() {}
}
class Route {
    public static function get() {}
    public static function post() {}
    public static function group() {}
    public static function rule() {}
    public static function any() {}
}

// 定义应用目录
define('APP_PATH', __DIR__ . '/../../../application/');

// 加载composer自动加载
require_once __DIR__ . '/../../../vendor/autoload.php';

// 使用GatewayWorker和Workerman
use GatewayWorker\BusinessWorker;
use GatewayWorker\Gateway;
use GatewayWorker\Register;
use Workerman\Worker;

// 检查是否为Windows系统
$is_windows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

if ($is_windows) {
    // Windows系统下，我们使用一个简化的方式启动WebSocket服务
    // 直接使用Workerman的WebSocket服务，而不使用GatewayWorker的复杂架构
    $ws_worker = new Worker('websocket://0.0.0.0:8282');
    $ws_worker->name = 'RaingadIMWebSocket';
    $ws_worker->count = 1;
    
    // 连接事件
    $ws_worker->onConnect = function($connection) {
        echo "New connection established\n";
        $connection->send(json_encode([
            'type' => 'connect',
            'data' => ['client_id' => $connection->id],
            'msg' => '连接成功'
        ]));
    };
    
    // 消息事件
    $ws_worker->onMessage = function($connection, $data) {
        echo "Received message: $data\n";
        // 简单的echo服务，将收到的消息原样返回
        $connection->send($data);
    };
    
    // 关闭事件
    $ws_worker->onClose = function($connection) {
        echo "Connection closed\n";
    };
} else {
    // Linux系统下，正常启动GatewayWorker架构
    // 注册中心
    $register = new Register('text://0.0.0.0:1236');
    
    // Gateway进程
    $gateway = new Gateway('websocket://0.0.0.0:8282');
    $gateway->name = 'RaingadIMGateway';
    $gateway->count = 4;
    $gateway->lanIp = '127.0.0.1';
    $gateway->startPort = 2000;
    $gateway->registerAddress = '127.0.0.1:1236';
    
    // BusinessWorker进程
    $worker = new BusinessWorker();
    $worker->name = 'RaingadIMBusinessWorker';
    $worker->count = 4;
    $worker->registerAddress = '127.0.0.1:1236';
    $worker->eventHandler = \app\im\gateway\Events::class;
}

// 运行Worker
Worker::runAll();
