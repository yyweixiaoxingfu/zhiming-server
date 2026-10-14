<?php
//

namespace app\index\controller;

use app\member\facade\JwtAuth;
/**
 * 前台公共控制器
 * @package app\index\controller
 */
class Api extends Home
{
    protected $except = [];
    protected $guard = 'default';
    private $memberID;
    private $memberInfo;
    /**
     * 初始化方法
     * @author 
     */
    protected function initialize()
    {
        parent::initialize();
        $this->middleware = ['Auth:'.$this->guard =>['except'=>$this->except]];
        $action = $this->request->action();
        if(in_array($action,$this->except)){
            $jwtAuth = JwtAuth::guard($this->guard);
            $member_info = $jwtAuth->user();
            $this->request->memberInfo = $member_info;
            $this->request->memberID   = $member_info['id'] ?? null;
        }
    }

    public function __get($name)
    {
        if (in_array($name,['memberInfo','memberID'])){
            return $this->request->param($name);
        }
    }

}
