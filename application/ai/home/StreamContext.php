<?php
// StreamContext.php - AI流式响应上下文
namespace app\ai\home;
use think\Controller;

/**
 * AI流式响应上下文类
 * 用于存储流式响应过程中的状态和数据
 */
class StreamContext extends Controller
{
    // 错误处理相关
    public bool $firstError = true;
    public bool $isError = false;
    public string $errorCode = '';
    public string $errorMsg = '';
    
    // 模型特定状态
    public string $tongyiNowText = '';
    public string $claudeMsgId = '';
    
    // 缓冲区数据
    public string $bufferData = ''; // 通用缓冲区
    public string $bardBuffer = ''; // Bard模型缓冲区
    public int $bardBufferCount = 0; // Bard缓冲区计数
    
    // 响应数据
    public string $responseData = '';
    public string $imageUrl = '';
    
    // 其他状态
    public bool $isStart = true;
    
    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->__initialize();
    }
    
    /**
     * 初始化上下文
     */
    private function __initialize()
    {
        $this->firstError = true;
        $this->isError = false;
        $this->errorCode = '';
        $this->errorMsg = '';
        $this->tongyiNowText = '';
        $this->claudeMsgId = '';
        $this->bufferData = '';
        $this->bardBuffer = '';
        $this->bardBufferCount = 0;
        $this->responseData = '';
        $this->imageUrl = '';
        $this->isStart = true;
    }
}