<?php

namespace app\member\facade;


use think\Facade;

class JwtAuth extends Facade
{
    protected static function getFacadeClass()
    {
        return 'app\member\library\JwtAuth';
    }
}
