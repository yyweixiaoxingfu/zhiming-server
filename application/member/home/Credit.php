<?php
namespace app\member\home;

use app\index\controller\Api;
use app\member\model\MemberCreditLogsModel;
use app\member\model\MemberCreditModel;
use think\Db;


/**
 * @title 会员钱包
 * Class Credit
 * @package app\member\home
 */
class Credit extends Api
{


      /**
     * @title 我的钱包
     * @author zxn
     * @method POST
     * @url /member/credit/get_credit
     * @header name:Authorization require:1 default:1455523e6d407a3c desc:token
     */
    public function get_credit()
    {
        if ($this->request->isPost()) {
            $member      = $this->memberInfo;
            //$credit_data = MemberCreditModel::where('member_id', $member['id'])->group('type')->column('credit','type');
            $currency_data       = config('currency');              //货币参数
            $credit_data         = [];
            foreach ($currency_data as $key => $value) {
                $credit_data[$value] = MemberCreditModel::getCredit($member['id'], $value, ['credit']);
            }
            // print_r($credit_data);die;
            // if (empty($credit_data)) {
            //     return returnData(0,'',[]);
            // }
            return returnData(1,'',$credit_data);
        }
    }


    /**
     * @title 钱包明细
     * @author zxn
     * @method POST
     * @url /member/credit/logs
     * @header name:Authorization require:1 default:1455523e6d407a3c desc:token
     * @param name:type type:string require:1 default: desc:账号类型，不填默认deposit
     * @param name:page type:string require:1 default: desc:分页
     * @param name:time type:string require:1 default: desc:时间年-月
     */
    public function logs(){
        if ($this->request->isPost()) {
            $member         = $this->memberInfo;
            $post_data      = SafeFilterItem(input('post.'));
            $type           = isset($post_data['type']) && $post_data['type'] ? $post_data['type'] : 'deposit';
            $page           = isset($post_data['page']) && $post_data['page'] ? (int)$post_data['page'] : 1;
            $limit          = isset($post_data['limit']) && $post_data['limit'] ? (int)$post_data['limit'] : 20;
            $credit_total   = MemberCreditModel::getCredit($member['id'],$type,['credit'],false);
            
            // 数据列表
            $where          = [['member_id', '=', $member['id']], ['type', '=', $type]];
            $date           = isset($post_data['time']) && $post_data['time'] ? $post_data['time'] : '';
            if(!empty($date)){
                $where[] = Db::raw("FROM_UNIXTIME(create_time,'%Y-%m')='" . $date . "'");
            }
            $total = MemberCreditLogsModel::where($where)->count();
            $data_list = MemberCreditLogsModel::where($where)
                ->order('create_time desc,id desc')
                ->page($page,$limit)
                ->select()
                ->each(function ($item){
                    $item['create_time'] = format_time($item['create_time']);
                    return $item;
                });
            return returnData(1,'',['data'=>$data_list,'total'=>$total,'credit_total'=>$credit_total]);
        }
    }

}