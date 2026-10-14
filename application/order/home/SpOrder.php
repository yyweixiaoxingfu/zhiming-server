<?php

namespace app\order\home;

use app\admin\model\SpCateModel;
use app\order\model\SpOrderModel;
use app\index\controller\Api;
use app\admin\model\SpProjectModel;
use app\admin\model\SpExpertModel;
use app\member\model\MemberCreditModel;
use app\index\controller\Sm;
use app\index\controller\Stars;
use app\common\library\sm\cls_ancientCalendarData;
use think\facade\Log;
use think\facade\Env;
use think\Exception;
use think\Db;


class SpOrder extends Api
{

    protected $except = ['get_order_find'];
    /**
     * 确认订单接口 - 处理测算项目的订单创建
     * @return array 订单创建结果
     */
    /**
     * 确认订单接口 - 处理测算项目的订单创建
     * @return array 订单创建结果
     */
    public function confirm_order()
    {
        if ($this->request->isPost()) {
            try {
                // 参数获取与基础验证
                $post = SafeFilterItem(input('post.'));
                // 验证cs_data是否为空且为JSON格式
                if (!isset($post['cs_data']) || empty($post['cs_data'])) {
                    return returnData(0, '关键参数为空：cs_data');
                }

                $cs_data = json_decode($post['cs_data'], true);
                if (!is_array($cs_data) || empty($cs_data)) {
                    return returnData(0, '数据格式错误：cs_data不是有效的JSON或为空数组');
                }

                // 验证必要参数
                $requiredParams = ['cate_type', 'link_id', 'cid', 'order_type'];
                $errorParams = [];
                foreach ($requiredParams as $param) {
                    if (!isset($post[$param]) || !$post[$param]) {
                        $errorParams[] = $param;
                    }
                }

                if (!empty($errorParams)) {
                    return returnData(0, '关键参数为空：' . implode(',', $errorParams));
                }

                // 参数类型转换
                $cate_type = (int)$post['cate_type'];
                $link_id = (int)$post['link_id'];
                $cid = (int)$post['cid'];
                $order_type = (int)$post['order_type'];

                // 验证分类信息
                $cate_info = SpCateModel::where('id', $cid)->where('cate_type', $cate_type)->find();
                if (!$cate_info) {
                    return returnData(0, '相关分类数据有误');
                }

                // 初始化数据和对象
                $lunar_calendar_hour = array(
                    '子时' => '0:01',
                    '丑时' => '2:00',
                    '寅时' => '4:00',
                    '卯时' => '6:00',
                    '辰时' => '8:00',
                    '巳时' => '10:00',
                    '午时' => '12:00',
                    '未时' => '14:00',
                    '申时' => '16:00',
                    '酉时' => '18:00',
                    '戌时' => '20:00',
                    '亥时' => '22:00'
                );
                $cls_ancientCalendarData = new cls_ancientCalendarData();
                $user_id = $this->memberID;
                $user_info = $this->memberInfo;
                $format_data = '';
                $find_info = null;
                $order_filed = '';
                $from_data = [];

                // 根据分类类型处理不同的数据逻辑
                switch ($cate_type) {
                    case 1:
                        $order_filed = 'CS';
                        // 获取项目信息
                        $find_info = SpProjectModel::where('id', $link_id)->where('p_status', 1)->find();
                        if (!$find_info) {
                            return returnData(0, '项目不存在或已下架');
                        }

                        // 处理项目内容字段
                        $p_content = json_decode($find_info['p_content'], true);
                        if (!is_array($p_content) || !isset($p_content['fields'])) {
                            return returnData(0, '项目配置数据异常');
                        }

                        $fields = array_flip(parse_attr($p_content['fields']));
                        $fields_key = array_keys($fields);
                        sort($fields_key);
                        $cs_data_key = array_keys($cs_data);
                        sort($cs_data_key);
                        // 验证必要字段
                        if ($fields_key != $cs_data_key) {
                            return returnData(0, '提交的参数与项目要求不匹配');
                        }
                        // 初始化标志位
                        $has_xing = false;
                        foreach ($cs_data_key as $key) {
                            if (strpos($key, 'xing') !== false) {
                                $has_xing = true;
                                break;
                            }
                        }

                        // 处理每个字段
                        foreach ($cs_data as $key => $value) {
                            if ($value==='') {
                                return returnData(0, '请完善' . (isset($fields[$key]) ? $fields[$key] : $key) . '信息');
                            }

                            // 处理姓名和姓氏
                            if ($key == 'name' || strpos($key, 'name') !== false) {
                                $this->processNameField($key, $value, $from_data, $has_xing, $fields, $cs_data);
                            }
                            // 处理姓氏
                            elseif ($key == 'xing' || strpos($key, 'xing') !== false) {
                                $this->processXingField($key, $value, $from_data);
                            }
                            // 处理性别
                            elseif ($key == 'sex' || strpos($key, 'sex') !== false) {
                                $this->processSexField($key, $value, $from_data);
                            }
                            // 处理生日
                            elseif ($key == 'birthday' || strpos($key, 'birthday') !== false) {
                                $result = $this->processBirthdayField($key, $value, $cs_data, $lunar_calendar_hour, $cls_ancientCalendarData);
                                if ($result['status'] === false) {
                                    return $result['response'];
                                }
                                $from_data = array_merge($from_data, $result['data']);
                            }
                            // 处理数字和数字类型
                            elseif ($key == 'number' || strpos($key, 'number') !== false) {
                                if (strpos($key, 'number_type') !== false) {
                                    $from_data['number_type'] = $value;
                                } else {
                                    $from_data['number'] = $value;
                                }
                            }
                            //处理小六壬内容
                            elseif ($key == 'content' || strpos($key, 'content') !== false) {
                                $from_data['content'] = $value;
                            }
                             //处理塔罗牌
                            elseif ($key == 'tarot_data' || strpos($key, 'tarot_data') !== false) {
                                $from_data['tarot_data'] = $value;
                            }
                            //处理星座
                            elseif ($key == 'stars_data' || strpos($key, 'stars_data') !== false) {
                                $from_data['stars_data'] = $value;
                            }
                            //处理字库查询
                            elseif ($key == 'zidian_data' || strpos($key, 'zidian_data') !== false) {
                                $from_data['zidian_data'] = $value;
                            }
                        }
                        break;

                    case 4:
                        $order_filed = 'EX';
                        $find_info = SpExpertModel::where('id', $link_id)->where('is_del', 0)->where('review_status', 1)->where('status', 0)->find();
                        if (!$find_info) {
                            return returnData(0, '专家不存在或已下架');
                        }
                        break;

                    default:
                        return returnData(0, '不支持的分类类型：' . $cate_type);
                }

                // 准备订单数据
                $time = time();
                $format_data = json_encode($from_data, JSON_UNESCAPED_UNICODE);
                $order_no = generate_order_number($order_filed, 0, 'sp_order', 'order_no');

                // 构建订单数据
                $order_data = [
                    'order_no'      => $order_no,
                    'cid'           => $cid,
                    'order_type'    => $order_type,
                    'original_price'=> isset($find_info['p_price']) ? $find_info['p_price'] : $find_info['price'],
                    'user_id'       => $user_id,
                    'pay_price'     => isset($find_info['p_price']) ? $find_info['p_price'] : $find_info['price'],
                    'points_num'    => 0,
                    'p_ad_num'      => 1,
                    'cate_type'     => $cate_type,
                    'link_id'       => $link_id,
                    'submit_data'   => json_encode($cs_data, JSON_UNESCAPED_UNICODE),
                    'format_data'   => $format_data,
                    'cs_title'      => isset($find_info['p_title']) ? $find_info['p_title'] : $find_info['name'],
                    'cs_desc'       => isset($find_info['p_desc']) ? $find_info['p_desc'] : $find_info['desc'],
                    'create_time'   => $time,
                    'expert_user_id' => 0,
                ];
                // 添加积分信息
                if ($cate_type == 1 && isset($find_info['p_points']) && $find_info['p_points'] > 0) {
                    $order_data['points_num'] = $find_info['p_points'];
                }
                // 添加体验信息
                if ($cate_type == 1 && isset($find_info['p_ad_num']) && $find_info['p_ad_num'] > 0) {
                    $order_data['p_ad_num'] = $find_info['p_ad_num'];
                }
                // 添加专家用户ID
                if ($cate_type == 4) {
                    $order_data['expert_user_id'] = $find_info['user_id'];
                }
                // 处理VIP折扣
                if ($user_info['is_vip'] == 1 && $user_info['vip_end_time'] > $time) {
                    $vip_discount = floatval($find_info['vip_discount'] ?? 0);
                    if (!$vip_discount) {
                        $vip_discount = floatval(config('vip_fee', 1.0)); // 默认不打折
                    }
                    if ($vip_discount > 0 && $vip_discount <= 1) {
                        $order_data['discount'] = $vip_discount;
                        $order_data['pay_price'] = bcmul($order_data['original_price'], $vip_discount, 2);
                    }
                }
                // 保存订单
                $res = SpOrderModel::insertGetId($order_data);
                if ($res) {
                    return returnData(1, '下单成功', ['order_no' => $order_no]);
                } else {
                    return returnData(0, '下单失败，请稍后重试');
                }
            } catch (Exception $e) {
                // 记录错误日志
                Log::error('订单确认异常: ' . $e->getMessage(), ['trace' => $e->getTraceAsString(), 'post' => $post ?? []]);
                return returnData(0, '系统处理异常，请稍后重试');
            }
        }
        return returnData(0, '无效的请求方式');
    }

    /**
     * 处理姓名字段
     * @param string $key 字段名
     * @param mixed $value 字段值
     * @param array &$from_data 输出数据数组
     * @param bool $has_xing 是否已有xing字段
     * @param array $fields 字段映射
     * @param array $cs_data 原始数据
     */
    private function processNameField($key, $value, &$from_data, $has_xing, $fields, $cs_data)
    {
        $name_key = explode('_', $key);

        // 如果已有单独的姓氏字段，直接保存姓名
        if ($has_xing) {
            $from_data[$key] = $value;
            if (count($name_key) > 1 && isset($cs_data[$name_key[0] . '_name'])) {
                $from_data[$name_key[0] . '_name'] = $cs_data[$name_key[0] . '_name'];
            }
        } else {
            // 否则分割姓名获取姓氏和名字
            $format_name = splitName($value);
            if (count($name_key) > 1) {
                $from_data[$name_key[0] . '_xing'] = $format_name[0];
                $from_data[$name_key[0] . '_name'] = $format_name[1];
            } else {
                $from_data['xing'] = $format_name[0];
                $from_data['name'] = $format_name[1];
            }
        }
    }

    /**
     * 处理姓氏字段
     * @param string $key 字段名
     * @param mixed $value 字段值
     * @param array &$from_data 输出数据数组
     */
    private function processXingField($key, $value, &$from_data)
    {
        $xing_key = explode('_', $key);
        if (count($xing_key) > 1) {
            $from_data[$xing_key[0] . '_xing'] = $value;
        } else {
            $from_data['xing'] = $value;
        }
    }

    /**
     * 处理性别字段
     * @param string $key 字段名
     * @param mixed $value 字段值
     * @param array &$from_data 输出数据数组
     */
    private function processSexField($key, $value, &$from_data)
    {
        $sex_key = explode('_', $key);
        if (count($sex_key) > 1) {
            $from_data[$sex_key[0] . '_sex'] = $value;
        } else {
            $from_data['sex'] = $value;
        }
    }

    /**
     * 处理生日字段
     * @param string $key 字段名
     * @param mixed $value 字段值
     * @param array $cs_data 原始数据
     * @param array $lunar_calendar_hour 农历时辰映射
     * @param object $cls_ancientCalendarData 日历转换对象
     * @return array 处理结果
     */
    private function processBirthdayField($key, $value, $cs_data, $lunar_calendar_hour, $cls_ancientCalendarData)
    {
        $birthday_key = explode('_', $key);
        $result = ['status' => true, 'data' => []];

        if (count($birthday_key) > 1) {
            // 处理带前缀的生日字段
            $prefix = $birthday_key[0];
            $calendar_type = isset($cs_data[$prefix . '_calendar_type']) && $cs_data[$prefix . '_calendar_type'] !== ''
                ? (int)$cs_data[$prefix . '_calendar_type'] : 1;
            $birthday = isset($cs_data[$prefix . '_birthday']) && $cs_data[$prefix . '_birthday'] !== ''
                ? $cs_data[$prefix . '_birthday'] : '';

            if (!$birthday) {
                $result['status'] = false;
                $result['response'] = returnData(0, '生日数据有误，请检查');
                return $result;
            }

            $date_time = $this->parseDateTime($birthday, $calendar_type, $lunar_calendar_hour, $cls_ancientCalendarData);
            $result['data'][$prefix . '_cs_date'] = getCDate(
                $date_time['year'],
                $date_time['month'],
                $date_time['day'],
                $date_time['hour'],
                $date_time['minute']
            );
        } else {
            // 处理标准生日字段
            $calendar_type = isset($cs_data['calendar_type']) && $cs_data['calendar_type'] !== ''
                ? (int)$cs_data['calendar_type'] : 1;
            $date_time = $this->parseDateTime($value, $calendar_type, $lunar_calendar_hour, $cls_ancientCalendarData);
            $result['data']['cs_date'] = getCDate(
                $date_time['year'],
                $date_time['month'],
                $date_time['day'],
                $date_time['hour'],
                $date_time['minute']
            );
        }

        return $result;
    }

    /**
     * 解析日期时间
     * @param string $datetime 日期时间字符串
     * @param int $calendar_type 日历类型(1-公历, 2-农历)
     * @param array $lunar_calendar_hour 农历时辰映射
     * @param object $cls_ancientCalendarData 日历转换对象
     * @return array 解析后的日期时间
     */
    private function parseDateTime($datetime, $calendar_type, $lunar_calendar_hour, $cls_ancientCalendarData)
    {
        $parts = explode(' ', $datetime);
        $date_part = isset($parts[0]) ? $parts[0] : '';
        $time_part = isset($parts[1]) ? $parts[1] : '00:00';

        $format_date = explode('-', $date_part);
        $year = (int)$format_date[0];
        $month = isset($format_date[1]) ? (int)$format_date[1] : 1;
        $day = isset($format_date[2]) ? (int)$format_date[2] : 1;

        if ($calendar_type == 1) {
            // 公历时间处理
            $format_time = explode(':', $time_part);
            $hour = isset($format_time[0]) ? (int)$format_time[0] : 0;
            $minute = isset($format_time[1]) ? (int)$format_time[1] : 0;
        } else {
            // 农历时间处理
            $lunar_hour = isset($lunar_calendar_hour[$time_part]) ? $lunar_calendar_hour[$time_part] : '00:00';
            $format_time = explode(':', $lunar_hour);
            $hour = (int)$format_time[0];
            $minute = (int)$format_time[1];

            // 转换农历为公历
            $solar_date = $cls_ancientCalendarData->convertLunarToSolar($year, $month, $day);
            if (is_array($solar_date) && count($solar_date) >= 3) {
                $year = (int)$solar_date[0];
                $month = (int)$solar_date[1];
                $day = (int)$solar_date[2];
            }
        }
        return compact('year', 'month', 'day', 'hour', 'minute');
    }

    public function get_zidian_status()
    { 
        if($this->request->isPost()){
            $post       = SafeFilterItem(input('post.'));
            $user_id    = $this->memberID;
            $key        = isset($post['key']) && $post['key'] ? $post['key'] : '';
            $time       = time();
            $expired_data = Db::name('sp_key')->where('status',1)->where('end_time','<=',$time)->column('id');
            if(!empty($expired_data)){
                Db::name('sp_key')->where('status',1)->where('id','in',$expired_data)->update(['status'=>2]);
            }
            if(!$key){
                $key_find = Db::name('sp_key')->where('status',1)->where('user_id',$user_id)->find();
                if(!empty($key_find)){
                    return returnData(1, '查询成功', ['key' => $key_find['key'],'end_time'=>$key_find['end_time'],'is_status'=>$key_find['status']]);
                }
            }else{
                $key_find = Db::name('sp_key')->where('key',$key)->where('user_id',$user_id)->find();
                if(!empty($key_find) && $key_find['status'] == 1){
                    return returnData(1, '查询成功', ['key' => $key_find['key'],'end_time'=>$key_find['end_time'],'is_status'=>$key_find['status']]);
                }
            }
            return returnData(1, '查询成功',['key' =>'','end_time'=>'','is_status'=>0]);
        }
    }
    // 发起支付(非在线支付，无需回调)
    public function initiate_pay(){ 
        if($this->request->isPost()){
            $post_data  = SafeFilter(input('post.'));
            $member     = $this->memberInfo;
            $user_id    = $member['id'];
            $target     = trim($post_data['target']);
            $targetType = trim($post_data['target_type']);
            $payType    = trim($post_data['pay_type']);
            $order      = SpOrderModel::where('order_no',$target)->where('user_id', $user_id)->where('status',0)->where('pay_time',0)->find();
            if(empty($order)){
                return returnData(0, '订单不存在');
            }
            $my_credit  = MemberCreditModel::getCredit ($user_id, $payType, ['credit']);
            if($my_credit<$order['p_ad_num']){
                return returnData(0, '您的体验次数不足');
            }
            $tip = '';
            $log_type = '';
            if($order['cate_type']==4){
                $find_data = SpExpertModel::where('id',$order['link_id'])->find();
                $tip = '参与专家:'.$find_data['name'].'的咨询服务';
                $log_type = 'ex_order_pay';
            }else{
                $find_data = SpProjectModel::where('id',$order['link_id'])->find();
                $tip = '体验测算项目:'.$find_data['p_title'];
                $log_type = 'cs_order_pay';
            }
            $time = time();
            try{
                Db::startTrans();
                $res = SpOrderModel::where('order_no',$target)->update([
                    'status'        =>1,
                    'pay_time'      =>$time,
                    'update_time'   =>$time,
                    'pay_type'      =>$payType,
                ]);
                if($res === false){
                    Db::rollback();
                    return returnData(0, '操作失败');
                }
                $credit_result = MemberCreditModel::credit(
                    $user_id,
                    $payType,
                    -$order['p_ad_num'],
                    $tip,
                    '',
                    $target,
                    $log_type
                );
                if($credit_result === false){
                    Db::rollback();
                    return returnData(0, '操作失败');
                }
                $key = '';
                if($order['cate_type']==1){
                    // 调用独立的测算处理方法
                    $calculationResult = $this->processCalculation($order, $find_data, $user_id, $time, $target);
                    if(!$calculationResult['success']){
                        Db::rollback();  
                        return returnData(0, $calculationResult['message']);
                    }
                    $key = $calculationResult['key'];
                }
                Db::commit();
                return returnData(1, '提交成功,正在计算中...',['key'=>$key]);
            }catch(\Exception $e){
                echo $e->getMessage();
                Db::rollback();
                return returnData(0, '操作失败');
            }
        }
    }

    /**
     * 处理测算逻辑
     * @param array $order 订单信息
     * @param array $find_data 项目或专家信息
     * @param int $user_id 用户ID
     * @param int $time 当前时间戳
     * @param string $target 订单号
     * @return array 测算结果
     */
    public function processCalculation($order, $find_data, $user_id, $time, $target){
        $key = '';
        $sm = new Sm();
        $stars = new Stars();
        try {
            switch ($find_data['p_model']) {
                case 'bazijp':  
                    $result = $sm->year_luck($order['format_data']);
                    break;
                 case 'hehun':  
                    $result = $sm->bazihh($order['format_data']);
                    break;
                 case 'jinnian':  
                    $result = $sm->jinnian($order['format_data']);
                    break;
                 case 'bazixmjq':  
                    $result = $sm->xmfx($order['format_data']);
                    break;
                 case 'bazi':  
                    $result = $sm->bazi($order['format_data']);
                    break;
                 case 'ziwei':  
                    $result = $sm->ziweids($order['format_data']);
                    break;
                 case 'yinyuancs':  
                    $result = $sm->yinyuancs($order['format_data']);
                    break;
                 case 'xmpd':  
                    $result = $sm->xmpd($order['format_data']);
                    break;
                 case 'hmjx':  
                    $result = $sm->hmjx($order['format_data']);
                    break;
                 case 'ffqm':  
                    $result = $sm->ffqm($order['format_data']);
                    break;
                case 'shengxiao':  
                    $result = $sm->shengxiao($order['format_data']);
                    break;
                case 'bazizh':  
                    $result = $sm->bazizh($order['format_data']);
                    break;
                case 'xiaoliuren':  
                    $result = $sm->get_day_fate($order['format_data']);
                    break;
                case 'zidian':  
                    $key_data = Db::name('sp_key')->where('star_time',0)->where('end_time',0)->where('status',0)->where('user_id',0)->order('day asc,create_time asc')->find();
                    if(empty($key_data)){
                        return ['success' => false, 'message' => '数据错误'];
                    }
                    $res = Db::name('sp_key')->where('key',$key_data['key'])->update([
                        'status'        =>1,
                        'star_time'     =>$time,
                        'end_time'      =>$time+$key_data['day']*86400+$key_data['house']*3600+$key_data['minutes']*60,
                        'update_time'   =>$time,
                        'user_id'       =>$user_id,
                    ]);
                    if($res === false){
                        return ['success' => false, 'message' => '操作失败'];
                    }
                    $key    = $key_data['key'];
                    $result = ['key'=>$key_data['key']];
                    break;
                case 'xingzuo':  
                    $result = $stars->xingzuo($order['format_data']);
                    break;
                default:
                    if(strpos($find_data['p_model'],'taluo')!==false){
                        $result = $stars->tarot($order['format_data'],$order['link_id'],true);
                    }else{
                        return ['success' => false, 'message' => '数据错误s'];
                    }
                    break;
            }
            if(!$result){
                return ['success' => false, 'message' => '数据错误'];
            }
            // 保存测算结果，自动处理缓存
            SpOrderModel::saveResult($target, $result);
            // 清理format_data（根据业务需求决定是否保留）
            SpOrderModel::cleanFormatData($target, false);
            SpProjectModel::where('id', $order['link_id'])->setInc('p_cs_num');
            return ['success' => true, 'key' => $key];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => '测算处理失败: ' . $e->getMessage()];
        }
    }

    //订单列表
    public function get_order_list(){ 
        if($this->request->isPost()){
            $post_data      = SafeFilter(input('post.'));
            $member_id      = $this->memberID;
            $order_no       = isset($post_data['order_no']) && $post_data['order_no']!=''?$post_data['order_no']:'';
            $order_status   = isset($post_data['order_status']) && $post_data['order_status']!=''?intval($post_data['order_status']):'';
            $cate_type      = isset($post_data['cate_type']) && $post_data['cate_type']!=''?intval($post_data['cate_type']):'';
            $page           = isset($post_data['page']) && $post_data['page']!=''?intval($post_data['page']):1;
            $limit          = isset($post_data['limit']) && $post_data['limit']!=''?intval($post_data['limit']):10;
            $where[]        = ['user_id','=',$member_id];
            if($order_status != ''){
                $where[] = ['status','=',$order_status];
            }
            if($cate_type != ''){
                $where[] = ['cate_type','=',$cate_type];
            }
            if($order_no != ''){
                $where[] = ['order_no','=',$order_no];
            }
            $order_list = SpOrderModel::where($where)->page($page,$limit)->order('id desc,create_time desc')->field('id,order_no,cate_type,link_id,pay_type,status,create_time,update_time,pay_time,cs_title,cs_desc')->select();
            $total      = SpOrderModel::where($where)->count();
            if(empty($order_list)){
                return returnData(0, '暂无订单数据');
            }
            foreach($order_list as $key=>$order){
                $order_list[$key]['p_model'] = 'expert';
                if($order['cate_type'] == 1){
                    $order_list[$key]['p_model'] = SpProjectModel::where('id',$order['link_id'])->value('p_model');
                }
                $order_list[$key]['create_time'] = isset($order['create_time']) && $order['create_time']?date('Y-m-d H:i',$order['create_time']):'';
                $order_list[$key]['update_time'] = isset($order['update_time']) && $order['update_time']?date('Y-m-d H:i',$order['update_time']):'';
                $order_list[$key]['pay_time']    = isset($order['pay_time']) && $order['pay_time']?date('Y-m-d H:i',$order['pay_time']):'';
            }
            return returnData(1, '获取成功',['order_list'=>$order_list,'total'=>$total]);
        }
    }
    //订单详情
    public function get_order_find(){ 
        if($this->request->isPost()){ 
            $post_data      = SafeFilter(input('post.'));
            //$member_id      = $this->memberID;
            $order_no       = isset($post_data['order_no']) && $post_data['order_no']!=''?$post_data['order_no']:'';
            if($order_no == ''){
                return returnData(0, '订单号不能为空');
            }
            // 使用getOrderData方法获取订单数据，自动处理return_data
            //$order = SpOrderModel::getOrderData(['order_no' => $order_no, 'user_id' => $member_id]);
            $order = SpOrderModel::getOrderData(['order_no' => $order_no]);
            if(empty($order)){
                return returnData(0, '订单不存在');
            }
            unset($order['format_data']);
            if($order['submit_data']){ 
                $order['submit_data'] = json_decode($order['submit_data'],true);
            }
            if($order['return_data']){ 
                $order['return_data'] = json_decode($order['return_data'],true);
            }
            $order['create_time'] = isset($order['create_time']) && $order['create_time']?date('Y-m-d H:i',$order['create_time']):'';
            $order['update_time'] = isset($order['update_time']) && $order['update_time']?date('Y-m-d H:i',$order['update_time']):'';
            $order['pay_time']    = isset($order['pay_time']) && $order['pay_time']?date('Y-m-d H:i',$order['pay_time']):'';
            return returnData(1, '获取成功',$order);
        }
    }


}
