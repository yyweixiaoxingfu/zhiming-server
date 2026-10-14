<?php

namespace app\ai\service;

use app\member\model\MemberModel;
use app\member\model\MemberCreditModel;

/**
 * AI认证服务类
 * 处理认证和权限管理
 */
class AIAuthService
{
    /**
     * 验证用户身份
     * @param string $userRndStr 用户随机字符串
     * @return array|false 用户信息或false
     */
    public static function verifyUser(?string $userRndStr)
    {
        if (empty($userRndStr)) {
            return false;
        }
        return MemberModel::where('rndstr', $userRndStr)->where('lock', 0)->find();
    }

    /**
     * 检查用户积分是否足够
     * @param array|object $user 用户信息
     * @param float $modelPrice 模型价格
     * @return bool 是否足够
     */
    public static function checkUserQuota($user, float $modelPrice): bool
    {
        // 使用MemberCreditModel获取用户积分
        $userId = is_object($user) ? $user->id : $user["id"];
        $points = MemberCreditModel::getCredit($userId, 'points', ['credit']);
        return $points >= $modelPrice;
    }

    /**
     * 更新用户最后问题
     * @param int $userId 用户ID
     * @param string $lastQuestion 最后问题
     * @param int $lastModelId 最后模型ID
     * @return bool 是否更新成功
     */
    public static function updateUserLastQuestion(int $userId, string $lastQuestion, int $lastModelId): bool
    {
        return MemberModel::where('id', $userId)->where('lock', 0)->update([
            'lastquestion' => $lastQuestion,
            'lastmodelid'  => $lastModelId
        ]) > 0;
    }

    /**
     * 用户登出
     * @param string $userRndStr 用户随机字符串
     * @return bool 是否登出成功
     */
    public static function logout(string $userRndStr): bool
    {
        return MemberModel::where('rndstr', $userRndStr)->update(['rndstr' => '']) > 0;
    }

    /**
     * 验证上传令牌
     * @param string $nonce 随机数
     * @param string $sign 签名
     * @param string $token 令牌
     * @return bool 是否验证通过
     */
    public static function verifyUploadToken(string $nonce, string $sign, string $token): bool
    {
        $currentTime = time();
        // 验证时间戳是否在有效范围内（60秒）
        if (($currentTime - $nonce) > 60 || ($currentTime - $nonce) < 0) {
            return false;
        }
        // 验证签名
        return $sign === md5(md5($nonce) . $token);
    }
}
