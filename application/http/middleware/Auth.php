<?php
namespace app\http\middleware;

use app\member\facade\JwtAuth;

/**
 * 检查用户登录
 */
class Auth
{
    public function handle($request, \Closure $next, $guard = 'default')
    {
        $jwtAuth = JwtAuth::guard($guard);
        if (!$jwtAuth->check() || !$userInfo = $jwtAuth->user()) {
            return returnData(401, '请先登录!');
        }
        if ($userInfo['lock'] == 1) {
            return returnData(401, '该账号已被禁用');
        }
        // TODO 其他用户限制

        $request->memberInfo = $userInfo;
        $request->memberID   = $userInfo['id'] ?? null;
        return $next($request);
    }

}