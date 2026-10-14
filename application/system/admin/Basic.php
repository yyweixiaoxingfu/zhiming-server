<?php
namespace app\system\admin;

use app\common\controller\Common;
use app\common\library\ExcelServer;
use think\facade\Cache;

class Basic extends Common
{
    public function export_error($uuid = ''){
        if (!$uuid) $this->error('操作失败');

        try {
            $error = Cache::get($uuid);

            ExcelServer::export($error['title'] ?? '错误记录',$error['data']);

        }catch (\Exception $e){
            $this->error('操作失败');
        }
    }



}
