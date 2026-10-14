<?php

namespace app\payment\library;

interface PayAppInterface
{
    public function getName();
    public function getType();
    public function getKey();
    public function getConfig();
    public function getCallback();

    //public function setting();

    public function doPay(array $params, &$msg='');
    public function callback(array $params, &$msg='');
    //public function result(array $params, &$msg='');

}