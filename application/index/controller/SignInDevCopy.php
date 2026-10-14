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
                $continuous_days = $this->getContinuousSignInDays($user_id, $today);
                $new_continuous_days = $continuous_days + 1;
                
                // 计算奖励金额
                $total_reward = $config['base_reward'];
                $extra_reward = 0;
                $is_continuous_reward = false;
                
                if ($config['has_continuous_reward'] && $new_continuous_days % $config['continuous_days'] === 0) {
                    $total_reward += $config['continuous_reward'];
                    $extra_reward = $config['continuous_reward'];
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
                    'reward'          => $total_reward,
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

    /**
     * 计算用户当前连续签到天数
     * @param int $user_id 用户ID
     * @param string $today 当前日期
     * @return int 连续签到天数
     */
    private function getContinuousSignInDays($user_id, $today)
    {
        try {
            // 获取用户最近的签到记录，按日期倒序
            $recent_signs = Db::name('sp_sign_in')
                ->where('user_id', $user_id)
                ->where('sp_date', '<', $today) // 排除今天
                ->order('sp_date', 'DESC')
                ->limit(30)            // 最多检查最近30天
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
    public function get_sign_in_status()
    {
        try {
            // 获取用户ID（已在父类中验证登录状态）
            $user_id = $this->memberID;
            $today   = date('Y-m-d');
            
            // 获取今日签到状态
            $today_sign = Db::name('sp_sign_in')
                ->where('user_id', $user_id)
                ->where('sp_date', $today)
                ->find();

            // 获取当前连续签到天数
            $continuous_days = 0;
            if ($today_sign) {
                $continuous_days = $today_sign['continuous_days'] ?? 0;
            } else {
                $continuous_days = $this->getContinuousSignInDays($user_id, $today);
            }
            
            // 获取下一个奖励节点
            $next_reward_days = $this->getNextRewardDays($continuous_days);
            
            // 获取配置信息
            $sign_num    = (int)config('sign_num');
            $sign_lj_num = 0;
            
            // 如果今天是奖励节点，计算额外奖励
            if ($next_reward_days == $today) {
                $sign_lj_num = (int)config('sign_lj_num');
            }
            
            return returnData(1, '获取成功', [
                'today_signed'      => !empty($today_sign),
                'continuous_days'   => $continuous_days,
                'next_reward_days'  => $next_reward_days,
                'base_reward'       => $sign_num,
                'extra_reward'      => $sign_lj_num,
            ]);
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



// namespace app\index\controller;
// use think\facade\Log;
// use think\Exception;
// use app\member\model\MemberCreditModel;
// use think\Db;

// header('Access-Control-Allow-Origin: *'); // 允许所有域名访问

// /**
//  * 前台首页控制器
//  * @package app\index\controller
//  */
// class SignIn extends Api
// {
//     /**
//      * 用户签到操作
//      * @return array 签到结果和奖励信息
//      */
//     public function sign_in()
//     {
//         try {
//             // 获取用户ID（需要根据您的登录系统调整获取方式）
//             $user_id     = $this->memberID;
//             $user_info   = $this->memberInfo;
//             // 获取当前日期信息
//             $today              = date('Y-m-d');
//             //$today_timestamp    = strtotime($today);
//             $date_flag          = date('Y-m');
//             // 检查今日是否已签到
//             $today_sign = Db::name('sp_sign_in')
//                 ->where('user_id', $user_id)
//                 ->where('sp_date', $today)
//                 ->find();

//             if ($today_sign) {
//                 return returnData(0, '今日已签到');
//             }
//             // 发放签到奖励
//             $base_reward  = config('sign_num') ?(int)config('sign_num'): 0; // 基础签到奖励
//             if(!$base_reward || $base_reward <= 0){
//                 return returnData(0, '签到奖励配置有误');
//             }
//             $sign_currency_name = config('sign_currency_name') ? config('sign_currency_name') : ''; // 签到奖励货币名称
//             if(!$sign_currency_name){
//                 return returnData(0, '签到奖励货币配置有误');
//             }
//             // $currency    = config('currency');
//             // $currency    = array_flip($currency);
           

//             // 开启事务
//             Db::startTrans();

//             try {
//                 // 获取连续签到天数
//                 $continuous_days = $this->getContinuousSignInDays($user_id, $today);
//                 // 保存签到记录
//                 $sign_data = [
//                     'user_id'           => $user_id,
//                     'level'             => $user_info['level'],
//                     'currency_name'     => $sign_currency_name,
//                     'credit'            => 0,
//                     'sp_date'           => $today,
//                     'sp_date_flag'      => $date_flag,
//                     'create_time'       => time(),
//                     'create_at'         => date('Y-m-d H:i:s', time()),
//                     'continuous_days'   => $continuous_days + 1 // 新的连续签到天数
//                 ];

//                 $res = Db::name('sp_sign_in')->insertGetId($sign_data);
//                 if(!$res){
//                     Db::rollback();
//                     return returnData(0, '签到失败，请稍后重试');
//                 }
//                 $total_reward = $this->grantSignInReward($res,$user_id, $continuous_days + 1, $base_reward);

//                 // 提交事务
//                 Db::commit();

//                 // 返回签到结果
//                 return returnData(1, '签到成功', [
//                     'continuous_days'   => $continuous_days + 1,
//                     'reward'            => $total_reward,
//                     'extra_reward'      => $total_reward > $base_reward ? ($total_reward - $base_reward) : 0
//                 ]);
//             } catch (Exception $e) {
//                 // 回滚事务
//                 Db::rollback();
//                 Log::error('签到操作失败: ' . $e->getMessage());
//                 return returnData(0, '签到失败，请稍后重试');
//             }
//         } catch (Exception $e) {
//             Log::error('处理签到请求异常: ' . $e->getMessage());
//             return returnData(0, '系统异常，请稍后重试');
//         }
//     }

//     /**
//      * 计算用户当前连续签到天数
//      * @param int $user_id 用户ID
//      * @param string $today 当前日期
//      * @return int 连续签到天数
//      */
//     private function getContinuousSignInDays($user_id, $today)
//     {
//         // 获取用户最近的签到记录，按日期倒序
//         $recent_signs = Db::name('sp_sign_in')
//             ->where('user_id', $user_id)
//             ->where('sp_date', '<', $today) // 排除今天
//             ->order('sp_date', 'DESC')
//             ->limit(30) // 最多检查最近30天
//             ->column('sp_date');

//         if (empty($recent_signs)) {
//             return 0; // 没有签到记录
//         }
//         $continuous_days = 0;
//         $current_date    = strtotime($today) - 86400; // 昨天
//         // 从昨天开始往前检查连续签到
//         foreach ($recent_signs as $sign_date) {
//             $sign_timestamp = strtotime($sign_date);
//             // 如果是连续的一天
//             if ($sign_timestamp == $current_date) {
//                 $continuous_days++;
//                 $current_date -= 86400; // 检查前一天
//             } else {
//                 // 连续性中断
//                 break;
//             }
//         }

//         return $continuous_days;
//     }

//     /**
//      * 发放签到奖励
//      * @param int $user_id 用户ID
//      * @param int $continuous_days 连续签到天数
//      * @param int $base_reward 基础奖励
//      * @return int 实际发放的总奖励
//      */
//     private function grantSignInReward($sign_in_id,$user_id, $continuous_days, $base_reward)
//     {
//         $total_reward = $base_reward;
//         $sign_lj_day  = config('sign_lj_day') ?(int)config('sign_lj_day'): 0; // 连续签到奖励天数
//         $sign_lj_num  = config('sign_lj_num') ?(int)config('sign_lj_num'): 0; // 连续签到奖励奖励
//         $lj_type      = 0;
//         if($sign_lj_day && $sign_lj_num){
//             $continuous_days = $continuous_days % $sign_lj_day;
//             if($continuous_days == 0){
//                 $lj_type = 1;
//                 $total_reward += $sign_lj_num;
//             }
//         }
//         $sign_currency_name = config('sign_currency_name');
//         // 以下为示例代码，需要根据实际系统调整
//         $tip = '签到'.$continuous_days.'天奖励';
//         if($lj_type){
//             $tip = '累计签到'.$sign_lj_num.'天奖励'.$sign_lj_num.'积分';
//         }
//         // 这里根据您的系统设计发放奖励
//         // 例如：更新用户积分、余额等
//         Db::name('sp_sign_in')->where('id',$sign_in_id)->update(['credit'=>$total_reward]);
//         MemberCreditModel::credit($user_id, $sign_currency_name, $total_reward, $tip,'',$sign_in_id,'sign_in');
//         return $total_reward;
//     }

//     /**
//      * 获取用户连续签到状态
//      * @return array 连续签到信息
//      */
//     public function get_sign_in_status()
//     {
//         try {
//             // 获取用户ID
//             $user_id     = $this->memberID;
//             $today       = date('Y-m-d');
//             // 获取今日签到状态
//             $today_sign = Db::name('sp_sign_in')
//                 ->where('user_id', $user_id)
//                 ->where('sp_date', $today)
//                 ->find();

//             // 获取当前连续签到天数
//             $continuous_days = 0;
//             if ($today_sign) {
//                 $continuous_days = $today_sign['continuous_days'] ?? 0;
//             } else {
//                 $continuous_days = $this->getContinuousSignInDays($user_id, $today);
//             }
//             //$continuous_days = 4;
//             $next_reward_days = $this->getNextRewardDays($continuous_days);
//             $sign_num    = config('sign_num');
//             $sign_lj_num = 0;
//             if($next_reward_days == $today){
//                 $sign_lj_num  = config('sign_lj_num') ?(int)config('sign_lj_num'): 0; // 连续签到奖励奖励
//             }
//             return returnData(1, '获取成功', [
//                 'today_signed'          => !empty($today_sign),
//                 'continuous_days'       => $continuous_days,
//                 'next_reward_days'      => $next_reward_days,
//                 'base_reward'           => $sign_num,
//                 'extra_reward'          => $sign_lj_num,
//             ]);
//         } catch (Exception $e) {
//             Log::error('获取签到状态失败: ' . $e->getMessage());
//             return returnData(0, '获取失败，请稍后重试');
//         }
//     }

//     /**
//      * 获取下一个奖励节点天数
//      * @param int $current_days 当前连续签到天数
//      * @return int 下一个奖励节点天数
//      */
//     private function getNextRewardDays($current_days)
//     {
//         $time = time();
//         $date = date('Y-m-d',$time);
//         // 获取特殊奖励节点信息
//         $sign_lj_day  = config('sign_lj_day') ?(int)config('sign_lj_day'): 0; // 连续签到奖励天数
//         if($sign_lj_day){
//             $continuous_days = $current_days % $sign_lj_day;
//             if($continuous_days > 0){
//                 $next_reward_days   = $sign_lj_day - $continuous_days;
//                 $next_time          = strtotime('+'.$next_reward_days.' days');
//                 $date               = date('Y-m-d', $next_time);
//             }
//         }
//         return $date;
//     }
// }

