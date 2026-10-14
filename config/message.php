<?php 
 return array (
  'driver' => 
  array (
    'sms' => '短信',
    'email' => '邮件',
    'info' => '站内信',
    'notice' => '通知',
  ),
  'sms' => 
  array (
    'is_close' => true,
    'default' => 'kaixintong',
    'engine' => 
    array (
      'aliyun' => 
      array (
        'sign' => '',
        'key' => '',
        'secret' => '',
      ),
      'duanxinbao' => 
      array (
        'key' => '',
        'secret' => '',
      ),
      'juhe' => 
      array (
        'app_key' => '',
      ),
      'kaixintong' => 
      array (
        'userid' => '',
        'account' => '',
        'password' => '',
      ),
      'qiyexinshi' => 
      array (
        'userid' => '',
        'account' => '',
        'password' => '',
      ),
      'yunxinshi' => 
      array (
        'uid' => '',
        'password' => '',
      ),
      'huaweiyun' => 
      array (
        'endpoint' => '',
        'app_key' => '',
        'app_secret' => '',
        'sender' => '',
        'signature' => '',
      ),
    ),
  ),
  'email' => 
  array (
    'is_close' => false,
    'host' => 'smtp.gmail.com',
    'username' => '',
    'password' => '',
    'port' => '587',
    'fromEmail' => 'a18236930243@gmail.com',
    'fromUserName' => '知命罗盘【官方】',
  ),
  'info' => 
  array (
    'is_close' => false,
  ),
  'notice' => 
  array (
    'is_close' => false,
  ),
);