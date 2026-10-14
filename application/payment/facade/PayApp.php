<?php

namespace app\payment\facade;


use think\Facade;

class PayApp extends Facade
{
    protected static function getFacadeClass()
    {
        return 'app\payment\library\PayAppManager';
    }
}
