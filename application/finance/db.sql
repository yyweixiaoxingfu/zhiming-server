CREATE TABLE `w_member_apply_cash` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `sn` varchar(30) DEFAULT NULL COMMENT '提现单号',
  `member_id` int(11) DEFAULT '0',
  `amount` decimal(10,2) DEFAULT NULL COMMENT '提现金额',
  `cash` decimal(10,2) DEFAULT '0.00' COMMENT '到账金额',
  `charge` decimal(10,2) DEFAULT NULL COMMENT '手续费',
  `status` enum('review','agree','reject','payed','ongoing') DEFAULT 'review' COMMENT '状态,review：审核中,agree：审核通过，reject：审核驳回，payed:已支付，ongoing:支付处理中',
  `type` varchar(20) DEFAULT NULL COMMENT '提现类型 deposit余额提现',
  `method` varchar(50) DEFAULT NULL COMMENT '提现方式wechat,alipay,bank',
  `account` varchar(255) DEFAULT NULL COMMENT '支付宝账号/银行卡号/微信openid',
  `name` varchar(255) DEFAULT '' COMMENT '姓名',
  `bank` varchar(255) DEFAULT '' COMMENT '开户行',
  `mobile` varchar(255) DEFAULT NULL COMMENT '银行预留手机号',
  `deal_time` int(11) DEFAULT '0' COMMENT '审核时间',
  `reject_reason` varchar(255) DEFAULT NULL COMMENT '驳回原因',
  `pay_time` int(11) DEFAULT '0' COMMENT '支付时间',
  `trans_id` varchar(100) DEFAULT NULL COMMENT '三方交易流水号',
  `receiving_code` int(11) DEFAULT 0 COMMENT '收款码',
  `create_time` int(11) DEFAULT '0',
  `update_time` int(11) DEFAULT '0',
  `delete_time` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT '用户提现';

CREATE TABLE `w_member_bank` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(10) DEFAULT '0',
  `name` varchar(255) DEFAULT '',
  `bank_name` varchar(255) DEFAULT '',
  `card_no` varchar(255) DEFAULT '' COMMENT '银行卡号',
  `mobile` varchar(255) DEFAULT '',
  `is_default` tinyint(1) DEFAULT '0' COMMENT '0非默认，1默认',
  `delete_time` int(10) DEFAULT '0' COMMENT '删除时间',
  `create_time` int(10) DEFAULT '0',
  `update_time` int(11) DEFAULT '0',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT '用户银行卡';

INSERT INTO `w_admin_config`(`id`, `name`, `title`, `group`, `type`, `value`, `options`, `tips`, `ajax_url`, `next_items`, `param`, `format`, `table`, `level`, `key`, `option`, `pid`, `ak`, `create_time`, `update_time`, `sort`, `status`) VALUES (44, 'withdraw_fee', '提现手续费（%）', 'ti', 'text', '3', '', '', '', '', '', '', '', 0, '', '', '', '', 1626171445, 1626171445, 1, 1);
INSERT INTO `w_admin_config`(`id`, `name`, `title`, `group`, `type`, `value`, `options`, `tips`, `ajax_url`, `next_items`, `param`, `format`, `table`, `level`, `key`, `option`, `pid`, `ak`, `create_time`, `update_time`, `sort`, `status`) VALUES (45, 'withdraw_low', '最低提现金额', 'ti', 'text', '100', '', '', '', '', '', '', '', 0, '', '', '', '', 1626171486, 1626171486, 2, 1);
INSERT INTO `w_admin_config`(`id`, `name`, `title`, `group`, `type`, `value`, `options`, `tips`, `ajax_url`, `next_items`, `param`, `format`, `table`, `level`, `key`, `option`, `pid`, `ak`, `create_time`, `update_time`, `sort`, `status`) VALUES (46, 'withdraw_bei', '提现倍数', 'ti', 'text', '100', '', '', '', '', '', '', '', 0, '', '', '', '', 1626171520, 1626171520, 100, 1);
INSERT INTO `w_admin_config`(`id`, `name`, `title`, `group`, `type`, `value`, `options`, `tips`, `ajax_url`, `next_items`, `param`, `format`, `table`, `level`, `key`, `option`, `pid`, `ak`, `create_time`, `update_time`, `sort`, `status`) VALUES (47, 'withdraw_type', '提现方式', 'ti', 'checkbox', 'wechat,alipay,bank', 'wechat:微信\r\nalipay:支付宝\r\nbank:银行卡', '', '', '', '', '', '', 0, '', '', '', '', 1639101866, 1639101866, 100, 1);
INSERT INTO `w_admin_config`(`id`, `name`, `title`, `group`, `type`, `value`, `options`, `tips`, `ajax_url`, `next_items`, `param`, `format`, `table`, `level`, `key`, `option`, `pid`, `ak`, `create_time`, `update_time`, `sort`, `status`) VALUES (48, 'withdraw_auto_pay', '开启线上打款', 'ti', 'switch', '', '', '开启之前请确认已开通微信、支付宝等相关线上转账功能', '', '', '', '', '', 0, '', '', '', '', 1639102024, 1639102069, 100, 1);
INSERT INTO `w_admin_config`(`id`, `name`, `title`, `group`, `type`, `value`, `options`, `tips`, `ajax_url`, `next_items`, `param`, `format`, `table`, `level`, `key`, `option`, `pid`, `ak`, `create_time`, `update_time`, `sort`, `status`) VALUES (49, 'withdraw_tips', '提现注意事项', 'ti', 'textarea', '1、满100可提现\r\n2、提现收取3%手续费\r\n3、提现金额T+1到账\r\n其他问题请联系客服！！！', '', '', '', '', '', '', '', 0, '', '', '', '', 1639102352, 1639102352, 100, 1);
UPDATE `w_admin_config` SET `value` = concat(`value`,'\r\nti:提现') WHERE `id` = 10;


INSERT INTO `w_admin_menu`(`id`, `pid`, `module`, `title`, `icon`, `url_type`, `url_value`, `url_target`, `online_hide`, `create_time`, `update_time`, `sort`, `system_menu`, `status`, `params`) VALUES (331, 316, 'user', '提现列表', 'fa fa-fw fa-money', 'module_admin', 'finance/cash/index', '_self', 0, 1620632261, 1626228062, 100, 0, 1, '');
INSERT INTO `w_admin_menu`(`id`, `pid`, `module`, `title`, `icon`, `url_type`, `url_value`, `url_target`, `online_hide`, `create_time`, `update_time`, `sort`, `system_menu`, `status`, `params`) VALUES (332, 331, 'user', '审核通过', '', 'module_admin', 'finance/cash/agree', '_self', 0, 1620632709, 1628237395, 100, 0, 1, '');
INSERT INTO `w_admin_menu`(`id`, `pid`, `module`, `title`, `icon`, `url_type`, `url_value`, `url_target`, `online_hide`, `create_time`, `update_time`, `sort`, `system_menu`, `status`, `params`) VALUES (333, 331, 'user', '导出', '', 'module_admin', 'finance/cash/export', '_self', 0, 1626231866, 1626231866, 100, 0, 1, '');
INSERT INTO `w_admin_menu`(`id`, `pid`, `module`, `title`, `icon`, `url_type`, `url_value`, `url_target`, `online_hide`, `create_time`, `update_time`, `sort`, `system_menu`, `status`, `params`) VALUES (334, 331, 'user', '标记已转账', '', 'module_admin', 'finance/cash/mark_payed', '_self', 0, 1626687631, 1628237436, 100, 0, 1, '');
INSERT INTO `w_admin_menu`(`id`, `pid`, `module`, `title`, `icon`, `url_type`, `url_value`, `url_target`, `online_hide`, `create_time`, `update_time`, `sort`, `system_menu`, `status`, `params`) VALUES (335, 331, 'user', '提现驳回', '', 'module_admin', 'finance/cash/reject', '_self', 0, 1620632793, 1628237412, 100, 0, 1, '');
