<?php
 

namespace plugins\DevTeam;

use app\common\controller\Plugin;

/**
 * 系统环境信息插件
 * @package plugins\DevTeam

 */
class DevTeam extends Plugin
{
    /**
     * @var array 插件信息
     */
    public $info = [
        // 插件名[必填]
        'name'        => 'DevTeam',
        // 插件标题[必填]
        'title'       => '开发团队成员信息',
        // 插件唯一标识[必填],格式：插件名.开发者标识.plugin
        'identifier'  => 'dev_team.ming.plugin',
        // 插件图标[选填]
        'icon'        => 'fa fa-fw fa-users',
        // 插件描述[选填]
        'description' => '在后台首页显示开发团队成员信息',
        // 插件[必填]
        'author'      => '',
        // 作者主页[选填]
        'author_url'  => '#',
        // 插件版本[必填],格式采用三段式：主版本号.次版本号.修订版本号
        'version'     => '1.0.0',
        // 是否有后台管理功能[选填]
        'admin'       => '0',
    ];

    /**
     * @var array 插件钩子
     */
    public $hooks = [
        'admin_index'
    ];

    /**
     * 后台首页钩子

     */
    public function adminIndex()
    {
        $config = $this->getConfigValue();
        if ($config['display']) {
            $this->fetch('widget', $config);
        }
    }

    /**
     * 安装方法

     * @return bool
     */
    public function install(){
        return true;
    }

    /**
     * 卸载方法必

     * @return bool
     */
    public function uninstall(){
        return true;
    }
}
