<?php

namespace app\finance\library;

interface CashInterface
{
    public function doCash(array $params, &$msg = '');

}