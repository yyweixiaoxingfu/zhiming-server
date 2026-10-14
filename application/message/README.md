## 配置 config/message.php
- driver：系统支持的消息引擎，可选项包括sms、email、info、notice，不支持的引擎后台不显示模板配置
- sms、email、info、notice：引擎参数配置，可以只配置支持的引擎，如果需要该为后台配置，可在library/driver/xxx.php中setConfig中修改，最终配置格式应保持一致
- is_close：每个消息引擎中均需要单独配置，值为true时此类消息均不发送

## demo

> 发送验证码
```php
$server = new MessageServer();
// $res = $server->sendVerifyCode('login','13500135000');
$res = $server->sendVerifyCode('login','1600605436@qq.com','email');
if(!$res){
    return returnData(0,$server->getError() ?? '发送失败');
}
return returnData(1,'发送成功');
```
> 验证验证码
```php
// $res = app(MessageServer::class)->checkVerifyCode('13500135000','login','587033');
$res = app(MessageServer::class)->checkVerifyCode('1600605436@qq.com','login','587033');
if(!$res){
    return returnData(0,'验证码错误');
}
```

> 根据后台配置模板发送消息 actionSend
```php
// demo1
$member_ids = 6;
$params = ['order_no' => 'SN19506145412', 'goods_name'=>'羽绒被','pay_money'=> '198.00'];
app(MessageServer::class)->actionSend('order_pay','member',$member_ids,$params);
// demo2
$members = MemberModel::where('lock',0)->select();
$params = ['order_no' => 'SN19506145412', 'goods_name'=>'羽绒被','pay_money'=> '198.00'];
app(MessageServer::class)->actionSend('order_pay','',$members,$params,['target_type'=>'member']);
```

> 自定义消息发送 customSend 
```php
// demo1
app(MessageServer::class)->customSend('系统消息','你好，这是一条测试消息，请忽略！','info','admin',1,[],['uid_send'=>2]);
// demo2
app(MessageServer::class)->customSend('系统消息','你好，这是一条测试消息，请忽略！',['info','email'],'admin',1,[]);
// demo3
app(MessageServer::class)->customSend('系统消息','尊敬的{user_name}，你好，这是一条测试消息，请忽略！',['info','email'],'member','4,5,6',[],['uid_send'=>10]);
// demo4
$members = MemberModel::where('lock',0)->select();
app(MessageServer::class)->customSend('系统消息','尊敬的{user_name}，你好，{content}！',['sms','info','email'],'',$members,['content'=>'这是一条测试消息'],['uid_send'=>10,'target_type'=>'member']);
```