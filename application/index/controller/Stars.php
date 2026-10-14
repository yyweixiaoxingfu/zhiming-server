<?php

namespace app\index\controller;

use think\Db;
use app\index\model\Sm as SmModel;
use app\index\model\Stars as StarsModel;
use app\order\model\SpOrderModel;

/**
 * 星座 塔罗牌 祭祀前台首页控制器
 * @package app\index\controller
 */
class Stars extends Home
{

    public function xzfx()
    {
        if ($this->request->isPost()) {
            $post_data  = SafeFilterItem(input('post.'));
            $m         = $post_data['m'];
            $d         = $post_data['d'];
            $h         = $post_data['h'];
            $i         = $post_data['i'];
            $StarModel     = new StarsModel();
            $star_data     = $StarModel->shangsheng($m, $d, $h, $i);
            $tid           = $star_data['tid'];
            $star_data_ys  = $StarModel->get_date_yunshi($tid, date('Y-m-d'));
            return returnData(1, '测算成功', ['star_data' => $star_data, 'star_data_ys' => $star_data_ys]);
        }
    }

    //星座数据
    public function xingzuo($format_data)
    {
        $format_data = json_decode($format_data, true);
        if (!$format_data) {
            return 0;
        }
        $StarModel          = new StarsModel();
        $star_id            = $format_data['stars_data'];
        $stars_base_data    =  Db::name('star_sign_base')->where('id', $star_id)->find();
        if (empty($stars_base_data)) {
            return 0;
        }
        $stars_base_data['icon']        = setUpImg($stars_base_data['icon']);
        $stars_base_data['text_icon']   = setUpImg($stars_base_data['text_icon']);
        $stars_ss_data      = Db::name('star_sign_shangsheng_data')->where('base_id', $stars_base_data['id'])->find();
        if (empty($stars_ss_data)) {
            return 0;
        }
        $stars_ai_data                  = $StarModel->xz_aq($stars_base_data['id']);
        $stars_cy_data                  = $StarModel->xz_cy($stars_base_data['id']);
        $stars_sy_data                  = $StarModel->xz_sy($stars_base_data['id']);
        $stars_zh_data                  = $StarModel->xz_zh_on($stars_base_data['id']);
        $star_ys_data                   = $StarModel->get_date_yunshi($stars_ss_data['tid'], date('Y-m-d'));
        $result = ['stars_base_data' => $stars_base_data,'stars_ai_data' => $stars_ai_data,'stars_cy_data' => $stars_cy_data,'stars_sy_data' => $stars_sy_data,'stars_zh_data' => $stars_zh_data,'stars_ss_data' => $stars_ss_data, 'star_ys_data' => $star_ys_data];
        return $result;
    }


    //塔罗牌测算
    public function tarot($format_data='',$links_id='',$is_method_call = false)
    {
         $submit_type = 0;
         if($this->request->isPost() && !$is_method_call){
            $submit_type    = 1;
            $post_data      = SafeFilterItem(input('post.'));
            $order_no       = isset($post_data['order_no']) && $post_data['order_no'] ? $post_data['order_no'] : 0;
            if(!$order_no){
                return returnData(0, '订单号有误');
            }
            $order_find = SpOrderModel::where('order_no',$order_no)->where('status',0)->find();
            if(empty($order_find)){
                return returnData(0, '订单不存在');
            }
            $links_id       = $order_find['link_id'];
            $format_data    = $order_find['submit_data'];
        }
        $link_id       = $links_id;
        $format_data   = json_decode($format_data, true);
        if(!$link_id){
            return 0;
        }
        if (empty($format_data)) {
            return 0;
        }
        switch ($link_id) {
                case 13:
                    $tarot_table = 'tarot_yunshi';
                    break;
                case 14:
                    $tarot_table = 'tarot_fuhe';
                    break;
                case 15:
                    $tarot_table = 'tarot_tuodan';
                    break;
                case 16:
                    $tarot_table = 'tarot_anlian';
                    break;
                case 20:
                    $tarot_table = 'tarot_aiqing';
                    break;
                case 21:
                    $tarot_table = 'tarot_bieren';
                    break;
                case 22:
                    $tarot_table = 'tarot_fenshou';
                    break;
                case 23:
                    $tarot_table = 'tarot_jixu';
                    break;
                case 24:
                    $tarot_table = 'tarot_xinli';
                    break;
                case 25:
                    $tarot_table = 'tarot_zuihou';
                    break;
                default:
                    return 0;
                    break;
            }
            $requestScheme = $_SERVER['REQUEST_SCHEME'] ?? 'http';
            $local_url     = $requestScheme . '://' . $_SERVER['HTTP_HOST'];
            $tarot_ids     = explode(',',$format_data['tarot_data']);
            $field = '*';
            if($submit_type){
                $field = 'id,pid,title,zf,img';
            }
            $tarot_data  = Db::name($tarot_table)
                ->where('id','in',$tarot_ids)
                ->order('lie asc')
                ->field($field)
                ->select();

            if (!empty($tarot_data)) {
                foreach ($tarot_data as $key => &$value) {
                    if(!$value['img']){
                        $value['img'] = $local_url.'/uploads/images/taluo/images/' . $value['pid'] . '_' . $value['zf'] . '.jpg';
                    }else{
                        if(is_file($value['img'])){
                            $value['img'] = setUpImg($value['img']);
                        }else{
                            $value['img'] = $local_url.$value['img'];
                        }
                    }
                }
                if($link_id==15 && $tarot_table=='tarot_tuodan' && !$submit_type){
                    $tarot_count    = count($tarot_data);
                    $tuodan_ratio   = false;
                    for($i=0;$i<$tarot_count;$i++){
                        if($i<=2){
                            $m = $i+1;
                            $this_months = date('m',strtotime('+'.$m.' months'));
                            $this_ym     = date('Y年m月',strtotime('+'.$m.' months'));
                            $d           = isset($tarot_data[$i]['day']) && $tarot_data[$i]['day'] ? $tarot_data[$i]['day'] : false;
                            if($d){
                                $this_day = $d.'日';
                            }else{
                                $this_day = generateRandomDay(true,$this_months).'日';
                            }
                            $this_time      = $this_ym.$this_day;
                            $tuodan_day[]   = $this_time;
                            $r              = isset($tarot_data[$i]['bili']) && $tarot_data[$i]['bili'] ? $tarot_data[$i]['bili'] : false;
                            if($r){
                                $tuodan_ratio = $r.'%';
                            }
                        }
                    }
                    if(!$tuodan_ratio){
                        $tuodan_ratio   = rand(50,100).'%';
                    }
                    $tarot_data['tuodan_day']  = $tuodan_day;
                    $tarot_data['tuodan_ratio'] = $tuodan_ratio;
                }
                
            }else{
                return 0;
            }
            if($submit_type){
                return returnData(1, '获取成功', ['tarot_data'=>$tarot_data]);
            }
            return $tarot_data;
    }
}
