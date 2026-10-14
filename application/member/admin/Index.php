<?php

namespace app\member\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\common\library\ExcelServer;
use app\member\model\MemberCreditModel;
use app\member\model\MemberModel;
use app\member\transformer\MemberTransformer;
use think\Db;
use think\facade\Env;

/**
 * 用户默认控制器
 * @package app\user\admin
 */
class Index extends Admin
{
    protected $css = <<<EOF
        <style>
            th,tr{
                text-align: center;
            }
        </style>
EOF;
    public function index()
    {
        $order       = $this->getOrder('create_time desc,id desc');
        $map         = $this->getMap();
        $MemberModel = new MemberModel();
        $data_list = MemberModel::where($map)->order($order)->paginate();
        $data_list = transformer($data_list, MemberTransformer::class);
        $maps      = urlencode(json_encode($map));
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setPageTitle('会员列表') // 设置页面标题
            ->setTableName($MemberModel->getName()) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['user', '会员信息'],
                ['tui_user', '推荐人'],
                ['deposit', MemberCreditModel::getCreditName('deposit')],
                ['points', MemberCreditModel::getCreditName('points')],
                ['frequency', MemberCreditModel::getCreditName('frequency')],
                ['regcode', '邀请码'],
                ['lock', '锁定', 'switch', ['0' => '正常', '1' => '锁定']],
                ['create_time', '创建时间', 'datetime'],
                ['right_button', '操作', 'btn']
            ])
            ->addOrder('id')
            ->setColumnWidth('lock', 60)
            ->setColumnWidth('id', 55)
            ->setColumnWidth('user,tui_user', 210)
            ->setColumnWidth('create_time', 150)
            ->setSearchArea([
                ['text', 'name', '会员名称'],
                ['text', 'mobile', '手机号'],
                ['text', 'account', '账号'],
                ['text', 'email', '邮箱'],
                ['text', 'regcode', '邀请码'],
                ['select', 'lock', '锁定', '', '', ['0' => '正常', '1' => '锁定']],
                ['daterange', 'create_time', '创建时间', '', '', ['time-picker' => 'true', 'time-picker24-hour' => 'true', 'time-picker-seconds' => 'true']],
            ])
            ->setExtraCss($this->css)
            ->addTopButton('add', ['href' => url('add')])
            ->addTopButton('delete', ['href' => url('delete')])
            ->addTopButton('export', ['title' => '导出', 'class' => 'btn btn-danger confirm', 'href' => url('export', ['map' => $maps])])
            ->addTopButton('import', ['table' => '\\app\\member\\model\\MemberModel', 'prefix' => 2])
            ->addRightButton('edit', ['class' => 'btn btn-sm btn-warning', 'href' => url('edit', ['id' => '__id__'])])
            ->addRightButton('delete', ['class' => 'btn btn-sm btn-danger ajax-get confirm', 'href' => url('delete', ['id' => '__id__'])])
            ->addRightButton('recharge', ['class' => 'btn btn-sm btn-info', 'title' => '充值', 'icon' => 'fa fa-fw fa-cny', 'href' => url('recharge', ['id' => '__id__'])], ['area' => ['500px', '500px']])
            ->setRowList($data_list) // 设置表格数据
            ->fetch(); // 渲染页面
    }

    public function add()
    {
        if ($this->request->post()) {
            $POST     = input('post.');
            // $result = $this->validate($POST, 'MemberValidate.user_add');
            // if (true !== $result) {
            //     $this->error($result);
            // }
            $MemberModel    = new MemberModel();
            if(isset($POST['mobile']) && !empty($POST['mobile'])){
                $POST['mobile'] = trim($POST['mobile']);
                $hasMobile      = MemberModel::where('mobile', $POST['mobile'])->find();
                if (!empty($hasMobile)) {
                    $this->error('该手机号已存在');
                }
            }
            if(isset($POST['email']) && !empty($POST['account'])){
                $POST['account'] = trim($POST['account']);
                if(!preg_match('/^(?=.*[a-zA-Z_])[a-zA-Z0-9_]{6,}$/', $POST['account'])){
                    return returnData(0,'账号格式不正确，只能包含字母、数字和下划线，不能有空格，长度必须大于6位');
                }
                $hasAccount = MemberModel::where('account',$POST['account'])->find();
                if(!empty($hasAccount)){
                    $this->error('该用户名已存在');
                }
            }
            if(isset($POST['email']) && !empty($POST['email'])){
                $POST['email'] = trim($POST['email']);
                $hasEmail = MemberModel::where('email',$POST['email'])->find();
                if(!empty($hasEmail)){
                    $this->error('该邮箱已存在');
                }
            }
            $POST['tid'] = (isset($POST['tid']) && $POST['tid']) ? intval($POST['tid']) : 0;
            $m_line      = '0,';
            $m_tid       = 0;
            if ($POST['tid'] > 0) {
                $hasRegCode = MemberModel::where(array('id' => $POST['tid']))->field('id,lock,line,num,layer')->find();
                if (empty($hasRegCode)) {
                    $this->error('推荐人不存在');
                }
                if ($hasRegCode['lock'] == 1) {
                    $this->error('推荐人被锁定');
                }
                $m_tid  = $hasRegCode['id'];
                $m_line = $hasRegCode['line'] . $hasRegCode['id'] . ',';
            }
            $m_layer = count(explode(',', trim($m_line, ",")));
            $password      = trim($POST['loginpass']);
            $password      = $MemberModel->setPasswordAttr($password); //加密
            $member        = array(
                'tid'       => $m_tid,
                'line'      => $m_line,
                'layer'     => $m_layer,
                'name'      => (isset($POST['name']) && !empty($POST['name'])) ? trim($POST['name']) : '',
                'account'   => (isset($POST['account']) && !empty($POST['account'])) ? trim($POST['account']) : '',
                'mobile'    => $POST['mobile'],
                'login_pwd' => $password,
                'regcode'   => createNO(3, 6, 'member', 'regcode'), //创建新用户的邀请码
                'lock'      => intval($POST['lock']),
            );
            Db::startTrans();
            $member = MemberModel::create($member);
            if (empty($member)) {
                Db::rollback();
                $this->error('操作失败');
            }
            if ($m_tid) {
                $updateTuserNum = MemberModel::where('id', $m_tid)->setInc('num');
                if (empty($updateTuserNum)) {
                    Db::rollback();
                    $this->error('注册失败');
                }
            }
            Db::commit();
            $this->success('注册成功', 'index');
        }
        return ZBuilder::make('form')
            ->addFormItem('text', 'tid', '推荐人ID')
            ->addFormItem('text', 'name', '昵称')
            ->addFormItem('text', 'account', '用户名')
            ->addFormItem('text', 'email', '邮箱')
            ->addFormItem('text', 'mobile', '手机号')
            ->addFormItem('password', 'loginpass', '登录密码', '不填表示不修改')
            ->addFormItem('password', 'quepass', '确认登录密码')
            ->addRadio('lock', '锁定', '', ['0' => '正常 ', '1' => '锁定'], '0')
            ->fetch();
    }

    public function edit($id = 0)
    {
        if ($this->request->post()) {
            $POST   = input('post.');
            // $result = $this->validate($POST, 'MemberValidate.edit');
            // if (true !== $result) {
            //     $this->error($result);
            // }
            $id   = intval($POST['id']);
            $user = MemberModel::get($id);
            if (empty($user)) {
                $this->error('数据有误');
            }
            $MemberModel       = new MemberModel();
            if (!empty($POST['loginpass'])) {
                $POST['login_pwd'] = $MemberModel->setPasswordAttr($POST['loginpass']); //加密
            }

            if(isset($POST['account']) && $POST['account']){
                $POST['account']    = trim($POST['account']);
                if(!preg_match('/^(?=.*[a-zA-Z_])[a-zA-Z0-9_]{6,}$/', $POST['account'])){
                    return returnData(0,'账号格式不正确，只能包含字母、数字和下划线，不能有空格，长度必须大于6位');
                }
                $hasAccount         = MemberModel::where('account', $POST['account'])->where('id', '<>', $id)->find();
                if (!empty($hasAccount)) {
                    $this->error('该用户名已存在');
                }
            }


            if(isset($POST['email']) && $POST['email']){
                $POST['email']    = trim($POST['email']);
                $hasEmail         = MemberModel::where('email', $POST['email'])->where('id', '<>', $id)->find();
                if (!empty($hasEmail)) {
                    $this->error('该邮箱已存在');
                }
            }

            if(isset($POST['mobile']) && $POST['mobile']){
                $POST['mobile']    = trim($POST['mobile']);
                $hasMobile         = MemberModel::where('mobile', $POST['mobile'])->where('id', '<>', $id)->find();
                if (!empty($hasMobile)) {
                    $this->error('该手机号已存在');
                }
            }
           
            if (intval($POST['tid']) != $user['tid']) {
                $change = $MemberModel->changePid($user, $POST['tid']);
                if (!$change) {
                    $this->error('推荐人修改失败：' . $MemberModel->getError() ?? '');
                }
            }
            $res = $user->save($POST);
            if ($res) {
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }
        $id                = intval($id);
        $data              = MemberModel::get($id);
        return ZBuilder::make('form')
            ->addFormItem('text', 'tid', '推荐人ID')
            ->addFormItem('text', 'name', '昵称')
            ->addFormItem('text', 'account', '用户名')
            ->addFormItem('text', 'email', '邮箱')
            ->addFormItem('text', 'mobile', '手机号')
            //->addFormItem('text', 'expert_fee', '专家费率')
            ->addFormItem('password', 'loginpass', '登录密码', '不填表示不修改')
            ->addFormItem('password', 'quepass', '确认登录密码')
            ->addRadio('lock', '锁定', '', ['0' => '正常 ', '1' => '锁定'], '0')
            ->setFormData($data)
            ->addHidden('id', $id)
            ->fetch();
    }

    /**
     * 删除会员
     */
    public function delete($id = 0)
    {
        if ($this->request->isPost()) {
            $POST = input('post.');
            ###########################批量删除（开始）##############################
            $ids = $POST['ids'];
            $res = MemberModel::destroy(['id' => $ids]);
            if ($res) {
                $this->success('删除成功', '', 'index');
            } else {
                $this->error('删除失败');
            }
            ###########################批量删除（结束）##############################
        } else {
            $id  = input('id');
            $res = MemberModel::destroy(['id' => $id]);
            if ($res) {
                $this->success('删除成功', '', 'index');
            } else {
                $this->error('删除失败');
            }
        }
    }

    /**
     * 导出会员数据
     */
    public function export($map)
    {
        $map = json_decode(urldecode($map), true);
        $data = MemberModel::where($map)->field('id,tid,name,mobile,lock,regcode')->order('create_time desc,id desc')->select()->map(function ($item) {
            $item['t_name']   = '';
            $item['t_mobile'] = '';
            if ($item['tid'] > 0) {
                $tuser            = MemberModel::where('id', $item['tid'])->field('name,mobile')->find();
                $item['t_name']   = $tuser['name'];
                $item['t_mobile'] = $tuser['mobile'];
            }
            $item['create_time'] = empty($item['create_time']) ? '' : date("Y-m-d H:i", $item['create_time']);
            $item['lock'] = $item['lock'] == 1 ? '锁定' : '正常';
            return $item;
        });
        $title = [
            ['id', '会员ID'],
            ['name', '会员昵称'],
            ['mobile', '手机号'],
            ['t_name', '推荐人姓名'],
            ['t_mobile', '推荐人手机号'],
            ['lock', '状态'],
            ['regcode', '邀请码'],
            ['create_time', '注册时间'],
        ];
        ExcelServer::export_old('会员数据', $title, $data);
    }

    /**
     * 批量导入会员
     */
    public function import()
    {
        if ($this->request->isPost()) {
            $POST = request()->param();
            if (!empty($POST['download'])) {
                $title = [
                    ['name', '姓名'],
                    ['mobile', '手机号'],
                ];
                ExcelServer::export_old('批量添加会员模板', $title, [], false);
                $this->success('', '', '_parent_reload');
            }
            $file = model('admin/attachment')->getFilePath($POST['file']);
            $file = Env::get('root_path') . 'public' . $file;
            $res = ExcelServer::import($file, ['name', 'mobile'], 2, 0);
            if ($res['code'] == 0) {
                $this->error($res['msg']);
            }
            $fileData = $res['data'];
            $exists_list = MemberModel::column('mobile');
            $num         = 0;
            foreach ($fileData as $k => $v) {
                if (in_array($v['mobile'], $exists_list)) {
                    continue; //跳过已存在的考生
                }
                $insertArr[] = [
                    'name'      => $v['name'],
                    'mobile'    => $v['mobile'],
                    'tid'       => 0,
                    'line'      => '0,',
                    'login_pwd' => MemberModel::setPasswordAttr(123456),
                    'regcode'   => createNO(3, 6, 'member', 'regcode'),
                    'identity'  => 'member',       //当前会员等级
                ];
                $num++;
            }
            if (!empty($insertArr)) {
                $MemberModel = new MemberModel();
                $res         = $MemberModel->saveAll($insertArr);
                if (empty($res)) {
                    $this->error('导入失败');
                }
            }
            $this->success('导入成功');
        }
        // 显示添加页面
        $js = "
<script type='text/javascript'>
 $(function(){
     $('.download').on('click',function (){
         $('#download').val(1);
         $('form').submit();
     });
 }); 
</script> 
";
        return ZBuilder::make('form')
            ->setPageTitle('批量添加会员-导入Excel')
            ->setPageTips('请按照模板格式上传文件，密码不填写则默认123456！！！')
            ->addFormItems([
                ['file', 'file', '上传excel文件', '导入会员表，请按照模板填写']
            ])
            ->addBtn('<button type="button" class="btn btn-info download">下载模板</button>')
            ->addHidden('download', 0)
            ->setExtraJs($js)
            ->fetch();
    }

    /**
     * 后台充值
     */
    public function recharge($id = '')
    {
        // 保存数据
        if ($this->request->isPost()) {
            $POST = input('post.');
            $id   = intval($POST['id']);
            $user = MemberModel::get($id);
            if (empty($user)) {
                $this->error('数据有误');
            };
            $type  = $POST['type'];
            $money = $POST['money'];
            $info  = $POST['info'];
            if (empty($info)) {
                $info = '后台充值';
            }
            $logs = MemberCreditModel::credit($user['id'], $type, $money, $info, '', '', 'arecharge', UID);
            if ($logs) {
                $this->success('充值成功', 'index', '_parent_reload');
            } else {
                $this->error('充值失败');
            }
        }
        $id   = intval($id);
        $name = config('currency');
        
        $name = array_flip($name);
        return ZBuilder::make('form')
            ->setPageTitle('新增')
            ->addFormItems([
                ['radio', 'type', '类型', '', $name, 'deposit'],
                ['number', 'money', '金额', '正数加，负数减'],
                ['text', 'info', '操作描述', ''],
            ])
            ->addHidden('id', $id)
            ->fetch();
    }
}
