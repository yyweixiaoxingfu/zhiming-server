CREATE TABLE `w_member_feedback` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `content` text COMMENT '反馈内容',
  `member_id` int(10) DEFAULT '0',
  `create_time` int(10) DEFAULT '0' COMMENT '反馈时间',
  `status` tinyint(1) DEFAULT '0' COMMENT '是否处理 0未处理  1已处理',
  `deal_time` int(10) DEFAULT '0' COMMENT '处理时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT '意见反馈';

CREATE TABLE `w_member` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT '' COMMENT '会员昵称',
  `mobile` varchar(255) DEFAULT '' COMMENT '手机号',
  `avatar` varchar(255) DEFAULT '' COMMENT '头像',
  `num` int(10) DEFAULT '0' COMMENT '直推人数',
  `tid` int(10) DEFAULT '0' COMMENT '推荐人id',
  `layer` int(10) DEFAULT '0' COMMENT '层级',
  `line` text COMMENT '层级路径',
  `sign` varchar(255) DEFAULT '' COMMENT '个性签名',
  `level` int(10) DEFAULT '0' COMMENT '会员等级',
  `owner_id` int(10) DEFAULT '0' COMMENT '归属ID',
  `identity` enum('agent','shop','store','member') DEFAULT 'member' COMMENT '''agent''=>代理,''shop''=>小店,''member''=>会员,''store''=>代理下店铺',
  `openid_wx` varchar(255) DEFAULT '' COMMENT '微信openid',
  `openid_xcx` varchar(255) DEFAULT '' COMMENT '小程序openid',
  `unionid_wx` varchar(255) DEFAULT '' COMMENT '微信unionid',
  `login_pwd` varchar(255) DEFAULT '' COMMENT '登录密码',
  `trade_pwd` varchar(255) DEFAULT '' COMMENT '交易密码',
  `regcode` varchar(255) DEFAULT '' COMMENT '推荐码',
  `lock` tinyint(1) DEFAULT '0' COMMENT '会员状态0正常 1锁定',
  `create_time` int(10) DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(10) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT '会员表';

CREATE TABLE `w_member_credit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `type` varchar(32) NOT NULL,
  `credit` decimal(18,2) NOT NULL,
  `freeze` decimal(18,2) NOT NULL,
  `create_time` int(11) unsigned NOT NULL DEFAULT '0',
  `update_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `ind_m_t` (`member_id`,`type`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT '会员账户表';

CREATE TABLE `w_member_credit_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(10) DEFAULT '0' COMMENT '会员id',
  `type` varchar(32) DEFAULT '0' COMMENT '货币类型',
  `num` decimal(18,2) DEFAULT NULL COMMENT '数量',
  `status` tinyint(1) DEFAULT '0' COMMENT '0加 1减',
  `info` varchar(255) DEFAULT '0' COMMENT '描述',
  `admin_id` int(10) DEFAULT '0' COMMENT '后台管理员id',
  `create_time` int(10) DEFAULT '0',
  `cate` varchar(20) DEFAULT '' COMMENT '记录分类',
  `target_id` varchar(50) DEFAULT NULL COMMENT '关联表id',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='log记录表';

CREATE TABLE `w_member_tid_records` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(10) DEFAULT '0',
  `old_pid` int(10) DEFAULT '0',
  `new_pid` int(10) DEFAULT '0' COMMENT '新上级',
  `admin_id` int(10) DEFAULT '0',
  `create_time` int(10) DEFAULT '0',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='上级修改记录';

INSERT INTO `w_admin_menu`(`id`, `pid`, `module`, `title`, `icon`, `url_type`, `url_value`, `url_target`, `online_hide`, `create_time`, `update_time`, `sort`, `system_menu`, `status`, `params`) VALUES (316, 315, 'user', '会员管理', 'fa fa-fw fa-user', 'module_admin', '', '_self', 0, 1617887030, 1620981864, 6, 0, 1, '');
INSERT INTO `w_admin_menu`(`id`, `pid`, `module`, `title`, `icon`, `url_type`, `url_value`, `url_target`, `online_hide`, `create_time`, `update_time`, `sort`, `system_menu`, `status`, `params`) VALUES (317, 316, 'user', '会员列表', 'fa fa-fw fa-user', 'module_admin', 'member/index/index', '_self', 0, 1617966179, 1618555318, 1, 0, 1, '');
INSERT INTO `w_admin_menu`(`id`, `pid`, `module`, `title`, `icon`, `url_type`, `url_value`, `url_target`, `online_hide`, `create_time`, `update_time`, `sort`, `system_menu`, `status`, `params`) VALUES (318, 317, 'user', '新增', '', 'module_admin', 'member/index/add', '_self', 0, 1617966178, 1618555318, 1, 0, 1, '');
INSERT INTO `w_admin_menu`(`id`, `pid`, `module`, `title`, `icon`, `url_type`, `url_value`, `url_target`, `online_hide`, `create_time`, `update_time`, `sort`, `system_menu`, `status`, `params`) VALUES (319, 317, 'user', '编辑', '', 'module_admin', 'member/index/edit', '_self', 0, 1617966178, 1618555318, 2, 0, 1, '');
INSERT INTO `w_admin_menu`(`id`, `pid`, `module`, `title`, `icon`, `url_type`, `url_value`, `url_target`, `online_hide`, `create_time`, `update_time`, `sort`, `system_menu`, `status`, `params`) VALUES (320, 317, 'user', '删除', '', 'module_admin', 'member/index/delete', '_self', 0, 1617966178, 1618555318, 3, 0, 1, '');
INSERT INTO `w_admin_menu`(`id`, `pid`, `module`, `title`, `icon`, `url_type`, `url_value`, `url_target`, `online_hide`, `create_time`, `update_time`, `sort`, `system_menu`, `status`, `params`) VALUES (321, 317, 'user', '快速编辑', '', 'module_admin', 'member/index/quickedit', '_self', 0, 1617966178, 1618555318, 6, 0, 1, '');
INSERT INTO `w_admin_menu`(`id`, `pid`, `module`, `title`, `icon`, `url_type`, `url_value`, `url_target`, `online_hide`, `create_time`, `update_time`, `sort`, `system_menu`, `status`, `params`) VALUES (322, 317, 'user', '导出', '', 'module_admin', 'member/index/export', '_self', 0, 1618320637, 1618555318, 7, 0, 1, '');
INSERT INTO `w_admin_menu`(`id`, `pid`, `module`, `title`, `icon`, `url_type`, `url_value`, `url_target`, `online_hide`, `create_time`, `update_time`, `sort`, `system_menu`, `status`, `params`) VALUES (323, 317, 'user', '批量导入', '', 'module_admin', 'member/index/import', '_self', 0, 1617966178, 1618555318, 4, 0, 1, '');
INSERT INTO `w_admin_menu`(`id`, `pid`, `module`, `title`, `icon`, `url_type`, `url_value`, `url_target`, `online_hide`, `create_time`, `update_time`, `sort`, `system_menu`, `status`, `params`) VALUES (324, 317, 'user', '充值', '', 'module_admin', 'member/index/recharge', '_self', 0, 1617966178, 1618555318, 5, 0, 1, '');
INSERT INTO `w_admin_menu`(`id`, `pid`, `module`, `title`, `icon`, `url_type`, `url_value`, `url_target`, `online_hide`, `create_time`, `update_time`, `sort`, `system_menu`, `status`, `params`) VALUES (325, 4, 'admin', '意见反馈', 'fa fa-fw fa-coffee', 'module_admin', '', '_self', 0, 1618294275, 1627285303, 7, 0, 1, '');
INSERT INTO `w_admin_menu`(`id`, `pid`, `module`, `title`, `icon`, `url_type`, `url_value`, `url_target`, `online_hide`, `create_time`, `update_time`, `sort`, `system_menu`, `status`, `params`) VALUES (326, 325, 'admin', '反馈列表', 'fa fa-fw fa-list', 'module_admin', 'member/feedback/index', '_self', 0, 1618294315, 1627285303, 1, 0, 1, '');
INSERT INTO `w_admin_menu`(`id`, `pid`, `module`, `title`, `icon`, `url_type`, `url_value`, `url_target`, `online_hide`, `create_time`, `update_time`, `sort`, `system_menu`, `status`, `params`) VALUES (327, 326, 'admin', '删除', '', 'module_admin', 'member/feedback/delete', '_self', 0, 1618294315, 1627285303, 3, 0, 1, '');
INSERT INTO `w_admin_menu`(`id`, `pid`, `module`, `title`, `icon`, `url_type`, `url_value`, `url_target`, `online_hide`, `create_time`, `update_time`, `sort`, `system_menu`, `status`, `params`) VALUES (328, 326, 'admin', '处理反馈', '', 'module_admin', 'member/feedback/feedback_deal', '_self', 0, 1618299602, 1627285303, 7, 0, 1, '');
INSERT INTO `w_admin_menu`(`id`, `pid`, `module`, `title`, `icon`, `url_type`, `url_value`, `url_target`, `online_hide`, `create_time`, `update_time`, `sort`, `system_menu`, `status`, `params`) VALUES (329, 316, 'admin', '资金明细', 'fa fa-fw fa-list', 'module_admin', 'member/credit_log/index', '_self', 0, 1618294315, 1627285303, 1, 0, 1, '');
INSERT INTO `w_admin_menu`(`id`, `pid`, `module`, `title`, `icon`, `url_type`, `url_value`, `url_target`, `online_hide`, `create_time`, `update_time`, `sort`, `system_menu`, `status`, `params`) VALUES (330, 329, 'admin', '导出', '', 'module_admin', 'member/credit_log/export', '_self', 0, 1618294315, 1627285303, 3, 0, 1, '');


INSERT INTO `w_admin_config`(`id`, `name`, `title`, `group`, `type`, `value`, `options`, `tips`, `ajax_url`, `next_items`, `param`, `format`, `table`, `level`, `key`, `option`, `pid`, `ak`, `create_time`, `update_time`, `sort`, `status`) VALUES (40, 'register_switch', '前台注册开关', 'member', 'switch', '1', '0', '是否开启前台注册', '', '', '', '', '', 0, '', '', '', '', 1617020330, 1627970342, 1, 1);
INSERT INTO `w_admin_config`(`id`, `name`, `title`, `group`, `type`, `value`, `options`, `tips`, `ajax_url`, `next_items`, `param`, `format`, `table`, `level`, `key`, `option`, `pid`, `ak`, `create_time`, `update_time`, `sort`, `status`) VALUES (41, 'invitation_code', '注册时邀请码是否必填', 'member', 'switch', '1', '', '前台注册时邀请码是否必填', '', '', '', '', '', 0, '', '', '', '', 1617020450, 1627970351, 2, 1);
INSERT INTO `w_admin_config`(`id`, `name`, `title`, `group`, `type`, `value`, `options`, `tips`, `ajax_url`, `next_items`, `param`, `format`, `table`, `level`, `key`, `option`, `pid`, `ak`, `create_time`, `update_time`, `sort`, `status`) VALUES (42, 'register_rule', '注册协议', 'member', 'ueditor', '', '', '', '', '', '', '', '', 0, '', '', '', '', 1618207977, 1625628958, 1, 1);
INSERT INTO `w_admin_config`(`id`, `name`, `title`, `group`, `type`, `value`, `options`, `tips`, `ajax_url`, `next_items`, `param`, `format`, `table`, `level`, `key`, `option`, `pid`, `ak`, `create_time`, `update_time`, `sort`, `status`) VALUES (43, 'currency', '货币', 'base', 'array', '余额:deposit\r\n积分:points', '', '', '', '', '', '', '', 0, '', '', '', '', 1620822262, 1620822455, 100, 1);
UPDATE `w_admin_config` SET `value` = concat(`value`,'\r\nmember:会员') WHERE `id` = 10;
