## 会员模块

### 文件
- `application\member` 整体代码
- `application\http\middleware\Auth.php` 中间件，验证token，检测会员登录状态
- `application\common\library\ExcelServer.php` excel导出
- `config\jwt.php` token验证参数配置
- `config\wechat.php` 微信授权登录参数配置（无微信授权登录可去掉）

### 数据库
`application\member\db.sql`

### composer依赖包
- `nesbot/carbon`
- `phpoffice/phpspreadsheet`
- `overtrue/wechat 4.*` （无微信授权登录可去掉）

### 已有功能
> 前台
- 会员登录注册相关
- 登录方式：账号密码登录、手机验证码登录、公众号登录、小程序登录
- 个人中心会员账号管理
- 意见反馈
> 后台
- 会员管理：增删改查、导入、导出
- 意见反馈管理
- 会员资金流水记录、导出