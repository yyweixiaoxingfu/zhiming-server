<?php

use app\common\library\sm\cls_strokeData;
use think\Db;
// 为方便系统核心升级，二次开发中需要用到的公共函数请写在这个文件，不要去修改common.php文件
if (!function_exists('splicingHttpSrc')) {
    function splicingHttpSrc($data = '')
    {
        $requestScheme = $_SERVER['REQUEST_SCHEME'] ?? 'http';
        return $requestScheme . '://' . $_SERVER['HTTP_HOST'] . '/' . $data;
    }
}
//统一返回数据
if (!function_exists('returnData')) {
    function returnData($code = 0, $msg = '', $data = [])
    {
        return json(array('code' => $code, 'msg' => $msg, 'data' => $data));
    }
}
if (!function_exists('createNO')) {
    function createNO($type = 3, $length, $table = null, $field = null)
    {
        if ($type == 0) {                           //纯数字
            $str = '1234567890';
        } elseif ($type == 1) {                     //纯字母
            $str = 'ABCDEFGHJKMNOPQRSTUVWXYZabcdefghjkmnopqrstuvwxyz';
        } else {
            $str = 'ABCDEFGHJKMNOPQRSTUVWXYZabcdefghjkmnopqrstuvwxyz1234567890';  //数字+字符串
        }
        $randStr = str_shuffle($str) . time() . rand(0, 999); //打乱字符串
        $rands = substr($randStr, 0, $length);
        if ($table && $field) {
            $record = Db::name($table)->where($field, $rands)->field('id')->find();
            if (!empty($record)) {
                return createNO($type, $length, $table, $field);
            }
        }
        return $rands;
    }
}
if (!function_exists('generate_order_number')) {
    function generate_order_number($prefix = '', $target_id = 0, $table = null, $field = null, $type = 0)
    {
        $i = rand(0, 999);
        do {
            if (999 == $i) {
                $i = 0;
            }
            $i++;

            if ($type == 1) {
                $public = $prefix . date('ymd');
            } else {
                $public = $prefix . date('ymdHi') . substr(microtime(), 2, 5);
            }

            $code = $public . str_pad($i, 3, '0', STR_PAD_LEFT);

            if ($table && $field) {
                $row = Db::name($table)->where($field, $code)->count('id');
            } else {
                $row = null;
            }
        } while ($row);
        return $code;
    }
}

if(!function_exists('generateCardKey')){
    function generateCardKey($prefix = '',$length = 8) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'; // 包含所有字符（大小写字母、数字）
        $key = '';
        for ($i = 0; $i < $length; $i++) {
            $randomIndex = mt_rand(0, strlen($characters)-1);
            $key .= substr($characters, $randomIndex, 1);
        }
        if($prefix){
            $key = $prefix.$key;
        }
        return $key;
    }
}

if (!function_exists('SafeFilterItem')) {
    function SafeFilterItem($value)
    {
        $ra = array('/([\x00-\x08,\x0b-\x0c,\x0e-\x19])/', '/script/i', '/javascript/i', '/vbscript/i', '/expression/i', '/applet/i', '/meta/i', '/xml/i', '/blink/i', '/link/i', '/style/i', '/embed/i', '/object/i', '/frame/i', '/layer/i', '/title/i', '/bgsound/i', '/base/i', '/onload/i', '/onunload/i', '/onchange/i', '/onsubmit/i', '/onreset/i', '/onselect/i', '/onblur/i', '/onfocus/i', '/onabort/i', '/onkeydown/i', '/onkeypress/i', '/onkeyup/i', '/onclick/i', '/ondblclick/i', '/onmousedown/i', '/onmousemove/i', '/onmouseout/i', '/onmouseover/i', '/onmouseup/i', '/onunload/i', '/svg/i');
        if (!is_array($value)) {
            $get_magic_quotes_gpc = (($quotes_gpc = ini_get('magic_quotes_gpc')) && strtolower($quotes_gpc) !== 'off');
            if (!$get_magic_quotes_gpc) {
                $value = addslashes($value);                              //给单引号（'）、双引号（"）、反斜线（\）与NUL（NULL字cache_get符）加上反斜线转义
            }
            $value = preg_replace($ra, '_', $value);      //删除非打印字符，粗暴式过滤xss可疑字符串
            $value = trim($value);                                       //去除 HTML 和 PHP 标记并转换为HTML实体
        }
        return $value;
    }
}
if (!function_exists('SafeFilter')) {
    function SafeFilter($arr)
    {
        if (is_array($arr)) {
            foreach ($arr as $key => $value) {
                if (!is_array($value)) {
                    $arr[$key] = SafeFilterItem($value);                                       //去除 HTML 和 PHP 标记并转换为HTML实体
                } else {
                    SafeFilter($arr[$key]);
                }
            }
        }
        return $arr;
    }
}

if (!function_exists('setUpImg')) {
    function setUpImg($img, $type = 0, $returnType = 'url')
    {
        if (empty($img)) return false;
        if (is_array($img)) {
            $arr_img = $img;
        } else {
            $arr_img = explode(',', $img);
        }
        $requestScheme = $_SERVER['REQUEST_SCHEME'] ?? 'http';
        $local_url = $requestScheme . '://' . $_SERVER['HTTP_HOST'] . '/';
        $str_img = [];
        foreach ($arr_img as &$row) {
            if (is_numeric($row)) {
                $attachment = \app\admin\model\Attachment::where(['id' => $row])->find();
                if (empty($attachment)) continue;
                if ($attachment['driver'] == 'local') {
                    $attachment_path = $local_url . $attachment['path'];
                } else {
                    $attachment_path = $attachment['path'];
                }
            } else {
                $preg = "/^http(s)?:\\/\\/.+/";
                if (preg_match($preg, $row)) {
                    $attachment_path = $row;
                } else {
                    $attachment_path = $local_url . $row;
                }
            }
            if ($returnType == 'all') {
                $str_img[] = [
                    'value' => $row,
                    'url' => $attachment_path,
                    'type' => $attachment['type'] ?? '',
                    'size' => $attachment['size'] ?? 0,
                    'time_length' => $attachment['time_length'] ?? 0
                ];
            } else {
                $str_img[] = $attachment_path;
            }
        }
        if (empty($str_img)) return false;
        if ($type == 0 && count($str_img) == 1) {
            return $str_img[0];
        }
        return $str_img;
    }
}
if (!function_exists('Img_url')) {
    function Img_url($img)
    {
        if (empty($img)) {
            return '';
        } else {
            $requestScheme = $_SERVER['REQUEST_SCHEME'] ?? 'http';
            $host          = $requestScheme . '://' . $_SERVER['HTTP_HOST'];
            $url           = $host . '/uploads/';
            $image         = str_replace('/uploads/', $url, $img);
            return $image;
        }
    }
}

if (!function_exists('set_up_img')) {
    function set_up_img($img, $type = 0, $returnType = 'url', $method = 'img')
    {
        $requestScheme = $_SERVER['REQUEST_SCHEME'] ?? 'http';
        if (empty($img) && $method == 'img') {
            $img = config('web_site_logo');
        }

        $char = "http";
        if (strpos($img, $char) !== false) {
            return $img;
        }

        $str_img = [];
        if ($type == 1) {
            $arr_img = explode(',', $img);
            foreach ($arr_img as &$row) {
                $img = $row;
                if (is_numeric($row)) {
                    $img = get_file_path($row);
                }
                if ($returnType == 'all') {
                    if (strpos($img, 'http') !== false) {
                        $url = $img;
                    } else {
                        $url = $requestScheme . '://' . $_SERVER['HTTP_HOST'] . $img;
                    }
                    $item['value']  = $row;
                    $item['url']    = $url;
                    $item['method'] = $method;
                    $str_img[]      = $item;
                } else {
                    $str_img[] = $requestScheme . '://' . $_SERVER['HTTP_HOST'] . $img;
                }
            }
            return $str_img;
        } else {
            if ($returnType == 'all') {
                if (strpos($img, 'http') !== false) {
                    $url = $img;
                } else {
                    $url = $requestScheme . '://' . $_SERVER['HTTP_HOST'] . get_file_path($img);
                }
                $item['value']  = $img;
                $item['url']    = $url;
                $item['method'] = $method;
                $str_img        = $item;
                return $str_img;
            } else {
                if (strpos($img, 'http') !== false) {
                    return $img;
                } else {
                    return $requestScheme . '://' . $_SERVER['HTTP_HOST'] . get_file_path($img);
                }
            }
        }
    }
}
if (!function_exists('time_tran')) {
    function time_tran($the_time)
    {
        $now_time  = time();
        $show_time = $the_time;
        $dur       = $now_time - $show_time;
        if ($dur < 0) {
            return $the_time;
        } else {
            if ($dur < 60) {
                return $dur . '秒前';
            } else {
                if ($dur < 3600) {
                    return floor($dur / 60) . '分钟前';
                } else {
                    if ($dur < 86400) {
                        return floor($dur / 3600) . '小时前';
                    } else {
                        if ($dur < 259200) { //3天内
                            return floor($dur / 86400) . '天前';
                        } else {
                            return $the_time;
                        }
                    }
                }
            }
        }
    }
}
if (!function_exists('is_serialized')) {
    function is_serialized($data)
    {
        if (!$data) return false;
        $data = trim($data);
        if ('N;' == $data)
            return true;
        if (!preg_match('/^([adObis]):/', $data, $badions))
            return false;
        switch ($badions[1]) {
            case 'a':
            case 'O':
            case 's':
                if (preg_match("/^{$badions[1]}:[0-9]+:.*[;}]\$/s", $data))
                    return true;
                break;
            case 'b':
            case 'i':
            case 'd':
                if (preg_match("/^{$badions[1]}:[0-9.E-]+;\$/", $data))
                    return true;
                break;
        }
        return false;
    }
}

if (!function_exists('getAvatar')) {
    function getAvatar($avatar)
    {
        if (empty($avatar)) {
            return setUpImg(config('web_site_logo'));
        } else {
            return is_numeric($avatar) ? setUpImg($avatar) : $avatar;
        }
    }
}

if (!function_exists('getQrcode')) {
    function getQrcode($url, $filename, $filepath = 'uploads/qrcode')
    {
        $domain = isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST'];
        if (!is_dir($filepath)) {
            mkdir($filepath, 777);
        }
        defined('DS') or define('DS', DIRECTORY_SEPARATOR);
        require_once __DIR__ . DS . ".." . DS . "vendor" . DS . "postermaker" . DS . "postermaker" . DS . "src" . DS . "QRcode.php";
        $filename = $filepath . '/' . $filename;
        if (!is_file(\think\facade\Env::get('app_path') . $filename)) {
            // \qrcode\QRcode::png($url, $filename, 'L', 5, 2); //生成二维码 
            \QRcode::png($url, $filename, 'L', 5, 2); //生成二维码
        }
        return $_SERVER['REQUEST_SCHEME'] . '://' . $domain . '/' . $filename;
    }
}

if (!function_exists('getExportDataByTitle')) {
    function getExportDataByTitle($title, $fileData)
    {
        $titleFlip = array_flip(array_values(array_flip($title)));
        $result = [];
        $index = 1;
        foreach ($fileData as $key => $row) {
            foreach ($titleFlip as $col => $i) {
                if (stripos($col, '.')) {
                    $allKey = explode('.', $col);
                    $res = $row;
                    foreach ($allKey as $k) {
                        $res = isset($res[$k]) ? $res[$k] : null;
                    }
                    $result[$key][$i] = $res;
                } else {
                    if (isset($row[$col])) {
                        $value = $row[$col];
                        $result[$key][$i] = $value;
                    } else {
                        $result[$key][$i] = '';
                    }
                    if ($col == 'index') $result[$key][$i] = (string)$index;
                }
            }
            $index++;
        }
        array_unshift($result, array_values($title));
        return $result;
    }
}

//获取excel数据，根据title跟数据可字段匹配
if (!function_exists('getExcelDataByTitle')) {
    function getExcelDataByTitle($title, $fileData)
    {
        $oneLine = array_shift($fileData);
        $titleFlip = array_flip($title);
        foreach ($oneLine as $key => $label) {
            if (isset($titleFlip[$label])) {
                $col = $titleFlip[$label];
                $current_title[] = $col;
            } else {
                $current_title[] = 'empty';
            }
        }
        $result = [];
        foreach ($fileData as $key => $row) {
            $isValid = false;
            foreach ($row as $k => $v) {
                $col = $current_title[$k];
                $result[$key][$col] = null;

                if (!is_null($v)) $isValid = true;
                if ($col != 'empty' && isset($v)) $result[$key][$col] = trim($v);
            }
            if ($isValid === false) unset($result[$key]);
        }
        return $result;
    }
}

/**
 * Emoji原形转换为String
 * @param string $content
 * @return string
 */
if (!function_exists('emojiEncode')) {
    function emojiEncode($content)
    {
        return json_decode(preg_replace_callback("/(\\\u[ed][0-9a-f]{3})/i", function ($str) {
            return addslashes($str[0]);
        }, json_encode($content)));
    }
}

/**
 * Emoji字符串转换为原形
 * @param string $content
 * @return string
 */
if (!function_exists('emojiDecode')) {
    function emojiDecode($content)
    {
        return json_decode(preg_replace_callback('/\\\\\\\\/i', function () {
            return '\\';
        }, json_encode($content)));
    }
}

//从数据库查找 得到繁体笔画
if (!function_exists('getnumft')) {
    function getnumft($txt)
    {
        global $sm_cache;  
        // 参数验证
        if (empty($txt) || !is_string($txt)) {
            return 0;
        }
        // 检查缓存
        $cache_key = 'bihua_' . md5($txt);
        if (isset($sm_cache['bihua'][$cache_key])) {
            return $sm_cache['bihua'][$cache_key];
        }
        try {
            // 使用精确匹配查询字库
            $num = Db::name('qm_zidians')->where('zi', $txt)->value('bihua');
            
            if (!empty($num)) {
                $bh_data = $num;
        } else {
                // 备用方案
                $cls_strokeData = new cls_strokeData();
                $bh_data = $cls_strokeData->find($txt);
                // 确保返回数值
                if (!is_numeric($bh_data)) {
                    $bh_data = 0;
                }
            }
            
            // 缓存结果
            if (!isset($sm_cache['bihua'])) {
                $sm_cache['bihua'] = array();
            }
            $sm_cache['bihua'][$cache_key] = $bh_data;
            
            return $bh_data;
        } catch (Exception $e) {
            // 错误处理
            return 0;
        }
    }
}
/*
 *获取笔画
 */

if (!function_exists('get_bihua')) {
    function get_bihua($str)
    {
        $num   = Db::name('Sm_bihua')->where('hanzi', 'like', "%" . $str . "%")->value('num');
        $data['bihua'] = '';
        $data['hzwh'] = '';
        if ($num != '') {
            $bh_data        = $num;
        } else {
            $cls_strokeData = new cls_strokeData();
            $bh_data        = $cls_strokeData->find($str);
        }
        $hzwh = Db::name('Sm_hzwh')->where('hz', 'like', "%" . $str . "%")->value('wh');
        if ($hzwh != '') {
            $hzwh_data = $hzwh;
        } else {
            $hzwh_data     = '金';
        }
        $data['bihua'] = $bh_data;
        $data['hzwh'] = $hzwh_data;
        return $data;
    }
}

//从数据库查找 命格计算
// 缓存数组，用于存储频繁查询的数据
static $sm_cache = array(
    'Sm_81'         => array(),
    'Sm_sancai'     => array(),
    'Sm_sjrs'       => array(),
    'Sm_qtbj'       => array(),
    'Sm_rgnm'       => array(),
    'Sm_smtf'       => array(),
    'Zodiac_ztys'   => array(),
    'Sm_rysmn'      => array(),
    'Sm_xishensy'   => array(),
);

if (!function_exists('getmgft')) {
    function getmgft($num)
    {
        global $sm_cache;
        // 检查缓存中是否存在数据
        if (isset($sm_cache['Sm_81'][$num])) {
            return $sm_cache['Sm_81'][$num];
        }
        $mg_data   = Db::name('Sm_81')->where('num', '=', $num)->find();
        // 将数据存入缓存
        $sm_cache['Sm_81'][$num] = $mg_data;
        return $mg_data;
    }  
}

if (!function_exists('getsanCaiData')) { 
    function getsanCaiData($title)
    {
        global $sm_cache;
        // 检查缓存中是否存在数据
        if (isset($sm_cache['Sm_sancai'][$title])) {
            return $sm_cache['Sm_sancai'][$title];
        }
        $mg_data   = Db::name('Sm_sancai')->where('title', '=', $title)->find();
        // 将数据存入缓存
        $sm_cache['Sm_sancai'][$title] = $mg_data;
        return $mg_data;
    }
}


//三才计算
if (!function_exists('getsancai_133')) { 
    function getsancai_133($genum)
    {
        $scnum = $genum % 10;
        switch ($scnum) {
            case 1:
                $tiantxt = "木";
                break;
            case 2:
                $tiantxt = "木";
                break;
            case 3:
                $tiantxt = "火";
                break;
            case 4:
                $tiantxt = "火";
                break;
            case 5:
                $tiantxt = "土";
                break;
            case 6:
                $tiantxt = "土";
                break;
            case 7:
                $tiantxt = "金";
                break;
            case 8:
                $tiantxt = "金";
                break;
            case 9:
                $tiantxt = "水";
                break;
            case 10:
                $tiantxt = "水";
                break;
            case 0:
                $tiantxt = "水";
                break;
        }
        return $tiantxt;
    }
}



//获取元神
if (!function_exists('get_yuanshen')) {
    function get_yuanshen($renge)
    {
        $renge = $renge % 10;
        if ($renge == 1) {
            $data['msg'] = '志氣高大，仁慈喜助人，不過稍嫌一板一眼、愛做作。';
            $data['yuanshen'] = '參天「甲木」';
            $data['wh'] = '木';
            $data['wh_'] = 'mu';
        } elseif ($renge == 2) {
            $data['msg'] = '思緒敏捷，環境適應力強，但常沒安全感，潛意識喜歡找靠山。';
            $data['yuanshen'] = '柔韌「乙芽」';
            $data['wh'] = '木';
            $data['wh_'] = 'mu';
        } elseif ($renge == 3) {
            $data['msg'] = '本人光明磊落，自信豪爽，可是性情急燥，做事衝動。';
            $data['yuanshen'] = '陽光「丙火」';
            $data['wh'] = '火';
            $data['wh_'] = 'huo';
        } elseif ($renge == 4) {
            $data['msg'] = '當事人心思細密，深具耐心，不過做事常考慮太多，決斷力不足。';
            $data['yuanshen'] = '長明「丁星」';
            $data['wh'] = '火';
            $data['wh_'] = 'huo';
        } elseif ($renge == 5) {
            $data['msg'] = '誠實可靠，穩重有信用，不過稍嫌頑固不化，變通力差。';
            $data['yuanshen'] = '厚重「戊山」';
            $data['wh'] = '土';
            $data['wh_'] = 'tu';
        } elseif ($renge == 6) {
            $data['msg'] = '處事精明，身段柔軟，但經常堅持力不夠，遇難易退縮。';
            $data['yuanshen'] = '可塑「己泥」';
            $data['wh'] = '土';
            $data['wh_'] = 'tu';
        } elseif ($renge == 7) {
            $data['msg'] = '性剛強重義氣，做事效率高，但嫌待人處事細膩感不足，欠缺謀略。';
            $data['yuanshen'] = '堅強「庚金」';
            $data['wh'] = '金';
            $data['wh_'] = 'jin';
        } elseif ($renge == 8) {
            $data['msg'] = '其人清秀，才華高超，可是魄力不足且行動力不夠。';
            $data['yuanshen'] = '晶瑩「辛鑽」';
            $data['wh'] = '金';
            $data['wh_'] = 'jin';
        } elseif ($renge == 9) {
            $data['msg'] = '其性樂觀豪邁不拘小節且熱情奔放，但有時衝動莽撞，容易情緒化。';
            $data['yuanshen'] = '濤濤「壬海」」';
            $data['wh'] = '水';
            $data['wh_'] = 'shui';
        } elseif ($renge == 0) {
            $data['msg'] = '本人聰慧，足智多謀，缺點則不切實際，愛鑽牛角尖。';
            $data['yuanshen'] = '清澈「癸水';
            $data['wh'] = '水';
            $data['wh_'] = 'shui';
        }
        return $data;
    }
}

//三才五行计算
if (!function_exists('getsancai')) {
    function getsancai($tiange)
    {
        $tian = $tiange % 10;
        switch ($tian) {
            case 1:
                $tiantxt = "木";
                break;
            case 2:
                $tiantxt = "木";
                break;
            case 3:
                $tiantxt = "火";
                break;
            case 4:
                $tiantxt = "火";
                break;
            case 5:
                $tiantxt = "土";
                break;
            case 6:
                $tiantxt = "土";
                break;
            case 7:
                $tiantxt = "金";
                break;
            case 8:
                $tiantxt = "金";
                break;
            case 9:
                $tiantxt = "水";
                break;
            case 0:
                $tiantxt = "水";
                break;
        }
        return $tiantxt;
    }
}


//得到一个汉字字意五行;
if (!function_exists('getzywh')) {
    function getzywh($txt)
    {
        global $sm_cache;
        
        // 参数验证
        if (empty($txt) || !is_string($txt)) {
            return '水'; // 返回默认值
        }
        
        // 检查缓存
        $cache_key = 'wh_' . md5($txt);
        if (isset($sm_cache['hzwh'][$cache_key])) {
            return $sm_cache['hzwh'][$cache_key];
        }
        
        try {
            // 先尝试精确匹配
            $num = Db::name('qm_zidians')->where('zi', $txt)->value('wuxing');
            
            // 如果字库中没有，则尝试使用旧表
            if (empty($num)) {
                $num = Db::name('Sm_hzwh')->where('hz', $txt)->value('wh');
            }
            
            // 设置默认值
            if (empty($num)) {
                $num = '水';
            }
            
            // 缓存结果
            if (!isset($sm_cache['hzwh'])) {
                $sm_cache['hzwh'] = array();
            }
            $sm_cache['hzwh'][$cache_key] = $num;
            
            return $num;
        } catch (Exception $e) {
            // 错误处理
            return '水';
        }
    }
}

//获得姓名匹配64挂
if (!function_exists('getPdGua')) {
    function getPdGua($num)
    {
        $num   = Db::name('Give_xmpd_gua')->where('gua', '=', $num)->find();
        return $num;
    }
}

if (!function_exists('getpf_133')) {
    function getpf_133($sc)
    {
        $szpf = 0;
        switch ($sc) {
            case "大吉":
                $szpf = 12;
                break;
            case "吉":
                $szpf = 8;
                break;
            case "半吉":
                $szpf = 5;
                break;
            case "大凶":
                $szpf = 0;
                break;
            case "凶":
                $szpf = 1;
                break;
            case "半凶":
                $szpf = 2;
                break;
            case "平":
                $szpf = 4;
                break;
        }
        return $szpf;
    }
}
//得到一个汉字拼音;
if (!function_exists('Pinyin_sm')) {
    function Pinyin_sm($_String, $_Code = 'gb2312')
    {
        // 使用静态变量缓存固定数据，避免每次调用重新初始化
        static $_Data = null;
        if ($_Data === null) {
            $_DataKey = "a|ai|an|ang|ao|ba|bai|ban|bang|bao|bei|ben|beng|bi|bian|biao|bie|bin|bing|bo|bu|ca|cai|can|cang|cao|ce|ceng|cha" .
                "|chai|chan|chang|chao|che|chen|cheng|chi|chong|chou|chu|chuai|chuan|chuang|chui|chun|chuo|ci|cong|cou|cu|" .
                "cuan|cui|cun|cuo|da|dai|dan|dang|dao|de|deng|di|dian|diao|die|ding|diu|dong|dou|du|duan|dui|dun|duo|e|en|er" .
                "|fa|fan|fang|fei|fen|feng|fo|fou|fu|ga|gai|gan|gang|gao|ge|gei|gen|geng|gong|gou|gu|gua|guai|guan|guang|gui" .
                "|gun|guo|ha|hai|han|hang|hao|he|hei|hen|heng|hong|hou|hu|hua|huai|huan|huang|hui|hun|huo|ji|jia|jian|jiang" .
                "|jiao|jie|jin|jing|jiong|jiu|ju|juan|jue|jun|ka|kai|kan|kang|kao|ke|ken|keng|kong|kou|ku|kua|kuai|kuan|kuang" .
                "|kui|kun|kuo|la|lai|lan|lang|lao|le|lei|leng|li|lia|lian|liang|liao|lie|lin|ling|liu|long|lou|lu|lv|luan|lue" .
                "|lun|luo|ma|mai|man|mang|mao|me|mei|men|meng|mi|mian|miao|mie|min|ming|miu|mo|mou|mu|na|nai|nan|nang|nao|ne" .
                "|nei|nen|neng|ni|nian|niang|niao|nie|nin|ning|niu|nong|nu|nv|nuan|nue|nuo|o|ou|pa|pai|pan|pang|pao|pei|pen" .
                "|peng|pi|pian|piao|pie|pin|ping|po|pu|qi|qia|qian|qiang|qiao|qie|qin|qing|qiong|qiu|qu|quan|que|qun|ran|rang" .
                "|rao|re|ren|reng|ri|rong|rou|ru|ruan|rui|run|ruo|sa|sai|san|sang|sao|se|sen|seng|sha|shai|shan|shang|shao|" .
                "she|shen|sheng|shi|shou|shu|shua|shuai|shuan|shuang|shui|shun|shuo|si|song|sou|su|suan|sui|sun|suo|ta|tai|" .
                "tan|tang|tao|te|teng|ti|tian|tiao|tie|ting|tong|tou|tu|tuan|tui|tun|tuo|wa|wai|wan|wang|wei|wen|weng|wo|wu" .
                "|xi|xia|xian|xiang|xiao|xie|xin|xing|xiong|xiu|xu|xuan|xue|xun|ya|yan|yang|yao|ye|yi|yin|ying|yo|yong|you" .
                "|yu|yuan|yue|yun|za|zai|zan|zang|zao|ze|zei|zen|zeng|zha|zhai|zhan|zhang|zhao|zhe|zhen|zheng|zhi|zhong|" .
                "zhou|zhu|zhua|zhuai|zhuan|zhuang|zhui|zhun|zhuo|zi|zong|zou|zu|zuan|zui|zun|zuo";
            $_DataValue = "-20319|-20317|-20304|-20295|-20292|-20283|-20265|-20257|-20242|-20230|-20051|-20036|-20032|-20026|-20002|-19990" .
                "|-19986|-19982|-19976|-19805|-19784|-19775|-19774|-19763|-19756|-19751|-19746|-19741|-19739|-19728|-19725" .
                "|-19715|-19540|-19531|-19525|-19515|-19500|-19484|-19479|-19467|-19289|-19288|-19281|-19275|-19270|-19263" .
                "|-19261|-19249|-19243|-19242|-19238|-19235|-19227|-19224|-19218|-19212|-19038|-19023|-19018|-19006|-19003" .
                "|-18996|-18977|-18961|-18952|-18783|-18774|-18773|-18763|-18756|-18741|-18735|-18731|-18722|-18710|-18697" .
                "|-18696|-18526|-18518|-18501|-18490|-18478|-18463|-18448|-18447|-18446|-18239|-18237|-18231|-18220|-18211" .
                "|-18201|-18184|-18183|-18181|-18012|-17997|-17988|-17970|-17964|-17961|-17950|-17947|-17931|-17928|-17922" .
                "|-17759|-17752|-17733|-17730|-17721|-17703|-17701|-17697|-17692|-17683|-17676|-17496|-17487|-17482|-17468" .
                "|-17454|-17433|-17427|-17417|-17202|-17185|-16983|-16970|-16942|-16915|-16733|-16708|-16706|-16689|-16664" .
                "|-16657|-16647|-16474|-16470|-16465|-16459|-16452|-16448|-16433|-16429|-16427|-16423|-16419|-16412|-16407" .
                "|-16403|-16401|-16393|-16220|-16216|-16212|-16205|-16202|-16187|-16180|-16171|-16169|-16158|-16155|-15959" .
                "|-15958|-15944|-15933|-15920|-15915|-15903|-15889|-15878|-15707|-15701|-15681|-15667|-15661|-15659|-15652" .
                "|-15640|-15631|-15625|-15454|-15448|-15436|-15435|-15419|-15416|-15408|-15394|-15385|-15377|-15375|-15369" .
                "|-15363|-15362|-15183|-15180|-15165|-15158|-15153|-15150|-15149|-15144|-15143|-15141|-15140|-15139|-15128" .
                "|-15121|-15119|-15117|-15110|-15109|-14941|-14937|-14933|-14930|-14929|-14928|-14926|-14922|-14921|-14914" .
                "|-14908|-14902|-14894|-14889|-14882|-14873|-14871|-14857|-14678|-14674|-14670|-14668|-14663|-14654|-14645" .
                "|-14630|-14594|-14429|-14407|-14399|-14384|-14379|-14368|-14355|-14353|-14345|-14170|-14159|-14151|-14149" .
                "|-14145|-14140|-14137|-14135|-14125|-14123|-14122|-14112|-14109|-14099|-14097|-14094|-14092|-14090|-14087" .
                "|-14083|-13917|-13914|-13910|-13907|-13906|-13905|-13896|-13894|-13878|-13870|-13859|-13847|-13831|-13658" .
                "|-13611|-13601|-13406|-13404|-13400|-13398|-13395|-13391|-13387|-13383|-13367|-13359|-13356|-13343|-13340" .
                "|-13329|-13326|-13318|-13147|-13138|-13120|-13107|-13096|-13095|-13091|-13076|-13068|-13063|-13060|-12888" .
                "|-12875|-12871|-12860|-12858|-12852|-12849|-12838|-12831|-12829|-12812|-12802|-12607|-12597|-12594|-12585" .
                "|-12556|-12359|-12346|-12320|-12300|-12120|-12099|-12089|-12074|-12067|-12058|-12039|-11867|-11861|-11847" .
                "|-11831|-11798|-11781|-11604|-11589|-11536|-11358|-11340|-11339|-11324|-11303|-11097|-11077|-11067|-11055" .
                "|-11052|-11045|-11041|-11038|-11024|-11020|-11019|-11018|-11014|-10838|-10832|-10815|-10800|-10790|-10780" .
                "|-10764|-10587|-10544|-10533|-10519|-10331|-10329|-10328|-10322|-10315|-10309|-10307|-10296|-10281|-10274" .
                "|-10270|-10262|-10260|-10256|-10254";
            $_TDataKey   = explode('|', $_DataKey);
            $_TDataValue = explode('|', $_DataValue);
            $_Data       = array_combine($_TDataKey, $_TDataValue);
            if (PHP_VERSION < '5.0') {
                $_Data =  Array_Combine($_TDataKey, $_TDataValue);
            }
            arsort($_Data);
        }
        
        if ($_Code != 'gb2312') {
            $_String = _U2_Utf8_Gb($_String);
        }
        $_Res = '';
        $length = strlen($_String);
        for ($i = 0; $i < $length; $i++) {
            $_P = ord(substr($_String, $i, 1));
            if ($_P > 160) {
                $_Q = ord(substr($_String, ++$i, 1));
                $_P = $_P * 256 + $_Q - 65536;
            }
            $_Res .= _Pinyin($_P, $_Data);
        }
        return preg_replace("/[^a-z0-9]*/", '', $_Res);
    }
}

if (!function_exists('gb_big5')) { 
    function gb_big5($string)
    {
        // 使用静态变量缓存转换映射，避免每次调用重新初始化
        static $convert_map = null;
        if ($convert_map === null) {
            $s = "皑,蔼,碍,爱,翱,袄,奥,坝,罢,摆,败,颁,办,绊,帮,绑,镑,谤,剥,饱,宝,报,鲍,辈,贝,钡,狈,备,惫,绷,笔,毕,毙,闭,边,编,贬,变,辩,辫,鳖,瘪,濒,滨,宾,摈,饼,拨,钵,铂,驳,卜,补,参,蚕,残,惭,惨,灿,苍,舱,仓,沧,厕,侧,册,测,层,诧,搀,掺,蝉,馋,谗,缠,铲,产,阐,颤,场,尝,长,偿,肠,厂,畅,钞,车,彻,尘,陈,衬,撑,称,惩,诚,骋,痴,迟,驰,耻,齿,炽,冲,虫,宠,畴,踌,筹,绸,丑,橱,厨,锄,雏,础,储,触,处,传,疮,闯,创,锤,纯,绰,辞,词,赐,聪,葱,囱,从,丛,凑,窜,错,达,带,贷,担,单,郸,掸,胆,惮,诞,弹,当,挡,党,荡,档,捣,岛,祷,导,盗,灯,邓,敌,涤,递,缔,点,垫,电,淀,钓,调,迭,谍,叠,钉,顶,锭,订,东,动,栋,冻,斗,犊,独,读,赌,镀,锻,断,缎,兑,队,对,吨,顿,钝,夺,鹅,额,讹,恶,饿,儿,尔,饵,贰,发,罚,阀,珐,矾,钒,烦,范,贩,饭,访,纺,飞,废,费,纷,坟,奋,愤,粪,丰,枫,锋,风,疯,冯,缝,讽,凤,肤,辐,抚,辅,赋,复,负,讣,妇,缚,该,钙,盖,干,赶,秆,赣,冈,刚,钢,纲,岗,皋,镐,搁,鸽,阁,铬,个,给,龚,宫,巩,贡,钩,沟,构,购,够,蛊,顾,剐,关,观,馆,惯,贯,广,规,硅,归,龟,闺,轨,诡,柜,贵,刽,辊,滚,锅,国,过,骇,韩,汉,阂,鹤,贺,横,轰,鸿,红,后,壶,护,沪,户,哗,华,画,划,话,怀,坏,欢,环,还,缓,换,唤,痪,焕,涣,黄,谎,挥,辉,毁,贿,秽,会,烩,汇,讳,诲,绘,荤,浑,伙,获,货,祸,击,机,积,饥,讥,鸡,绩,缉,极,辑,级,挤,几,蓟,剂,济,计,记,际,继,纪,夹,荚,颊,贾,钾,价,驾,歼,监,坚,笺,间,艰,缄,茧,检,碱,硷,拣,捡,简,俭,减,荐,槛,鉴,践,贱,见,键,舰,剑,饯,渐,溅,涧,浆,蒋,桨,奖,讲,酱,胶,浇,骄,娇,搅,铰,矫,侥,脚,饺,缴,绞,轿,较,秸,阶,节,茎,惊,经,颈,静,镜,径,痉,竞,净,纠,厩,旧,驹,举,据,锯,惧,剧,鹃,绢,杰,洁,结,诫,届,紧,锦,仅,谨,进,晋,烬,尽,劲,荆,觉,决,诀,绝,钧,军,骏,开,凯,颗,壳,课,垦,恳,抠,库,裤,夸,块,侩,宽,矿,旷,况,亏,岿,窥,馈,溃,扩,阔,蜡,腊,莱,来,赖,蓝,栏,拦,篮,阑,兰,澜,谰,揽,览,懒,缆,烂,滥,捞,劳,涝,乐,镭,垒,类,泪,篱,离,里,鲤,礼,丽,厉,励,砾,历,沥,隶,俩,联,莲,连,镰,怜,涟,帘,敛,脸,链,恋,炼,练,粮,凉,两,辆,谅,疗,辽,镣,猎,临,邻,鳞,凛,赁,龄,铃,凌,灵,岭,领,馏,刘,龙,聋,咙,笼,垄,拢,陇,楼,娄,搂,篓,芦,卢,颅,庐,炉,掳,卤,虏,鲁,赂,禄,录,陆,驴,吕,铝,侣,屡,缕,虑,滤,绿,峦,挛,孪,滦,乱,抡,轮,伦,仑,沦,纶,论,萝,罗,逻,锣,箩,骡,骆,络,妈,玛,码,蚂,马,骂,吗,买,麦,卖,迈,脉,瞒,馒,蛮,满,谩,猫,锚,铆,贸,么,霉,没,镁,门,闷,们,锰,梦,谜,弥,觅,绵,缅,庙,灭,悯,闽,鸣,铭,谬,谋,亩,钠,纳,难,挠,脑,恼,闹,馁,腻,撵,捻,酿,鸟,聂,啮,镊,镍,柠,狞,宁,拧,泞,钮,纽,脓,浓,农,疟,诺,欧,鸥,殴,呕,沤,盘,庞,国,爱,赔,喷,鹏,骗,飘,频,贫,苹,凭,评,泼,颇,扑,铺,朴,谱,脐,齐,骑,岂,启,气,弃,讫,牵,扦,钎,铅,迁,签,谦,钱,钳,潜,浅,谴,堑,枪,呛,墙,蔷,强,抢,锹,桥,乔,侨,翘,窍,窃,钦,亲,轻,氢,倾,顷,请,庆,琼,穷,趋,区,躯,驱,龋,颧,权,劝,却,鹊,让,饶,扰,绕,热,韧,认,纫,荣,绒,软,锐,闰,润,洒,萨,鳃,赛,伞,丧,骚,扫,涩,杀,纱,筛,晒,闪,陕,赡,缮,伤,赏,烧,绍,赊,摄,慑,设,绅,审,婶,肾,渗,声,绳,胜,圣,师,狮,湿,诗,尸,时,蚀,实,识,驶,势,释,饰,视,试,寿,兽,枢,输,书,赎,属,术,树,竖,数,帅,双,谁,税,顺,说,硕,烁,丝,饲,耸,怂,颂,讼,诵,擞,苏,诉,肃,虽,绥,岁,孙,损,笋,缩,琐,锁,獭,挞,抬,摊,贪,瘫,滩,坛,谭,谈,叹,汤,烫,涛,绦,腾,誊,锑,题,体,屉,条,贴,铁,厅,听,烃,铜,统,头,图,涂,团,颓,蜕,脱,鸵,驮,驼,椭,洼,袜,弯,湾,顽,万,网,韦,违,围,为,潍,维,苇,伟,伪,纬,谓,卫,温,闻,纹,稳,问,瓮,挝,蜗,涡,窝,呜,钨,乌,诬,无,芜,吴,坞,雾,务,误,锡,牺,袭,习,铣,戏,细,虾,辖,峡,侠,狭,厦,锨,鲜,纤,咸,贤,衔,闲,显,险,现,献,县,馅,羡,宪,线,厢,镶,乡,详,响,项,萧,销,晓,啸,蝎,协,挟,携,胁,谐,写,泻,谢,锌,衅,兴,汹,锈,绣,虚,嘘,须,许,绪,续,轩,悬,选,癣,绚,学,勋,询,寻,驯,训,讯,逊,压,鸦,鸭,哑,亚,讶,阉,烟,盐,严,颜,阎,艳,厌,砚,彦,谚,验,鸯,杨,扬,疡,阳,痒,养,样,瑶,摇,尧,遥,窑,谣,药,爷,页,业,叶,医,铱,颐,遗,仪,彝,蚁,艺,亿,忆,义,诣,议,谊,译,异,绎,荫,阴,银,饮,樱,婴,鹰,应,缨,莹,萤,营,荧,蝇,颖,哟,拥,佣,痈,踊,咏,涌,优,忧,邮,铀,犹,游,诱,舆,鱼,渔,娱,与,屿,语,吁,御,狱,誉,预,驭,鸳,渊,辕,园,员,圆,缘,远,愿,约,跃,钥,岳,粤,悦,阅,云,郧,匀,陨,运,蕴,酝,晕,韵,杂,灾,载,攒,暂,赞,赃,脏,凿,枣,灶,责,择,则,泽,贼,赠,扎,札,轧,铡,闸,诈,斋,债,毡,盏,斩,辗,崭,栈,战,绽,张,涨,帐,账,胀,赵,蛰,辙,锗,这,贞,针,侦,诊,镇,阵,挣,睁,狰,帧,郑,证,织,职,执,纸,挚,掷,帜,质,钟,终,种,肿,众,诌,轴,皱,昼,骤,猪,诸,诛,烛,瞩,嘱,贮,铸,筑,驻,专,砖,转,赚,桩,庄,装,妆,壮,状,锥,赘,坠,缀,谆,浊,兹,资,渍,踪,综,总,纵,邹,诅,组,钻,致,钟,么,为,只,凶,准,启,板,里,雳,余,链,泄";
        $t = "皚,藹,礙,愛,翺,襖,奧,壩,罷,擺,敗,頒,辦,絆,幫,綁,鎊,謗,剝,飽,寶,報,鮑,輩,貝,鋇,狽,備,憊,繃,筆,畢,斃,閉,邊,編,貶,變,辯,辮,鼈,癟,瀕,濱,賓,擯,餅,撥,缽,鉑,駁,蔔,補,參,蠶,殘,慚,慘,燦,蒼,艙,倉,滄,廁,側,冊,測,層,詫,攙,摻,蟬,饞,讒,纏,鏟,産,闡,顫,場,嘗,長,償,腸,廠,暢,鈔,車,徹,塵,陳,襯,撐,稱,懲,誠,騁,癡,遲,馳,恥,齒,熾,沖,蟲,寵,疇,躊,籌,綢,醜,櫥,廚,鋤,雛,礎,儲,觸,處,傳,瘡,闖,創,錘,純,綽,辭,詞,賜,聰,蔥,囪,從,叢,湊,竄,錯,達,帶,貸,擔,單,鄲,撣,膽,憚,誕,彈,當,擋,黨,蕩,檔,搗,島,禱,導,盜,燈,鄧,敵,滌,遞,締,點,墊,電,澱,釣,調,叠,諜,疊,釘,頂,錠,訂,東,動,棟,凍,鬥,犢,獨,讀,賭,鍍,鍛,斷,緞,兌,隊,對,噸,頓,鈍,奪,鵝,額,訛,惡,餓,兒,爾,餌,貳,發,罰,閥,琺,礬,釩,煩,範,販,飯,訪,紡,飛,廢,費,紛,墳,奮,憤,糞,豐,楓,鋒,風,瘋,馮,縫,諷,鳳,膚,輻,撫,輔,賦,複,負,訃,婦,縛,該,鈣,蓋,幹,趕,稈,贛,岡,剛,鋼,綱,崗,臯,鎬,擱,鴿,閣,鉻,個,給,龔,宮,鞏,貢,鈎,溝,構,購,夠,蠱,顧,剮,關,觀,館,慣,貫,廣,規,矽,歸,龜,閨,軌,詭,櫃,貴,劊,輥,滾,鍋,國,過,駭,韓,漢,閡,鶴,賀,橫,轟,鴻,紅,後,壺,護,滬,戶,嘩,華,畫,劃,話,懷,壞,歡,環,還,緩,換,喚,瘓,煥,渙,黃,謊,揮,輝,毀,賄,穢,會,燴,彙,諱,誨,繪,葷,渾,夥,獲,貨,禍,擊,機,積,饑,譏,雞,績,緝,極,輯,級,擠,幾,薊,劑,濟,計,記,際,繼,紀,夾,莢,頰,賈,鉀,價,駕,殲,監,堅,箋,間,艱,緘,繭,檢,堿,鹼,揀,撿,簡,儉,減,薦,檻,鑒,踐,賤,見,鍵,艦,劍,餞,漸,濺,澗,漿,蔣,槳,獎,講,醬,膠,澆,驕,嬌,攪,鉸,矯,僥,腳,餃,繳,絞,轎,較,稭,階,節,莖,驚,經,頸,靜,鏡,徑,痙,競,淨,糾,廄,舊,駒,舉,據,鋸,懼,劇,鵑,絹,傑,潔,結,誡,屆,緊,錦,僅,謹,進,晉,燼,盡,勁,荊,覺,決,訣,絕,鈞,軍,駿,開,凱,顆,殼,課,墾,懇,摳,庫,褲,誇,塊,儈,寬,礦,曠,況,虧,巋,窺,饋,潰,擴,闊,蠟,臘,萊,來,賴,藍,欄,攔,籃,闌,蘭,瀾,讕,攬,覽,懶,纜,爛,濫,撈,勞,澇,樂,鐳,壘,類,淚,籬,離,裏,鯉,禮,麗,厲,勵,礫,曆,瀝,隸,倆,聯,蓮,連,鐮,憐,漣,簾,斂,臉,鏈,戀,煉,練,糧,涼,兩,輛,諒,療,遼,鐐,獵,臨,鄰,鱗,凜,賃,齡,鈴,淩,靈,嶺,領,餾,劉,龍,聾,嚨,籠,壟,攏,隴,樓,婁,摟,簍,蘆,盧,顱,廬,爐,擄,鹵,虜,魯,賂,祿,錄,陸,驢,呂,鋁,侶,屢,縷,慮,濾,綠,巒,攣,孿,灤,亂,掄,輪,倫,侖,淪,綸,論,蘿,羅,邏,鑼,籮,騾,駱,絡,媽,瑪,碼,螞,馬,罵,嗎,買,麥,賣,邁,脈,瞞,饅,蠻,滿,謾,貓,錨,鉚,貿,麽,黴,沒,鎂,門,悶,們,錳,夢,謎,彌,覓,綿,緬,廟,滅,憫,閩,鳴,銘,謬,謀,畝,鈉,納,難,撓,腦,惱,鬧,餒,膩,攆,撚,釀,鳥,聶,齧,鑷,鎳,檸,獰,甯,擰,濘,鈕,紐,膿,濃,農,瘧,諾,歐,鷗,毆,嘔,漚,盤,龐,國,愛,賠,噴,鵬,騙,飄,頻,貧,蘋,憑,評,潑,頗,撲,鋪,樸,譜,臍,齊,騎,豈,啓,氣,棄,訖,牽,扡,釺,鉛,遷,簽,謙,錢,鉗,潛,淺,譴,塹,槍,嗆,牆,薔,強,搶,鍬,橋,喬,僑,翹,竅,竊,欽,親,輕,氫,傾,頃,請,慶,瓊,窮,趨,區,軀,驅,齲,顴,權,勸,卻,鵲,讓,饒,擾,繞,熱,韌,認,紉,榮,絨,軟,銳,閏,潤,灑,薩,鰓,賽,傘,喪,騷,掃,澀,殺,紗,篩,曬,閃,陝,贍,繕,傷,賞,燒,紹,賒,攝,懾,設,紳,審,嬸,腎,滲,聲,繩,勝,聖,師,獅,濕,詩,屍,時,蝕,實,識,駛,勢,釋,飾,視,試,壽,獸,樞,輸,書,贖,屬,術,樹,豎,數,帥,雙,誰,稅,順,說,碩,爍,絲,飼,聳,慫,頌,訟,誦,擻,蘇,訴,肅,雖,綏,歲,孫,損,筍,縮,瑣,鎖,獺,撻,擡,攤,貪,癱,灘,壇,譚,談,歎,湯,燙,濤,縧,騰,謄,銻,題,體,屜,條,貼,鐵,廳,聽,烴,銅,統,頭,圖,塗,團,頹,蛻,脫,鴕,馱,駝,橢,窪,襪,彎,灣,頑,萬,網,韋,違,圍,爲,濰,維,葦,偉,僞,緯,謂,衛,溫,聞,紋,穩,問,甕,撾,蝸,渦,窩,嗚,鎢,烏,誣,無,蕪,吳,塢,霧,務,誤,錫,犧,襲,習,銑,戲,細,蝦,轄,峽,俠,狹,廈,鍁,鮮,纖,鹹,賢,銜,閑,顯,險,現,獻,縣,餡,羨,憲,線,廂,鑲,鄉,詳,響,項,蕭,銷,曉,嘯,蠍,協,挾,攜,脅,諧,寫,瀉,謝,鋅,釁,興,洶,鏽,繡,虛,噓,須,許,緒,續,軒,懸,選,癬,絢,學,勳,詢,尋,馴,訓,訊,遜,壓,鴉,鴨,啞,亞,訝,閹,煙,鹽,嚴,顔,閻,豔,厭,硯,彥,諺,驗,鴦,楊,揚,瘍,陽,癢,養,樣,瑤,搖,堯,遙,窯,謠,藥,爺,頁,業,葉,醫,銥,頤,遺,儀,彜,蟻,藝,億,憶,義,詣,議,誼,譯,異,繹,蔭,陰,銀,飲,櫻,嬰,鷹,應,纓,瑩,螢,營,熒,蠅,穎,喲,擁,傭,癰,踴,詠,湧,優,憂,郵,鈾,猶,遊,誘,輿,魚,漁,娛,與,嶼,語,籲,禦,獄,譽,預,馭,鴛,淵,轅,園,員,圓,緣,遠,願,約,躍,鑰,嶽,粵,悅,閱,雲,鄖,勻,隕,運,蘊,醞,暈,韻,雜,災,載,攢,暫,贊,贓,髒,鑿,棗,竈,責,擇,則,澤,賊,贈,紮,劄,軋,鍘,閘,詐,齋,債,氈,盞,斬,輾,嶄,棧,戰,綻,張,漲,帳,賬,脹,趙,蟄,轍,鍺,這,貞,針,偵,診,鎮,陣,掙,睜,猙,幀,鄭,證,織,職,執,紙,摯,擲,幟,質,鍾,終,種,腫,衆,謅,軸,皺,晝,驟,豬,諸,誅,燭,矚,囑,貯,鑄,築,駐,專,磚,轉,賺,樁,莊,裝,妝,壯,狀,錐,贅,墜,綴,諄,濁,茲,資,漬,蹤,綜,總,縱,鄒,詛,組,鑽,緻,鐘,麼,為,隻,兇,準,啟,闆,裡,靂,餘,鍊,洩";
        $sarr = explode(',', $s);
        $tarr = explode(',', $t);
        $convert_map = array_combine($sarr, $tarr);
        }
        
        // 使用strtr进行更高效的字符串替换
        return strtr($string, $convert_map);
    }
}

if (!function_exists('_Array_Combine')) {
    function _Array_Combine($_Arr1, $_Arr2)
    {
        for ($i = 0; $i < count($_Arr1); $i++) $_Res[$_Arr1[$i]] = $_Arr2[$i];
        return $_Res;
    }
}

if (!function_exists('_U2_Utf8_Gb')) {
    function _U2_Utf8_Gb($_C)
    {
        $_String = '';
        if ($_C < 0x80) {
            $_String .= $_C;
        } elseif ($_C < 0x800) {
            $_String .= chr(0xC0 | $_C >> 6);
            $_String .= chr(0x80 | $_C & 0x3F);
        } elseif ($_C < 0x10000) {
            $_String .= chr(0xE0 | $_C >> 12);
            $_String .= chr(0x80 | $_C >> 6 & 0x3F);
            $_String .= chr(0x80 | $_C & 0x3F);
        } elseif ($_C < 0x200000) {
            $_String .= chr(0xF0 | $_C >> 18);
            $_String .= chr(0x80 | $_C >> 12 & 0x3F);
            $_String .= chr(0x80 | $_C >> 6 & 0x3F);
            $_String .= chr(0x80 | $_C & 0x3F);
        }
        return iconv('UTF-8', 'GB2312', $_String);
    }
}

if (!function_exists('_Pinyin')) {
    function _Pinyin($_Num, $_Data)
    {
        if ($_Num > 0 && $_Num < 160) {
            return chr($_Num);
        } elseif ($_Num < -20319 || $_Num > -10247) {
            return '';
        } else {
            foreach ($_Data as $k => $v) {
                if ($v <= $_Num) break;
            }
            return $k;
        }
    }
}
if (!function_exists('getJsData')) { 
    //定义一个参数
    function getJsData()
    {
        $sTermInfo = array(0, 21198, 42461, 63813, 85308, 106961, 128806, 150834, 173055, 195433, 217965, 240558, 263215, 285853, 308440, 330912, 353245, 375400, 397371, 419149, 440750, 462193, 483513, 504747);
        return $sTermInfo;
    }
}


//仿写js计算日历的方法
if (!function_exists('wzc')) {
    function wzc($year, $num, $type = 0)
    {
        $sTermInfo = getJsData();
        $MicroSecond      = (31556956000 * ($year - 1882) + $sTermInfo[$num] * 60000) + -2776572000000;
        $data             = $MicroSecond;
        if ($type > 0) {
            $time             = $MicroSecond / 1000;
            $date             = date('Y/m/d/H/i/s', $time);
            $array_date       = explode('/', $date);
            if ($type == 1) {  //时间格式
                $data  = $num . '/(' . $array_date[1] . "月" . $array_date[2] . "日" . $array_date[3] . ":" . $array_date[4] . ')';
            }
            if ($type == 2) {  //日
                $data  = $array_date[2];
            }
            if ($type == 3) {  //时分
                $data = $array_date[3] * 60 + $array_date[4];
            }
        }
        return $data;
    }
}

//起运基数与月支基数函数
if (!function_exists('getMzQyjs')) {
    function getMzQyjs($bzmonth, $bzday, $mz_qyjs = 0)
    {
        $md = $bzmonth * 100 + $bzday;
        if ($md >= 204 && $md <= 305) {
            $mz = 3;
            $qyjs = floor((($bzmonth - 2) * 30 + $bzday - 4) / 3);
        }
        if ($md >= 306 && $md <= 404) {
            $mz = 4;
            $qyjs = floor((($bzmonth - 3) * 30 + $bzday - 6) / 3);
        }

        if ($md >= 405 && $md <= 504) {
            $mz = 5;
            $qyjs = floor((($bzmonth - 4) * 30 + $bzday - 5) / 3);
        }

        if ($md >= 505 && $md <= 605) {
            $mz = 6;
            $qyjs = floor((($bzmonth - 5) * 30 + $bzday - 5) / 3);
        }

        if ($md >= 606 && $md <= 706) {
            $mz = 7;
            $qyjs = floor((($bzmonth - 6) * 30 + $bzday - 6) / 3);
        }

        if ($md >= 707 && $md <= 807) {
            $mz = 8;
            $qyjs = floor((($bzmonth - 7) * 30 + $bzday - 7) / 3);
        }

        if ($md >= 808 && $md <= 907) {
            $mz = 9;
            $qyjs = floor((($bzmonth - 8) * 30 + $bzday - 8) / 3);
        }

        if ($md >= 908 && $md <= 1007) {
            $mz = 10;
            $qyjs = floor((($bzmonth - 9) * 30 + $bzday - 8) / 3);
        }

        if ($md >= 1008 && $md <= 1106) {
            $mz = 11;
            $qyjs = floor((($bzmonth - 10) * 30 + $bzday - 8) / 3);
        }

        if ($md >= 1107 && $md <= 1207) {
            $mz = 0;
            $qyjs = floor((($bzmonth - 11) * 30 + $bzday - 7) / 3);
        }

        if ($md >= 1208 && $md <= 1231) {
            $mz = 1;
            $qyjs = floor(($bzday - 8) / 3);
        }

        if ($md >= 101 && $md <= 105) {
            $mz = 1;
            $qyjs = floor((30 + $bzday - 4) / 3);
        }

        if ($md >= 106 && $md <= 203) {
            $mz = 2;
            $qyjs = floor((($bzmonth - 1) * 30 + $bzday - 6) / 3);
        }
        if ($mz_qyjs == 1) {
            return $mz;
        } else {
            return $qyjs;
        }
    }
}

//计算年月日时 支纳干 dzg
if (!function_exists('getDateNs')) {
    function getDateNs($Ns)
    {
        $zg = 0;
        switch ($Ns) {
            case 1:
                $zg = 0;
                break;
            case 2:
                $zg = 6;
                break;
            case 8:
                $zg = 6;
                break;
            case 3:
                $zg = 1;
                break;
            case 4:
                $zg = 2;
                break;
            case 5:
                $zg = 5;
                break;
            case 11:
                $zg = 5;
                break;
            case 6:
                $zg = 3;
                break;
            case 7:
                $zg = 4;
                break;
            case 9:
                $zg = 7;
                break;
            case 10:
                $zg = 8;
                break;
            case 0:
                $zg = 9;
                break;
        }
        return $zg;
    }
}

//仿写Js获取未知的日期参数
if (!function_exists('getCDate')) {
    function getCDate($year, $month, $day, $hour, $min = 0)
    {
        date_default_timezone_set("UTC");
        $cY = 0;
        $cM = 0;
        $cD = 0;
        $cH = 0;
        $mc = $month;
        $m = $month - 1;
        $d = $day - 1;
        $h = $hour;
        $i = $min;
        if ($m < 2) {
            $cY = $year - 1900 + 36 - 1;
        } else {
            $cY = $year - 1900 + 36;
        }
        $term2 = wzc($year, 2, 2);
        if ($m == 1 && ($d + 1) > $term2) {
            $cY = $year - 1900 + 36;
        }
        if ($m == 1 && ($d + 1) == $term2 && ($h * 60 + $i) >= wzc($year, 2, 3)) {
            $cY = $year - 1900 + 36;
        }
        if ($m < 0) {
            $m = $m + 1;
        }
        $firstNode = wzc($year, $m * 2, 2);

        $cM = ($year - 1900) * 12 + $m + 12;
        if ($h < 0) {
            $nh_num = $h * 3600;
            $up_h = 0;
            $format_date = $year . '-' . $mc . '-' . ($d + 1) . ' ' . $up_h . ':' . $i . ':00';
            $utcTime     = (strtotime($format_date) + $nh_num) * 1000;
        } else {
            $format_date = $year . '-' . $mc . '-' . ($d + 1) . ' ' . $h . ':' . $i . ':00';
            $utcTime     = strtotime($format_date) * 1000;
        }
        if ($m * 2 - 2 >= 0) {
            $start_term  = ($utcTime - wzc($year, $m * 2 - 2, 0)) / 1000;
            $start_term1 = wzc($year, $m * 2 - 2, 1);
        }
        if ($m == 0) {
            $start_term  = ($utcTime - wzc($year - 1, 22, 0)) / 1000;
            $start_term1 = wzc($year - 1, 22, 1);
        }
        $end_term  = (wzc($year, $m * 2, 0) - $utcTime) / 1000;
        $end_term1 = wzc($year, $m * 2, 1);
        if (($d + 1) > $firstNode || (($d + 1) == $firstNode && ($h * 60 + $i * 1) >= wzc($year, $m * 2, 3))) {
            $cM = ($year - 1900) * 12 + $m + 13;
            $start_term  = ($utcTime - wzc($year, $m * 2, 0)) / 1000;
            $start_term1 = wzc($year, $m * 2, 1);
            if ($m != 11) {
                $end_term    = (wzc($year, $m * 2 + 2, 0) - $utcTime) / 1000;
                $end_term1   = wzc($year, $m * 2 + 2, 1);
            } elseif ($m == 11) {
                $end_term  = (wzc($year + 1, 0, 0) - $utcTime) / 1000;
                $end_term1 = wzc($year + 1, 0, 1);
            }
        }
        if ($start_term < 0) {
            $start_term = 0;
        }
        if ($end_term < 0) {
            $end_term = 0;
        }
        $format_date2 = $year . '-' . $mc . '-01 00:00:00';
        $utcTime2     = strtotime($format_date2) * 1000;
        $dayCyclical  = $utcTime2 / 86400000 + 25567 + 10;
        $cD           = $dayCyclical + $d;
        if ($h >= 23) {
            $cD = $dayCyclical + $d + 1;
        }
        $nh = 0;
        $nh_num = 0;
        if ($h == 1 || ($h < 24 && $h % 2 == 1)) {
            $nh = $h - 1;
            $nh = $nh + 2;
        } else {
            $nh = $h;
        }
        if ($nh < 0) {
            $nh_num = $nh * 3600;
            $nh = 0;
        }
        $format_date3  = $year . '-' . $mc . '-' . ($d + 1) . ' ' . $nh . ':00:00';
        $utcTime3      = (strtotime($format_date3) + $nh_num) * 1000;
        $houseCyclical = ($utcTime3 / 3600000 + 25567 * 24) / 2;
        $cH = $houseCyclical;
        $data['y'] = $year;
        $data['m'] = $month;
        $data['d'] = $day;
        $l_data    = lll($year, $month, $day, $hour);
        $term1     = wzc($year, $m * 2, 1);
        $term2     = wzc($year, $m * 2 + 1, 1);
        return array(
            'year'  => $year,
            'month' => $month,
            'day'   => $day,
            'hour'  => $hour,
            'min'   => $min,
            'cY'    => $cY,
            'cM'    => $cM,
            'cD'    => $cD,
            'cH'    => $cH,
            'term1' => $term1,
            'term2' => $term2,
            'start_term'    => $start_term,
            'end_term'      => $end_term,
            'start_term1'   => $start_term1,
            'end_term1'     => $end_term1,
            'lDate'         => $l_data['lDate'],
            'lunar_month_type'  => $l_data['lunar_month_type'],
            'lunar_year'        => $l_data['lunar_year'],
            'lunar_month'       => $l_data['lunar_month'],
            'lunar_day'         => $l_data['lunar_day'],
            'lunar_hour'        => $l_data['lunar_hour'],
        );
    }
}
//统一为js计算时间的方法
if (!function_exists('lYearDays')) {
    function lYearDays($year)
    {
        $i = 0;
        $sum = 348;
        $lunarInfo  = array(0x4bd8, 0x4ae0, 0xa570, 0x54d5, 0xd260, 0xd950, 0x5554, 0x56af, 0x9ad0, 0x55d2, 0x4ae0, 0xa5b6, 0xa4d0, 0xd250, 0xd295, 0xb54f, 0xd6a0, 0xada2, 0x95b0, 0x4977, 0x497f, 0xa4b0, 0xb4b5, 0x6a50, 0x6d40, 0xab54, 0x2b6f, 0x9570, 0x52f2, 0x4970, 0x6566, 0xd4a0, 0xea50, 0x6a95, 0x5adf, 0x2b60, 0x86e3, 0x92ef, 0xc8d7, 0xc95f, 0xd4a0, 0xd8a6, 0xb55f, 0x56a0, 0xa5b4, 0x25df, 0x92d0, 0xd2b2, 0xa950, 0xb557, 0x6ca0, 0xb550, 0x5355, 0x4daf, 0xa5b0, 0x4573, 0x52bf, 0xa9a8, 0xe950, 0x6aa0, 0xaea6, 0xab50, 0x4b60, 0xaae4, 0xa570, 0x5260, 0xf263, 0xd950, 0x5b57, 0x56a0, 0x96d0, 0x4dd5, 0x4ad0, 0xa4d0, 0xd4d4, 0xd250, 0xd558, 0xb540, 0xb6a0, 0x95a6, 0x95bf, 0x49b0, 0xa974, 0xa4b0, 0xb27a, 0x6a50, 0x6d40, 0xaf46, 0xab60, 0x9570, 0x4af5, 0x4970, 0x64b0, 0x74a3, 0xea50, 0x6b58, 0x5ac0, 0xab60, 0x96d5, 0x92e0, 0xc960, 0xd954, 0xd4a0, 0xda50, 0x7552, 0x56a0, 0xabb7, 0x25d0, 0x92d0, 0xcab5, 0xa950, 0xb4a0, 0xbaa4, 0xad50, 0x55d9, 0x4ba0, 0xa5b0, 0x5176, 0x52bf, 0xa930, 0x7954, 0x6aa0, 0xad50, 0x5b52, 0x4b60, 0xa6e6, 0xa4e0, 0xd260, 0xea65, 0xd530, 0x5aa0, 0x76a3, 0x96d0, 0x4afb, 0x4ad0, 0xa4d0, 0xd0b6, 0xd25f, 0xd520, 0xdd45, 0xb5a0, 0x56d0, 0x55b2, 0x49b0, 0xa577, 0xa4b0, 0xaa50, 0xb255, 0x6d2f, 0xada0, 0x4b63, 0x937f, 0x49f8, 0x4970, 0x64b0, 0x68a6, 0xea5f, 0x6b20, 0xa6c4, 0xaaef, 0x92e0, 0xd2e3, 0xc960, 0xd557, 0xd4a0, 0xda50, 0x5d55, 0x56a0, 0xa6d0, 0x55d4, 0x52d0, 0xa9b8, 0xa950, 0xb4a0, 0xb6a6, 0xad50, 0x55a0, 0xaba4, 0xa5b0, 0x52b0, 0xb273, 0x6930, 0x7337, 0x6aa0, 0xad50, 0x4b55, 0x4b6f, 0xa570, 0x54e4, 0xd260, 0xe968, 0xd520, 0xdaa0, 0x6aa6, 0x56df, 0x4ae0, 0xa9d4, 0xa4d0, 0xd150, 0xf252, 0xd520);
        for ($i = 0x8000; $i > 0x8; $i >>= 1) {
            $sum += ($lunarInfo[$year - 1900] & $i) ? 1 : 0;
        }
        return ($sum + leapDays($year, $lunarInfo));
    }
}
if (!function_exists('leapDays')) {
    function leapDays($year, $lunarInfo)
    {
        if (leapMonth($year, $lunarInfo)) {
            return (($lunarInfo[$year - 1899] & 0xf) == 0xf ? 30 : 29);
        } else {
            return (0);
        }
    }
}
if (!function_exists('leapMonth')) {
    function leapMonth($year, $lunarInfo)
    {
        $lm = $lunarInfo[$year - 1900] & 0xf;
        if ($lm == 0xf) {
            $num = 0;
        } else {
            $num = $lm;
        }
        return $num;
    }
}
if (!function_exists('monthDays')) {
    function monthDays($year, $month, $lunarInfo)
    {
        if ($lunarInfo[$year - 1900] & (0x10000 >> $month)) {
            $num = 30;
        } else {
            $num = 29;
        }
        return $num;
    }
}
if (!function_exists('Lunar')) {
    function Lunar($year, $month, $day, $hours)
    {
        date_default_timezone_set("UTC");
        $i      = 0;
        $leap   = 0;
        $temp   = 0;
        $utcTime      = -2206396800;
        $day          = $day - 1;
        if ($hours < 0) {
            $up_hours = $hours * 3600;
            $format_date1 = $year . '-' . $month . '-' . $day . ' 0:0:0';
            $utcTime1     = strtotime($format_date1) + $up_hours - 8 * 3600;
            $date_year    = date('Y', $utcTime1);
            if ($date_year != $year) {
                $date         = date('Y-m-d', $utcTime1);
                $utcTime1     = strtotime($date);
            }
        } else {
            $format_date1 = $year . '-' . $month . '-' . $day . ' ' . $hours . ':0:0';
            $utcTime1     = strtotime($format_date1);
        }
        $offset       = ($utcTime1 - $utcTime) / 86400;
        if ($hours >= 23) {
            $offset++;
        }
        for ($i = 1900; $i < 2100 && $offset > 0; $i++) {
            $temp = lYearDays($i);
            $offset -= $temp;
        }
        if ($offset < 0) {
            $offset += $temp;
            $i--;
        }
        $lunarInfo  = array(0x4bd8, 0x4ae0, 0xa570, 0x54d5, 0xd260, 0xd950, 0x5554, 0x56af, 0x9ad0, 0x55d2, 0x4ae0, 0xa5b6, 0xa4d0, 0xd250, 0xd295, 0xb54f, 0xd6a0, 0xada2, 0x95b0, 0x4977, 0x497f, 0xa4b0, 0xb4b5, 0x6a50, 0x6d40, 0xab54, 0x2b6f, 0x9570, 0x52f2, 0x4970, 0x6566, 0xd4a0, 0xea50, 0x6a95, 0x5adf, 0x2b60, 0x86e3, 0x92ef, 0xc8d7, 0xc95f, 0xd4a0, 0xd8a6, 0xb55f, 0x56a0, 0xa5b4, 0x25df, 0x92d0, 0xd2b2, 0xa950, 0xb557, 0x6ca0, 0xb550, 0x5355, 0x4daf, 0xa5b0, 0x4573, 0x52bf, 0xa9a8, 0xe950, 0x6aa0, 0xaea6, 0xab50, 0x4b60, 0xaae4, 0xa570, 0x5260, 0xf263, 0xd950, 0x5b57, 0x56a0, 0x96d0, 0x4dd5, 0x4ad0, 0xa4d0, 0xd4d4, 0xd250, 0xd558, 0xb540, 0xb6a0, 0x95a6, 0x95bf, 0x49b0, 0xa974, 0xa4b0, 0xb27a, 0x6a50, 0x6d40, 0xaf46, 0xab60, 0x9570, 0x4af5, 0x4970, 0x64b0, 0x74a3, 0xea50, 0x6b58, 0x5ac0, 0xab60, 0x96d5, 0x92e0, 0xc960, 0xd954, 0xd4a0, 0xda50, 0x7552, 0x56a0, 0xabb7, 0x25d0, 0x92d0, 0xcab5, 0xa950, 0xb4a0, 0xbaa4, 0xad50, 0x55d9, 0x4ba0, 0xa5b0, 0x5176, 0x52bf, 0xa930, 0x7954, 0x6aa0, 0xad50, 0x5b52, 0x4b60, 0xa6e6, 0xa4e0, 0xd260, 0xea65, 0xd530, 0x5aa0, 0x76a3, 0x96d0, 0x4afb, 0x4ad0, 0xa4d0, 0xd0b6, 0xd25f, 0xd520, 0xdd45, 0xb5a0, 0x56d0, 0x55b2, 0x49b0, 0xa577, 0xa4b0, 0xaa50, 0xb255, 0x6d2f, 0xada0, 0x4b63, 0x937f, 0x49f8, 0x4970, 0x64b0, 0x68a6, 0xea5f, 0x6b20, 0xa6c4, 0xaaef, 0x92e0, 0xd2e3, 0xc960, 0xd557, 0xd4a0, 0xda50, 0x5d55, 0x56a0, 0xa6d0, 0x55d4, 0x52d0, 0xa9b8, 0xa950, 0xb4a0, 0xb6a6, 0xad50, 0x55a0, 0xaba4, 0xa5b0, 0x52b0, 0xb273, 0x6930, 0x7337, 0x6aa0, 0xad50, 0x4b55, 0x4b6f, 0xa570, 0x54e4, 0xd260, 0xe968, 0xd520, 0xdaa0, 0x6aa6, 0x56df, 0x4ae0, 0xa9d4, 0xa4d0, 0xd150, 0xf252, 0xd520);
        $year       = $i;
        $leap       = leapMonth($i, $lunarInfo);
        $isLeap     = false;
        for ($i = 1; $i < 13 && $offset > 0; $i++) {
            if ($leap > 0 && $i == ($leap + 1) && $isLeap == false) {
                --$i;
                $isLeap = true;
                $temp = leapDays($year, $lunarInfo);
            } else {
                $temp = monthDays($year, $i, $lunarInfo);
            }
            if ($isLeap == true && $i == ($leap + 1)) {
                $isLeap = false;
            }
            $offset -= $temp;
        }
        if ($offset == 0 && $leap > 0 && $i == $leap + 1) if ($isLeap) {
            $isLeap = false;
        } else {
            $isLeap = true;
            --$i;
        }
        if ($offset < 0) {
            $offset += $temp;
            --$i;
        }
        $month = $i;
        $day   = $offset + 1;
        $is_Leap = 1;
        if (!$isLeap) {
            $is_Leap = 0;
        }
        return array('year' => $year, 'month' => $month, 'day' => $day, 'isLeap' => $is_Leap);
    }
}
if (!function_exists('cYear')) {
    function cYear($year)
    {
        $nStr1 = array('零', '一', '二', '三', '四', '五', '六', '七', '八', '九', '十');
        $s  = 0;
        $s  = $nStr1[floor($year / 1000)];
        $s .= $nStr1[floor(($year % 1000) / 100)];
        $s .= $nStr1[floor(($year % 100) / 10)];
        $s .= $nStr1[$year % 10];
        return ($s);
    }
}
if (!function_exists('cDay')) {
    function cDay($day)
    {
        $nStr1      = array('零', '一', '二', '三', '四', '五', '六', '七', '八', '九', '十');
        $nStr2      = array('初', '十', '廿', '卅');
        $s = '';
        switch ($day) {
            case 10:
                $s = '初十';
                break;
            case 20:
                $s = '二十';
                break;
            case 30:
                $s = '三十';
                break;
            default:
                $s  = $nStr2[floor($day / 10)];
                $s .= $nStr1[$day % 10];
        }
        return ($s);
    }
}

if (!function_exists('lll')) {
    function lll($year, $month, $day, $hours)
    {
        $hh = ["00:00-00:59早子", "01:00-01:59丑", "02:00-02:59丑", "03:00-03:59寅", "04:00-04:59寅", "05:00-05:59卯", "06:00-06:59卯", "07:00-07:59辰", "08:00-08:59辰", "09:00-09:59巳", "10:00-10:59巳", "11:00-11:59午", "12:00-12:59午", "13:00-13:59未", "14:00-14:59未", "15:00-15:59申", "16:00-16:59申", "17:00-17:59酉", "18:00-18:59酉", "19:00-19:59戌", "20:00-20:59戌", "21:00-21:59亥", "22:00-22:59亥", "23:00-23:59晚子"];
        $monthName  = array("", "正", "二", "三", "四", "五", "六", "七", "八", "九", "十", "十一", "十二");
        $lDObj      = Lunar($year, $month, $day + 1, $hours);
        $y          = $lDObj['year'];
        $m          = $lDObj['month'];
        $d          = $lDObj['day'];
        $isLeap     = $lDObj['isLeap'];
        $lY         = cYear($y);
        $lM         = $monthName[$m];
        $lD         = cDay($d);
        $lL         = $isLeap == 1 ? '闰' : '';
        if ($hours >= 0 && $hours < 24) {
            $lS = $hh[$hours];
        } else {
            $lS = '未知';
        }
        $lH = $isLeap == 1 ? '闰' : '平';
        return array(
            'lDate'             => $lY . '年' . $lL . $lM . '月' . $lD . '日' . ' ' . $lS . '时',
            'lunar_month_type'  => $lH,
            'lunar_year'        => $lY,
            'lunar_month'       => $lM,
            'lunar_day'         => $lD,
            'lunar_hour'        => $lS,
        );
    }
}
//统一为js计算时间的方法  end
/***
 *计算字符串出现的字符次数
 */
if (!function_exists('howstring')) {
    function howstring($array, $wh)
    {
        $b      = array_count_values($array);
        $is_num = isset($b[$wh]);
        if (!$is_num) {
            $b[$wh] = 0;
        }
        $c  =   $b[$wh];
        if ($c == '') {
            $c = 0;
        }
        return $c;
    }
}

if (!function_exists('whwq_ff')) {
    function whwq_ff($jin, $mu, $shui, $huo, $tu)
    {
        $quewh = '<strong>五行齐全</strong>';
        if ($jin >= 1) {
            $wangwh = '<strong>金</strong>旺';
            $wangwhs = '金';
        } elseif ($jin == 0) {
            $quewh = '缺<strong>金</strong>';
        }

        if ($mu >= 3) {
            $wangwh = '<strong>木</strong>旺';
            $wangwhs = '木';
        } elseif ($mu == 0) {
            $quewh = '缺<strong>木</strong>';
        }
        if ($shui >= 3) {
            $wangwh = '<strong>水</strong>旺';
            $wangwhs = '水';
        } elseif ($shui == 0) {
            $quewh = '缺<strong>水</strong>';
        }

        if ($huo >= 3) {
            $wangwh = '<strong>火</strong>旺';
            $wangwhs = '火';
        } elseif ($huo == 0) {
            $quewh = '缺<strong>火</strong>';
        }

        if ($tu >= 3) {
            $wangwh = '<strong>土</strong>旺';
            $wangwhs = '土';
        } elseif ($tu == 0) {
            $quewh = '缺<strong>土</strong>';
        }
        $arr['wang'] = $wangwh;
        $arr['que']  = $quewh;
        $whjk        = Db::name('Sm_whjk')->where('wh', '=', $wangwhs)->find();
        $arr['whjk'] = $whjk;
        return $arr;
    }
}

//PHP获取UTC时间
if (!function_exists('utcTime')) {
    function utcTime()
    {
        date_default_timezone_set('UTC');
        $timestamp = new \DateTime();
        $timeStr = $timestamp->format("Y-m-d H:i:s");
        return $timeStr;
    }
}

if (!function_exists('birthday')) {
    function birthday($mydate, $symbol = '-')
    {
        //计算年龄
        $birth      = $mydate;
        list($by, $bm, $bd) = explode($symbol, $birth);
        $cm   = date('n');
        $cd   = date('j');
        $age  = date('Y') - $by - 1;
        if ($cm > $bm || $cm == $bm && $cd > $bd) $age++;
        $array['age'] = $age;
        //计算星座
        $constellation_name = array(
            '水瓶座',
            '双鱼座',
            '白羊座',
            '金牛座',
            '双子座',
            '巨蟹座',
            '狮子座',
            '处女座',
            '天秤座',
            '天蝎座)',
            '射手座',
            '摩羯座'
        );
        if ($bd <= 22) {
            if ('1' !== $bm) $constellation = $constellation_name[$bm - 2];
            else $constellation = $constellation_name[11];
        } else $constellation = $constellation_name[$bm - 1];
        $array['constellation'] = $constellation;
        return $array;
    }
}
//车牌号格式化
if (!function_exists('findNum')) {
    function findNum($str = '')
    {
        return preg_replace('/\D/s', '', $str);
    }
}

//号码格式化
if (!function_exists('hmjx_word')) {
    function hmjx_word($word)
    {
        $word = intval($word);
        $word = $word / 80;
        $temp = intval($word);
        $word = $word - $temp;
        $word = intval($word * 80);
        if ($word == "0") {
            $word = "81";
        }
        return $word;
    }
}

if (!function_exists('seo_info')) {
    function seo_info($tid, $category = 0)
    {
        $data = Db::name('Sm_class')->where('id', '=', $tid)->find();
        if ($category != 0) {                 //文章二级分类
            $category_Arr = array(1 => '白羊座', 2 => '金牛座', 3 => '双子座', 4 => '巨蟹座', 5 => '狮子座', 6 => '处女座', 7 => '天秤座', 8 => '天蝎座', 9 => '射手座', 10 => '摩羯座', 11 => '水瓶座', 12 => '双鱼座', 13 => '鼠', 14 => '牛', 15 => '虎', 16 => '兔', 17 => '龙', 18 => '蛇', 19 => '马', 20 => '羊', 21 => '猴', 22 => '鸡', 23 => '狗', 24 => '猪', '25' => '星座爱情', 26 => '星座性格', 27 => '星座时尚', 28 => '星座开运', 29 => '星座知识', 30 => '星座情感', 31 => '爱情测试', 32 => '性格测试', 33 => '财富测试', 34 => '智商测试', 35 => '家居', 36 => '事业', 37 => '爱情', 38 => '灵异'); //该变量应该从后台ctl_news.php获取
            $data['name']           = $category_Arr[$category] . '-' . $data['name'];
            $data['title']          = $category_Arr[$category] . '，' . $data['title'];
            $data['keywords']       = $category_Arr[$category] . '，' . $data['keywords'];
            $data['description']    = $category_Arr[$category] . '，' . $data['description'];
        }
        return $data;
    }
}
//检查是否为复姓
if (!function_exists('splitName')) {
    function splitName($fullname)
    {
        $hyphenated = array(
            '欧阳',
            '太史',
            '端木',
            '上官',
            '司马',
            '东方',
            '独孤',
            '南宫',
            '万俟',
            '闻人',
            '夏侯',
            '诸葛',
            '尉迟',
            '公羊',
            '赫连',
            '澹台',
            '皇甫',
            '宗政',
            '濮阳',
            '公冶',
            '太叔',
            '申屠',
            '公孙',
            '慕容',
            '仲孙',
            '钟离',
            '长孙',
            '宇文',
            '城池',
            '司徒',
            '鲜于',
            '司空',
            '汝嫣',
            '闾丘',
            '子车',
            '亓官',
            '司寇',
            '巫马',
            '公西',
            '颛孙',
            '壤驷',
            '公良',
            '漆雕',
            '乐正',
            '宰父',
            '谷梁',
            '拓跋',
            '夹谷',
            '轩辕',
            '令狐',
            '段干',
            '百里',
            '呼延',
            '东郭',
            '南门',
            '羊舌',
            '微生',
            '公户',
            '公玉',
            '公仪',
            '梁丘',
            '公仲',
            '公上',
            '公门',
            '公山',
            '公坚',
            '左丘',
            '公伯',
            '西门',
            '公祖',
            '第五',
            '公乘',
            '贯丘',
            '公皙',
            '南荣',
            '东里',
            '东宫',
            '仲长',
            '子书',
            '子桑',
            '即墨',
            '达奚',
            '褚师'
        );
        $vLength = mb_strlen($fullname, 'utf-8');
        $lastname   = '';
        $firstname  = ''; //前为姓,后为名 
        if ($vLength > 2) {
            $preTwoWords = mb_substr($fullname, 0, 2, 'utf-8'); //取命名的前两个字,看是否在复姓库中 
            if (in_array($preTwoWords, $hyphenated)) {
                $lastname = $preTwoWords;
                $firstname = mb_substr($fullname, 2, 10, 'utf-8');
            } else {
                $lastname = mb_substr($fullname, 0, 1, 'utf-8');
                $firstname = mb_substr($fullname, 1, 10, 'utf-8');
            }
        } else if ($vLength == 2) { //全名只有两个字时,以前一个为姓,后一下为名 
            $lastname = mb_substr($fullname, 0, 1, 'utf-8');
            $firstname = mb_substr($fullname, 1, 10, 'utf-8');
        } else {
            $lastname = $fullname;
        }
        return array($lastname, $firstname);
    }
}


if(!function_exists('getTreeFlat')){
    function getTreeFlat($table, $filed, $pid = 0, $level = 0, $order = '', $map = [], $v_filed = 'name', &$result = [])
    {
        if ($level == 0) {
            $maps = $map;
            foreach ($maps as $k => &$v) {
                if ($v[0] == 'mid') {
                    unset($maps[$k]);
                }
                if ($v[0] == 'parentid') {
                    $v[0] = 'id';
                }
                if ($v[0] == 'pid') {
                    $v[0] = 'id';
                }
            }
        } else {
            $maps = $map;
        }
        // 根据你的实际表名调整
        $rows = Db::name($table)
            ->where('is_del', 0)
            ->where($filed, $pid)
            ->where($maps)
            ->order($order)
            ->select();

        $level_name  = '顶级分类';
        if ($level > 0) {
            $lines = [
                1 => ' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;一级分类',
                2 => ' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;二级分类',
                3 => ' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;三级分类',
                4 => '四级分类'
            ];
            $level_name = $lines[$level];
        }
        $parent_name = '';
        if ($pid > 0) {
            $parent_name = Db::name($table)->where('id', $pid)->value($v_filed);
        }
        foreach ($rows as $row) {

            $row['level_name']  = $level_name;          // 方便前端缩进或样式
            $row['parent_name'] = $parent_name;          // 方便前端缩进或样式
            $result[]     = $row;            // 压入结果集
            // 递归取子级
            getTreeFlat($table, $filed, $row['id'], $level + 1, $order, $map, $v_filed, $result);
        }
        return $result;
    }
}

if(!function_exists('cyclical')){
    function cyclical($num)
    {    
        $Gan = array("甲", "乙", "丙", "丁", "戊", "己", "庚", "辛", "壬", "癸");
        $Zhi = array("子", "丑", "寅", "卯", "辰", "巳", "午", "未", "申", "酉", "戌", "亥");
        return ($Gan[$num % 10] . $Zhi[$num % 12]);
    }
}
if(!function_exists('zhi2ganSN')){
    function zhi2ganSN($y12,$m12,$whNum)
    {
        $zhi2ganNum = array(1, 3, 2, 1, 3, 2, 1, 3, 2, 1, 3, 2);
        $zhi2gan    = array(array(9), array(9, 7, 5), array(2, 0), array(1), array(1, 9, 4), array(6, 2), array(3), array(3, 1, 5), array(8, 6), array(7), array(7, 3, 4), array(0, 8));
        $zhiNum     = array(
            array(1.2, 1.1, 1, 1, 1.04, 1.06, 1, 1, 1.2, 1.2, 1.06, 1.14), 
            array(0.36, 0.33, 0.3, 0.3, 0.312, 0.318, 0.3, 0.3, 0.36, 0.36, 0.318, 0.342), 
            array(0.2, 0.228, 0.2, 0.2, 0.23, 0.212, 0.2, 0.22, 0.228, 0.248, 0.232, 0.2), 
            array(0.5, 0.55, 0.53, 0.5, 0.55, 0.57, 0.6, 0.58, 0.5, 0.5, 0.57, 0.5), 
            array(0.3, 0.3, 0.36, 0.36, 0.318, 0.342, 0.36, 0.33, 0.3, 0.3, 0.342, 0.318), 
            array(0.84, 0.742, 0.798, 0.84, 0.77, 0.7, 0.7, 0.728, 0.742, 0.7, 0.7, 0.84), 
            array(1.2, 1.06, 1.14, 1.2, 1.1, 1, 1, 1.04, 1.06, 1, 1, 1.2), 
            array(0.36, 0.318, 0.342, 0.36, 0.33, 0.3, 0.3, 0.312, 0.318, 0.3, 0.3, 0.36), 
            array(0.24, 0.22, 0.2, 0.2, 0.208, 0.2, 0.2, 0.2, 0.24, 0.24, 0.212, 0.228), 
            array(0.5, 0.55, 0.53, 0.5, 0.55, 0.6, 0.6, 0.58, 0.5, 0.5, 0.57, 0.5), 
            array(0.3, 0.342, 0.3, 0.3, 0.33, 0.3, 0.3, 0.33, 0.342, 0.36, 0.348, 0.3), 
            array(0.7, 0.7, 0.84, 0.84, 0.742, 0.84, 0.84, 0.798, 0.7, 0.7, 0.728, 0.742), 
            array(1, 1, 1.2, 1.2, 1.06, 1.14, 1.2, 1.1, 1, 1, 1.04, 1.06), 
            array(0.3, 0.3, 0.36, 0.36, 0.318, 0.342, 0.36, 0.33, 0.3, 0.3, 0.312, 0.318), 
            array(0.24, 0.212, 0.228, 0.24, 0.22, 0.2, 0.2, 0.208, 0.212, 0.2, 0.2, 0.24), 
            array(0.5, 0.55, 0.53, 0.5, 0.55, 0.57, 0.6, 0.58, 0.5, 0.5, 0.57, 0.5), 
            array(0.36, 0.33, 0.3, 0.3, 0.312, 0.318, 0.3, 0.3, 0.36, 0.36, 0.318, 0.342), 
            array(0.7, 0.798, 0.7, 0.7, 0.77, 0.742, 0.7, 0.77, 0.798, 0.84, 0.812, 0.7), 
            array(1, 1.14, 1, 1, 1.1, 1.06, 1, 1.1, 1.14, 1.2, 1.16, 1), 
            array(0.3, 0.342, 0.3, 0.3, 0.33, 0.318, 0.3, 0.33, 0.342, 0.36, 0.348, 0.3), 
            array(0.2, 0.2, 0.24, 0.24, 0.212, 0.228, 0.24, 0.22, 0.2, 0.2, 0.208, 0.212), 
            array(0.5, 0.55, 0.53, 0.5, 0.55, 0.57, 0.6, 0.58, 0.5, 0.5, 0.57, 0.5), 
            array(0.36, 0.318, 0.342, 0.36, 0.33, 0.3, 0.3, 0.312, 0.318, 0.3, 0.3, 0.36), 
            array(0.84, 0.77, 0.7, 0.7, 0.728, 0.742, 0.7, 0.7, 0.84, 0.84, 0.724, 0.798));
        $yZhiNum = $zhi2ganNum[$y12];
        $yyy     = 0;
        $yyx     = 0;
        while ($yyy < $y12) {
            $yyx += $zhi2ganNum[$yyy];
            $yyy++;
        }
        if ($yZhiNum == 1) {
            $yZhiNum1 = $zhiNum[$yyx][$m12];
            $whNum[floor(($zhi2gan[$y12][0]) / 2)] += $yZhiNum1;
        } elseif ($yZhiNum == 2) {
            $yZhiNum1 = $zhiNum[$yyx][$m12];
            $whNum[floor(($zhi2gan[$y12][0]) / 2)] += $yZhiNum1;
            $yZhiNum2 = $zhiNum[$yyx + 1][$m12];
            $whNum[floor(($zhi2gan[$y12][1]) / 2)] += $yZhiNum2;
        } else {
            $yZhiNum1 = $zhiNum[$yyx][$m12];
            $whNum[floor(($zhi2gan[$y12][0]) / 2)] += $yZhiNum1;
            $yZhiNum2 = $zhiNum[$yyx + 1][$m12];
            $whNum[floor(($zhi2gan[$y12][1]) / 2)] += $yZhiNum2;
            $yZhiNum3 = $zhiNum[$yyx + 2][$m12];
            $whNum[floor(($zhi2gan[$y12][2]) / 2)] += $yZhiNum3;
        }
        return $whNum;
    }
}

if(!function_exists('whwh')){
    function whwh($num,$riGan, $d10, $whNum, $wh1, $wh1Num, $wh2, $wh2Num, $whh1, $whn1, $whh2, $whn2, $zzz1, $zzz2)
    {
        $wh = array("木", "火", "土", "金", "水");   
        if ($riGan[$d10][$num * 2] < 4) {
            $wh1 .= $wh[$num] . $whNum[$num] . "; ";
            $wh1Num += $whNum[$num];
            if ($whNum[$num] <= $whn1) {
                $whh1 = $num;
                $whn1 = $whNum[$num];
            }
            if ($zzz1 == 0) {
                $whh1 = $num;
                $whn1 = $whNum[$num];
                $zzz1 = 1;
            }
        
        } else {
            $wh2 .= $wh[$num] . $whNum[$num] . "; ";
            $wh2Num += $whNum[$num];
            if ($whNum[$num] <= $whn2) {
                $whh2 = $num;
                $whn2 = $whNum[$num];
            }
            if ($zzz2 == 0) {
                $whh2 = $num;
                $whn2 = $whNum[$num];
                $zzz2 = 1;
            }
        }
        return [
            'wh1'=>$wh1,'wh1Num'=>$wh1Num,'whh1'=>$whh1,'whn1'=>$whn1,'zzz1'=>$zzz1,
            'wh2'=>$wh2,'wh2Num'=>$wh2Num,'whh2'=>$whh2,'whn2'=>$whn2,'zzz2'=>$zzz2
        ];   
    }
}

if(!function_exists('mapFourDigitToHexagram')){
    function mapFourDigitToHexagram($fourDigit) {
        // 确保是4位数
        $fourDigit = str_pad($fourDigit, 4, '0', STR_PAD_LEFT);
        // 使用加权哈希算法，确保均匀分布
        $hash = 0;
        $weights = [1, 10, 100, 1000]; // 权重因子
        for ($i = 0; $i < 4; $i++) {
            $digit = intval($fourDigit[$i]);
            // 使用加权值计算哈希，增加分布均匀性
            $hash += $digit * $weights[$i];
        }
        // 取模64，确保结果在0-63范围内
        return $hash % 64;
    }
}

if(!function_exists('jiazi_to_wh_sx')){
    function jiazi_to_wh_sx($jiazi){
        if($jiazi=='甲子'){//1
            $data['sx'] = '屋上之鼠';
            $data['wh'] = '海中金';
            $data['sx_py'] = 'shu';
            $data['wh_py'] = 'jin';
        }elseif($jiazi=='乙丑'){//2
            $data['sx'] = '海内之牛';
            $data['wh'] = '海中金';
            $data['sx_py'] = 'niu';
            $data['wh_py'] = 'jin';
        }elseif($jiazi=='丙寅'){//3
            $data['sx'] = '山木之虎';
            $data['wh'] = '炉中火';
            $data['sx_py'] = 'hu';
            $data['wh_py'] = 'huo';
        }elseif($jiazi=='丁卯'){//4
            $data['sx'] = '望月之兔';
            $data['wh'] = '炉中火';
            $data['sx_py'] = 'tu';
            $data['wh_py'] = 'huo';
        }elseif($jiazi=='戊辰'){//5
            $data['sx'] = '清温之龙';
            $data['wh'] = '大林木';
            $data['sx_py'] = 'long';
            $data['wh_py'] = 'mu';
        }elseif($jiazi=='己巳'){//6
            $data['sx'] = '福气之蛇';
            $data['wh'] = '大林木';
            $data['sx_py'] = 'she';
            $data['wh_py'] = 'mu';
        }elseif($jiazi=='庚午'){//7
            $data['sx'] = '堂裹之马';
            $data['wh'] = '路旁土';
            $data['sx_py'] = 'ma';
            $data['wh_py'] = 'tu';
        }elseif($jiazi=='辛未'){//8
            $data['sx'] = '得禄之羊';
            $data['wh'] = '路旁土';
            $data['sx_py'] = 'yang';
            $data['wh_py'] = 'tu';
        }elseif($jiazi=='壬申'){//9
            $data['sx'] = '清透之猴';
            $data['wh'] = '剑峰金';
            $data['sx_py'] = 'hou';
            $data['wh_py'] = 'jin';
        }elseif($jiazi=='癸酉'){//10
            $data['sx'] = '楼宿之鸡';
            $data['wh'] = '剑峰金';
            $data['sx_py'] = 'ji';
            $data['wh_py'] = 'jin';
        }elseif($jiazi=='甲戌'){//11
            $data['sx'] = '守身之狗';
            $data['wh'] = '山头火';
            $data['sx_py'] = 'gou';
            $data['wh_py'] = 'huo';
        }elseif($jiazi=='乙亥'){//12
            $data['sx'] = '过往之猪';
            $data['wh'] = '山头火';
            $data['sx_py'] = 'zhu';
            $data['wh_py'] = 'huo';
        }elseif($jiazi=='丙子'){//13
            $data['sx'] = '田内之鼠';
            $data['wh'] = '涧下水';
            $data['sx_py'] = 'shu';
            $data['wh_py'] = 'shui';
        }elseif($jiazi=='丁丑'){//14
            $data['sx'] = '湖内之牛';
            $data['wh'] = '涧下水';
            $data['sx_py'] = 'niu';
            $data['wh_py'] = 'shui';
        }elseif($jiazi=='戊寅'){//15
            $data['sx'] = '过山之虎';
            $data['wh'] = '城头土';
            $data['sx_py'] = 'hu';
            $data['wh_py'] = 'tu';
        }elseif($jiazi=='己卯'){//16
            $data['sx'] = '山林之兔';
            $data['wh'] = '城头土';
            $data['sx_py'] = 'tu';
            $data['wh_py'] = 'tu';
        }elseif($jiazi=='庚辰'){//17
            $data['sx'] = '恕性之龙';
            $data['wh'] = '白腊金';
            $data['sx_py'] = 'long';
            $data['wh_py'] = 'jin';
        }elseif($jiazi=='辛巳'){//18
            $data['sx'] = '冬藏之蛇';
            $data['wh'] = '白腊金';
            $data['sx_py'] = 'she';
            $data['wh_py'] = 'jin';
        }elseif($jiazi=='壬午'){//19
            $data['sx'] = '军中之马';
            $data['wh'] = '杨柳木';
            $data['sx_py'] = 'ma';
            $data['wh_py'] = 'mu';
        }elseif($jiazi=='癸未'){//20
            $data['sx'] = '群内之羊';
            $data['wh'] = '杨柳木';
            $data['sx_py'] = 'yang';
            $data['wh_py'] = 'mu';
        }elseif($jiazi=='甲申'){//21
            $data['sx'] = '过树之猴';
            $data['wh'] = '井泉水';
            $data['sx_py'] = 'hou';
            $data['wh_py'] = 'shui';
        }elseif($jiazi=='乙酉'){//22
            $data['sx'] = '唱午之鸡';
            $data['wh'] = '井泉水';
            $data['sx_py'] = 'ji';
            $data['wh_py'] = 'shui';
        }elseif($jiazi=='丙戌'){//23
            $data['sx'] = '自眠之狗';
            $data['wh'] = '屋上土';
            $data['sx_py'] = 'gou';
            $data['wh_py'] = 'tu';
        }elseif($jiazi=='丁亥'){//24
            $data['sx'] = '过山之猪';
            $data['wh'] = '屋上土';
            $data['sx_py'] = 'zhu';
            $data['wh_py'] = 'tu';
        }elseif($jiazi=='戊子'){//25
            $data['sx'] = '仓内之鼠';
            $data['wh'] = '霹雳火';
            $data['sx_py'] = 'shu';
            $data['wh_py'] = 'huo';
        }elseif($jiazi=='己丑'){//26
            $data['sx'] = '栏内之牛';
            $data['wh'] = '霹雳火';
            $data['sx_py'] = 'niu';
            $data['wh_py'] = 'huo';
        }elseif($jiazi=='庚寅'){//27
            $data['sx'] = '出山之虎';
            $data['wh'] = '松柏木';
            $data['sx_py'] = 'hu';
            $data['wh_py'] = 'mu';
        }elseif($jiazi=='辛卯'){//28
            $data['sx'] = '蟾窟之兔';
            $data['wh'] = '松柏木';
            $data['sx_py'] = 'tu';
            $data['wh_py'] = 'mu';
        }elseif($jiazi=='壬辰'){//29
            $data['sx'] = '行雨之龙';
            $data['wh'] = '长流水';
            $data['sx_py'] = 'long';
            $data['wh_py'] = 'shui';
        }elseif($jiazi=='癸巳'){//30
            $data['sx'] = '草中之蛇';
            $data['wh'] = '长流水';
            $data['sx_py'] = 'she';
            $data['wh_py'] = 'shui';
        }elseif($jiazi=='甲午'){//31
            $data['sx'] = '云中之马';
            $data['wh'] = '沙中金';
            $data['sx_py'] = 'ma';
            $data['wh_py'] = 'jin';
        }elseif($jiazi=='乙未'){//32
            $data['sx'] = '敬重之羊';
            $data['wh'] = '沙中金';
            $data['sx_py'] = 'yang';
            $data['wh_py'] = 'jin';
        }elseif($jiazi=='丙申'){//33
            $data['sx'] = '山上之猴';
            $data['wh'] = '山下火';
            $data['sx_py'] = 'hou';
            $data['wh_py'] = 'huo';
        }elseif($jiazi=='丁酉'){//34
            $data['sx'] = '独立之鸡';
            $data['wh'] = '山下火';
            $data['sx_py'] = 'ji';
            $data['wh_py'] = 'huo';
        }elseif($jiazi=='戊戌'){//35
            $data['sx'] = '进山之狗';
            $data['wh'] = '平地木';
            $data['sx_py'] = 'gou';
            $data['wh_py'] = 'mu';
        }elseif($jiazi=='己亥'){//36
            $data['sx'] = '道院之猪';
            $data['wh'] = '平地木';
            $data['sx_py'] = 'zhu';
            $data['wh_py'] = 'mu';
        }elseif($jiazi=='庚子'){//37
            $data['sx'] = '梁上之鼠';
            $data['wh'] = '壁上土';
            $data['sx_py'] = 'shu';
            $data['wh_py'] = 'tu';
        }elseif($jiazi=='辛丑'){//38
            $data['sx'] = '路途之牛';
            $data['wh'] = '壁上土';
            $data['sx_py'] = 'niu';
            $data['wh_py'] = 'tu';
        }elseif($jiazi=='壬寅'){//39
            $data['sx'] = '过林之虎';
            $data['wh'] = '金箔金';
            $data['sx_py'] = 'hu';
            $data['wh_py'] = 'jin';
        }elseif($jiazi=='癸卯'){//40
            $data['sx'] = '山林之兔';
            $data['wh'] = '金箔金';
            $data['sx_py'] = 'tu';
            $data['wh_py'] = 'jin';
        }elseif($jiazi=='甲辰'){//41
            $data['sx'] = '伏潭之龙';
            $data['wh'] = '覆灯火';
            $data['sx_py'] = 'long';
            $data['wh_py'] = 'huo';
        }elseif($jiazi=='乙巳'){//42
            $data['sx'] = '出穴之蛇';
            $data['wh'] = '覆灯火';
            $data['sx_py'] = 'she';
            $data['wh_py'] = 'huo';
        }elseif($jiazi=='丙午'){//43
            $data['sx'] = '行路之马';
            $data['wh'] = '天河水';
            $data['sx_py'] = 'ma';
            $data['wh_py'] = 'shui';
        }elseif($jiazi=='丁未'){//44
            $data['sx'] = '失群之羊';
            $data['wh'] = '天河水';
            $data['sx_py'] = 'yang';
            $data['wh_py'] = 'shui';
        }elseif($jiazi=='戊申'){//45
            $data['sx'] = '独立之猴';
            $data['wh'] = '大驿土';
            $data['sx_py'] = 'hou';
            $data['wh_py'] = 'tu';
        }elseif($jiazi=='己酉'){//46
            $data['sx'] = '报晓之鸡';
            $data['wh'] = '大驿土';
            $data['sx_py'] = 'ji';
            $data['wh_py'] = 'tu';
        }elseif($jiazi=='庚戌'){//47
            $data['sx'] = '寺观之狗';
            $data['wh'] = '钗钏金';
            $data['sx_py'] = 'gou';
            $data['wh_py'] = 'jin';
        }elseif($jiazi=='辛亥'){//48
            $data['sx'] = '圈里之猪';
            $data['wh'] = '钗钏金';
            $data['sx_py'] = 'zhu';
            $data['wh_py'] = 'jin';
        }elseif($jiazi=='壬子'){//49
            $data['sx'] = '山上之鼠';
            $data['wh'] = '桑柘木';
            $data['sx_py'] = 'shu';
            $data['wh_py'] = 'mu';
        }elseif($jiazi=='癸丑'){//50
            $data['sx'] = '栏内之牛';
            $data['wh'] = '桑柘木';
            $data['sx_py'] = 'niu';
            $data['wh_py'] = 'mu';
        }elseif($jiazi=='甲寅'){//51
            $data['sx'] = '立定之虎';
            $data['wh'] = '大溪水';
            $data['sx_py'] = 'hu';
            $data['wh_py'] = 'shui';
        }elseif($jiazi=='乙卯'){//52
            $data['sx'] = '得道之兔';
            $data['wh'] = '大溪水';
            $data['sx_py'] = 'tu';
            $data['wh_py'] = 'shui';
        }elseif($jiazi=='丙辰'){//53
            $data['sx'] = '天上之龙';
            $data['wh'] = '沙中土';
            $data['sx_py'] = 'long';
            $data['wh_py'] = 'tu';
        }elseif($jiazi=='丁巳'){//54
            $data['sx'] = '塘内之蛇';
            $data['wh'] = '沙中土';
            $data['sx_py'] = 'she';
            $data['wh_py'] = 'tu';
        }elseif($jiazi=='戊午'){//55
            $data['sx'] = '廊内之马';
            $data['wh'] = '天上火';
            $data['sx_py'] = 'ma';
            $data['wh_py'] = 'huo';
        }elseif($jiazi=='己未'){//56
            $data['sx'] = '草野之羊';
            $data['wh'] = '天上火';
            $data['sx_py'] = 'yang';
            $data['wh_py'] = 'huo';
        }elseif($jiazi=='庚申'){//57
            $data['sx'] = '食果之猴';
            $data['wh'] = '石榴木';
            $data['sx_py'] = 'hou';
            $data['wh_py'] = 'mu';
        }elseif($jiazi=='辛酉'){//58
            $data['sx'] = '笼内之鸡';
            $data['wh'] = '石榴木';
            $data['sx_py'] = 'ji';
            $data['wh_py'] = 'mu';
        }elseif($jiazi=='壬戌'){//59
            $data['sx'] = '顾家之狗';
            $data['wh'] = '大海水';
            $data['sx_py'] = 'gou';
            $data['wh_py'] = 'shui';
        }elseif($jiazi=='癸亥'){//60
            $data['sx'] = '林下之猪';
            $data['wh'] = '大海水';
            $data['sx_py'] = 'zhu';
            $data['wh_py'] = 'shui';
        }
        $data['sx_py_img'] = splicingHttpSrc('static/bzmp/'.$data['sx_py'].'.png');
        $data['wh_py_img'] = splicingHttpSrc('static/bzmp/wu'.$data['wh_py'].'.png');
        return $data;
    }
}

    /**
 * 生成随机的日期（只包含日）
 * 
 * @param bool $considerMonth 是否考虑月份的实际天数（默认为false，直接返回1-31之间的随机数）
 * @param int $month 指定月份（1-12，当$considerMonth为true时有效）
 * @param int $year 指定年份（用于判断闰年，当$considerMonth为true时有效）
 * @return int 随机的日期（1-31之间，根据参数可能会有上限限制）
 */
if(!function_exists('generateRandomDay')){
    function generateRandomDay($considerMonth = false, $month = null, $year = null) {
        // 如果不考虑月份实际天数，直接返回1-31之间的随机数
        if (!$considerMonth) {
            return rand(1, 31);
        }
        // 如果没有指定月份，随机选择一个月份
        if ($month === null) {
            $month = rand(1, 12);
        }
        // 如果没有指定年份，使用当前年份
        if ($year === null) {
            $year = date('Y');
        }
        // 根据月份确定最大天数
        $maxDays = 0;
        switch ($month) {
            case 2: // 2月
                // 判断是否为闰年
                if (($year % 4 == 0 && $year % 100 != 0) || ($year % 400 == 0)) {
                    $maxDays = 29;
                } else {
                    $maxDays = 28;
                }
                break;
            case 4: case 6: case 9: case 11: // 4、6、9、11月
                $maxDays = 30;
                break;
            default: // 其他月份
                $maxDays = 31;
                break;
        }
        // 返回1到最大天数之间的随机数
        return rand(1, $maxDays);
    }
}
if(!function_exists('setEnvVal')){
    function setEnvVal($env_path, $key, $val, $section = null) {
        // 获取现有内容
        $env_content = @file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($env_content === false) {
            // 如果文件不存在或无法读取，创建一个空数组
            $env_content = [];
        }
        
        $new_line = $key . '=' . $val;
        $found = false;
        $in_target_section = false;
        $section_index = -1;
        $next_section_index = count($env_content);
        
        // 规范化section名称（确保带方括号）
        $target_section = $section !== null ? '[' . trim($section, '[]') . ']' : null;
        
        // 遍历所有行，查找目标section和key
        for ($i = 0; $i < count($env_content); $i++) {
            $line = $env_content[$i];
            $trimmed_line = trim($line);
            
            // 检查是否是section行
            if (substr($trimmed_line, 0, 1) === '[' && substr($trimmed_line, -1) === ']') {
                // 如果当前在目标section中，记录下一个section的位置
                if ($in_target_section) {
                    $next_section_index = $i;
                    break;
                }
                
                // 检查是否是目标section
                if ($trimmed_line === $target_section) {
                    $in_target_section = true;
                    $section_index = $i;
                }
            } else {
                // 如果不在section中且没有指定section，或者在指定的section中
                if (($target_section === null && !$in_target_section) || $in_target_section) {
                    // 检查是否是目标key（支持注释和非注释版本）
                    if (strpos($trimmed_line, $key . '=') === 0 || strpos($trimmed_line, '#' . $key . '=') === 0) {
                        // 找到key，更新它
                        $env_content[$i] = $new_line;
                        $found = true;
                        break;
                    }
                }
            }
        }
        
        // 如果没有找到key
        if (!$found) {
            if ($target_section !== null) {
                if ($section_index === -1) {
                    // section不存在，添加新section和key
                    $env_content[] = '';
                    $env_content[] = $target_section;
                    $env_content[] = $new_line;
                } else {
                    // section存在，在section末尾添加key
                    array_splice($env_content, $next_section_index, 0, $new_line);
                }
            } else {
                // 没有指定section，在文件末尾添加key
                $env_content[] = '';
                $env_content[] = $new_line;
            }
        }
        
        // 写入数据
        return (bool) @file_put_contents($env_path, implode(PHP_EOL, $env_content) . PHP_EOL);
    }
}
