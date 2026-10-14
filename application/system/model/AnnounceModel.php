<?php
//

namespace app\system\model;

use think\Exception;
use think\Model;
use think\Db;
use think\helper\Hash;

/**
 * 商品分类模型
 * @package app\admin\model
 */
class AnnounceModel extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $name = 'system_announce';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;


    public function updateOrCreate($where,$data){
        $has = $this->where($where)->count();
        if ($has>0){
            $res = $this->update($data,$where);
        }else{
            $res = $this->create($data);
        }
        return $res;
    }
}
