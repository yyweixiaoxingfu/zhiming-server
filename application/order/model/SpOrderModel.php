<?php
namespace app\order\model;

use think\Model;
use think\facade\Env;
use app\common\CacheManager;

/**
 * 测算订单模型
 */
class SpOrderModel extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $name = 'sp_order';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;
    
    // JSON数据阈值（字节）
    const JSON_THRESHOLD = 5120; // 5MB
    
    /**
     * 获取订单数据，自动处理return_data和format_data
     * @param array $where 查询条件
     * @return array|null
     */
    public static function getOrderData($where)
    {
        $order = self::where($where)->find();
        if (!$order) {
            return null;
        }
        
        // 处理return_data
        if ($order['return_data_status'] == 1 && $order['return_data_path']) {
            $filePath = Env::get('root_path').'public/' . $order['return_data_path'];
            if (file_exists($filePath)) {
                $order['return_data'] = file_get_contents($filePath);
            }
        }
        
        // 处理format_data（如果需要）
        if ($order['format_data_status'] == 1 && $order['format_data_path']) {
            $filePath = realpath(Env::get('root_path').'public/' . $order['format_data_path']);
            if (file_exists($filePath)) {
                $order['format_data'] = file_get_contents($filePath);
            }
        }
        
        return $order;
    }
    
    /**
     * 保存测算结果，自动处理缓存
     * @param string $orderNo 订单号
     * @param array $result 测算结果
     * @return bool 保存结果
     */
    public static function saveResult($orderNo, $result)
    {
        $returnData = json_encode($result, JSON_UNESCAPED_UNICODE);
        $dataSize   = strlen($returnData);
        $updateData = [
            'return_data'           => $returnData,
            'return_data_status'    => 0,
            'return_data_size'      => $dataSize,
            'update_time'           => time(),
            'cache_update_time'     => time()
        ];
        // 如果超过阈值，存储到文件系统
        if ($dataSize > self::JSON_THRESHOLD) {
            $filePath = 'cache/order/' . date('Y/m/') . $orderNo . '_return.json';
            $fullPath = Env::get('root_path').'public/' . $filePath;
            $dir      = dirname($fullPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($fullPath, $returnData);
            
            $updateData['return_data'] = null;
            $updateData['return_data_status'] = 1;
            $updateData['return_data_path'] = $filePath;
        }
        
        return self::where('order_no', $orderNo)->update($updateData);
    }
    
    /**
     * 清理format_data，将其存储到文件系统或直接清理
     * @param string $orderNo 订单号
     * @param bool $keepInFile 是否保留在文件系统
     * @return bool 清理结果
     */
    public static function cleanFormatData($orderNo, $keepInFile = false)
    {
        $order = self::where('order_no', $orderNo)->find();
        if (!$order) {
            return false;
        }
        
        $updateData = [
            'format_data_status' => 2, // 已清理
            'cache_update_time' => time()
        ];
        
        if ($keepInFile && $order['format_data']) {
            $filePath = 'cache/order/' . date('Y/m/') . $orderNo . '_format.json';
            $fullPath = Env::get('root_path').'public/' . $filePath;
            $dir = dirname($fullPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($fullPath, $order['format_data']);
            
            $updateData['format_data'] = null;
            $updateData['format_data_status'] = 1; // 文件系统存储
            $updateData['format_data_path'] = $filePath;
            $updateData['format_data_size'] = strlen($order['format_data']);
        } else {
            $updateData['format_data'] = null;
        }
        
        return self::where('order_no', $orderNo)->update($updateData);
    }
}
