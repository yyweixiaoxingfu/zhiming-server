<?php
namespace app\common;

use think\Model;

class CacheModel extends Model
{
    // 需要缓存的字段
    protected $cacheFields = [];
    
    // 缓存过期时间
    protected $cacheExpire = 604800;
    
    /**
     * 获取模型数据
     * @param mixed $data 主键值或查询条件
     * @return array|Model|null
     */
    public static function getCache($data)
    {
        $model = new static();
        $pk = $model->getPk();
        
        // 构建缓存键
        if (is_array($data)) {
            $where = $data;
            $key = $model->name . ':' . md5(json_encode($where));
        } else {
            $where = [$pk => $data];
            $key = $model->name . ':' . $data;
        }
        
        // 尝试从缓存获取
        return CacheManager::get($key, function() use ($model, $where) {
            return $model->where($where)->find();
        });
    }
    
    /**
     * 保存模型数据
     * @param bool $data 数据
     * @return bool 保存结果
     */
    public function saveCache($data = null)
    {
        $result = $this->save($data);
        if ($result) {
            // 清除相关缓存
            $pk = $this->getPk();
            $key = $this->name . ':' . $this->$pk;
            CacheManager::del($key);
        }
        return $result;
    }
    
    /**
     * 删除模型数据
     * @return bool 删除结果
     */
    public function deleteCache()
    {
        $pk = $this->getPk();
        $key = $this->name . ':' . $this->$pk;
        
        $result = $this->delete();
        if ($result) {
            // 清除相关缓存
            CacheManager::del($key);
        }
        return $result;
    }
}
