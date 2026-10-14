<?php
//

namespace app\index\controller;

use app\common\controller\Common;
use think\Request;

/**
 * 前台公共控制器
 * @package app\index\controller
 */
class Home extends Common
{
    public $model;
    public $transformer;
    public $page;
    public $pageSize;
    /**
     * 初始化方法
     * @author 
     */
    protected function initialize()
    {
        // 系统开关
        if (!config('web_site_status')) {
            $this->error('站点已经关闭，请稍后访问~');
        }
        $this->pageInfo();
    }
    public function pageInfo()
    {
        $this->page = request()->param('page', 1);
        $this->pageSize = request()->param('page_size', 10);
    }

    public function get_list(Request $request){
//        $params=input();
        $params=$request->post();
        if(isset($params)){
            //$filter = json_decode($filter,true);
            //$page = isset($params['page']) ? $params['page']:'0';
            $limit = isset($params['limit']) ? $params['limit']:$this->pageSize;
        }
        $data = $this->model->scope(function($query) use($params){
            if (method_exists($this->model, 'getQuery')) {
                $query = $this->model->getQuery($query, $params);
            }

            return $query->order('id','desc');
        })->paginate($limit);

        //
        if ($this->transformer){
            $data = transformer($data, $this->transformer,[]);
        }
        return returnData(1,'success',$data);
    }
}
