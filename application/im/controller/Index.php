<?php

namespace app\im\controller;

use think\Controller;

/**
 * IM模块首页控制器
 * @package app\im\controller
 */
class Index extends Controller
{
    /**
     * IM首页
     * @return mixed
     */
    public function index()
    {
        return $this->fetch();
    }
}
