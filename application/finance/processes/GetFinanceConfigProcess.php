<?php


namespace app\finance\processes;


class GetFinanceConfigProcess
{
    public function handle(&$basicConfig){
        $key='withdraw_fee';
        $basicConfig[$key] = (float) config($key);
        $key='withdraw_low';
        $basicConfig[$key] = (float) config($key);
        $key='withdraw_bei';
        $basicConfig[$key] = (float) config($key);
        $key='withdraw_type';
        $basicConfig[$key] = config($key);
        $key='withdraw_auto_pay';
        $basicConfig[$key] = (bool) config($key);
        $key='withdraw_tips';
        $basicConfig[$key] = (string) config($key);
        return true;
    }

}