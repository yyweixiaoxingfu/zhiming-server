<?php
//

namespace app\system\model;

use think\Model;

/**
 * Class RegionModel
 * @package app\system\model
 */
class RegionModel extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $name = 'system_region';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    public static function getAll($fields = true, $map = [])
    {
        $map = [];
        return self::where($map)->order('sort,id')->column($fields, 'id');
    }
    /**
     * 获取所有子节点id
     * @param int $pid 父级id
     * @author 
     * @return array
     */
    public static function getChildsId($pid = 0)
    {
        $ids = self::where('pid', $pid)->column('id');
        foreach ($ids as $value) {
            $ids = array_merge($ids, self::getChildsId($value));
        }
        return $ids;
    }
    /**
     * 获取所有子节点id
     * @param int $pid 父级id
     * @author 
     * @return array
     */
    public static function getChildsCodeByCode($pCode,$self=false)
    {
        $codes = self::whereRaw('FIND_IN_SET('.$pCode.',path)')->column('code');
        if ($self) $codes[] = $pCode;
        return $codes;
    }

    public function updateOrCreate($where,$data){
        $has = $this->where($where)->count();
        if ($has>0){
            $res = $this->update($data,$where);
        }else{
            $res = $this->create($data);
        }
        return $res;
    }

    public static function getRegion($params=[],&$msg='',$value='code'){
        $upRegionCode = $params['region'] ?? null;
        $upRegionId = $params['id'] ?? null;
        if ($upRegionCode || $upRegionId){
            if ($upRegionCode){
                $upInfo = Self::where('code',$upRegionCode)->field('id')->find();
            }
            if ($upRegionId){
                $upInfo = Self::where('id',$upRegionId)->field('id')->find();
            }
            if (!isset($upInfo['id'])) return false;
            $where = [
                'pid'=>$upInfo['id']
            ];
        }else{
            $where = [
                'pid'=>0
            ];
        }
        $field ='name as label,'.$value.' as value';

        $res = Self::where($where)->field($field)->order('sort,id')->fetchCollection()->select();
        if (isset($params['type']) && $params['type']=='admin_select'){
            $res = $res->column('label','value');
        }
        return is_array($res) ? $res : $res->toArray();
    }

    public static function modifyRegion($code,$string='/',$brother=true){
        try{
            $info=Self::where('code',$code)->field('id,path,name,code,pid')->find();
            if(empty($info)) return [];
            $region=$regionCode=[];
            if ($info['pid']!=0){
                $parentCode=explode(',',trim($info['path'],','));
                //$parentCodeString=str_replace(',', '","', trim($info['path'],','));

                $parentInfoAll=Self::whereIn('code',$parentCode)->select();

                $parentInfoAll=$parentInfoAll->column(null,'code');

                foreach ($parentCode as $pCode){
                    if(!isset($parentInfoAll[$pCode])) return null;
                    $parentInfo=$parentInfoAll[$pCode];
                    $region[]=$parentInfo['name'];
                    $regionCode[]=$parentInfo['code'];
                }
            }



            array_push($region,$info['name']);
            array_push($regionCode,$info['code']);
            return [
                'name'=>implode($string,$region),
                'path'=>implode($string,$regionCode),
                'code'=>$info['code'],
                'brother'=>$brother ? Self::getRegion(['id'=>$info['pid']]) : []
            ];
        }catch (\Exception $e){
            return null;
        }
    }

    public static function getRegionNameByCode($code){
        if(empty($code)) return '';
        $name = self::where(['code'=>$code])->value('name');
        return $name;
    }
}
