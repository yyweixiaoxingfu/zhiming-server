<?php
namespace app\system\library\traits;



trait ModelBaseTrait
{
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
