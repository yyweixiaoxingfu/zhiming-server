/*
 Navicat Premium Data Transfer

 Source Server         : My
 Source Server Type    : MySQL
 Source Server Version : 50728 (5.7.28)
 Source Host           : 127.0.0.1:3306
 Source Schema         : godwill

 Target Server Type    : MySQL
 Target Server Version : 50728 (5.7.28)
 File Encoding         : 65001

 Date: 26/01/2026 17:36:49
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for w_topic_level
-- ----------------------------
DROP TABLE IF EXISTS `w_topic_level`;
CREATE TABLE `w_topic_level`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '等级题目表ID',
  `level_tip` int(10) NULL DEFAULT 0,
  `level_num` int(10) NULL DEFAULT 0 COMMENT '级别',
  `topic_num` int(10) NULL DEFAULT 0 COMMENT '题目数量',
  `level_nid` int(10) NULL DEFAULT 0 COMMENT '原数据ID',
  `soft` int(10) NULL DEFAULT 0 COMMENT '排序（level_id）',
  `create_time` int(10) NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `t_level_id`(`id`) USING BTREE,
  INDEX `t_cha_id`(`level_tip`) USING BTREE,
  INDEX `t_cu_id`(`level_num`) USING BTREE,
  INDEX `t_l_nid`(`level_nid`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '等级表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of w_topic_level
-- ----------------------------

-- ----------------------------
-- Table structure for w_topic_questions
-- ----------------------------
DROP TABLE IF EXISTS `w_topic_questions`;
CREATE TABLE `w_topic_questions`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tl_id` int(10) NULL DEFAULT 0 COMMENT '上级表ID',
  `tl_difficulty` int(10) NULL DEFAULT 0 COMMENT '难度系数',
  `tl_level` int(10) NULL DEFAULT 0 COMMENT '题目级别',
  `soft` int(10) NULL DEFAULT 0 COMMENT '排序',
  `questions` longtext CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '题目（一般为2道）',
  `create_time` int(10) NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `t_q_id`(`id`) USING BTREE,
  INDEX `t_q_tl_id`(`tl_id`) USING BTREE,
  INDEX `t_q_dy_id`(`tl_difficulty`) USING BTREE,
  INDEX `t_q_l_id`(`tl_level`) USING BTREE,
  FULLTEXT INDEX `t_q_q_data`(`questions`)
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '题目表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of w_topic_questions
-- ----------------------------

-- ----------------------------
-- Table structure for w_topic_storage
-- ----------------------------
DROP TABLE IF EXISTS `w_topic_storage`;
CREATE TABLE `w_topic_storage`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '数据ID',
  `chapter_id` int(10) NULL DEFAULT 0 COMMENT '（L1~L4级别ID）',
  `course_id` int(10) NULL DEFAULT NULL COMMENT '（初中高级别ID）',
  `questions_count` int(10) NULL DEFAULT NULL COMMENT '数量',
  `questions_data` longtext CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '内容',
  `create_time` int(10) NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) NULL DEFAULT 0 COMMENT '更新时间',
  `level_nid` int(10) NULL DEFAULT NULL,
  `is_sync` tinyint(1) NULL DEFAULT 0 COMMENT '是否同步',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `t_storage_id`(`id`) USING BTREE,
  INDEX `t_storage_chapter_id`(`chapter_id`) USING BTREE,
  INDEX `t_storage_course_id`(`course_id`) USING BTREE,
  INDEX `t_storage_l_nid`(`level_nid`) USING BTREE,
  INDEX `t_storage_is_sync`(`is_sync`) USING BTREE,
  FULLTEXT INDEX `t_storage_q_data`(`questions_data`)
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '数据备份表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of w_topic_storage
-- ----------------------------

SET FOREIGN_KEY_CHECKS = 1;
