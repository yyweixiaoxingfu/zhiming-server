<?php
//

namespace app\system\model;

use think\Exception;
use think\facade\Cache;
use think\Model;
use think\Db;
use think\helper\Hash;

/**
 * 商品分类模型
 * @package app\admin\model
 */
class ServiceModel extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $name = 'system_services';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    /**
     * @param $module
     * @param $version
     * @return mixed
     */
    public function clear($module, $version=false){
        $deleted = $this->where('module','=',$module);
        if($version){
            $deleted->whereNotIn('version', [$version]);
        }
        $deleted->delete();
        return $deleted;
    }

    public function cached(){
        Cache::clear();
    }


}
