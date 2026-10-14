<?php

return [
    // GatewayWorker配置
    'registerAddress' => env('GATEWAY_REGISTER_ADDRESS', '127.0.0.1:1236'),
    'gatewayAddress' => env('GATEWAY_GATEWAY_ADDRESS', 'websocket://0.0.0.0:8282'),
    'businessAddress' => env('GATEWAY_BUSINESS_ADDRESS', 'tcp://127.0.0.1:8283'),
    'lanIp' => env('GATEWAY_LAN_IP', '127.0.0.1'),
    'startPort' => env('GATEWAY_START_PORT', 2000),
    'daemonize' => env('GATEWAY_DAEMONIZE', false),
    'pidFile' => env('GATEWAY_PID_FILE', __DIR__ . '/../runtime/gateway_worker.pid'),
    'logFile' => env('GATEWAY_LOG_FILE', __DIR__ . '/../runtime/gateway_worker.log'),
    'count' => env('GATEWAY_COUNT', 4),
    'name' => env('GATEWAY_NAME', 'RaingadIM'),
    'protocol' => env('GATEWAY_PROTOCOL', 'websocket'),
    'host' => env('GATEWAY_HOST', '0.0.0.0'),
    'port' => env('GATEWAY_PORT', 8282),
];
