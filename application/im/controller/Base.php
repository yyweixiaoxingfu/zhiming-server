<?php

namespace app\im\controller;

use think\Controller;
use app\member\facade\JwtAuth;

/**
 * IM模块基础控制器
 * @package app\im\controller
 */
class Base extends Controller
{
    protected $auth;
    protected $memberInfo;
    protected $memberId;

    /**
     * 初始化方法
     * @author 
     */
    protected function initialize()
    {
        parent::initialize();
        // 初始化JWT认证
        $this->auth = JwtAuth::guard('default');
        // 获取当前用户信息
        $this->memberInfo = $this->auth->user();
        $this->memberId   = $this->memberInfo['id'] ?? 0;
    }
}
