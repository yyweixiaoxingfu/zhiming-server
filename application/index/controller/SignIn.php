<?php

namespace app\index\controller;

use think\facade\Log;
use think\Exception;
use app\member\model\MemberCreditModel;
use think\Db;

header('Access-Control-Allow-Origin: *'); // 允许所有域名访问

/**
 * 签到控制器
 * @package app\index\controller
 */
class SignIn extends Api
{
    /**
     * 用户签到操作
     * @return array 签到结果和奖励信息
     */
    public function sign_in()
    {
        try {
            // 获取用户ID（已在父类中验证登录状态）
            $user_id   = $this->memberID;
            $user_info = $this->memberInfo;

            // 获取当前日期信息
            $today      = date('Y-m-d');
            $date_flag  = date('Y-m');

            // 检查今日是否已签到（使用缓存可进一步优化）
            $today_sign = Db::name('sp_sign_in')
                ->where('user_id', $user_id)
                ->where('sp_date', $today)
                ->find();

            if ($today_sign) {
                return returnData(0, '今日已签到');
            }

            // 一次性获取并验证配置
            $config = $this->getSignInConfig();
            if (!$config['valid']) {
                return returnData(0, $config['error']);
            }

            // 开启事务
            Db::startTrans();

            try {
                // 获取连续签到天数
                $get_sign_data = $this->get_sign_in_status($user_id, 2);
                $continuous_days     = $get_sign_data['continuous_days'];
                $total_reward        = $get_sign_data['base_reward'];
                $extra_reward        = $get_sign_data['extra_reward'];
                $new_continuous_days = $continuous_days + 1;
                $is_continuous_reward = false;
                if ($config['has_continuous_reward'] && $extra_reward > 0) {
                    $total_reward += $extra_reward;
                    $is_continuous_reward = true;
                }
                // 构建签到记录数据
                $sign_data = [
                    'user_id'           => $user_id,
                    'level'             => $user_info['level'] ?? 0,
                    'currency_name'     => $config['currency_name'],
                    'credit'            => $total_reward, // 直接设置正确的奖励值，避免额外更新
                    'sp_date'           => $today,
                    'sp_date_flag'      => $date_flag,
                    'create_time'       => time(),
                    'create_at'         => date('Y-m-d H:i:s'),
                    'continuous_days'   => $new_continuous_days
                ];
                // 插入签到记录
                $sign_in_id = Db::name('sp_sign_in')->insertGetId($sign_data);
                if (!$sign_in_id) {
                    Db::rollback();
                    Log::error('签到记录创建失败: user_id = ' . $user_id);
                    return returnData(0, '签到失败，请稍后重试');
                }
                $currency    = config('currency');
                $currency    = array_flip($currency);
                // 生成奖励描述
                $tip = '签到' . $new_continuous_days . '天奖励' . $total_reward . $currency[$config['currency_name']];
                if ($is_continuous_reward) {
                    $tip = '连续签到' . $config['continuous_days'] . '天奖励' . $total_reward . $currency[$config['currency_name']];
                }

                // 发放奖励
                $credit_result = MemberCreditModel::credit(
                    $user_id,
                    $config['currency_name'],
                    $total_reward,
                    $tip,
                    '',
                    $sign_in_id,
                    'sign_in'
                );

                if (!$credit_result) {
                    Db::rollback();
                    Log::error('签到奖励发放失败: user_id = ' . $user_id . ', reward = ' . $total_reward);
                    return returnData(0, '奖励发放失败，请稍后重试');
                }

                // 提交事务
                Db::commit();

                // 返回签到结果
                return returnData(1, '签到成功', [
                    'continuous_days' => $new_continuous_days,
                    'total_reward'    => $total_reward,
                    'base_reward'     => $get_sign_data['base_reward'],
                    'extra_reward'    => $extra_reward
                ]);
            } catch (Exception $e) {
                // 回滚事务
                Db::rollback();
                Log::error('签到操作失败: ' . $e->getMessage());
                return returnData(0, '签到失败，请稍后重试');
            }
        } catch (Exception $e) {
            Log::error('处理签到请求异常: ' . $e->getMessage());
            return returnData(0, '系统异常，请稍后重试');
        }
    }

    //补签
    public function repair_sign_in() {
        try { 
            if($this->request->isPost()){
                $post           = SafeFilterItem(input('post.'));
                $repair_date    = $post['repair_date'] ?? '';
                $user_id        = $this->memberID;
                $user_info      = $this->memberInfo;
                $date_flag      = date('Y-m');
                $today          = date('Y-m-d');   
                $sign_lj_bq_num = config('sign_lj_bq_num'); //每月最大补签数
                if(!$repair_date){
                    return returnData(0, '请选择补签日期');
                }
                if(strtotime($repair_date) >= strtotime($today)){
                    return returnData(0, '补签日期不能晚于或等于当前日期');
                }
                $repair_sign = Db::name('sp_sign_in')
                    ->where('user_id', $user_id)
                    ->where('sp_date', $repair_date)
                    ->find();
                if(!empty($repair_sign)){
                    return returnData(0, '该日期已存在签到记录，请勿重复操作');
                }
                $repair_sign_count = Db::name('sp_sign_in')
                    ->where('user_id', $user_id)
                    ->where('sign_type',2)
                    ->where('sp_date_flag', $date_flag)
                    ->count();
                if($repair_sign_count>=$sign_lj_bq_num){
                    return returnData(0, '已超出最大补签数');
                }
                // 开启事务
                Db::startTrans();
                try {
                    // 1. 插入补签记录
                    $continuous_days = $this->getContinuousSignInDays($user_id, $repair_date);
                    $new_continuous_days = $continuous_days + 1;
                    
                    // 补签记录数据
                    $sign_in_data = [
                        'user_id'           => $user_id,
                        'level'             => $user_info['level'] ?? 0,
                        'sign_type'         => 2,           // 2表示补签
                        'sp_date'           => $repair_date,
                        'sp_date_flag'      => $date_flag,
                        'sign_time'         => $repair_date,
                        'create_time'       => time(),
                        'create_at'         => date('Y-m-d H:i:s'),
                        'continuous_days'   => $new_continuous_days,
                    ];
                    
                    // 插入补签记录
                    $sign_in_id = Db::name('sp_sign_in')->insertGetId($sign_in_data);
                    if (!$sign_in_id) {
                        Db::rollback();
                        return returnData(0, '补签失败，请稍后重试');
                    }
                    
                    // 2. 更新补签日期之后的所有签到记录的连续签到天数
                    $this->updateContinuousDaysAfterRepair($user_id, $repair_date);
                    
                    // 3. 计算补签奖励
                    $config = $this->getSignInConfig();
                    if (!$config['valid']) {
                        Db::rollback();
                        return returnData(0, $config['error']);
                    }
                    
                    $base_reward  = $config['base_reward'];
                    $extra_reward = 0;
                    
                    // 检查是否触发连续签到奖励
                    // 这里可以根据实际需求决定补签是否享受连续签到奖励
                    // 为了避免过度奖励，这里不计算额外奖励，仅发放基础奖励
                    $total_reward = $base_reward;
                    
                    // 更新补签记录的奖励信息
                    Db::name('sp_sign_in')
                        ->where('id', $sign_in_id)
                        ->update([
                            'currency_name' => $config['currency_name'],
                            'credit' => $total_reward
                        ]);
                    
                    // 4. 发放补签奖励
                    $currency = config('currency');
                    $currency = array_flip($currency);
                    $tip = '补签日期:'.$repair_date.',补签奖励' . $total_reward . $currency[$config['currency_name']];
                    
                    $credit_result = MemberCreditModel::credit(
                        $user_id,
                        $config['currency_name'],
                        $total_reward,
                        $tip,
                        '',
                        $sign_in_id,
                        'repair_sign_in'
                    );
                    
                    if (!$credit_result) {
                        Db::rollback();
                        return returnData(0, '奖励发放失败，请稍后重试');
                    }
                    
                    // 提交事务
                    Db::commit();
                    
                    // 返回补签结果
                    return returnData(1, '补签成功', [
                        'continuous_days' => $new_continuous_days,
                        'total_reward'    => $total_reward
                    ]);
                    
                } catch (Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    Log::error('补签操作失败: ' . $e->getMessage());
                    return returnData(0, '补签失败，请稍后重试');
                }
            }
        } catch (Exception $e) {
            Log::error('处理补签请求异常: ' . $e->getMessage());
            return returnData(0, '系统异常，请稍后重试');
        }
    }

    /**
     * 更新补签日期之后的所有签到记录的连续签到天数
     * @param int $user_id 用户ID
     * @param string $repair_date 补签日期
     */
    private function updateContinuousDaysAfterRepair($user_id, $repair_date)
    {
        try {
            // 获取补签日期之后的所有签到记录，按日期正序排列
            $after_signs = Db::name('sp_sign_in')
                ->where('user_id', $user_id)
                ->where('sp_date', '>', $repair_date)
                ->order('sp_date', 'ASC')
                ->select();
            
            if (empty($after_signs)) {
                return; // 没有需要更新的记录
            }
            
            // 从补签日期开始重新计算连续天数
            $current_continuous = $this->getContinuousSignInDays($user_id, $repair_date) + 1;
            $last_date = $repair_date;
            foreach ($after_signs as $sign) {
                $sign_date = $sign['sp_date'];
                // 检查是否连续（相差1天）
                $days_diff = (strtotime($sign_date) - strtotime($last_date)) / 86400;
                if ($days_diff == 1) {
                    // 连续签到，天数+1
                    $current_continuous++;
                    // 更新连续签到天数
                    Db::name('sp_sign_in')
                        ->where('id', $sign['id'])
                        ->update(['continuous_days' => $current_continuous]);
                    
                    $last_date = $sign_date;
                } else {
                    // 不连续，重新开始计算
                    // 但不更新当前记录的连续天数，因为它可能是一个新的连续周期的开始
                    break;
                }
            }
        } catch (Exception $e) {
            Log::error('更新补签后连续天数异常: ' . $e->getMessage());
            throw $e; // 抛出异常让外层事务回滚
        }
    }

    /**
     * 计算用户当前连续签到天数
     * @param int $user_id 用户ID
     * @param string $today 当前日期
     * @return int 连续签到天数
     */
    private function getContinuousSignInDays($user_id, $today)
    {
        $sign_lj_max_day = (int)config('sign_lj_max_day');  // 最大连续签到天数
        if ($sign_lj_max_day <= 0) {
            $sign_lj_max_day = 30;
        }
        try {
            // 获取用户最近的签到记录，按日期倒序
            $recent_signs = Db::name('sp_sign_in')
                ->where('user_id', $user_id)
                ->where('sp_date', '<', $today)      // 排除今天
                ->order('sp_date', 'DESC')
                ->limit($sign_lj_max_day)            // 最多检查最近$sign_lj_max_day天
                ->column('sp_date');

            if (empty($recent_signs)) {
                return 0; // 没有签到记录
            }

            $continuous_days = 0;
            $current_date = strtotime($today) - 86400; // 昨天
            // 从昨天开始往前检查连续签到
            foreach ($recent_signs as $sign_date) {
                $sign_timestamp = strtotime($sign_date);
                // 如果是连续的一天
                if ($sign_timestamp == $current_date) {
                    $continuous_days++;
                    $current_date -= 86400; // 检查前一天
                } else {
                    // 连续性中断
                    break;
                }
            }
            return $continuous_days;
        } catch (Exception $e) {
            Log::error('计算连续签到天数异常: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * 获取签到配置信息
     * @return array 配置信息数组
     */
    private function getSignInConfig()
    {
        // 一次性获取所有配置
        $base_reward       = (int)config('sign_num');
        $currency_name     = config('sign_currency_name');
        $continuous_days   = (int)config('sign_lj_day');
        $continuous_reward = (int)config('sign_lj_num');

        // 验证基础配置
        if ($base_reward <= 0) {
            return [
                'valid' => false,
                'error' => '签到奖励配置有误'
            ];
        }

        if (empty($currency_name)) {
            return [
                'valid' => false,
                'error' => '签到奖励货币配置有误'
            ];
        }

        // 返回配置信息
        return [
            'valid'                 => true,
            'base_reward'           => $base_reward,
            'currency_name'         => $currency_name,
            'continuous_days'       => $continuous_days,
            'continuous_reward'     => $continuous_reward,
            'has_continuous_reward' => $continuous_days > 0 && $continuous_reward > 0
        ];
    }

    /**
     * 获取用户连续签到状态
     * @return array 连续签到信息
     */
    public function get_sign_in_status($user_id = 0, $curl_type = 1)
    {
        try {
            // 获取用户ID（已在父类中验证登录状态）
            if (!$user_id) {
                $user_id = $this->memberID;
            }
            $today   = date('Y-m-d');

            // 获取今日签到状态
            $today_sign = Db::name('sp_sign_in')
                ->where('user_id', $user_id)
                ->where('sp_date', $today)
                ->find();

            // 获取当前连续签到天数
            $continuous_days = 0;
            $occur_days = 0;
            if ($today_sign) {  //如果今天已签到，则获取连续签到天数
                $continuous_days    = $today_sign['continuous_days'] ?? 0;
                $occur_days         = $continuous_days;
            } else {            //如果今天未签到，则计算连续签到天数
                $continuous_days    = $this->getContinuousSignInDays($user_id, $today);
                $occur_days = $continuous_days + 1;
            }
            $next_reward_days   = $this->getNextRewardDays($occur_days);
            // 获取配置信息
            $sign_num           = (int)config('sign_num');
            $sign_lj_num        = 0;
            $sign_lj_day        = (int)config('sign_lj_day');           //累计签到天（基数-D）
            // 如果今天是奖励节点，计算额外奖励
            //if ( $continuous_days>=$sign_lj_day) {
            if ($next_reward_days == $today && $occur_days >= $sign_lj_day) {
                $sign_lj_max_day     = (int)config('sign_lj_max_day');   //最大连续签到天数
                if ($occur_days >= $sign_lj_max_day) {
                    $occur_days = $sign_lj_max_day;
                }
                $fast_day            = $occur_days % $sign_lj_day;
                $sign_lj_fee         = (float)config('sign_lj_fee');     //签到额外赠送（倍率-F）
                $sign_lj_day         = (int)config('sign_lj_day');       //累计签到天（基数-D）
                $sign_lj_num         = (int)config('sign_lj_num');       //累计签到赠送（基数-N）
                if (!$fast_day) {
                    $sign_lj_num = $sign_lj_num + ($occur_days / $sign_lj_day - 1) * $sign_lj_fee * $sign_lj_num;
                }
                if (!empty($today_sign)) {
                    $next_reward_days = date('Y-m-d', strtotime('+' . $sign_lj_day . ' days'));
                }
                //计算额外奖励数量
            }
            if ($curl_type == 1) {
                return returnData(1, '获取成功', [
                    'today_signed'      => !empty($today_sign) ? 1 : 0,             //今日是否签到
                    'continuous_days'   => $continuous_days,                // 连续签到天数
                    'base_reward'       => $sign_num,                       // 今日基础奖励数量
                    'extra_reward'      => $sign_lj_num,                    // 今日额外奖励数量
                    'next_reward_days'  => $next_reward_days,               // 下一个奖励节点日期

                ]);
            } else {
                return [
                    'today_signed'      => !empty($today_sign) ? 1 : 0,             //今日是否签到
                    'continuous_days'   => $continuous_days,                // 连续签到天数
                    'base_reward'       => $sign_num,                       // 今日基础奖励数量
                    'extra_reward'      => $sign_lj_num,                    // 今日额外奖励数量
                    'next_reward_days'  => $next_reward_days,               // 下一个奖励节点日期

                ];
            }
        } catch (Exception $e) {
            Log::error('获取签到状态失败: ' . $e->getMessage());
            return returnData(0, '获取失败，请稍后重试');
        }
    }

    /**
     * 获取下一个奖励节点天数
     * @param int $current_days 当前连续签到天数
     * @return string 下一个奖励节点日期
     */
    private function getNextRewardDays($current_days)
    {
        $today = date('Y-m-d');
        // 获取特殊奖励节点信息
        $sign_lj_day = (int)config('sign_lj_day');
        if ($sign_lj_day > 0) {
            $remainder = $current_days % $sign_lj_day;
            if ($remainder > 0) {
                $days_to_reward = $sign_lj_day - $remainder;
                $next_time = strtotime('+' . $days_to_reward . ' days');
                return date('Y-m-d', $next_time);
            }
        }
        return $today;
    }
}
