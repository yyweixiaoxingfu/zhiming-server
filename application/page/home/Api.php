<?php
//

namespace app\page\home;
use app\index\controller\Api as IndexApi;
use think\Controller;
use app\page\model\PageModel;
use app\member\facade\JwtAuth;  
use think\facade\Env;
/**
 * 前台公共控制器
 * @package app\index\controller
 */
//class Api extends IndexApi{
class Api extends controller
{   
    public function index(){
       $pageModel = new PageModel();
       $res = $pageModel->getDefaultItems();
       return json($res);
    }

}