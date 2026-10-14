CREATE TABLE `w_message_action` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module` varchar(50) DEFAULT 'admin_add' COMMENT '所属模块',
  `action` varchar(20) DEFAULT NULL COMMENT '场景标识',
  `desc` varchar(255) DEFAULT NULL COMMENT '场景描述',
  `title` varchar(255) DEFAULT NULL COMMENT '默认标题',
  `content` text COMMENT '默认模板',
  `keywords_desc` text COMMENT '消息模板关键字定义',
  `is_queue` tinyint(1) DEFAULT '0' COMMENT '是否队列发送',
  `sms_enabled` tinyint(1) DEFAULT '0' COMMENT '是否启用短信',
  `email_enabled` tinyint(1) DEFAULT '0' COMMENT '是否启用邮件',
  `info_enabled` tinyint(1) DEFAULT '0' COMMENT '是否启用站内信',
  `notice_enabled` tinyint(1) DEFAULT '0' COMMENT '是否启用手机消息推送',
  `sort` int(11) DEFAULT '0' COMMENT '排序',
  `version` varchar(255) DEFAULT NULL COMMENT '版本',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8 COMMENT='消息场景';

CREATE TABLE `w_message_template` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `action` varchar(20) DEFAULT NULL COMMENT '场景',
  `driver` varchar(20) DEFAULT NULL COMMENT '模板引擎',
  `title` varchar(255) DEFAULT NULL COMMENT '标题',
  `template` text COMMENT '内容/模板编号',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COMMENT='消息模板';

CREATE TABLE `w_member_message` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '接收消息的用户id',
  `uid_send` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '发送消息的用户id',
  `type` varchar(128) NOT NULL DEFAULT '' COMMENT '消息分类',
  `content` text NOT NULL COMMENT '消息内容',
  `msg_type` varchar(255) DEFAULT NULL COMMENT '消息关联类型',
  `msg_id` int(11) DEFAULT NULL COMMENT '消息关联id',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `read_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '阅读时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=48 DEFAULT CHARSET=utf8 COMMENT='会员消息表';

INSERT INTO `w_admin_menu`(`id`, `pid`, `module`, `title`, `icon`, `url_type`, `url_value`, `url_target`, `online_hide`, `create_time`, `update_time`, `sort`, `system_menu`, `status`, `params`) VALUES (336, 5, 'admin', '消息配置', 'fa fa-fw fa-comment-o', 'module_admin', 'message/index/setting', '_self', 0, 1637572012, 1637572012, 100, 0, 1, '');
INSERT INTO `w_admin_menu`(`id`, `pid`, `module`, `title`, `icon`, `url_type`, `url_value`, `url_target`, `online_hide`, `create_time`, `update_time`, `sort`, `system_menu`, `status`, `params`) VALUES (337, 336, 'admin', '消息模板配置', '', 'module_admin', 'message/index/template', '_self', 0, 1637574129, 1637574129, 100, 0, 1, '');
INSERT INTO `w_admin_menu`(`id`, `pid`, `module`, `title`, `icon`, `url_type`, `url_value`, `url_target`, `online_hide`, `create_time`, `update_time`, `sort`, `system_menu`, `status`, `params`) VALUES (338, 336, 'admin', '新增', '', 'module_admin', 'message/index/add', '_self', 0, 1637637058, 1637637058, 100, 0, 1, '');
INSERT INTO `w_admin_menu`(`id`, `pid`, `module`, `title`, `icon`, `url_type`, `url_value`, `url_target`, `online_hide`, `create_time`, `update_time`, `sort`, `system_menu`, `status`, `params`) VALUES (339, 336, 'admin', '编辑', '', 'module_admin', 'message/index/edit', '_self', 0, 1637637058, 1637637058, 100, 0, 1, '');
INSERT INTO `w_admin_menu`(`id`, `pid`, `module`, `title`, `icon`, `url_type`, `url_value`, `url_target`, `online_hide`, `create_time`, `update_time`, `sort`, `system_menu`, `status`, `params`) VALUES (340, 336, 'admin', '删除', '', 'module_admin', 'message/index/delete', '_self', 0, 1637637058, 1637637058, 100, 0, 1, '');
INSERT INTO `w_admin_menu`(`id`, `pid`, `module`, `title`, `icon`, `url_type`, `url_value`, `url_target`, `online_hide`, `create_time`, `update_time`, `sort`, `system_menu`, `status`, `params`) VALUES (341, 336, 'admin', '快速编辑', '', 'module_admin', 'message/index/quickedit', '_self', 0, 1637637058, 1637637058, 100, 0, 1, '');
INSERT INTO `w_admin_menu`(`id`, `pid`, `module`, `title`, `icon`, `url_type`, `url_value`, `url_target`, `online_hide`, `create_time`, `update_time`, `sort`, `system_menu`, `status`, `params`) VALUES (342, 336, 'admin', '参数设置', '', 'module_admin', 'message/index/config', '_self', 0, 1637637058, 1637637058, 100, 0, 1, '');
