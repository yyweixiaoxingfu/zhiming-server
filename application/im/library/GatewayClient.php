<?php
/**
 * GatewayClient客户端库
 */

namespace app\im\library;

class GatewayClient
{
    private static $registerAddress = '';
    private static $secretKey = '';
    private static $timeout = 3;

    /**
     * 设置注册中心地址
     * @param string $address 注册中心地址
     */
    public static function setRegisterAddress($address)
    {
        self::$registerAddress = $address;
    }

    /**
     * 设置密钥
     * @param string $key 密钥
     */
    public static function setSecretKey($key)
    {
        self::$secretKey = $key;
    }

    /**
     * 设置超时时间
     * @param int $timeout 超时时间（秒）
     */
    public static function setTimeout($timeout)
    {
        self::$timeout = $timeout;
    }

    /**
     * 发送消息给指定用户
     * @param int $uid 用户ID
     * @param string $message 消息内容
     * @return bool
     */
    public static function sendToUid($uid, $message)
    {
        return self::sendCommand('sendToUid', [$uid, $message]);
    }

    /**
     * 发送消息给指定组
     * @param string $group 组名
     * @param string $message 消息内容
     * @return bool
     */
    public static function sendToGroup($group, $message)
    {
        return self::sendCommand('sendToGroup', [$group, $message]);
    }

    /**
     * 发送命令到注册中心
     * @param string $command 命令
     * @param array $args 参数
     * @return bool
     */
    private static function sendCommand($command, $args)
    {
        if (empty(self::$registerAddress)) {
            return false;
        }

        $data = [
            'type' => $command,
            'args' => $args,
            'time' => time(),
            'secret_key' => self::$secretKey
        ];

        $context = stream_context_create([
            'socket' => [
                'timeout' => self::$timeout
            ]
        ]);

        $socket = stream_socket_client(self::$registerAddress, $errno, $errstr, self::$timeout, STREAM_CLIENT_CONNECT, $context);
        if (!$socket) {
            return false;
        }

        fwrite($socket, json_encode($data) . "\n");
        $response = fread($socket, 8192);
        fclose($socket);

        return $response === 'ok';
    }
}
