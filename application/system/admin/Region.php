<?php
namespace app\system\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\admin\model\Menu as MenuModel;
use app\system\model\RegionModel;

/**
 * 地址库
 * @package app\admin\controller
 */
class Region extends Admin
{
    /**
     * 地址库列表
     */
    public function index($group = 'region')
    {
        // 保存模块排序
        if ($this->request->isPost()) {
            $modules = $this->request->post('sort/a');
            if ($modules) {
                $data = [];
                foreach ($modules as $key => $module) {
                    $data[] = [
                        'id'   => $module,
                        'sort' => $key + 1
                    ];
                }
                $MenuModel = new MenuModel();
                if (false !== $MenuModel->saveAll($data)) {
                    $this->success('保存成功');
                } else {
                    $this->error('保存失败');
                }
            }
        }

        cookie('__forward__', $_SERVER['REQUEST_URI']);
        $key = $group;
        $tab_list[$key]['title'] = '地区管理';
        $tab_list[$key]['url']  = url('index', ['group' => $key]);


        // 获取节点数据
        $data_list = RegionModel::getAll();

        $max_level = $this->request->get('max', 0);

        $info = $this->getNestMenu($data_list, $max_level);

        $this->assign('menus', $info);

        $this->assign('page_title', '地区管理');
        return $this->fetch();
    }

    /**
     * 新增
     */
    public function add($module = 'region', $pid = 0)
    {
        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post('', null, 'trim');

            // 验证
            $result = $this->validate($data, 'RegionValidate.backend_add');
            // 验证失败 输出错误信息
            if(true !== $result) $this->error($result);
            $pInfo = RegionModel::get($data['pid']);
            $data['path'] = $pInfo['path'].$pInfo['code'].',';


            if ($menu = RegionModel::create($data)) {
                // 记录行为
                $details = '所属地区ID('.$data['pid'].'),地区名称('.$data['name'].'),地区编号('.$data['code'].')';
                action_log('region_add', 'system_region', $menu['id'], UID, $details);
                $this->success('新增成功', url('index'));
            } else {
                $this->error('新增失败');
            }
        }

        // 使用ZBuilder快速创建表单
        return ZBuilder::make('form')
            ->setPageTitle('新增地区')
            ->addFormItems([
                ['text', 'name', '地区名称'],
                ['text', 'code', '地区编号'],
            ])
            ->addHidden('pid',$pid)
            ->addText('sort', '排序', '', 100)
            ->fetch();
    }

    /**
     * 编辑
     */
    public function edit($id = 0)
    {
        if ($id === 0) $this->error('缺少参数');

        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post('', null, 'trim');

            // 验证
            $result = $this->validate($data, 'RegionValidate.backend_edit');
            // 验证失败 输出错误信息
            if(true !== $result) $this->error($result);

            if (RegionModel::update($data)) {
                // 记录行为
                $details = '节点ID('.$id.')';
                action_log('region_edit', 'system_region', $id, UID, $details);
                $this->success('编辑成功', url('index'));
            } else {
                $this->error('编辑失败');
            }
        }

        // 获取数据
        $info = RegionModel::get($id);

        // 使用ZBuilder快速创建表单
        return ZBuilder::make('form')
            ->setPageTitle('编辑地区')
            ->addFormItem('hidden', 'id')
            ->addFormItems([
                ['text', 'name', '地区名称'],
                ['text', 'code', '地区编号'],
            ])
            ->addText('sort', '排序', '', 100)
            ->setFormData($info)
            ->fetch();
    }

    /**
     * 删除
     */
    public function delete($record = [])
    {
        $id = $this->request->param('id');
        $menu = RegionModel::where('id', $id)->find();

        // 获取该节点的所有后辈节点id
        $menu_childs = RegionModel::getChildsId($id);

        // 要删除的所有节点id
        $all_ids = array_merge([(int)$id], $menu_childs);

        // 删除节点
        if (RegionModel::destroy($all_ids)) {
            // 记录行为
            $details = '地区ID('.$id.'),地区名称('.$menu['name'].'),地区编号('.$menu['code'].')';
            action_log('region_delete', 'system_region', $id, UID, $details);
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * 获取嵌套式节点
     * @param array $lists 原始节点数组
     * @param int $pid 父级id
     * @param int $max_level 最多返回多少层，0为不限制
     * @param int $curr_level 当前层数
     * @author 
     * @return string
     */
    private function getNestMenu($lists = [], $max_level = 0, $pid = 0, $curr_level = 1)
    {
        $result = '';
        foreach ($lists as $key => $value) {
            if ($value['pid'] == $pid) {
                $disable  = '';
                $value['icon'] = 'fa fa-fw fa-cube';
                $value['module'] = 'region';

                // 组合节点
                $result .= '<li class="dd-item dd3-item '.$disable.'" data-id="'.$value['id'].'">';
                $result .= '<div class="dd-handle dd3-handle">拖拽</div><div class="dd3-content"><i class="'.$value['name'].'"></i> '.$value['name'];
                $result .= '<div class="action">';
                $result .= '<a href="'.url('add', ['module' => $value['module'], 'pid' => $value['id']]).'" data-toggle="tooltip" data-original-title="新增子节点"><i class="list-icon fa fa-plus fa-fw"></i></a><a href="'.url('edit', ['id' => $value['id']]).'" data-toggle="tooltip" data-original-title="编辑"><i class="list-icon fa fa-pencil fa-fw"></i></a>';

                $result .= '<a href="'.url('delete', ['id' => $value['id'], 'table' => 'admin_menu']).'" data-toggle="tooltip" data-original-title="删除" class="ajax-get confirm"><i class="list-icon fa fa-times fa-fw"></i></a>';

                $result .= '</div>';
                $result .= '</div>';

                if ($max_level == 0 || $curr_level != $max_level) {
                    unset($lists[$key]);
                    // 下级节点
                    $children = $this->getNestMenu($lists, $max_level, $value['id'], $curr_level + 1);
                    if ($children != '') {
                        $result .= '<ol class="dd-list">'.$children.'</ol>';
                    }
                }

                $result .= '</li>';
            }
        }
        return $result;
    }

}
