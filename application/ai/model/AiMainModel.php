<?php
namespace app\ai\model;

use think\Model;

/**
 * 智能模型
 */
class AiMainModel extends Model
{
    protected $name = 'ai_main';
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;
    
    /**
     * 获取AI主配置
     * @param int $id 配置ID
     * @return array|false
     */
    public static function getConfig($id = 1)
    {
        return self::get($id);
    }
    
    /**
     * 获取代理地址
     * @return string
     */
    public static function getProxyAddress()
    {
        return self::where('id', 1)->value('proxyaddress');
    }
    
    /**
     * 更新配置
     * @param array $data 配置数据
     * @param int $id 配置ID
     * @return bool
     */
    public static function updateConfig($data, $id = 1)
    {
        return self::where('id', $id)->update($data);
    }
}