<?php

namespace app\index\controller;

use app\system\model\BannerModel;
use app\admin\model\SpCateModel;
use app\admin\model\SpConfigModel;
use app\admin\model\SpProjectModel;
use app\admin\model\SpExpertModel;
use app\admin\model\SpNewsModel;
use app\member\model\MemberModel;
use think\facade\Log;
use think\Exception;
use think\Db;

header('Access-Control-Allow-Origin: *'); // 允许所有域名访问

/**
 * 前台首页控制器
 * @package app\index\controller
 */
class Index extends Home
{
    public function index()
    {
        // 默认跳转模块
        if (config('home_default_module') != '' && config('home_default_module') != 'index') {
            $this->redirect(config('home_default_module') . '/index/index');
        }
        //return $this->fetch();
        return '<style type="text/css">
            *{ padding: 0; margin: 0; } 
            .think_default_text{ padding: 4px 48px;} 
            a{color:#2E5CD5;cursor: pointer;text-decoration: none} 
            a:hover{text-decoration:underline; } 
            body{ 
            background: #fff; 
            font-family: "Century Gothic","Microsoft yahei"; 
            color: #333;font-size:18px} 
            h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } 
            p{ line-height: 1.6em; font-size: 42px }
        </style>
        <div style="padding: 24px 48px;"> 
            <h1>:)</h1>
            <p> 
                ' . config("dolphin.product_name") . ' ' . config("dolphin.product_version") . '<br/>
                <span style="font-size:30px">极速 · 极简 · 极致</span>
            </p>
        </div>';
    }

    public function get_home_info()
    {
        if ($this->request->isPost()) {
            //$post   = SafeFilterItem(input('post.'));
            $banner = BannerModel::where('pos', 'home')->order('sort asc,id desc')->select();
            if ($banner) {
                $banner = $banner->toArray();
                foreach ($banner as $k => &$v) {
                    $v['pic'] = setUpImg($v['pic']);
                    $v['create_time'] = $v['create_time'] ? date('Y-m-d H:i', $v['create_time']) : '';
                    $v['update_time'] = $v['update_time'] ? date('Y-m-d H:i', $v['update_time']) : '';
                }
            }
            $SpConfig     =  SpConfigModel::where('id', 1)->find();
            $comment_data = explode('|', $SpConfig['comment_data']);
            $limit        =  $SpConfig['hot_x'] * $SpConfig['hot_y'];
            //$find_cate_id = 0;
            $cate_list    = SpCateModel::where('cate_show_type', 0)->where('pid', '>', 0)->where('cate_status', 1)->where('is_del', 0)->limit($limit)->order('sort asc')->field('id,cate_name,cate_title,cate_desc,cate_type,cate_keyword,cate_img,link_id')->select();
            if (!empty($cate_list)) {
                foreach ($cate_list as $k => &$v) {
                    $v['cate_img'] = setUpImg($v['cate_img']);
                    $v['p_model'] = '';
                    if ($v['cate_type'] == 1) {
                        if ($v['link_id']) {
                            $v['p_model']  = SpProjectModel::where('id', $v['link_id'])->value('p_model');
                        }
                    } elseif ($v['cate_type'] == 2) {
                        $v['p_model'] = 'news';
                    } elseif ($v['cate_type'] == 4) {
                        $v['p_model'] = 'expert';
                    }
                }
            }
            $home_cate = SpCateModel::where('pid', 0)->where('cate_status', 1)->where('is_del', 0)->order('sort asc')->field('id,cate_name,cate_title,cate_desc,cate_type,cate_keyword')->select();
            if (!empty($home_cate)) {
                foreach ($home_cate as $k => &$v) {
                    $v['child'] = SpCateModel::where('pid', $v['id'])->where('cate_show_type', 1)->where('cate_status', 1)->where('is_del', 0)->order('sort asc')->field('id,cate_name,cate_title,cate_desc,cate_type,cate_keyword')->select();
                    // if (!empty($v['child']) && $find_cate_id == 0) {
                    //     $find_cate_id = $v['child'][0]['id'];
                    // }
                }
            }
            // $list = SpProjectModel::where('cid', $find_cate_id)->where('p_status', 1)->order('sort asc')->field('id,p_title,p_model,p_ad_num,p_img,p_points,p_price,p_desc,p_extra,cid,sort,p_keyword,p_cs_num')->select();
            // if(!empty($list)){
            //     foreach($list as $k=>&$v){
            //         $v['p_img'] = setUpImg($v['p_img']);
            //     }
            // }
            //  $home_list = Db::name('sm_class')->where('home_type',1)->where('status',0)->where('is_del',0)->order('sort asc')->field('id,name,title,keywords,description,home_img,url,desc,cs_num')->select();
            //  if(!empty($home_list)){
            //     foreach($home_list as $k=>&$v){
            //         $v['home_img'] = setUpImg($v['home_img']);
            //     }
            //  }
            return returnData(1, '', [
                'cate_list'         => $cate_list,
                'comment_data'      => $comment_data,
                'banner'            => $banner,
                'home_cate'         => $home_cate,
                'hot_x'             => $SpConfig['hot_x'],
                'hot_y'             => $SpConfig['hot_y'],  
                'hot_zone_type'     => $SpConfig['hot_zone_type'],
                'home_cate_type'    => $SpConfig['home_cate_type']
            ]);
        }
    }

    public function get_data_list()
    {
        if ($this->request->isPost()) {
            $post     = SafeFilterItem(input('post.'));
            $cate_id   = isset($post['cate_id']) && $post['cate_id'] > 0 ? intval($post['cate_id']) : 0;
            $cate_type = isset($post['cate_type']) && $post['cate_type'] > 0 ? intval($post['cate_type']) : 0;
            switch ($cate_type) {
                case 1:
                    $list = SpProjectModel::where('cid', $cate_id)->where('p_status', 1)->order('sort asc,id desc')->field('id,p_title,p_model,p_ad_num,p_img,p_points,p_price,p_desc,p_extra,cid,sort,p_keyword,p_cs_num')->select();
                    if (!empty($list)) {
                        foreach ($list as $k => &$v) {
                            $v['p_img'] = setUpImg($v['p_img']);
                        }
                    }
                    break;
                case 2:
                    $list = SpNewsModel::where('cid', $cate_id)->where('status', 1)->order('sort asc,id desc')->field('id,title,desc,cover,cid,keyword,click_count,author,model,url')->select();
                    if (!empty($list)) {
                        foreach ($list as $k => &$v) {
                            $v['cover'] = setUpImg($v['cover']);
                            $v['p_model'] = 'news';
                        }
                    }
                    break;
                case 3:
                    $list = $this->get_day_calendar();
                    break;
                case 4:
                    $list = SpExpertModel::where('cid', $cate_id)->where('is_del', 0)->where('status', 0)->order('sort asc,id desc')->field('id,name,desc,cover,images,price,vip_discount,label')->select();
                    if (!empty($list)) {
                        foreach ($list as $k => &$v) {
                            $v['cover'] = setUpImg($v['cover']);
                            $v['images'] = setUpImg($v['images']);
                            $v['p_model'] = 'expert';
                        }
                    }
                    break;
            }
            return returnData(1, '获取成功', $list);
        }
    }

    public function get_day_calendar()
    {
        $date = date('Ymd');
        $day_info = Db::name('sp_day')->where('the_date', $date)->find();
        if (!empty($day_info)) {
            $day_info['api_data_1'] = json_decode($day_info['api_data_1'], true);
            $day_info['api_data_2'] = json_decode($day_info['api_data_2'], true);
            return $day_info;
        } else {
            $add_data = [
                'the_date'      => $date,
                'format_date'   => date('Y-m-d'),
                'create_time'   => time(),
            ];
            $SpConfig         =  SpConfigModel::where('id', 1)->find();
            $calendar_api_1   = $SpConfig['calendar_api_1'];
            $calendar_json    = file_get_contents($calendar_api_1);
            $calendar_data_1  = json_decode($calendar_json, true);
            $my_data = [];
            if (!empty($calendar_data_1) && isset($calendar_data_1['id']) && $calendar_data_1['id'] == $date) {
                $add_data['api_data_1'] = json_encode($calendar_data_1, JSON_UNESCAPED_UNICODE);
            }
            $calendar_api_2  = $SpConfig['calendar_api_2'];
            $calendar_api_2  = str_replace('{$date}', $date, $calendar_api_2);
            $calendar_json   = file_get_contents($calendar_api_2);
            $calendar_data_2   = json_decode($calendar_json, true);
            if (!empty($calendar_data_2) && isset($calendar_data_2['code']) && $calendar_data_2['code'] == 1) {
                $add_data['api_data_2'] = json_encode($calendar_data_2['data'], JSON_UNESCAPED_UNICODE);
            }
            $id = Db::name('sp_day')->insertGetId($add_data);
            if ($id) {
                $my_data = Db::name('sp_day')->where('id', $id)->find();
                $my_data['api_data_1'] = json_decode($my_data['api_data_1'], true);
                $my_data['api_data_2'] = json_decode($my_data['api_data_2'], true);
            }
            return $my_data;
        }
    }

    public function get_details_info()
    {
        if ($this->request->isPost()) {
            $post     = SafeFilterItem(input('post.'));
            $link_id  = isset($post['link_id']) && $post['link_id'] > 0 ? intval($post['link_id']) : 0;
            $type     = isset($post['cate_type']) && $post['cate_type'] > 0 ? intval($post['cate_type']) : 0;
            $user_id  = isset($post['user_id']) && $post['user_id'] > 0 ? intval($post['user_id']) : 0;
            if (!$link_id || !$type) {
                return returnData(0, '参数错误');
            }
            $find_data = [];
            switch ($type) {
                case 1:
                    $find_data = SpProjectModel::where('id', $link_id)->where('p_status', 1)->field('id,cid,p_title,p_model,p_ad_num,p_img,p_points,p_price,p_desc,p_content,p_extra,cid,sort,p_keyword,p_cs_num,rec_pro_ids,rec_expert_ids')->find();
                    if (!empty($find_data)) {
                        $find_data['p_img']     = setUpImg($find_data['p_img']);
                        $p_content              = json_decode($find_data['p_content'], true);
                        if (!empty($p_content)) {
                            unset($p_content['id']);
                            foreach ($p_content as $k => &$v) {
                                if (mb_strpos($k, 'img') !== false) {
                                    $p_content[$k] = setUpImg($v);
                                }
                                if (mb_strpos($k, 'content') !== false) {
                                    $p_content[$k] = Img_url($v);
                                }
                                if ($k == 'comment_total' && $v) {
                                    $cn_punctuations = ['，', '。', '：', '”', '“', '！'];
                                    $en_punctuations = [',', '.', ';', '"', '"', '!'];
                                    $v               = str_replace($en_punctuations, $cn_punctuations, $v);
                                    $p_content[$k]   = parse_attr($v);
                                }
                                if ($k == 'fields' && $v) {
                                    $cn_punctuations = ['，', '。', '：', '”', '“', '！'];
                                    $en_punctuations = [',', '.', ';', '"', '"', '!'];
                                    $v               = str_replace($en_punctuations, $cn_punctuations, $v);
                                    $p_content[$k]   = array_flip(parse_attr($v));
                                }
                                if ($k == 'label_total' && $v) {
                                    $p_content[$k] = explode(',', $v);
                                }
                                if (!$p_content[$k]) {
                                    $p_content[$k] = false;
                                }
                            }
                        }
                        $find_data['p_content'] = $p_content;
                        $find_data['rec_pro_data'] = [];
                        if ($find_data['rec_pro_ids']) {
                            $rec_pro_ids = array_unique(explode(',', $find_data['rec_pro_ids']));
                            $cate_list = SpProjectModel::where('id', 'in', $rec_pro_ids)->where('p_status', 1)->order('sort asc')->field('id,p_title,p_model,p_ad_num,p_img,cid,p_keyword')->select();
                            if (!empty($cate_list)) {
                                foreach ($cate_list as $k => &$v) {
                                    $v['p_img'] = setUpImg($v['p_img']);
                                }
                            }
                            $find_data['rec_pro_data'] = $cate_list;
                        } else {
                            $find_data['rec_pro_ids'] = false;
                        }
                        $find_data['rec_expert_data'] = [];
                        if ($find_data['rec_expert_ids']) {
                            $rec_expert_ids = array_unique(explode(',', $find_data['rec_expert_ids']));
                            $expert_list = SpExpertModel::where('id', 'in', $rec_expert_ids)->where('is_del', 0)->where('status', 0)->order('sort asc,id desc')->field('id,name,desc,cover,images,price,vip_discount,label')->select();
                            if (!empty($expert_list)) {
                                foreach ($expert_list as $k => &$v) {
                                    $v['cover'] = setUpImg($v['cover']);
                                    $v['images'] = setUpImg($v['images']);
                                }
                            }
                            $find_data['rec_expert_data'] = $expert_list;
                        } else {
                            $find_data['rec_expert_ids'] = false;
                        }
                    }
                    break;
                case 2:
                    $find_data = SpNewsModel::where('id', $link_id)->where('status', 1)->field('id,cid,title,desc,cover,keyword,content,click_count,author,model,url,rec_pro_ids,rec_expert_ids')->find();
                    if (!empty($find_data)) {
                        $find_data['cover']  = setUpImg($find_data['cover']);
                        foreach ($find_data as $k => &$v) {
                            if (mb_strpos($k, 'content') !== false) {
                                $find_data[$k] = Img_url($v);
                            }
                        }
                        $find_data['rec_pro_data'] = [];
                        if ($find_data['rec_pro_ids']) {
                            $rec_pro_ids = array_unique(explode(',', $find_data['rec_pro_ids']));
                            $cate_list = SpProjectModel::where('id', 'in', $rec_pro_ids)->where('p_status', 1)->order('sort asc')->field('id,p_title,p_model,p_img,cid,p_keyword')->select();
                            if (!empty($cate_list)) {
                                foreach ($cate_list as $k => &$v) {
                                    $v['p_img'] = setUpImg($v['p_img']);
                                }
                            }
                            $find_data['rec_pro_data'] = $cate_list;
                        } else {
                            $find_data['rec_pro_ids'] = false;
                        }
                        $find_data['rec_expert_data'] = [];
                        if ($find_data['rec_expert_ids']) {
                            $rec_expert_ids = array_unique(explode(',', $find_data['rec_expert_ids']));
                            $expert_list = SpExpertModel::where('id', 'in', $rec_expert_ids)->where('is_del', 0)->where('status', 0)->order('sort asc,id desc')->field('id,name,desc,cover,images,price,vip_discount,label')->select();
                            if (!empty($expert_list)) {
                                foreach ($expert_list as $k => &$v) {
                                    $v['cover'] = setUpImg($v['cover']);
                                    $v['images'] = setUpImg($v['images']);
                                }
                            }
                            $find_data['rec_expert_data'] = $expert_list;
                        } else {
                            $find_data['rec_expert_ids'] = false;
                        }
                        $find_data['like_count'] = Db::name('sp_operation')->where('link_id', $find_data['id'])->where('ls_type', 1)->where('is_del', 0)->count();
                        SpNewsModel::where('id', $find_data['id'])->where('status', 1)->update(['click_count' => $find_data['click_count'] + 1]);
                    }
                    break;
                case 4:
                    $find_data = SpExpertModel::where('id', $link_id)->where('is_del', 0)->where('status', 0)->field('id,cid,name,desc,cover,images,price,class_ids,vip_discount,content,label')->find();
                    if (!empty($find_data)) {
                        $find_data['cover']         = setUpImg($find_data['cover']);
                        $find_data['images']        = setUpImg($find_data['images']);
                        foreach ($find_data as $k => &$v) {
                            if (mb_strpos($k, 'content') !== false) {
                                $find_data[$k] = Img_url($v);
                            }
                        }
                        $find_data['class_data'] = [];
                        if ($find_data['class_ids']) {
                            $find_data['class_data'] = Db::name('sm_class')->where('id', 'in', $find_data['class_ids'])->where('parentid', '<>', 0)->where('url', '<>', '')->where('is_del', 0)->column('name');
                        } else {
                            $find_data['class_ids'] = false;
                        }
                    }
                    break;
            }
            if (!empty($find_data)) {
                $find_data['is_like'] = 0;
                $find_data['is_collect'] = 0;
                $find_data['like_count']        = Db::name('sp_operation')->where('link_id', $find_data['id'])->where('ls_type', 1)->where('is_del', 0)->count();
                $find_data['collect_count']     = Db::name('sp_operation')->where('link_id', $find_data['id'])->where('ls_type', 2)->where('is_del', 0)->count();
                if ($user_id) {
                    $is_like       = Db::name('sp_operation')->where('user_id', $user_id)->where('link_id', $find_data['id'])->where('ls_type', 1)->where('is_del', 0)->find();
                    if (!empty($is_like)) {
                        $find_data['is_like'] = 1;
                    }
                    $is_collect    = Db::name('sp_operation')->where('user_id', $user_id)->where('link_id', $find_data['id'])->where('ls_type', 2)->where('is_del', 0)->find();
                    if (!empty($is_collect)) {
                        $find_data['is_collect'] = 1;
                    }
                }
            }
            return returnData(1, '获取成功', $find_data);
        }
    }

    public function get_expert_list(){
         if ($this->request->isPost()) {
                $post     = SafeFilterItem(input('post.'));
                $page     = isset($post['page']) && $post['page'] ? $post['page'] : 1;
                $limit    = isset($post['limit']) && $post['limit'] ? $post['limit'] : 10;
                $expert_image         =  SpConfigModel::where('id', 1)->value('expert_image');
                $expert_image         = setUpImg($expert_image);
                $total = SpExpertModel::where('is_del', 0)->where('status', 0)->order('sort asc,id desc')->count();
                $list = SpExpertModel::where('is_del', 0)->where('status', 0)->order('sort asc,id desc')->page($page, $limit)->field('id,name,desc,cover,images,price,vip_discount,label')->select();
                if (!empty($list)) {
                    foreach ($list as $k => &$v) {
                        $v['cover']   = setUpImg($v['cover']);
                        $v['images']  = setUpImg($v['images']);
                        $v['p_model'] = 'expert';
                    }
                }
                return returnData(1, '获取成功', ['expert_list'=>$list,'total'=>$total,'page'=>$page,'expert_image'=>$expert_image]);
         }
    }

    /**
     * 获取用户签到日历数据
     * @return array 包含日历数据的数组
     */
    public function sign_in_data()
    {
        try {
            // 1. 参数获取与验证
            $post = SafeFilterItem(input('post.'));
            // 加强参数验证，使用filter_var确保类型安全
            $user_id = 0;
            if (isset($post['user_id'])) {
                $user_id = filter_var($post['user_id'], FILTER_VALIDATE_INT, [
                    'options' => ['min_range' => 1, 'default' => 0]
                ]);
            }

            // 年份和月份的严格验证
            $year = filter_var($post['year'] ?? date('Y'), FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 2000, 'max_range' => 2100, 'default' => date('Y')]
            ]);
            $month = filter_var($post['month'] ?? date('m'), FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1, 'max_range' => 12, 'default' => date('m')]
            ]);

            // 2. 获取当月第一天和最后一天的时间戳
            $firstDay = strtotime("{$year}-{$month}-01");
            //$lastDay  = strtotime("{$year}-{$month}-" . date('t', $firstDay));
            $lastDay  = strtotime(date('Y-m-d'));
            $first_weekdayIndex = $end_weekdayIndex = 0;

            // 3. 获取用户签到记录并转换为关联数组以提高查找效率
            $sign_dates = [];
            $repair_sign_count = 0;
            if ($user_id) {
                // 使用预处理语句防止SQL注入
                $sign_dates = Db::name('sp_sign_in')
                    ->where('user_id', '=', $user_id)
                    ->where('sp_date_flag', '=', "{$year}-{$month}")
                    ->order('sp_date asc')
                    ->column('sp_date,credit,currency_name');
                $repair_sign_count = Db::name('sp_sign_in')
                    ->where('user_id', $user_id)
                    ->where('sign_type',2)
                    ->where('sp_date_flag', '=', "{$year}-{$month}")
                    ->count();
                // 转换为关联数组，使用日期作为键，大大提高查找效率
                //$sign_dates = array_flip($my_sign_data);
            }

            // 4. 系统参数配置
            $the_code = config('sign_num'); //签到奖励金额
            $sign_lj_bq_num = config('sign_lj_bq_num');
            $repair_sign_in = -1;//未签到,不能补签
                if($sign_lj_bq_num>0 && $repair_sign_count<$sign_lj_bq_num){
                    $repair_sign_in = 0;   //未签到,可以签到
                }
            // 5. 生成当前月的日期数据
            $dates = [];
            for ($time = $firstDay; $time <= $lastDay; $time = strtotime('+1 day', $time)) {
                $day = date('j', $time);
                $weekdayIndex = date('w', $time);
                if ($weekdayIndex == 0) {
                    $weekdayIndex = '7'; //将周日(0)转换为7，符合周一为1的日历习惯
                }
                if ($first_weekdayIndex == 0) {
                    $first_weekdayIndex = $weekdayIndex; // 记录月初是星期几
                }
                $time_date   = date('Y-m-d', $time);
                $reward_code = isset($sign_dates[$time_date]) ? $sign_dates[$time_date]['credit']: $the_code;
                if($time<time()){
                    $reward_code = isset($sign_dates[$time_date]) ? $sign_dates[$time_date]['credit']: '';
                }
                $sign_in = $repair_sign_in;
                if(isset($sign_dates[$time_date])){
                    $sign_in = 2; //已签到
                }else{
                    if(date('Y-m-d') == $time_date){
                        $sign_in = 1; //今天未签到
                    }
                }
                $dates[] = [
                    'year'         => (int)$year,
                    'month'        => (int)$month,
                    'day'          => (int)$day,
                    'date'         => $time_date,
                    'weekdayIndex' => (int)$weekdayIndex,
                    'sign_in'      => $sign_in,  //使用isset代替in_array，提高效率
                    'reward_code'  => $reward_code,
                ];
                //$end_weekdayIndex = $weekdayIndex; // 记录月末是星期几
            }

            // 6. 生成上个月的补充日期数据
            // $first_arr = [];
            // if ($first_weekdayIndex > 1) {
            //     $LastDay = date('t', strtotime('-1 month', $firstDay));
            //     $lastMonth = date('m', strtotime('-1 month', $firstDay));
            //     $lastYear = $year;
            //     if ($lastMonth > $month) {
            //         $lastYear = date('Y', strtotime('-1 year', $firstDay));
            //     }

            //     for ($i = $first_weekdayIndex - 1; $i > 0; $i--) {
            //         // 修复：使用正确的上个月日期进行签到状态判断
            //         $lastMonthDate = sprintf('%04d-%02d-%02d', $lastYear, $lastMonth, $LastDay);
            //         $first_arr[] = [
            //             'year'         => (int)$lastYear,
            //             'month'        => (int)$lastMonth,
            //             'day'          => (int)$LastDay,
            //             'date'         => "{$lastYear}-{$lastMonth}-{$LastDay}",
            //             'weekdayIndex' => (int)$i,
            //             'sign_in'      => isset($sign_dates[$lastMonthDate]), // 使用正确的日期和isset方法
            //             'reward_code'  => isset($sign_dates[$lastMonthDate]) ? $sign_dates[$lastMonthDate]['credit']: '',
            //         ];
            //         $LastDay--;
            //     }
            // }

            // 7. 生成下个月的补充日期数据
            // $next_arr = [];
            // if ($end_weekdayIndex > 1) {
            //     $end_date = 1;
            //     $nextMonth = date('m', strtotime('+1 month', $firstDay));
            //     $nextYear = $year;
            //     if ($nextMonth < $month) {
            //         $nextYear = date('Y', strtotime('+1 year', $firstDay));
            //     }

            //     for ($i = $end_weekdayIndex + 1; $i <= 7; $i++) {
            //         // 修复：使用正确的下个月日期进行签到状态判断
            //         $nextMonthDate = sprintf('%04d-%02d-%02d', $nextYear, $nextMonth, $end_date);
            //         $next_arr[] = [
            //             'year'         => (int)$nextYear,
            //             'month'        => (int)$nextMonth,
            //             'day'          => (int)$end_date,
            //             'date'         => "{$nextYear}-{$nextMonth}-{$end_date}",
            //             'weekdayIndex' => (int)$i,
            //             'sign_in'      => isset($sign_dates[$nextMonthDate]), // 使用正确的日期和isset方法
            //             'reward_code'  => isset($sign_dates[$nextMonthDate]) ? $sign_dates[$nextMonthDate]['credit']: $the_code,
            //         ];
            //         $end_date++;
            //     }
            // }

            // 8. 组装并返回结果
            $result = [
                //'last_month'     => array_reverse($first_arr),      // 日历上个月的数据
                'repair_count'   =>$repair_sign_count,
                'the_month'      => $dates,                         // 当前月数据         
                //'next_month'     => $next_arr,                      // 日历下个月的数据
                'the_day'        => date('Y-m-d'),                  // 今天
                'the_month_info' => date('Y-m'),                    // 当前月    
            ];

            return returnData(1, '获取成功', $result);
        } catch (Exception $e) {
            // 添加错误处理，记录日志
            Log::error('生成签到日历失败: ' . $e->getMessage());
            return returnData(0, '获取签到日历失败，请稍后重试');
        }
    }

    public function stars_list_info(){
       $stars_data =  Db::name('star_sign_base')->where('id','>',0)->order('sort asc')->field('id,xz,se_date,icon,text_icon')->select();
       if(!empty($stars_data)){
          foreach($stars_data as $key => $val){
              $stars_data[$key]['icon'] = setUpImg($val['icon']);
              $stars_data[$key]['text_icon'] = setUpImg($val['text_icon']);
          }
       }
       return returnData(1, '获取成功', $stars_data);
    }

    public function stars_find_info(){
        if($this->request->isPost()){
            $post = SafeFilterItem(input('post.'));
            $star_id   = isset($post['star_id']) && $post['star_id'] > 0 ? intval($post['star_id']) : 0;
            if($star_id){
                $stars_data =  Db::name('star_sign_base')->where('id', $star_id)->field('id,xz,se_date,icon,text_icon')->find();
                if(!empty($stars_data)){
                    $stars_data['icon'] = setUpImg($stars_data['icon']);
                    $stars_data['text_icon'] = setUpImg($stars_data['text_icon']);
                }
                return returnData(1, '获取成功', $stars_data);
            }
        }
    }

    public function system_config(){
        $SpConfig         =  SpConfigModel::where('id', 1)->field('system_year,hot_zone_type,home_cate_type,cs_num,ns_num,zj_num')->find();
        return returnData(1, '获取成功', $SpConfig);
    }

    // public function sign_in_datas(){
    //     $post     = SafeFilterItem(input('post.'));
    //     $user_id  = isset($post['user_id']) && $post['user_id'] > 0 ? intval($post['user_id']) : 0;
    //     $year     = isset($post['year']) && $post['year'] > 0 ? intval($post['year']) : date('Y');
    //     $month    = isset($post['month']) && $post['month'] > 0 ? intval($post['month']) : date('m');
    //     // 获取当月第一天和最后一天的时间戳
    //     $firstDay            = strtotime("{$year}-{$month}-01");
    //     $lastDay             = strtotime("{$year}-{$month}-". date('t', $firstDay));
    //     $first_weekdayIndex  = $end_weekdayIndex = 0;
    //     $my_sign_data = [];
    //     if($user_id){
    //         $MemberModel = new MemberModel;
    //         $userInfo            = $MemberModel->get($user_id);
    //         $my_sign_data        = Db::name('sp_sign_in')->where('user_id', $user_id)->where('sp_date_flag',"{$year}-{$month}")->column('sp_date');
    //     }

    //     /**系统参数**/
    //     $the_code            = config('sign_num');              //签到奖励金额
    //     $the_currency        = config('sign_currency_name');    //签到奖励货币

    //     $currency_data       = config('currency');              //货币参数
    //     $currency_data       = array_flip($currency_data);
    //     $the_currency_name   = $currency_data[$the_currency];
    //     /**系统参数**/
    //     $dates               = [];
    //     for ($time = $firstDay; $time <= $lastDay; $time = strtotime('+1 day', $time)) {
    //         $day          = date('j', $time);
    //         $weekdayIndex = date('w', $time);
    //         if ($weekdayIndex == 0) {
    //             $weekdayIndex = '7';
    //         }
    //         if ($first_weekdayIndex == 0) {
    //             $first_weekdayIndex = $weekdayIndex;
    //         }
    //         $time_date = date('Y-m-d', $time);
    //         $dates[] = [
    //             'year'          => (int)$year,
    //             'month'         => (int)$month,
    //             'day'           => (int)$day,
    //             'weekdayIndex'  => (int)$weekdayIndex,
    //             'sign_in'       => in_array($time_date, $my_sign_data),
    //             'reward_code'   => $the_code,
    //         ];
    //         $end_weekdayIndex = $weekdayIndex;
    //     }

    //     // 获得第一个元素的 weekdayIndex、
    //     $first_arr = [];
    //     if ($first_weekdayIndex > 1) {
    //         //$lastMonthLastDay = date('t', strtotime('last month'));
    //         $LastDay   = date('t', strtotime('-1 month', $firstDay));
    //         $lastMonth = date('m', strtotime('-1 month', $firstDay));
    //         $lastYear  = $year;
    //         if($lastMonth>$month){
    //             $lastYear = date('Y', strtotime('-1 year', $firstDay));
    //         }
    //         for ($i = $first_weekdayIndex-1; $i > 0; $i -- ) {
    //             $first_arr [] = [
    //                 'year'         => (int)$lastYear,
    //                 'month'         => (int)$lastMonth,
    //                 'day'           => (int)$LastDay,
    //                 'weekdayIndex'  => (int)$i,
    //                 'sign_in'       => in_array($time_date, $my_sign_data),
    //                 'reward_code'   => $the_code,
    //             ];
    //             $LastDay -- ;
    //         }
    //     }
    //     $next_arr = [];
    //     if ($end_weekdayIndex > 1) {
    //         $end_date = 1;
    //         $nextMonth = date('m', strtotime('+1 month', $firstDay));
    //         $nextYear  = $year;
    //         if($nextMonth<$month){
    //             $nextYear = date('Y', strtotime('+1 year', $firstDay));
    //         }
    //         for ( $i = $end_weekdayIndex+1; $i <= 7 ; $i++ ) {
    //             $next_arr [] = [
    //                 'year'          => (int)$nextYear,
    //                 'month'         => (int)$nextMonth,
    //                 'day'           => (int)$end_date,
    //                 'weekdayIndex'  => (int)$i,
    //                 'sign_in'       => in_array($time_date, $my_sign_data),
    //                 'reward_code'   => $the_code,
    //             ];
    //             $end_date ++ ;
    //         }
    //     }
    //     $dates = [
    //         'last_month'     => array_reverse($first_arr),      //日历上个月的数据
    //         'the_month'      => $dates,                         //当前月数据         
    //         'next_month'     => $next_arr,                      //日历下个月的数据
    //         'the_day'        =>date('Y-m-d'),                   //今天
    //         'the_month_info' =>date('Y-m'),                     //当前月    
    //         'the_code'       =>$the_code,                       //今天签到可获得的积分
    //         'next_code'      =>$the_code                        //下次签到可获得的积分
    //     ];
    //     return returnData(1, '获取成功', $dates);

    // }
}
