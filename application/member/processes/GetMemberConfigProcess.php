<?php

namespace app\member\processes;


class GetMemberConfigProcess
{
    public function handle(&$basicConfig){

        $key='invitation_code';
        $basicConfig[$key] = (bool) config($key);
        $key='register_switch';
        $basicConfig[$key] = (bool) config($key);
        $key='register_rule';
        $basicConfig[$key] = Img_url((string) config($key));

        return true;
    }
}