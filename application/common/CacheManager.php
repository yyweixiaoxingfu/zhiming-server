<?php
namespace app\common;

//use think\Cache;
use think\facade\Cache;
use think\facade\Env;
use think\Db;

class CacheManager
{
   
    // 缓存配置（使用静态属性而不是常量，因为包含动态值）
    private static $config = null;
    
    /**
     * 获取缓存配置
     * @return array 缓存配置
     */
    private static function getConfig()
    {
        if (self::$config === null) {
            // 初始化配置
            self::$config = [
                'redis' => [
                    'prefix' => 'sp_',
                    'expire' => 604800, // 7天
                ],
                'file' => [
                    'path' => Env::get('root_path') . 'public/cache/',
                    'expire' => 2592000, // 30天
                ],
                'threshold' => [
                    'json' => 1024, // JSON数据超过1KB则存储到文件系统
                ],
            ];
        }
        return self::$config;
    }
    
    /**
     * 获取缓存数据
     * @param string $key 缓存键
     * @param callable $callback 回调函数，用于获取原始数据
     * @return mixed 缓存数据
     */
    public static function get($key, $callback = null)
    {
        // 1. 尝试从Redis获取
        $data = Cache::store('redis')->get($key);
        if ($data !== false) {
            return $data;
        }
        
        // 2. 尝试从文件系统获取
        $filePath = self::getFilePath($key);
        if (file_exists($filePath)) {
            $data = file_get_contents($filePath);
            $data = json_decode($data, true);
            if ($data) {
                // 写入Redis缓存
                $config = self::getConfig();
                Cache::store('redis')->set($key, $data, $config['redis']['expire']);
                return $data;
            }
        }
        
        // 3. 从数据源获取
        if ($callback) {
            $data = call_user_func($callback);
            if ($data) {
                self::set($key, $data);
            }
            return $data;
        }
        
        return null;
    }
    
    /**
     * 设置缓存数据
     * @param string $key 缓存键
     * @param mixed $data 缓存数据
     * @return bool 设置结果
     */
    public static function set($key, $data)
    {
        // 1. 写入Redis缓存
        $config = self::getConfig();
        $result = Cache::store('redis')->set($key, $data, $config['redis']['expire']);
        
        // 2. 对于大数据，同时写入文件系统
        $dataSize = strlen(json_encode($data));
        if ($dataSize > $config['threshold']['json']) {
            $filePath = self::getFilePath($key);
            $dir = dirname($filePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($filePath, json_encode($data, JSON_UNESCAPED_UNICODE));
        }
        
        return $result;
    }
    
    /**
     * 删除缓存数据
     * @param string $key 缓存键
     * @return bool 删除结果
     */
    public static function del($key)
    {
        // 1. 删除Redis缓存
        Cache::store('redis')->rm($key);
        
        // 2. 删除文件系统缓存
        $filePath = self::getFilePath($key);
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        return true;
    }
    
    /**
     * 获取文件存储路径
     * @param string $key 缓存键
     * @return string 文件路径
     */
    private static function getFilePath($key)
    {
        $key = str_replace(':', '/', $key);
        $config = self::getConfig();
        return $config['file']['path'] . $key . '.json';
    }
    
    /**
     * 清理过期缓存
     * @return array 清理结果
     */
    public static function cleanup()
    {
        $result = [
            'redis' => 0,
            'file' => 0,
        ];
        
        // 清理Redis过期缓存（Redis自动处理）
        
        // 清理文件系统过期缓存
        $config = self::getConfig();
        $filePath = $config['file']['path'];
        $expireTime = time() - $config['file']['expire'];
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($filePath),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getMTime() < $expireTime) {
                unlink($file->getPathname());
                $result['file']++;
            }
        }
        
        return $result;
    }
}
