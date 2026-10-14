<?php


namespace app\member\library;


use Carbon\Carbon;
use think\Exception;
use think\facade\Cache;

class JwtAuth
{
    private $jwtToken;
    private $user;
    private $guard = 'default';
    Private $allGuards = [];
    public function __construct()
    {
        $this->jwtToken = new JwtToken();
        $this->allGuards = config('jwt.guards');
    }
    public function guard($name){
        if ($name=='default' || !$name) return $this;
        if (!isset($this->allGuards[$name])) throw new Exception('guard is error');
        $this->guard = $name;
        return $this;
    }

    public function createToken($uid)
    {
        $jwt_data = [
            'id' => $uid,
            'guard' => $this->guard,
            'iss' => 'jwt_admin_bfg',                         //该JWT的签发者
            'iat' => time(),                                  //签发时间
            'exp' => Carbon::now()->addMinutes(config('jwt.ttl'))->timestamp,                             //过期时间
            'jti' => md5(uniqid('JWT') . time())    //该Token唯一标识
        ];
        $cache_time = $jwt_data['iat'] + config('jwt.refresh_ttl')* 60;
        $jwtToken = $this->jwtToken;
        $token = $jwtToken->getToken($jwt_data);
        //session('Token', $token);
        cache($jwt_data['jti'], $token, $cache_time);
        return $token;
    }

    public function getToken()
    {
        $token = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : null;
        if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) && !$token){
            $token = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }
        if(!$token) $token=isset($_REQUEST['Authorization']) ? $_REQUEST['Authorization'] : null;
        if(!$token) return false;

        return $token;
    }
    public function refresh($token=null){
        if (!$token) $token = $this->requireToken(true);
        $jwtToken = $this->jwtToken;
        $payload = $jwtToken->getPayload($token);

        $oldToken = Cache::pull($payload['jti']);
        if (!$oldToken){
            return false;
        }
        $token = $this->createToken($payload['id']);
        return $token;

    }
    public function requireToken($returnToken=false){
        $token = $this->getToken();
        if(!$token) throw new Exception('A token is required');
        return $returnToken ? $token : true;
    }
    public function check($getPayload=false){

        try {
            $token = $this->requireToken(true);
            $payload = $this->jwtToken->verifyToken($token);
            $oldToken = Cache::get($payload['jti']);
            if (!$oldToken){
                return false;
            }
            if ($payload['guard'] != $this->guard) return false;
        } catch (Exception $e) {
            return false;
        }

        return $getPayload ? $payload : true;

    }
    public function user()
    {
        if ($this->user !== null) {
            return $this->user;
        }

        if ($this->getToken() &&
            ($payload = $this->check(true))
        ) {
            return $this->user = $this->retrieveById($payload['id']);
        }
    }

    public function retrieveById($uid){
        $model = $this->createModel();
        return $model->retrieveById($uid);
    }
    public function createModel()
    {
        $class = $this->getModelClass();
        $class = '\\'.ltrim($class, '\\');

        return new $class;
    }
    public function getModelClass(){
        $guard = $this->guard;
        if ($guard == 'default')  $guard = config('jwt.defaults.guard');
        return $this->allGuards[$guard]['model'] ?? null;
    }
}