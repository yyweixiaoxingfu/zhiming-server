# 知命罗盘 · 服务端

> 八字 / 紫微 / 塔罗 / 姓名 等命理测算服务的后端，基于 ThinkPHP 5.1。
> 配套前端见独立仓库 `zhiming-app`（uni-app）。

[![License](https://img.shields.io/badge/license-Apache--2.0-blue.svg)](./LICENSE)

## 功能概览

| 模块 | 目录 | 说明 |
| --- | --- | --- |
| 测算接口 | `application/index` | 八字精批、流年运势、生辰详批、八字合婚、姻缘分析、姓名配对、财运分析、号码吉凶、紫微斗数、姓名详批、宝宝取名 |
| 塔罗 / 星座 / 生肖 | `application/index` | 塔罗九宫（运势、复合、脱单、暗恋、爱情、心理、分手、继续、他人）、星座运势、生肖运势、小六壬 |
| 后台管理 | `application/admin` | 基于 ZBuilder 的站点配置、权限、菜单、附件、插件管理 |
| 会员与分销 | `application/member`、`application/user` | 注册登录、等级、积分、推广分销、提现 |
| 订单与支付 | `application/order`、`application/payment` | 余额 / 积分 / 支付宝 / 微信（App、H5、PC、公众号、小程序、扫码、服务商） |
| AI 对话 | `application/ai` | 多模型接入、流式输出、语音识别（ASR）、语音合成（TTS）、内容安全 |
| 即时通讯 | `application/im` | 基于 GatewayWorker 的 WebSocket 服务 |
| 消息 | `application/message` | 短信、站内信、模板消息 |
| 财务 | `application/finance` | 账单、流水、对账 |
| 页面与内容 | `application/page`、`application/system` | 单页、公告、帮助中心、Banner |

## 技术栈

- PHP **7.4.x**（唯一兼容版本：PHP 8.0+ 会让 ThinkPHP 5.1 直接致命错误，PHP < 7.4 依赖装不上）
- ThinkPHP 5.1
- MySQL 5.7+ / MariaDB（需 `utf8mb4`）
- Composer 2
- Workerman GatewayWorker（IM 服务）

## 环境要求

必需 PHP 扩展：`pdo_mysql`、`mbstring`、`openssl`、`gd`、`json`、`curl`、`fileinfo`、`zip`、`simplexml`、`dom`、`bcmath`。

> 📘 **完整部署步骤请看 [docs/部署文档.md](./docs/部署文档.md)**，覆盖
> **Windows（phpEnv / phpStudy）** 与 **Linux（宝塔面板 / Nginx / Apache）** 四种环境，
> 含 PHP 版本要求、伪静态规则、目录权限、初始化配置、安全加固清单与分平台排错表。
> 初次部署请务必阅读其中的「第 0 节」（PHP 必须 7.4）和「第 5 步」（生成 `install.lock`）。

## 快速开始

> 以下命令为 Linux / macOS 写法。**Windows 用户请对照
> [部署文档](./docs/部署文档.md)**：`touch` 需换成 `type nul >`，
> 环境配置与站点创建可在 phpEnv / phpStudy 面板中完成。

```bash
# 1. 安装依赖
composer install

# 2. 配置环境
cp .env.example .env
#    按实际环境修改数据库账号与 JWT 密钥

# 3. 准备数据库
#    创建数据库（utf8mb4），导入完整初始化数据（含表结构 + 字典/词库 + 演示内容）：
mysql -u 用户名 -p 数据库名 < data/databsae.sql
#    然后生成安装锁文件，跳过安装引导：
mkdir -p data && touch data/install.lock

# 4. 配置 Web 服务器
#    站点根目录指向 public/，并开启 URL 重写（public/.htaccess 已提供 Apache 规则）
#    完整 Nginx / Apache / 宝塔配置见 docs/部署文档.md

# 5. 保证以下目录可写
chmod -R 775 runtime log data public/uploads
```

> ⚠️ 第 3 步的 `data/install.lock` **不能省略**：项目没有内置安装引导模块，
> 缺少该文件会直接报「模块不存在:install」。

后台登录地址：

```
http://你的域名/admin.php/user/publics/signin.html
```

初始账号 `admin` / 密码 `123456`，**登录后请立即修改密码**。
注意必须带完整的 `/user/publics/signin.html` 路径，只访问 `admin.php` 打不开后台。

IM 服务（可选）：

```bash
php public/start_im.php start
```

## 数据库

仓库已内置完整的初始化数据：**`data/databsae.sql`**（12.7 MB），包含全部表结构、字典/词库数据（如 `w_sm_*`、`w_give_*`、`w_tarot_*`、`w_qm_zidians` 等）以及演示内容，导入即可完整体验。

```bash
mysql -u 用户名 -p 数据库名 < data/databsae.sql
```

> ⚠️ **部署后务必修改以下凭据**：该 SQL 来自实际运行环境，其中的管理员密码哈希、会员记录、SMTP 与短信配置、AI 接口密钥均为原环境数据。导入后请立即到后台修改管理员密码，并重新配置邮件、短信、支付与 AI 相关密钥。

## 目录结构

```
php/
├── application/      # 业务模块（admin / index / ai / member / order / payment ...）
├── config/           # 应用配置
├── data/             # 安装锁 + 完整数据库导出 databsae.sql
├── docs/             # 部署文档、缓存方案、二维码
├── extend/           # 扩展类库
├── plugins/          # 插件
├── public/           # Web 根目录（index.php、admin.php、静态资源、uploads 上传目录）
├── route/            # 路由定义
├── runtime/          # 运行时缓存（已忽略）
├── thinkphp/         # 框架核心
├── vendor/           # Composer 依赖（随仓库提供，无需 composer install 即可运行）
├── .env.example      # 环境变量模板
├── LICENSE           # Apache-2.0
└── NOTICE            # 第三方组件声明
```

## 安全建议

- 部署到公网前将 `.env` 中的 `APP_DEBUG` 改为 `false`，并关闭 `config/app.php` 的 `app_debug` 与 `config/database.php` 的 `debug`。
- 修改 `config/app.php` 中的 `data_auth_key`，不要使用空值。
- 支付证书放在 `public/static/cert/`，该目录已被 `.gitignore` 忽略，请勿提交。
- 微信 / 支付宝 / AI / 短信等密钥全部通过后台配置或 `.env` 注入，不要写死在代码中。

## 免责声明

本项目所有测算结果均基于传统民俗文化与固定的词典规则生成，**属于娱乐内容，不构成任何形式的运势预测、医疗、法律或投资建议**。请勿将其结果用于人生重大决策。

## 特别感谢

本项目站在众多开源项目与第三方服务的肩膀上，在此向以下公司与组织致以诚挚感谢。
（完整版权与许可证信息见 [NOTICE](./NOTICE)）

### 一、框架与基础组件（代码级引用）

| 公司 / 组织 | 项目 | 引用位置 | 协议 |
| --- | --- | --- | --- |
| **ThinkPHP 官方**（thinkphp.cn，作者 liu21st） | ThinkPHP 5.1 | `thinkphp/` 框架目录，以及 `think-captcha` / `think-image` / `think-helper` 官方扩展 | Apache-2.0 |
| **广东卓锐软件有限公司**<br>**河源市卓锐科技有限公司**（zrthink.com） | DolphinPHP 海豚PHP 框架（dolphinphp.com） | `application/common/builder/` 的 ZBuilder、`config/dolphin.php`、`config/system.php`、`config/session.php`、`config/cookie.php`、`public/static/admin/js/dolphin.js`、`public/static/admin/css/dolphin.css`、`plugins/Wxpush/` | Apache-2.0 |
| **Verdant Industries**（作者 Lalit Patel，lalit.org） | XML2Array / Array2XML | `application/system/library/XML2Array.php`、`Array2XML.php` | Apache-2.0 |
| **Workerman**（workerman.net） | GatewayWorker | IM 即时通讯模块 `application/im/` | MIT |
| **PHPMailer** | PHPMailer | 邮件发送 | LGPL-2.1 |
| **PhpSpreadsheet** | PhpSpreadsheet | Excel 导入导出 | MIT |
| **HTMLPurifier**（ezyang） | HTMLPurifier | 富文本 XSS 过滤 | LGPL-2.1 |
| **yansongda/pay** | Pay | 微信 / 支付宝支付 | MIT |
| **Carbon**（nesbot） | Carbon | 时间处理 | MIT |
| **Symfony / PSR** | 各组件 | Composer 依赖 | MIT |
| **Alibaba Cloud** | alibabacloud/client | 阿里云 SDK | Apache-2.0 |

### 二、云服务商（AI / 短信 / 支付 / 翻译 / 搜索）

本项目在后台以可配置方式接入了以下平台的服务，均为**可选**能力，不配置不影响核心测算功能：

- **OpenAI**（api.openai.com）—— GPT 对话、Whisper、TTS
- **Microsoft Azure OpenAI**（resource-url.openai.azure.com）
- **阿里云**（dashscope.aliyuncs.com / dysms.console.aliyun.com）—— 通义千问 DashScope、短信服务
- **百度智能云**（aip.baidubce.com / cloud.baidu.com）—— 翻译、内容审核
- **智谱 AI**（open.bigmodel.cn）
- **DeepSeek**（deepseek.com）
- **硅基流动 SiliconFlow**（siliconflow.cn）
- **无问芯穹 Infini-AI**（cloud.infini-ai.com）
- **科大讯飞**（xfyun.cn）—— 语音合成
- **创蓝 253**（sms.253.com）—— 短信通道
- **DuckDuckGo**（lite.duckduckgo.com）—— 联网搜索
- **腾讯微信 / 支付宝** —— 登录、支付

### 三、数据来源与算法参考

- **星座屋**（cache.xzw.com）—— `application/index/model/Stars.php` 实时拉取星座运势数据
- **中华人民共和国民政部**（mca.gov.cn）—— `application/system/command/SystemRegion.php` 行政区划数据
- **NASA**（eclipse.gsfc.nasa.gov）—— `application/common/library/phpu/Astronomy.php` 中 ΔT 计算方法参考
- **Jean Meeus《Astronomical Algorithms》** —— 节气与日月食算法公式来源
- **ipeifei**（bbs.ipfei.cn / api.ipfei.com）—— AI 接口中转与配置教程
- **one-api**（github.com/songquanpeng/one-api）—— 模型聚合转发方案参考

> 若你是上述任一项目的作者，且不希望本仓库引用你的作品或服务，
> 请通过微信或 Issue 联系作者，我会在第一时间移除相关内容。

## 技术支持与定制

本项目免费开源，如果你在部署过程中遇到问题，或需要**二次开发、功能定制、商业化部署**等服务，欢迎通过微信联系作者：

<p align="center">
  <img src="./docs/images/wechat-qr.png" alt="作者微信二维码" width="220">
</p>

> 扫码添加微信，备注「知命罗盘」。

## 许可证

本项目采用 [Apache License 2.0](./LICENSE) 发布。第三方组件的归属与许可证信息见 [NOTICE](./NOTICE)。

其中 `application/common/` 下的 ZBuilder 后台构建器源自 DolphinPHP（广东卓锐软件有限公司），使用前请自行确认其授权范围。
