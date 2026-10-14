## 会员模块

### 文件
- `application\system` 整体代码
- `application\common\library\sms` 短信包

### 数据库
`application\system\db.sql`

### composer依赖包
无

### 其他配置
application\command.php文件配置添加
```php
    \app\system\command\SystemService::class,
    \app\system\command\SystemRegion::class,
```
执行 `php think systemregion` 更新地址库

执行 `php think systemservice` 更新service

### 已有功能
> 前台
- 系统公告
- 帮助中心
- 获取系统配置
- 地区联动接口
- 单图上传
- 发送短信验证码
> 后台
- 系统公告
- 轮播图
- 帮助中心
- 地址库管理