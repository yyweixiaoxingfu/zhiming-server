CREATE TABLE `w_system_announce` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT '' COMMENT '标题',
  `detail` text COMMENT '详情',
  `sort` int(10) NOT NULL DEFAULT '0' COMMENT '排序',
  `create_time` int(10) DEFAULT '0' COMMENT '时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='常见问题';

CREATE TABLE `w_system_banner` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pic` varchar(255) DEFAULT '' COMMENT '图片',
  `pos` enum('home') DEFAULT 'home' COMMENT '图片位置 1首页',
  `link_type` varchar(20) DEFAULT NULL COMMENT '跳转类型url网页地址goods商品shop店铺',
  `link` varchar(255) DEFAULT '0' COMMENT '跳转地址/id',
  `sort` int(10) DEFAULT '0' COMMENT '排序',
  `create_time` int(10) DEFAULT '0',
  `update_time` int(10) DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='轮播图';

CREATE TABLE `w_system_help_center` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `set_id` int(10) DEFAULT '0',
  `name` varchar(255) DEFAULT '' COMMENT '标题',
  `content` text,
  `sort` int(10) DEFAULT '9999',
  `create_time` int(10) DEFAULT '0',
  `update_time` int(10) DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='帮助中心';

CREATE TABLE `w_system_help_set` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT '0' COMMENT '帮助中心分类',
  `sort` int(10) DEFAULT '0' COMMENT '排序',
  `create_time` int(10) DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='帮助类型';

CREATE TABLE `w_system_region` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `pid` smallint(5) unsigned NOT NULL,
  `path` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `code` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `sort` smallint(10) unsigned DEFAULT NULL,
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `ind_pid` (`pid`) USING BTREE,
  KEY `ind_path` (`path`) USING BTREE,
  KEY `ind_code` (`code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='地区表';

CREATE TABLE `w_system_services` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `module` varchar(80) COLLATE utf8_unicode_ci NOT NULL COMMENT '所属模块',
  `key` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '主键名',
  `namespace` text COLLATE utf8_unicode_ci NOT NULL COMMENT '命名空间',
  `name` varchar(80) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '名称',
  `addon` longtext COLLATE utf8_unicode_ci COMMENT '属性',
  `sort` smallint(5) unsigned DEFAULT '9999',
  `version` varchar(80) COLLATE utf8_unicode_ci NOT NULL COMMENT '版本号',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `foundation_services_module_index` (`module`) USING BTREE,
  KEY `foundation_services_key_index` (`key`) USING BTREE,
  KEY `foundation_services_name_index` (`name`) USING BTREE,
  KEY `foundation_services_version_index` (`version`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `w_system_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '主键名',
  `type` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '类型',
  `module` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '模块',
  `value` longtext COLLATE utf8_unicode_ci NOT NULL COMMENT '主键值',
  `version` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '版本号',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `foundation_versions_key_type_version_module_unique` (`key`,`type`,`version`,`module`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;