<?php

//算命控制器
namespace app\index\model;

use think\Model;
use think\Db;

class Stars extends Model

{
    public function get_xingzuo_content($tid)
    {
        $data = Db::name('star_sign_yunshi_data')->where('tid', $tid)->find();
        $data['jintian']    = urldecode($data['jintian']);
        $data['mingtian']   = urldecode($data['mingtian']);
        $data['zhou']       = urldecode($data['zhou']);
        $data['yue']        = urldecode($data['yue']);
        return $data;
    }

    /**
     *根据提供的月份和日期获取星座信息
     */
    public static function get_xz($day_m = null, $day_d = null)
    {
        if ($day_m == '') {
            $day_m = date('m', time());
        }
        if ($day_d == '') {
            $day_d = date('d', time());
        }

        if ($day_m == 3) {
            if ($day_d >= 21) {
                $xz = '白羊座';
            } else {
                $xz = '双鱼座';
            }
        }
        if ($day_m == 4) {
            if ($day_d >= 20) {
                $xz = '金牛座';
            } else {
                $xz = '白羊座';
            }
        }
        if ($day_m == 5) {
            if ($day_d >= 21) {
                $xz = '双子座';
            } else {
                $xz = '金牛座';
            }
        }
        if ($day_m == 6) {
            if ($day_d >= 22) {
                $xz = '巨蟹座';
            } else {
                $xz = '双子座';
            }
        }
        if ($day_m == 7) {
            if ($day_d >= 23) {
                $xz = '狮子座';
            } else {
                $xz = '巨蟹座';
            }
        }
        if ($day_m == 8) {
            if ($day_d >= 23) {
                $xz = '处女座';
            } else {
                $xz = '狮子座';
            }
        }
        if ($day_m == 9) {
            if ($day_d >= 23) {
                $xz = '天秤座';
            } else {
                $xz = '处女座';
            }
        }
        if ($day_m == 10) {
            if ($day_d >= 24) {
                $xz = '天蝎座';
            } else {
                $xz = '天秤座';
            }
        }
        if ($day_m == 11) {
            if ($day_d >= 23) {
                $xz = '射手座';
            } else {
                $xz = '天蝎座';
            }
        }
        if ($day_m == 12) {
            if ($day_d >= 22) {
                $xz = '摩羯座';
            } else {
                $xz = '射手座';
            }
        }
        if ($day_m == 1) {
            if ($day_d >= 22) {
                $xz = '水瓶座';
            } else {
                $xz = '摩羯座';
            }
        }
        if ($day_m == 2) {
            if ($day_d >= 19) {
                $xz = '双鱼座';
            } else {
                $xz = '水瓶座';
            }
        }
        return $xz;
    }

    public function get_date_yunshi($tid, $date)
    {
        $xingzuoid = $tid - 400;
        $data = file_get_contents('http://cache.xzw.com/114la/json/fortune/' . $xingzuoid . '.js?showfortune');
        $data = json_decode($data, true);
        return $data;
    }

    /***
     *上升星座
	 //計算方法： 出生月參數（見表一查詢）＋ 生日數字 × 4 ＋ 出生時間 ＝ 計算出的時間（見表二查詢）便得出上升星座。
     */
    public function shangsheng($m, $d, $h, $i)
    {
        if ($i < 10) {
            $i = '0' . $i;
        }
        $table1 = array(1 => '640', 2 => '843', 3 => '1033', 4 => '1235', 5 => '1433', 6 => '1636', 7 => '1834', 8 => '2036', 9 => '2238', 10 => '0037', 11 => '0239', 12 => '0437');
        //$table2 = array('18:00'=>'白羊','19:29'=>'双子','23:13'=>'巨蟹','01:29'=>'狮子','03:46'=>'处女','06:00'=>'天秤','08:13'=>'天蝎','10:30'=>'射手','12:46'=>'摩羯','14:48'=>'水瓶','16:30'=>'双鱼');
        $num_m      = $table1[$m];
        $num_d      = $d * 4;
        $num_hi     = $h . $i;
        $num_all    = $num_m + $num_d + $num_hi;
        if ($num_all >= 2529) {
            $num_all = $num_all - 2400;
        }
        if ($num_all >= 1800 && $num_all < 1929) {
            $xz = '白羊座';
        } elseif ($num_all >= 1929 && $num_all < 2313) {
            $xz = '双子座';
        } elseif ($num_all >= 2313 && $num_all < 2529) {
            $xz = '巨蟹座';
        } elseif ($num_all >= 129 && $num_all < 346) {
            $xz = '狮子座';
        } elseif ($num_all >= 346 && $num_all < 600) {
            $xz = '处女座';
        } elseif ($num_all >= 600 && $num_all < 813) {
            $xz = '天秤座';
        } elseif ($num_all >= 813 && $num_all < 1030) {
            $xz = '天蝎座';
        } elseif ($num_all >= 1030 && $num_all < 1246) {
            $xz = '射手座';
        } elseif ($num_all >= 1246 && $num_all < 1448) {
            $xz = '摩羯座';
        } elseif ($num_all >= 1448 && $num_all < 1630) {
            $xz = '水瓶座';
        } elseif ($num_all >= 1630 && $num_all < 1800) {
            $xz = '双鱼座';
        } else {
            $xz = '金牛座';
        }
        $data = Db::name('star_sign_shangsheng_data')->where('title', $xz)->find();
        return $data;
    }

    /**
     * 取列表数据
     */
    public static function fenxi($row)
    {
        if (isset($row['xz']) && $row['xz']) {
            $xz = str_replace('座', '', $row['data']['xz']);
            $zh_on          = self::xz_zh_on($xz);
            $row['zh_on']   = $zh_on;
            $base           = self::xz_base($xz . '座');
            $row['base']    = $base;
            $row['aq']      = self::xz_aq($xz . '座');
            $row['cy']      = self::xz_cy($xz . '座');
            $row['sy']      = self::xz_sy($xz . '座');
            return $row;
        }
        return false;
    }

    public static function xz_aq($xz)
    {
        $data = Db::name('star_sign_aq')->where('id', $xz)->find();
        return $data;
    }


    public static function xz_cy($xz)
    {
        $data = Db::name('star_sign_cy')->where('id', $xz)->find();
        return $data;
    }

    public static function xz_sy($xz)
    {
        $data = Db::name('star_sign_sy')->where('id', $xz)->find();
        return $data;
    }


    public static function xz_zh_on($xz)
    {
        $data = Db::name('star_sign_zh')->where('id', $xz)->find();
        return $data;
    }


    public static function xz_base($xz)
    {
        $data = Db::name('star_sign_base')->where('xz', $xz)->find();
        return $data;
    }

    public function car_bieren()
    {
        $one = Db::name('tarot_bieren')->where('lie',1)->orderRaw('RAND()')->find();
        if ($one['img'] == '') {
            $one['img'] = self::carimg($one['pid'], $one['zf']);
        }
        $one['titles'] = preg_replace('|[0-9a-zA-Z/]+|', '', $one['title']);
        $one['titles'] = preg_replace("/\t/", "", $one['titles']);

        $two = Db::name('tarot_bieren')->where('lie','=',2)->where('pid','<>',$one['pid'])->orderRaw('RAND()')->find();
        if ($two['img'] == '') {
            $two['img'] = self::carimg($two['pid'], $two['zf']);
        }
        $two['titles'] = preg_replace('|[0-9a-zA-Z/]+|', '', $two['title']);
        $two['titles'] = preg_replace("/\t/", "", $two['titles']);

        
        $three = Db::name('tarot_bieren')->where('lie','=',3)->where('pid','<>',$one['pid'])->where('pid','<>',$two['pid'])->orderRaw('RAND()')->find();
        if ($three['img'] == '') {
            $three['img'] = self::carimg($three['pid'], $three['zf']);
        }
        $three['titles'] = preg_replace('|[0-9a-zA-Z/]+|', '', $three['title']);
        $three['titles'] = preg_replace("/\t/", "", $three['titles']);
      
        $four = Db::name('tarot_bieren')->where('lie','=',4)->where('pid','<>',$one['pid'])->where('pid','<>',$two['pid'])->where('pid','<>',$three['pid'])->orderRaw('RAND()')->find();
        if ($four['img'] == '') {
            $four['img'] = self::carimg($four['pid'], $four['zf']);
        }
        $four['titles'] = preg_replace('|[0-9a-zA-Z/]+|', '', $four['title']);
        $four['titles'] = preg_replace("/\t/", "", $four['titles']);


        $five = Db::name('tarot_bieren')->where('lie','=',5)->where('pid','<>',$one['pid'])->where('pid','<>',$two['pid'])->where('pid','<>',$three['pid'])->where('pid','<>',$four['pid'])->orderRaw('RAND()')->find();
        if ($five['img'] == '') {
            $five['img'] = self::carimg($five['pid'], $five['zf']);
        }
        $five['titles'] = preg_replace('|[0-9a-zA-Z/]+|', '', $five['title']);
        $five['titles'] = preg_replace("/\t/", "", $five['titles']);


        $six = Db::name('tarot_bieren')->where('lie','=',6)->where('pid','<>',$one['pid'])->where('pid','<>',$two['pid'])->where('pid','<>',$three['pid'])->where('pid','<>',$four['pid'])->where('pid','<>',$five['pid'])->orderRaw('RAND()')->find();
        if ($six['img'] == '') {
            $six['img'] = self::carimg($six['pid'], $six['zf']);
        }
        $six['titles'] = preg_replace('|[0-9a-zA-Z/]+|', '', $six['title']);
        $six['titles'] = preg_replace("/\t/", "", $six['titles']);
        return array($one, $two, $three, $four, $five, $six);
    }

    /**
     *抽取数据库牌库-1-2-3列随机抽取
     */
    public function car()
    {
        $one = Db::name('tarot_yunshi')->where('lie',1)->orderRaw('RAND()')->find();
        if ($one['img'] == '') {
            $one['img'] = self::carimg($one['pid'], $one['zf']);
        }

        $two = Db::name('tarot_yunshi')->where('lie',2)->where('pid','<>',$one['pid'])->orderRaw('RAND()')->find();
        if ($two['img'] == '') {
            $two['img'] = self::carimg($two['pid'], $two['zf']);
        }

        $three = Db::name('tarot_yunshi')->where('lie',3)->where('pid','<>',$one['pid'])->where('pid','<>',$two['pid'])->orderRaw('RAND()')->find();
        if ($three['img'] == '') {
            $three['img'] = self::carimg($three['pid'], $three['zf']);
        }
        return array($one, $two, $three);
    }

    /***
     * 卡牌图库
     */
    public function carimg($pid, $zf)
    {
        $imgdir = '/statics/ffsm/taluo/yunshi/images/';
        return $imgdir . $pid . '_' . $zf . '.jpg';
    }


    /***---------分割线---------***/


    /**
     *抽取数据库牌库-1-2-3列随机抽取
     */
    public function car_xinli()
    {
        $one = Db::name('tarot_xinli')->where('lie',1)->orderRaw('RAND()')->find();
        if ($one['img'] == '') {
            $one['img'] = self::carimg($one['pid'], $one['zf']);
        }


        $two = Db::name('tarot_xinli')->where('lie',2)->where('pid','<>',$one['pid'])->orderRaw('RAND()')->find();
        if ($two['img'] == '') {
            $two['img'] = self::carimg($two['pid'], $two['zf']);
        }

        $three = Db::name('tarot_xinli')->where('lie',3)->where('pid','<>',$one['pid'])->where('pid','<>',$two['pid'])->orderRaw('RAND()')->find();
        if ($three['img'] == '') {
            $three['img'] = self::carimg($three['pid'], $three['zf']);
        }
       
        $four = Db::name('tarot_xinli')->where('lie',4)->where('pid','<>',$one['pid'])->where('pid','<>',$two['pid'])->where('pid','<>',$three['pid'])->orderRaw('RAND()')->find();
        if ($four['img'] == '') {
            $four['img'] = self::carimg($four['pid'], $four['zf']);
        }
        return array($one, $two, $three, $four);
    }



    /**
     *抽取数据库牌库-1-2-3列随机抽取
     */
    public function car_anlian()
    {
        $one = Db::name('tarot_anlian')->where('lie',1)->orderRaw('RAND()')->find();
        if ($one['img'] == '') {
            $one['img'] = self::carimg($one['pid'], $one['zf']);
        }

        $two = Db::name('tarot_anlian')->where('lie',2)->where('pid','<>',$one['pid'])->orderRaw('RAND()')->find();
        if ($two['img'] == '') {
            $two['img'] = self::carimg($two['pid'], $two['zf']);
        }

        $three = Db::name('tarot_anlian')->where('lie',3)->where('pid','<>',$one['pid'])->where('pid','<>',$two['pid'])->orderRaw('RAND()')->find();
        if ($three['img'] == '') {
            $three['img'] = self::carimg($three['pid'], $three['zf']);
        }

        $four = Db::name('tarot_anlian')->where('lie',4)->where('pid','<>',$one['pid'])->where('pid','<>',$two['pid'])->where('pid','<>',$three['pid'])->orderRaw('RAND()')->find();
        if ($four['img'] == '') {
            $four['img'] = self::carimg($four['pid'], $four['zf']);
        }
        return array($one, $two, $three, $four);
    }


    /**
     *抽取数据库牌库-1-2-3列随机抽取
     */
    public function car_jixu()
    {
        $one = Db::name('tarot_jixu')->where('lie',1)->orderRaw('RAND()')->find();
        if ($one['img'] == '') {
            $one['img'] = self::carimg($one['pid'], $one['zf']);
        }

        $two = Db::name('tarot_jixu')->where('lie',2)->where('pid','<>',$one['pid'])->orderRaw('RAND()')->find();
        if ($two['img'] == '') {
            $two['img'] = self::carimg($two['pid'], $two['zf']);
        }

        $three = Db::name('tarot_jixu')->where('lie',3)->where('pid','<>',$one['pid'])->where('pid','<>',$two['pid'])->orderRaw('RAND()')->find();
        if ($three['img'] == '') {
            $three['img'] = self::carimg($three['pid'], $three['zf']);
        }

        $four = Db::name('tarot_jixu')->where('lie',4)->where('pid','<>',$one['pid'])->where('pid','<>',$two['pid'])->where('pid','<>',$three['pid'])->orderRaw('RAND()')->find();
        if ($four['img'] == '') {
            $four['img'] = self::carimg($four['pid'], $four['zf']);
        }
        return array($one, $two, $three, $four);
    }



    /**
     *抽取数据库牌库-1-2-3列随机抽取
     */
    public function car_aiqing()
    {
        $one = Db::name('tarot_aiqing')->where('lie',1)->orderRaw('RAND()')->find();
        if ($one['img'] == '') {
            $one['img'] = self::carimg($one['pid'], $one['zf']);
        }
        $one['titles'] = preg_replace('|[0-9a-zA-Z/]+|', '', $one['title']);
        $one['titles'] = preg_replace("/\t/", "", $one['titles']);


        $two = Db::name('tarot_aiqing')->where('lie',2)->where('pid','<>',$one['pid'])->orderRaw('RAND()')->find();
        if ($two['img'] == '') {
            $two['img'] = self::carimg($two['pid'], $two['zf']);
        }
        $two['titles'] = preg_replace('|[0-9a-zA-Z/]+|', '', $two['title']);
        $two['titles'] = preg_replace("/\t/", "", $two['titles']);

        $three = Db::name('tarot_aiqing')->where('lie',3)->where('pid','<>',$one['pid'])->where('pid','<>',$two['pid'])->orderRaw('RAND()')->find();
        if ($three['img'] == '') {
            $three['img'] = self::carimg($three['pid'], $three['zf']);
        }
        $three['titles'] = preg_replace('|[0-9a-zA-Z/]+|', '', $three['title']);
        $three['titles'] = preg_replace("/\t/", "", $three['titles']);


        $four = Db::name('tarot_aiqing')->where('lie',4)->where('pid','<>',$one['pid'])->where('pid','<>',$two['pid'])->where('pid','<>',$three['pid'])->orderRaw('RAND()')->find();
        $four['des'] = explode(',', $four['des']);
        if ($four['img'] == '') {
            $four['img'] = self::carimg($four['pid'], $four['zf']);
        }
        $four['titles'] = preg_replace('|[0-9a-zA-Z/]+|', '', $four['title']);
        $four['titles'] = preg_replace("/\t/", "", $four['titles']);


        $five = Db::name('tarot_aiqing')->where('lie',5)->where('pid','<>',$one['pid'])->where('pid','<>',$two['pid'])->where('pid','<>',$three['pid'])->where('pid','<>',$four['pid'])->orderRaw('RAND()')->find();
        if ($five['img'] == '') {
            $five['img'] = self::carimg($five['pid'], $five['zf']);
        }
        $five['titles'] = preg_replace('|[0-9a-zA-Z/]+|', '', $five['title']);
        $five['titles'] = preg_replace("/\t/", "", $five['titles']);


        $six = Db::name('tarot_aiqing')->where('lie',6)->where('pid','<>',$one['pid'])->where('pid','<>',$two['pid'])->where('pid','<>',$three['pid'])->where('pid','<>',$four['pid'])->where('pid','<>',$five['pid'])->orderRaw('RAND()')->find();
        if ($six['img'] == '') {
            $six['img'] = self::carimg($six['pid'], $six['zf']);
        }
        $six['titles'] = preg_replace('|[0-9a-zA-Z/]+|', '', $six['title']);
        $six['titles'] = preg_replace("/\t/", "", $six['titles']);

        return array($one, $two, $three, $four, $five, $six);
    }

    /***
     * car_tuodan
     */
    public function car_tuodan()
    {
        $one = Db::name('tarot_tuodan')->where('lie',1)->orderRaw('RAND()')->find();
        if ($one['img'] == '') {
            $one['img'] = self::carimg($one['pid'], $one['zf']);
        }

        $two = Db::name('tarot_tuodan')->where('lie',2)->where('pid','<>',$one['pid'])->orderRaw('RAND()')->find();
        if ($two['img'] == '') {
            $two['img'] = self::carimg($two['pid'], $two['zf']);
        }
        $two['day'] = explode(',', $two['day']);
        if ($two['day'] == '') {
            $two['day'] = array('05月06日', '06月13日', '07月14日');
        }

        $three = Db::name('tarot_tuodan')->where('lie',3)->where('pid','<>',$one['pid'])->where('pid','<>',$two['pid'])->orderRaw('RAND()')->find();
        if ($three['img'] == '') {
            $three['img'] = self::carimg($three['pid'], $three['zf']);
        }

        $four = Db::name('tarot_tuodan')->where('lie',4)->where('pid','<>',$one['pid'])->where('pid','<>',$two['pid'])->where('pid','<>',$three['pid'])->orderRaw('RAND()')->find();
        if ($four['img'] == '') {
            $four['img'] = self::carimg($four['pid'], $four['zf']);
        }

        $five = Db::name('tarot_tuodan')->where('lie',5)->where('pid','<>',$one['pid'])->where('pid','<>',$two['pid'])->where('pid','<>',$three['pid'])->where('pid','<>',$four['pid'])->orderRaw('RAND()')->find();
        if ($five['img'] == '') {
            $five['img'] = self::carimg($five['pid'], $five['zf']);
        }
        return array($one, $two, $three, $four, $five);
    }




    public function car_fuhe()
    {
        $one = Db::name('tarot_fuhe')->where('lie',1)->orderRaw('RAND()')->find();
        if ($one['img'] == '') {
            $one['img'] = self::carimg($one['pid'], $one['zf']);
        }

        $two = Db::name('tarot_fuhe')->where('lie',2)->where('pid','<>',$one['pid'])->orderRaw('RAND()')->find();
        if ($two['img'] == '') {
            $two['img'] = self::carimg($two['pid'], $two['zf']);
        }

        $three = Db::name('tarot_fuhe')->where('lie',3)->where('pid','<>',$one['pid'])->where('pid','<>',$two['pid'])->orderRaw('RAND()')->find();
        if ($three['img'] == '') {
            $three['img'] = self::carimg($three['pid'], $three['zf']);
        }

        $four = Db::name('tarot_fuhe')->where('lie',4)->where('pid','<>',$one['pid'])->where('pid','<>',$two['pid'])->where('pid','<>',$three['pid'])->orderRaw('RAND()')->find();
        if ($four['img'] == '') {
            $four['img'] = self::carimg($four['pid'], $four['zf']);
        }
        return array($one, $two, $three, $four);
    }




    public function car_zuihou()
    {
        $one = Db::name('tarot_zuihou')->where('lie',1)->orderRaw('RAND()')->find();
        if ($one['img'] == '') {
            $one['img'] = self::carimg($one['pid'], $one['zf']);
        }
        $two = Db::name('tarot_zuihou')->where('lie',2)->where('pid','<>',$one['pid'])->orderRaw('RAND()')->find();
        if ($two['img'] == '') {
            $two['img'] = self::carimg($two['pid'], $two['zf']);
        }

        $three = Db::name('tarot_zuihou')->where('lie',3)->where('pid','<>',$one['pid'])->where('pid','<>',$two['pid'])->orderRaw('RAND()')->find();
        if ($three['img'] == '') {
            $three['img'] = self::carimg($three['pid'], $three['zf']);
        }


        $four = Db::name('tarot_zuihou')->where('lie',4)->where('pid','<>',$one['pid'])->where('pid','<>',$two['pid'])->where('pid','<>',$three['pid'])->orderRaw('RAND()')->find();
        if ($four['img'] == '') {
            $four['img'] = self::carimg($four['pid'], $four['zf']);
        }
        return array($one, $two, $three, $four);
    }


    public function car_fenshou()
    {
        $one = Db::name('tarot_fenshou')->where('lie',1)->orderRaw('RAND()')->find();
        if ($one['img'] == '') {
            $one['img'] = self::carimg($one['pid'], $one['zf']);
        }

        $two = Db::name('tarot_fenshou')->where('lie',2)->where('pid','<>',$one['pid'])->orderRaw('RAND()')->find();
        if ($two['img'] == '') {
            $two['img'] = self::carimg($two['pid'], $two['zf']);
        }

        $three = Db::name('tarot_fenshou')->where('lie',3)->where('pid','<>',$one['pid'])->where('pid','<>',$two['pid'])->orderRaw('RAND()')->find();
        if ($three['img'] == '') {
            $three['img'] = self::carimg($three['pid'], $three['zf']);
        }

        $four = Db::name('tarot_fenshou')->where('lie',4)->where('pid','<>',$one['pid'])->where('pid','<>',$two['pid'])->where('pid','<>',$three['pid'])->orderRaw('RAND()')->find();
        if ($four['img'] == '') {
            $four['img'] = self::carimg($four['pid'], $four['zf']);
        }
        return array($one, $two, $three, $four);
    }
}
