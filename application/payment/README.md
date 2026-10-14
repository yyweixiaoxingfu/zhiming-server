## 支付模块

### 文件
- `application\payment` 支付模块代码
- `config\payment.php` 配置文件
- `application\alipay` 支付宝支付
- `application\wechat` 微信支付

### 数据库
`application\payment\db.sql`

### composer依赖包
`yansongda/pay 2.*`

### 已有功能
> 前台
- 支付:需根据自身业务调整支付前验证逻辑
- 回调:需根据自身业务调整回调业务逻辑
> 后台
- 支付记录以及导出