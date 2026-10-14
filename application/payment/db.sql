CREATE TABLE `w_pay` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `target_type` varchar(16) NOT NULL COMMENT '''recharge''=>充值,''order''=>下单',
  `target_no` varchar(255) NOT NULL DEFAULT '' COMMENT '订单或充值编号',
  `member_id` int(10) NOT NULL DEFAULT '0' COMMENT '会员id',
  `money` decimal(18,2) DEFAULT NULL COMMENT '支付金额',
  `pay_money` decimal(18,2) DEFAULT NULL COMMENT '已支付金额',
  `trade_no` varchar(255) DEFAULT '' COMMENT '交易编号',
  `turn_no` varchar(255) DEFAULT '' COMMENT '第三方交易单号',
  `status` enum('pay','nopay') DEFAULT 'nopay' COMMENT '''pay''=>''已支付'',''nopay''=>''未支付''',
  `pay_type` varchar(32) DEFAULT NULL COMMENT '支付方式:deposit余额支付,alipay支付宝,wechat微信,bank银行卡',
  `data` text COMMENT '支付信息',
  `create_time` int(10) DEFAULT '0' COMMENT '创建时间',
  `pay_time` int(10) DEFAULT '0' COMMENT '付款时间',
  `openid` varchar(50) DEFAULT NULL COMMENT '微信openid',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `index_targetno` (`target_no`) USING BTREE,
  KEY `index_tradeno` (`trade_no`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='支付记录表';

INSERT INTO `w_admin_menu`(`id`, `pid`, `module`, `title`, `icon`, `url_type`, `url_value`, `url_target`, `online_hide`, `create_time`, `update_time`, `sort`, `system_menu`, `status`, `params`) VALUES (343, 4, 'admin', '支付流水管理', 'fa fa-fw fa-paypal', 'module_admin', '', '_self', 0, 1626351812, 1626351812, 100, 0, 1, '');
INSERT INTO `w_admin_menu`(`id`, `pid`, `module`, `title`, `icon`, `url_type`, `url_value`, `url_target`, `online_hide`, `create_time`, `update_time`, `sort`, `system_menu`, `status`, `params`) VALUES (344, 343, 'admin', '支付单流水', 'fa fa-fw fa-list', 'module_admin', 'payment/pay/index', '_self', 0, 1626351857, 1626351857, 100, 0, 1, '');
INSERT INTO `w_admin_menu`(`id`, `pid`, `module`, `title`, `icon`, `url_type`, `url_value`, `url_target`, `online_hide`, `create_time`, `update_time`, `sort`, `system_menu`, `status`, `params`) VALUES (345, 344, 'admin', '导出', '', 'module_admin', 'payment/pay/export', '_self', 0, 1626659710, 1626659710, 100, 0, 1, '');
