<?php

namespace app\finance\facade;


use think\Facade;

class CashApp extends Facade
{
    protected static function getFacadeClass()
    {
        return 'app\finance\library\CashAppManager';
    }
}
