<?php
namespace app\finance\home;


use app\index\controller\Api;
use app\finance\model\MemberBankModel;

/**
 * @title 会员银行卡
 * Class MemberBank
 * @package app\finance\home
 */
class MemberBank extends Api
{
    /**
     * @title 添加银行卡
     * @header name:Authorization require:1 default:1455523e6d407a3c desc:token
     * @param name:name type:string require:1 default: desc:开户人姓名
     * @param name:card_no type:string require:1 default: desc:银行卡号
     * @param name:bank_name type:captcha require:1 default: desc:银行名称
     * @param name:mobile type:captcha require:1 default: desc:手机号
     * @param name:is_default type:captcha require:1 default: desc:是否默认银行卡
     * @author bh
     * @url /finance/member_bank/add
     * @method POST
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $member_id = $this->memberID;
            $data      = SafeFilter(input('post.'));
            $result    = $this->validate($data, 'MemberBankValidate');
            if (true !== $result) {
                return returnData(0, $result);
            }
            if (!empty($data['is_default'])) {
                MemberBankModel::where('member_id', $member_id)->update(['is_default' => 0]);
            }
            $hasCard = MemberBankModel::where(['member_id' => $member_id, 'card_no' => $data['card_no']])->find();
            if (!empty($hasCard)) {
                return returnData(0, '该银行卡号已存在');
            }
            $res            = MemberBankModel::create([
                'member_id'   => $member_id,
                'name'        => $data['name'],
                'bank_name'   => $data['bank_name'],
                'card_no'     => $data['card_no'],
                'mobile'      => $data['mobile'],
                'is_default'  => empty($data['is_default']) ? 0 : 1,
                'create_time' => time()
            ]);
            if ($res) {
                return returnData(1, '操作成功');
            } else {
                return returnData(0, '操作失败');
            }
        }
    }

    /**
     * @title 编辑银行卡页面
     * @header name:Authorization require:1 default:1455523e6d407a3c desc:token
     * @param name:id type:string require:1 default: desc:卡id
     * @author bh
     * @url /finance/member_bank/edit_card_page
     * @method POST
     */
    public function edit_card_page()
    {
         if ($this->request->isPost()) {
            $member_id = $this->memberID;
            $data      = SafeFilter(input('post.'));
            $id        = intval($data['id']);
            if (empty($id)) {
                return returnData(0, '请选择银行卡');
            }
            $bank = MemberBankModel::where(['id' => $id, 'member_id' => $member_id])->find();
            if (empty($bank)) {
                return returnData(0, '数据有误');
            }
            return returnData(1, '操作成功', $bank);
         }
       
    }

    /**
     * @title 编辑银行卡
     * @header name:Authorization require:1 default:1455523e6d407a3c desc:token
     * @param name:id type:string require:1 default: desc:卡id
     * @param name:name type:string require:1 default: desc:开户人姓名
     * @param name:card_no type:string require:1 default: desc:银行卡号
     * @param name:bank_name type:captcha require:1 default: desc:银行名称
     * @param name:mobile type:captcha require:1 default: desc:手机号
     * @param name:is_default type:captcha require:1 default: desc:是否默认银行卡
     * @author bh
     * @url /finance/member_bank/edit
     * @method POST
     */
    public function edit()
    {
        if ($this->request->isPost()) {
            $member_id = $this->memberID;
            $member    = $this->memberInfo;
            $data      = SafeFilter(input('post.'));
            $id        = intval($data['id']);
            if (empty($id)) {
                return returnData(0, '请选择银行卡');
            }
            $result = $this->validate($data, 'MemberBankValidate');
            if (true !== $result) {
                return returnData(0, $result);
            }
            $bank = MemberBankModel::where(['id' => $id, 'member_id' => $member_id])->find();
            if (empty($bank)) {
                return returnData(0, '数据有误');
            }
            if (!empty($data['is_default'])) {
                MemberBankModel::where('member_id', $member_id)->update(['is_default' => 0]);
            }
            $arr = [
                'name'        => $data['name'],
                'bank_name'   => $data['bank_name'],
                'card_no'     => $data['card_no'],
                'mobile'      => $data['mobile'],
                'is_default'  => empty($data['is_default']) ? 0 : 1,
                'update_time' => time()
            ];
            $res = $bank->save($arr);
            if ($res) {
                return returnData(1, '操作成功');
            } else {
                return returnData(0, '操作失败');
            }
        }
    }

    /**
     * @title 删除银行卡
     * @header name:Authorization require:1 default:1455523e6d407a3c desc:token
     * @param name:id type:string require:1 default: desc:银行卡ID
     * @author bh
     * @url /finance/member_bank/del_card
     * @method POST
     */
    public function del_card()
    {
        if ($this->request->isPost()) {
            $data      = SafeFilter(input('post.'));
            $id        = intval($data['id']);
            $member_id = $this->memberID;
            if (empty($id)) {
                return returnData(0, '请选择银行卡');
            }
            $bank = MemberBankModel::where(['id' => $id, 'member_id' => $member_id])->find();
            if (empty($bank)) {
                return returnData(0, '数据有误');
            }
            $res = MemberBankModel::destroy(['id' => $id, 'member_id' => $member_id]);
            if ($res) {
                return returnData(1, '删除成功');
            } else {
                return returnData(0, '删除失败');
            }
        }
       
    }

    /**
     * @title 银行卡列表
     * @author bh
     * @header name:Authorization require:1 default:1455523e6d407a3c desc:token
     * @url /finance/member_bank/card_list
     * @method POST
     */
    public function card_list()
    {
        if ($this->request->isPost()) {
            $member_id = $this->memberID;
            $data      = MemberBankModel::where(['member_id' => $member_id])
                ->field('id,name,card_no,bank_name,mobile,is_default')
                ->order('is_default desc')
                ->select();
            if (!empty($data)) {
                foreach ($data as &$v) {
                    $v['card_no'] = substr_replace($v['card_no'], '*********', 4, 11);;
                }
            }
            return returnData(1, '', $data);
        }
    }

}