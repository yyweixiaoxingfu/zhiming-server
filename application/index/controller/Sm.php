<?php

namespace app\index\controller;

use app\index\model\Sm as SmModel;
use app\index\controller\Api;
use think\Db;
use app\common\library\phpu\Calendar;
use app\common\library\phpu\ChineseCalendar;
use app\common\library\phpu\SolarTerm;

/**
 * 算命前台首页控制器
 * @package app\index\controller
 */
class Sm extends Api
{
    //1.今年运势
    public function year_luck($format_data)
    {
        $format_data = json_decode($format_data, true);
        if (!$format_data) {
            return 0;
        }
        $from_data            = $format_data['cs_date'];
        $from_data['xing']    = $format_data['xing'];;
        $from_data['name']    = $format_data['name'];
        $from_data['xm_name'] = $format_data['xing'] . $format_data['name'];
        $from_data['sex']     = $format_data['sex'];
        $sm_job               = new SmModel();
        $bazizh               = $sm_job->bazizh($from_data);  //已测试完成
        if (empty($bazizh)) {
            return 0;
        }
        $bazipp               = $sm_job->bazipp($from_data);
        if (empty($bazipp)) {
            return 0;
        }
        $bazism               = $sm_job->bazism($from_data);
        if (empty($bazism)) {
            return 0;
        }
        $result               = ['bazi_zh' => $bazizh, 'bazi_pp' => $bazipp, 'bazi_sm' => $bazism];
        return $result;
    }

    //2.八字合婚
    public function bazihh($format_data)
    {
        $format_data = json_decode($format_data, true);
        if (!$format_data) {
            return 0;
        }
        $sm_job                     = new SmModel();
        $from_data['m_username']    = $format_data['male_xing'] . $format_data['male_name'];
        $from_data['m_year']        = $format_data['male_cs_date']['year'];
        $from_data['m_month']       = $format_data['male_cs_date']['month'];
        $from_data['m_day']         = $format_data['male_cs_date']['day'];
        $from_data['m_hour']        = $format_data['male_cs_date']['hour'];
        $from_data['g_username']    = $format_data['female_xing'] . $format_data['female_name'];
        $from_data['g_year']        = $format_data['female_cs_date']['year'];
        $from_data['g_month']       = $format_data['female_cs_date']['month'];
        $from_data['g_day']         = $format_data['female_cs_date']['day'];
        $from_data['g_hour']        = $format_data['female_cs_date']['hour'];
        $marry_arr                  = $sm_job->baziMarriage($from_data);
        $m_info_boy                 = birthday($from_data['m_year'] . '-' . $from_data['m_month'] . '-' . $from_data['m_day']);
        $g_info_boy                 = birthday($from_data['g_year'] . '-' . $from_data['g_month'] . '-' . $from_data['g_hour']);
        $marry_arr['m_age']              = $m_info_boy['age'];
        $marry_arr['m_constellation']    = $m_info_boy['constellation'];
        $marry_arr['g_age']              = $g_info_boy['age'];
        $marry_arr['g_constellation']    = $g_info_boy['constellation'];
        $tid    = 385; //星座&星座
        $xz     = [];
        if (!empty($m_info_boy['constellation']) && !empty($g_info_boy['constellation'])) {
            $xz = Db::name('peidui_data')->where(array(['tid', '=', $tid], ['t1', '=', $m_info_boy['constellation']], ['t2', '=', $g_info_boy['constellation']]))->find();
        }
        $marry_arr['constellation_data'] = $xz;
        return $marry_arr;
    }

    //3.今年运势
    public function jinnian($format_data)
    {
        if (!$format_data) {
            return 0;
        }
        //$sm_job               = new SmModel();
        $json_data = json_decode($format_data, true);
        if (!$json_data) {
            return 0;
        }
        $result = $this->year_luck($format_data);
        if (empty($result)) {
            return 0;
        }
        return $result;
    }

    //4.姓名详批
    public function xmfx($format_data)
    {
        $format_data = json_decode($format_data, true);
        if (!$format_data) {
            return 0;
        }
        $sm_job               = new SmModel();
        $from_data            = $format_data['cs_date'];
        $from_data['xing']    = $format_data['xing'];;
        $from_data['name']    = $format_data['name'];
        $from_data['xm_name'] = $format_data['xing'] . $format_data['name'];
        $from_data['sex']     = $format_data['sex'];
        $result               = $sm_job->xmfx($from_data);
        if (empty($result)) {
            return 0;
        }
        return $result;
    }

    //5.八字详批
    public function bazi($format_data)
    {
        $result              = $this->year_luck($format_data);
        if (empty($result)) {
            return 0;
        }
        return $result;
    }

    //6.紫薇斗数
    public function ziweids($format_data)
    {
        $format_data = json_decode($format_data, true);
        if (!$format_data) {
            return 0;
        }
        $from_data             = $format_data['cs_date'];
        $from_data['username'] = $format_data['xing'] . $format_data['name'];
        $from_data['sex']      = $format_data['sex'];
        $sm_job                = new SmModel();
        $result                = $sm_job->get_ziwei($from_data);
        return $result;
    }

    //7.姻缘测试
    public function yinyuancs($format_data)
    {
        $result              = $this->year_luck($format_data);
        if (empty($result)) {
            return 0;
        }
        return $result;
    }

    //8.名字配对
    //当前页面 爱情建议 爱情危机 爱情宿命 为页面写死的数据 头部图片 开发阶段需要写活
    public function xmpd($format_data)
    {
        $format_data = json_decode($format_data, true);
        if (!$format_data) {
            return 0;
        }
        $nan_xing   = $format_data['male_xing'];
        $nan_name   = $format_data['male_name'];
        $nv_xing    = $format_data['female_xing'];
        $nv_name    = $format_data['female_name'];   //测试数据 
        $sm_job     = new SmModel();
        $result     = $sm_job->matchingName($nan_xing, $nan_name, $nv_xing, $nv_name);  //已测试完成
        if (empty($result)) {
            return 0;
        }
        return $result;
    }

    //9.号码测试吉凶 解析 #婚姻 财运 事业 健康数据需要写活
    public function hmjx($format_data)
    {
        $format_data = json_decode($format_data, true);
        if (!$format_data) {
            return 0;
        }
        $word       = $format_data['number'];
        $sztType    = $format_data['number_type']; //1手机号 2车牌
        if ($sztType == 1) {
            $tid        = 389;
        } elseif ($sztType == 2) {
            $tid        = 393;
        }
        $seo        = seo_info($tid);
        $word_1     = '';
        if ($sztType == 2) {
            $format_word   = findNum($word);
            $result['format_word'] = $format_word;
            $word_z        = hmjx_word($format_word);
            $tel_jx = Db::name('Sm_tel')->where('num', '=', $word_z)->find();
            $result['tel_jx']        = [
                'id'        => $tel_jx['id'],
                'title'     => $tel_jx['title'],
                'content'   => $tel_jx['content'],
                'jx'        => $tel_jx['jx'],
            ];
            $result['hy'] = $tel_jx['hy_content'];
            $result['cy'] = $tel_jx['cy_content'];
            $result['sy'] = $tel_jx['sy_content'];
            $result['jk'] = $tel_jx['jk_content'];
        } else {
            $word_tel    = str_split($word);
            $word_z      = hmjx_word($word_tel);
            $word_z1 = $word_tel[3] . $word_tel[4];
            $word_z2 = $word_tel[5] . $word_tel[6];
            $word_z3 = $word_tel[7] . $word_tel[8];
            $word_z4 = $word_tel[9] . $word_tel[10];
            $word_z5 = $word_tel[3] . $word_tel[4] . $word_tel[5] . $word_tel[6];
            $word_z6 = $word_tel[7] . $word_tel[8] . $word_tel[9] . $word_tel[10];
            $result = array(
                'word_z1' => $word_z1,
                'word_z2' => $word_z2,
                'word_z3' => $word_z3,
                'word_z4' => $word_z4,
                'word_z5' => $word_z5,
                'word_z6' => $word_z6,
            );
            $word_1  = hmjx_word($word_z1); // 4 5位
            $word_2  = hmjx_word($word_z2); // 6 7位
            $word_3  = hmjx_word($word_z3); // 8 9位
            $word_4  = hmjx_word($word_z4); // 10 11位
            $word_5  = hmjx_word($word_z5);
            $word_6  = hmjx_word($word_z6);
            $result['hy'] = Db::name('Sm_tel')->where('num', $word_1)->value('hy_content');
            $result['cy'] = Db::name('Sm_tel')->where('num', $word_2)->value('cy_content');
            $result['sy'] = Db::name('Sm_tel')->where('num', $word_3)->value('sy_content');
            $result['jk'] = Db::name('Sm_tel')->where('num', $word_4)->value('jk_content');
            $tel_jx = Db::name('Sm_tel')->where('num', 'in', [$word_1, $word_2, $word_3, $word_4, $word_5, $word_6])->field('id,title,jx,content')->select();
            $result['tel_jx']   = $tel_jx;
            $hexagram           = mapFourDigitToHexagram($word_z6);
            $result['gua_ci']   = Db::name('sm_gua_ci')->where('id',$hexagram)->find();
        }
        if ($sztType == 2) {
            $seo['title']       = '车牌号码：' . $word . '测吉凶，车牌号码' . $word . '好不好';
            $seo['description'] = '车牌号码：' . $word . '测吉凶，车牌号码' . $word . '好不好' . $seo['description'];
        } else {
            $seo['title']       = '手机号码：' . $word . '测吉凶，' . $word . '手机号码好不好？';
            $seo['description'] = '手机号码：' . $word . '测吉凶，' . $word . '手机号码好不好？' . $seo['description'];
        }
        if (empty($result)) {
            return 0;
        }
        $result['seo'] = $seo;
        return $result;
    }




    //10.宝宝起名  //姓名列表需处理分页问题  // 0女 1男 2未知
    public function ffqm($format_data)
    {
        $format_data = json_decode($format_data, true);
        if (!$format_data) {
            return 0;
        }
        $from_data               = $format_data['cs_date'];
        $from_data['xing']       = $format_data['xing'];
        $from_data['hs']         = $format_data['name_type'] + 1;
        $from_data['name']       = '';
        $from_data['sex']        = $sex =  $format_data['sex'] == 0 ? 1 : 0;
        $sm_job                  = new SmModel();
        $baziwh                  = $sm_job->get_bzwh($from_data['xing'], $from_data['name'], $from_data['sex'], $from_data['year'], $from_data['month'], $from_data['day'], $from_data['hour'], $from_data['min'], $from_data['cY'], $from_data['cM'], $from_data['cD'], $from_data['cH'], $from_data['term1'], $from_data['term2'], $from_data['start_term'], $from_data['end_term'], $from_data['start_term1'], $from_data['end_term1'], $from_data['lDate']);
        $bazipp                  = $sm_job->bazipp($from_data);
        $bazism                  = $sm_job->bazism($from_data);
        $xing_data               = Db::name('Give_bjxing')->where('xing', '=', $from_data['xing'])->find();
        $xid                     = isset($xing_data['id']) && $xing_data['id'] > 0 ? $xing_data['id'] : 0;
        $no_xing                 = 0;
        $wxid                = ['xid', '=', $xid];
        if ($xid <= 0) {
            $xid        = 1;
            $no_xing    = 1;
            $wxid       = ['1', '=', 1];
        }
        $wsex = ['sex', '=', $sex];
        if ($sex == 2) {
            $wsex  = ['sex', '>', -1];
        }
        $hsx = ['geshu', '=', $from_data['hs']];
        if ($from_data['hs'] == 0) {
            $hsx = ['geshu', '<>', 0];
        }
        $list  = Db::name('Give_bjxingming')->where([$wxid, $wsex, $hsx])->limit(100)->order('geshu ASC,id DESC')->select();
        if ($no_xing) {
            foreach ($list as $key => $val) {
                $arr_tj_list[]['name'] = str_ireplace("赵", $from_data['xing'], $val['name']);
            }
            $list = $arr_tj_list;
        }
        return ['name_list' => $list, 'baziwh' => $baziwh, 'bazipp' => $bazipp, 'bazism' => $bazism];
    }

    //11.生肖运势
    public function shengxiao($format_data)
    {
        if (!$format_data) {
            return 0;
        }
        //$sm_job               = new SmModel();
        $json_data = json_decode($format_data, true);
        if (!$json_data) {
            return 0;
        }
        $result = $this->year_luck($format_data);
        if (empty($result)) {
            return 0;
        }
        return $result;
    }

    //12.生辰八字
    public function bazizh($format_data)
    {
        $format_data = json_decode($format_data, true);
        if (!$format_data) {
            return 0;
        }
        $from_data            = $format_data['cs_date'];
        $from_data['xing']    = $format_data['xing'];;
        $from_data['name']    = $format_data['name'];
        $from_data['xm_name'] = $format_data['xing'] . $format_data['name'];
        $from_data['sex']     = $format_data['sex'];
        $sm_job               = new SmModel();
        $bazizh               = $sm_job->bazizh($from_data);
        $bazipp               = $sm_job->bazipp($from_data);  //已测试完成
        $bazism               = $sm_job->bazism($from_data);
        if (empty($bazizh)) {
            return 0;
        }
        $result = ['bazizh' => $bazizh, 'bazipp' => $bazipp, 'bazism' => $bazism];
        return $result;
    }

    public function bzsm_bzpp()
    {
        if ($this->request->isPost()) {
            $post_data       = SafeFilterItem(input('post.'));
            $format_data     = isset($post_data['format_data']) && $post_data['format_data'] != '' ? json_decode($post_data['format_data'], true) : '';
            if (empty($format_data)) {
                return 0;
            }
            $from_data            = $format_data['cs_date'];
            $from_data['xing']    = $format_data['xing'];;
            $from_data['name']    = $format_data['name'];
            $from_data['xm_name'] = $format_data['xing'] . $format_data['name'];
            $from_data['sex']     = $format_data['sex'];
            $sm_job               = new SmModel();
            $bazism                 = $sm_job->bazism($from_data);
            return returnData(1, '测算成功', ['bazism' => $bazism]);
        }
    }

    //获取姓名解析
    public function ffqm_query()
    {
        if ($this->request->isPost()) {
            $post_data       = SafeFilterItem(input('post.'));
            $xing            = $post_data['xing'];
            $name            = $post_data['name'];
            $sex             = $post_data['sex'];
            $sm_job          = new SmModel();
            $nameXf          = $sm_job->nameXf($xing, $name, $sex);  //已测试完成
            return returnData(1, '测算成功', ['nameXf' => $nameXf]);
        }
    }



    //6.公司测名
    public function gsqm()
    {
        if ($this->request->isPost()) {
            $post_data  = SafeFilterItem(input('post.'));
            $corpname        = $post_data['corpname'];
            $corpname_data   = $this->gsqm_name($corpname);
            return returnData(1, '测算成功', ['gsqm' => $corpname_data]);
        }
    }


    //计算公司名字的五行
    public function gsqm_name($name)
    {
        $ming = $name;
        if (strpos($ming, '/') != false) {
            $ming = str_replace('/', '', $ming);
        }
        $a1 = substr($ming, 0, 3);
        $a2 = substr($ming, 3, 3);
        $a3 = substr($ming, 6, 3);
        $a4 = substr($ming, 9, 3);
        $a5 = substr($ming, 12, 3);
        $a6 = substr($ming, 15, 3);
        $sm_job = new SmModel();
        $allbihua = 0;
        if ($a1 != '') {
            $corpname1 = $sm_job->wordsWx($a1);
            $allbihua  = $corpname1['bihua'] + $allbihua;
            $data['corpname1'] = $corpname1;
        }
        if ($a2 != '') {
            $corpname2 = $sm_job->wordsWx($a2);
            $allbihua  = $corpname2['bihua'] + $allbihua;
            $data['corpname2'] = $corpname2;
        }
        if ($a3 != '') {
            $corpname3 = $sm_job->wordsWx($a3);
            $allbihua  = $corpname3['bihua'] + $allbihua;
            $data['corpname3'] = $corpname3;
        }
        if ($a4 != '') {
            $corpname4 = $sm_job->wordsWx($a4);
            $allbihua  = $corpname4['bihua'] + $allbihua;
            $data['corpname4'] = $corpname4;
        }
        if ($a5 != '') {
            $corpname5 = $sm_job->wordsWx($a5);
            $allbihua  = $corpname5['bihua'] + $allbihua;
            $data['corpname5'] = $corpname5;
        }
        if ($a6 != '') {
            $corpname6 = $sm_job->wordsWx($a6);
            $allbihua  = $corpname6['bihua'] + $allbihua;
            $data['corpname6'] = $corpname6;
        }
        $data['allbihua'] = $allbihua;
        if ($allbihua > 82) {
            $allbihua = $allbihua - 81;
        }
        //查询公司信息
        $company                =  Db::name('Sm_company')->where('num', '=', $allbihua)->find();
        $data['company_info']   = $company;
        return $data;
    }

    //称骨算命
    public function suanming()
    {
        if ($this->request->isPost()) {
            $post_data  = SafeFilterItem(input('post.'));
            $y              = $post_data['y'];
            $m              = $post_data['m'];
            $d              = $post_data['d'];
            $h              = $post_data['h'];
            $i              = $post_data['i'];
            $from_data      = getCDate($y, $m, $d, $h, $i);  //前期计算需要存入数据库的数据
            $lunar_month    = $from_data['lunar_month'];
            if ($from_data['lunar_month'] == '十二') {
                $lunar_month = '腊';
            }
            $sm_job         = new SmModel();
            $cgsm_data      = $sm_job->cg($y, $lunar_month, $from_data['lunar_day'], $h);
            return returnData(1, '测算成功', ['cgsm' => $cgsm_data]);
        }
    }


    //18.小六壬测算
    public function get_day_fate($format_data)
    {
        $format_data = json_decode($format_data, true);
        if (!$format_data) {
            return 0;
        }
        $filed_data         = require_once '../application/common/library/phpu/locales/zh-cn.php';
        $heavenly_stems     = $filed_data['heavenly_stems'];   //天干
        $earthly_branches   = $filed_data['earthly_branches']; //地支
        $symbolic_animals   = $filed_data['symbolic_animals']; //生肖
        $star_sign          = $filed_data['star_sign'];        //星座
        $Calendar           = new Calendar();
        $demo               = $Calendar->setConfig('default')->createCalendar(date('Y'), date('m'), date('d'));
        $SolarTerms         = new SolarTerm();
        $St                 = $SolarTerms->solarTerms(date('Y'), 'Asia/Shanghai');
        foreach ($St as $key => $value) {
            $st_data[] = $value['d']->format('Y-m-d H:i');
        }
        $demo           = $Calendar->setConfig('day')->createCalendar(date('Y'), date('m'), date('d'));
        $ChineseCalendar = new ChineseCalendar();
        $scs            = $ChineseCalendar->sexagenaryCycle(date('Y'), date('m'), date('d'), date('H'));
        $th_dz_data     = $heavenly_stems[$scs['y']['g']] . $earthly_branches[$scs['y']['z']] . '(' . $symbolic_animals[$scs['y']['z']] . ')年 ' . $heavenly_stems[$scs['m']['g']] . $earthly_branches[$scs['m']['z']] . '月 ' . $heavenly_stems[$scs['d']['g']] . $earthly_branches[$scs['d']['z']] . '日 ' . $heavenly_stems[$scs['h']['g']] . $earthly_branches[$scs['h']['z']] . '时';
        $signIndex      = $ChineseCalendar->signIndex(date('m'), date('d'));
        $star_sign_data = $star_sign[$signIndex];
        $lunar          = $demo['lunar'];
        $month          = $lunar[1];
        $days           = $lunar[2];
        $month_data     = [1 => '一月', 2 => '二月', 3 => '三月', 4 => '四月', 5 => '五月', 6 => '六月', 7 => '七月', 8 => '八月', 9 => '九月', 10 => '十月', 11 => '十一月', 12 => '十二月'];
        $r_month_date   = [1 => '(闰)一月', 2 => '(闰)二月', 3 => '(闰)三月', 4 => '(闰)四月', 5 => '(闰)五月', 6 => '(闰)六月', 7 => '(闰)七月', 8 => '(闰)八月', 9 => '(闰)九月', 10 => '(闰)十月', 11 => '(闰)十一月', 12 => '(闰)十二月'];
        $reversal_month = array_flip($month_data);
        $post_month     = isset($reversal_month[$month]) ? $reversal_month[$month] : (isset(array_flip($r_month_date)[$month]) ? array_flip($r_month_date)[$month] : 1);
        $day_data       = [1 => '初一', 2 => '初二', 3 => '初三', 4 => '初四', 5 => '初五', 6 => '初六', 7 => '初七', 8 => '初八', 9 => '初九', 10 => '初十', 11 => '十一', 12 => '十二', 13 => '十三', 14 => '十四', 15 => '十五', 16 => '十六', 17 => '十七', 18 => '十八', 19 => '十九', 20 => '二十', 21 => '廿一', 22 => '廿二', 23 => '廿三', 24 => '廿四', 25 => '廿五', 26 => '廿六', 27 => '廿七', 28 => '廿八', 29 => '廿九', 30 => '三十'];
        $reversal_day   = array_flip($day_data);
        $post_day       = isset($reversal_day[$days]) ? $reversal_day[$days] : 1;
        $hour           = date('H');
        $hour_data      = [1 => '23~00', 2 => '01~02', 3 => '03~04', 4 => '05~06', 5 => '07~08', 6 => '09~10', 7 => '11~12', 8 => '13~14', 9 => '15~16', 10 => '17~18', 11 => '19~20', 12 => '21~22'];
        $hour_desc      = [1 => '子时', 2 => '丑时', 3 => '寅时', 4 => '卯时', 5 => '辰时', 6 => '巳时', 7 => '午时', 8 => '未时', 9 => '申时', 10 => '酉时', 11 => '戌时', 12 => '亥时'];
        $reversal_hour  = array_flip($hour_data);
        $keys           = '23~00';
        if ($hour % 2) {
            $num = (int)$hour + 1;
            if ($num < 10) {
                $num = '0' . $num;
            }
            if ($hour !== '23') {
                $keys = $hour . '~' . $num;
            }
        } else {
            $num = (int)$hour - 1;
            if ($num >= 0 and $num < 10) {
                $num = '0' . $num;
            }
            if ($hour !== '00') {
                $keys = $num . '~' . $hour;
            }
        }
        $post_hour      = isset($reversal_hour[$keys]) ? $reversal_hour[$keys] : 1;
        $lunar[3]       = $hour_desc[$post_hour];
        $d_value        = Db::name('sm_xliuren')->where('id', '<>', 0)->column('f_name,f_value,f_exp_title,f_exp_desc,f_exp_note,f_exp_content', 'id');
        $this_date      = $demo['y'] . '-' . $demo['m'] . '-' . $demo['d'] . ' ' . $demo['h'] . ' 星期' . $demo['w'];
        $solar_terms    = $demo['solar_terms'];
        $month          = $post_month;      //输入为1~12  执行为0~11
        $day            = $post_day;        //输入为1~30  执行为0~29
        $hour           = $post_hour;       //输入为1~12  执行为0~11
        $d_fate_num     = $month + ($day - 1) + ($hour - 1);
        $by_date        = $d_fate_num % 6 == 0 ? $by_date = 6 : $d_fate_num % 6;
        $result         =  ['submit_content' => $format_data['content'], 'date' => $this_date, 'lunar' => $lunar, 'th_dz_data' => $th_dz_data, 'star_sign_data' => $star_sign_data, 'solar_terms' => $solar_terms, 'inference_data' => $d_value[$by_date]];
        return $result;
    }


    /**
     * 获取字库信息
     * @return array 返回查询结果
     */
    public function get_zidians_info()
    {
        if ($this->request->isPost()) {
            // 输入验证和安全过滤
            $postData = SafeFilterItem(input('post.'));
            $userId = $this->memberID;
            $key = isset($postData['key']) && $postData['key'] != '' ? trim($postData['key']) : '';
            
            // 密钥验证
            if (empty($key)) {
                return returnData(0, '密钥有误，请重新获取');
            }
            
            // 使用参数绑定防止SQL注入
            $isValidKey = Db::name('sp_key')
                ->where('key', '=', $key)
                ->where('status', '=', 1)
                ->where('user_id', '=', $userId)
                ->find();
                
            if (empty($isValidKey)) {
                return returnData(0, '密钥有误，请重新获取');
            }
            
            // 构建查询条件
            $where = [];
            $searchValue = isset($postData['s_value']) && $postData['s_value'] != '' ? trim($postData['s_value']) : '';
            $searchType  = isset($postData['s_type']) && $postData['s_type'] != '' ? (int)$postData['s_type'] : 1;
            
            // 基本参数验证
            if ($searchType <= 0) {
                return returnData(0, '搜索类型错误');
            }
            
            if (empty($searchValue)) {
                return returnData(0, '请输入搜索内容');
            }
            
            // 默认排序
            $orderBy = 'id asc';
            
            // 根据搜索类型构建查询条件
            switch ($searchType) {
                case 1: // 汉字搜索
                    $where['zi'] = ['like', $searchValue];
                    
                    // 同音搜索
                    $samePinyin = isset($postData['tp_value']) && $postData['tp_value'] != '' ? trim($postData['tp_value']) : '';
                    if (!empty($samePinyin)) {
                        unset($where['zi']);
                        $where['pinyin'] = ['=', $samePinyin];
                    }
                    
                    // 同声母搜索
                    $sameShengmu = isset($postData['ts_value']) && $postData['ts_value'] != '' ? trim($postData['ts_value']) : '';
                    if (!empty($sameShengmu)) {
                        unset($where['zi']);
                        $where['shengmu'] = ['=', $sameShengmu];
                    }
                    
                    // 同韵母搜索
                    $sameYumu = isset($postData['ty_value']) && $postData['ty_value'] != '' ? trim($postData['ty_value']) : '';
                    if (!empty($sameYumu)) {
                        unset($where['zi']);
                        $where['yumu'] = ['=', $sameYumu];
                    }
                    break;
                    
                case 2:  // 拼音搜索
                    $where['pinyin'] = ['=', $searchValue];
                    break;
                    
                case 3:  // 笔画搜索
                    $where['bihua'] = ['=', $searchValue];
                    break;
                    
                case 4: // 字义搜索
                    $orderBy = 'star_value desc';
                    // 将中文逗号替换为英文逗号
                    $searchValue = str_replace('，', ',', $searchValue);
                    $keywords = explode(',', $searchValue);
                    // 过滤空值
                    $keywords = array_filter($keywords, function($value) {
                        return !empty(trim($value));
                    });
                    
                    if (count($keywords) > 1) {
                        // 多个关键词使用OR连接
                        foreach ($keywords as $keyword) {
                            $where['ziyi'][] = ['like', '%' . trim($keyword) . '%'];
                        }
                        $where['ziyi'][count($keywords)] = 'or';
                    } else {
                        // 单个关键词直接查询
                        $where['ziyi'] = ['like', '%' . trim(reset($keywords)) . '%'];
                    }
                    break;
            }
            
            // 声调过滤
            $toneValue = isset($postData['sd_items']) && $postData['sd_items'] != '' ? (int)$postData['sd_items'] : -1;
            if ($toneValue > -1) {
                $where['tone_value'] = ['=', $toneValue];
            }
            
            // 五行过滤
            $wuxingValue = isset($postData['wx_items']) && $postData['wx_items'] != '' ? (int)$postData['wx_items'] : 0;
            if ($wuxingValue > 0) {
                $where['wuxing_value'] = ['=', $wuxingValue];
            }
            
            // 星级过滤
            $starValue = isset($postData['star_value']) && $postData['star_value'] != '' ? (int)$postData['star_value'] : 0;
            if ($starValue > 0) {
                $where['star_value'] = ['=', $starValue];
            }
            
            // 级别过滤
            $levelValue = isset($postData['level_value']) && $postData['level_value'] != '' ? (int)$postData['level_value'] : 0;
            if ($levelValue > 0) {
                $where['level_value'] = ['=', $levelValue];
            }
            
            // 笔画类型过滤
            $strokeTypes = [1 => 'bihua', 2 => 'fan_bihua', 3 => 'shu_bihua'];
            $strokeCount = isset($postData['bihua']) && $postData['bihua'] != '' ? (int)$postData['bihua'] : 0;
            $strokeType  = isset($postData['bihua_type']) && $postData['bihua_type'] != '' ? (int)$postData['bihua_type'] : 0;
            
            if ($strokeCount > 0 && $strokeType > 0 && isset($strokeTypes[$strokeType])) {
                $strokeField = $strokeTypes[$strokeType];
                // 如果已经有笔画查询，则移除旧的条件
                if ($searchType == 3 && $searchValue && isset($where['bihua'])) {
                    unset($where['bihua']);
                }
                $where[$strokeField] = ['=', $strokeCount];
            }
            
            // 自定义排序验证
            $customOrder = isset($postData['s_order']) && $postData['s_order'] != '' ? trim($postData['s_order']) : '';
            if (!empty($customOrder)) {
                // 白名单验证，防止SQL注入
                $validOrderFields = ['id', 'zi', 'pinyin', 'bihua', 'star_value', 'level_value'];
                $field = explode(' ', ltrim($customOrder))[0];
                if (in_array($field, $validOrderFields)) {
                    $orderBy = $customOrder;
                }
            }
            // 分页参数
            $page = isset($postData['page']) && $postData['page'] != '' ? max(1, (int)$postData['page']) : 1;
            $pageSize = isset($postData['rows']) && $postData['rows'] != '' ? max(1, (int)$postData['rows']) : 20;
            // 生成缓存键
            $cacheKey = 'zidians_search_' . md5(json_encode($where) . $orderBy . $page . $pageSize);
            // 尝试从缓存获取结果
            $cache = new \app\common\CacheManager();
            $cachedResult = $cache->get($cacheKey);
            if ($cachedResult) {
                return returnData(1, '测算成功(缓存数据)', $cachedResult);
            }
            // 数据库查询
            $total = Db::name('qm_zidians')->where($where)->count();
            $data  = Db::name('qm_zidians')
                ->where($where)
                ->page($page, $pageSize)
                ->order(ltrim($orderBy))
                ->select();
            // 组装返回结果
            $result = [
                'text_data' => $data,
                'page' => $page,
                'total' => $total
            ];
            // 写入缓存（设置为1小时过期）
            $cache->set($cacheKey, $result, 3600);
            return returnData(1, '测算成功', $result);
        }
        return returnData(0, '请求方式错误');
    }
}
