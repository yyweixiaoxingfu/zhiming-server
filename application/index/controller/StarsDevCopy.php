<?php

namespace app\index\controller;

use think\Db;
use app\index\model\Sm as SmModel;
use app\index\model\Stars as StarsModel;

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
            //$y       = $post_data['y'];
            $m         = $post_data['m'];
            $d         = $post_data['d'];
            $h         = $post_data['h'];
            $i         = $post_data['i'];
            $StarModel = new StarsModel();
            $star_data = $StarModel->shangsheng($m, $d, $h, $i);
            $tid       = $star_data['tid'];
            $star_data_ys = $StarModel->get_date_yunshi($tid, date('Y-m-d'));
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
        $star_ys_data                   = $StarModel->get_date_yunshi($stars_ss_data['tid'], date('Y-m-d'));
        $result = ['stars_base_data' => $stars_base_data, 'stars_ss_data' => $stars_ss_data, 'star_ys_data' => $star_ys_data];
        return $result;
    }



    public function tarot($format_data,$link_id='')
    {
        if(!$link_id){
            return 0;
        }
        $format_data = json_decode($format_data, true);
        if (!$format_data) {
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
            $tarot_data  = Db::name($tarot_table)
                ->where('id','in',$tarot_ids)
                ->order('lie asc')
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
            }else{
                return 0;
            }
            return $tarot_data;

        // if ($this->request->isPost()) {
        //     $post_data  = SafeFilterItem(input('post.'));
        //     $tarot_type = $post_data['tarot_type'];
        //     //1塔罗牌占卜-你喜欢的人心里有想着别人吗？
        //     //2塔罗占卜牌 你今年的运势 
        //     //3塔罗牌占卜ta的心里有没有你？ 
        //     //4你暗恋的人也喜欢你吗？塔罗牌占卜 
        //     //5塔罗牌爱情占卜，你和TA该继续下去吗？ 
        //     //6爱情运势大揭秘，塔罗占卜你的爱情运势？ 
        //     //7塔罗牌脱单占卜3个月内你会脱单吗？
        //     //8你们有机会复合吗？塔罗牌占卜
        //     //9你和ta能走到最后吗？塔罗牌占卜
        //     //10你和ta该不该分手？塔罗牌占卜
        //     $StarModel = new StarsModel();
        //     if ($tarot_type == 1) {
        //         $tarot_desc['title'] = '塔罗牌占卜-你喜欢的人心里有想着别人吗？';
        //         $tarot_desc['desc'] = '他/她是否只爱你一人，对方心里有没有想着别人？';
        //         $tarot_data = $StarModel->car_bieren();
        //     } elseif ($tarot_type == 2) {
        //         $tarot_desc['title'] = '塔罗牌占卜-未来运势大揭秘？';
        //         $tarot_desc['desc'] = '圣三角牌阵解读你的过去，现在和未来，塔罗牌为你解开困惑。';
        //         $tarot_data = $StarModel->car();
        //     } elseif ($tarot_type == 3) {
        //         $tarot_desc['title'] = '塔罗牌占卜-ta的心里有没有你？';
        //         $tarot_desc['desc'] = '懂一个人好难TA的心里到底有没有我？塔罗揭秘你的爱情TA的心。';
        //         $tarot_data = $StarModel->car_xinli();
        //     } elseif ($tarot_type == 4) {
        //         $tarot_desc['title'] = '塔罗牌占卜-你暗恋的人也喜欢你吗？';
        //         $tarot_desc['desc'] = '暗恋虽然美好但太过于煎熬，塔罗揭秘ta是否也喜欢你。';
        //         $tarot_data = $StarModel->car_anlian();
        //     } elseif ($tarot_type == 5) {
        //         $tarot_desc['title'] = '塔罗牌占卜-你和TA该继续下去吗？';
        //         $tarot_desc['desc'] = '是分手还是继续，4张牌告诉你答案。感情遇到阻碍是继续还是该放手，他值得你继续等待吗？';
        //         $tarot_data = $StarModel->car_jixu();
        //     } elseif ($tarot_type == 6) {
        //         $tarot_desc['title'] = '塔罗牌占卜-爱情运势大揭秘，塔罗占卜你的爱情运势？';
        //         $tarot_desc['desc'] = '单身的你合适能脱单？你的桃花运如何？单身你的会在什么时候找到另外一半？';
        //         $tarot_data = $StarModel->car_aiqing();
        //     } elseif ($tarot_type == 7) {
        //         $tarot_desc['title'] = '塔罗牌占卜-3个月内你会脱单吗？？';
        //         $tarot_desc['desc'] = '孤身一人你是否知道命中注定的TA在哪？渴望感情而又害怕受伤，三个月内你能否结束单身生活?';
        //         $tarot_data = $StarModel->car_tuodan();
        //     } elseif ($tarot_type == 8) {
        //         $tarot_desc['title'] = '塔罗牌占卜-你们有机会复合吗？';
        //         $tarot_desc['desc'] = '分手后无法忘记对方，究竟还能不能复合，塔罗揭秘复合天机。';
        //         $tarot_data = $StarModel->car_fuhe();
        //     } elseif ($tarot_type == 9) {
        //         $tarot_desc['title'] = '塔罗牌占卜-你和ta能走到最后吗？';
        //         $tarot_desc['desc'] = '想要预知感情发展结果的你，默想对方的样子，做好占卜准备。';
        //         $tarot_data = $StarModel->car_zuihou();
        //     } elseif ($tarot_type == 10) {
        //         $tarot_desc['title'] = '塔罗牌占卜-你和ta该不该分手？';
        //         $tarot_desc['desc'] = '难以沟通、受尽委屈、频频失误、患得患失、这样的爱情该不该分手？';
        //         $tarot_data = $StarModel->car_fenshou();
        //     }
        //     return returnData(1, '测算成功', ['tarot_data' => $tarot_data, 'tarot_desc' => $tarot_desc]);
        // }
    }
}
