<?php

namespace app\finance\model;

use think\Db;
use think\Model;
use think\model\concern\SoftDelete;

class MemberBankModel extends Model
{
    use SoftDelete;
    protected $name = 'member_bank';
    protected $autoWriteTimestamp = true;
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
}