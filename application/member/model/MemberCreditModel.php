<?php
namespace app\member\model;

use think\Model;
use think\Db;

/**
 * 后台用户模型
 * @package app\admin\model
 */
class MemberCreditModel extends Model
{

    // 设置当前模型对应的完整数据表名称
    protected $name = 'member_credit';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    public function member()
    {
        return $this->belongsTo('MemberModel', 'member_id', 'id');
    }

    public static function getCredit($userId, $type, $column = ['credit'], $update = true)
    {
        $creditInfoBuilder = self::where('member_id', $userId)->where('type', $type);
        $creditInfo        = $creditInfoBuilder->field($column)->find();
        if (!$creditInfo && !$update) {
            return false;
        }
        if (!$creditInfo) {
            $creditInfo = self::initCredit($userId, $type);
        }
        $key = current($column);
        if (count($column) == 1 && $key != '*') {
            $data = $creditInfo->getData($key);
        } else {
            $data = $creditInfo->getData();
        }
        return $data;
    }

    public static function initCredit($userId, $type)
    {
        $creditInfo = self::where('member_id', $userId)->where('type', $type)->count();
        if ($creditInfo == 0) {
            $data = [
                'member_id' => $userId,
                'type'      => $type,
                'credit'    => 0,
                'freeze'    => 0,
            ];
            $sdf  = self::create($data);
            if (!$sdf) return false;
            return $sdf;
        }
        return true;
    }

    public static function credit($userId, $type, $num, $info, $time = null, $target_id = null, $cate = 0, $admin_id = null)
    {
        $res = self::initCredit($userId, $type);
        if (!$res) return false;
        if (!$num) return true;
        Db::startTrans();
        if ($num > 0) {
            $isDone = self::where('member_id', $userId)->where('type', $type)->setInc('credit', $num);
        } else {
            $isDone = self::where('member_id', $userId)->where('type', $type)->where('credit', '>=', -$num)->setInc('credit', $num);
        }

        if (!$isDone) {
            Db::rollback();
            return false;
        } else {
            if ($num >= 0) {
                $status = 0;
            } else {
                $status = 1;
            }
            $log = [
                'member_id'   => $userId,
                'type'        => $type,
                'status'      => $status,
                'num'         => $num,
                'cate'        => $cate,
                'info'        => $info,
                'create_time' => empty($time) ? time() :$time,
                'admin_id'    => empty($admin_id) ? 0 : $admin_id,
                'target_id'   => empty($target_id) ? 0 : $target_id,
            ];

            $lId = Db::name('member_credit_logs')->insert($log);
            if (!$lId) {
                Db::rollback();
                return false;
            }
            Db::commit();
            return true;
        }
    }

    public function getQuery($query, $params)
    {
        $filters                = isset($params['filters']) ? $params['filters'] : null;
        $sortArr                = isset($params['sort']) ? $params['sort'] : null;
        $sortArr['create_time'] = 'desc';
        if (isset($filters['type']) && $filters['type']) {
            $query->where('type', '=', $filters['type']);
        }
        if ($sortArr) {
            foreach ($sortArr as $key => $sort) {
                if (isset($key) && isset($sort)) {
                    $query = $query->order($key, $sort);
                }
            }
        }
        return $query;

    }

    public static function getCreditName($type=''){
        $curreny   = config('currency');
        $currency_ = array_flip($curreny);
        if($type && !empty($currency_)){
            return $currency_[$type] ?? '';
        }
        
        return $currency_;
    }

    // 组装log数据
    public static function assembleCreditLog($userId, $type, $num, $info, $time = null, $target_id = null, $cate = 0, $admin_id = null){
        if ($num >= 0) {
            $status = 0;
        } else {
            $status = 1;
        }
        $log = [
            'member_id'   => $userId,
            'type'        => $type,
            'status'      => $status,
            'num'         => $num,
            'cate'        => $cate,
            'info'        => $info,
            'create_time' => empty($time) ? time() :$time,
            'admin_id'    => $admin_id,
            'target_id'   => $target_id,
        ];
        return $log;
    }

    public static function creditAll($logs,$cate='commission')
    {
        $userlist = [];
        foreach ($logs as $log){
            if(empty($userlist[$log['member_id']][$log['type']])){
                $userlist[$log['member_id']][$log['type']] = $log['num'];
            }else{
                $userlist[$log['member_id']][$log['type']] += $log['num'];
            }
        }
        Db::startTrans();
        foreach ($userlist as $member_id => $user){
            foreach ($user as $type => $num){
                $res = self::initCredit($member_id, $type);
                if (!$res){
                    Db::rollback();
                    return false;
                }
                if ($num > 0) {
                    $isDone = self::where('member_id', $member_id)->where('type', $type)->setInc('credit', $num);
                } else {
                    $isDone = self::where('member_id', $member_id)->where('type', $type)->where('credit', '>=', -$num)->setInc('credit', $num);
                }
                if(!$isDone){
                    Db::rollback();
                    return false;
                }
            }
        }
        $lId = Db::name('member_credit_logs')->insertAll($logs);
        if (!$lId) {
            Db::rollback();
            return false;
        }
        Db::commit();
        return true;
    }

}
