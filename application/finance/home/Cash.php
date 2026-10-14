<?php
namespace app\finance\home;

use app\finance\model\MemberCashModel;
use app\index\controller\Api;
use app\member\model\MemberModel;
use think\Db;

/**
 * @title 用户提现
 * Class Cash
 * @package app\finance\home
 */
class Cash extends Api
{
    /**
     * @title 余额申请提现
     * @header name:Authorization require:1 default:1455523e6d407a3c desc:token
     * @param name:amount type:string require:1 default: desc:提现金额
     * @param name:method type:string require:1 default: desc:提现方式 bank银行卡
     * @param name:bank_id type:string require:1 default: desc:银行卡id
     * @param name:name type:string require:1 default: desc:支付宝姓名
     * @param name:account type:string require:1 default: desc:支付宝账户
     * @param name:receiving_code type:string require:1 default: desc:微信收款码
     * @return data:申请提现@
     * @data
     * @author ycz
     * @method POST
     * @url /finance/cash/apply
     */
    public function apply()
    {
        if ($this->request->isPost()) {
            $member    = $this->memberInfo;
            $data      = input('post.');
            $validate  = $this->validate($data, 'CashValidate');
            if ($validate != true) {
                return returnData(0, $validate);
            }
            $model  = new MemberCashModel();
            $result = $model->applyCash($member, $data);
            if ($result['status'] != 1) {
                return returnData(0, $result['error']);
            }
            return returnData(1, '申请成功');
        }
    }

    /**
     * @title 申请提现列表
     * @author zwp
     * @method POST
     * @url /finance/cash/cash_list
     * @header name:Authorization require:1 default:1455523e6d407a3c desc:token
     * @param name:page type:number require:0 default: 1 desc:分页
     * @param name:time type:string require:1 default: desc:时间年-月
     */
    public function cash_list()
    {
        if ($this->request->isPost()) {
            $member_id = $this->memberID;
            $status = MemberCashModel::getStatus();
            $method = MemberCashModel::getMethods();
            $where = [['member_id','=',$member_id]];
            $date = $this->request->param('time');
            if(!empty($date)){
                $where[] = Db::raw("FROM_UNIXTIME(create_time,'%Y-%m')='" . $date . "'");
            }
            $result = MemberCashModel::where($where)
                ->field('id,sn,member_id,amount,status,method,create_time')
                ->order('create_time desc')
                ->paginate()
                ->each(function ($item) use ($status,$method){
                    $item['status'] = $status[$item['status']] ?? '';
                    $item['method'] = $method[$item['method']] ?? '';
                    $item['create_time'] = format_time($item['create_time']);
                    return $item;
                });
            return returnData(1, '操作成功', $result);
        }
    }
}