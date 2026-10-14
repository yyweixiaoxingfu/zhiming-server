<?php
// StreamResponseHandler.php - AI模型流式响应处理器
namespace app\ai\library;
use app\ai\home\StreamContext;

if (ob_get_level() > 0) {
    // 清空缓冲区
    ob_end_clean();
}

// 设置响应头
error_reporting(E_ALL ^ E_WARNING);
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/event-stream");
header("X-Accel-Buffering: no");
set_time_limit(0);

/**
 * AI模型流式响应处理器
 * 处理不同AI模型的流式响应，统一输出格式
 */
class StreamResponseHandler
{
    private string $modelType;
    private string $modelValue;
    private StreamContext $context;
    
    private string $responseData = '';
    private bool $isStart = true;
    private bool $isError = false;
    private string $errorCode = '';
    private string $errorMsg = '';
    
    /**
     * 构造函数
     * @param string $modelType 模型类型
     * @param string $modelValue 模型值
     * @param StreamContext $context 上下文对象
     */
    public function __construct(string $modelType, string $modelValue, StreamContext $context)
    {
        $this->modelType = $modelType;
        $this->modelValue = $modelValue;
        $this->context = $context;
    }

    /**
     * 处理响应数据
     * @param resource $ch curl资源
     * @param string $data 响应数据
     * @return int 数据长度
     */
    public function handleResponse($ch, string $data): int
    {
        $complete = json_decode($data);
        
        // 错误处理
        if ($this->handleErrors($complete, $data)) {
            return strlen($data);
        }
        
        // 根据模型类型处理数据
        switch ($this->modelType) {
            case 'OpenAI':
                return $this->handleOpenAI($data);
            case '通义千问':
                return $this->handleTongyiQianwen($data);
            case 'Claude':
                return $this->handleClaude($data);
            case '文心千帆':
                return $this->handleBaidu($data);
            case '腾讯混元':
                return $this->handleTencent($data);
            case 'MiniMax':
                return $this->handleMiniMax($data);
            case '无问芯穹':
                return $this->handleWuwen($data);
            case 'bard':
                return $this->handleBard($data);
            default:
                return $this->handleGeneric($data);
        }
    }

    /**
     * 处理错误信息
     * @param mixed $complete 解析后的JSON数据
     * @param string $data 原始数据
     * @return bool 是否发生错误
     */
    private function handleErrors($complete, string $data): bool
    {
        // OpenAI错误处理
        if (isset($complete->error)) {
            $this->errorMsg = $complete->error->message;
            $this->errorCode = $this->mapOpenAIErrorCode($this->errorMsg);
            $this->sendError($this->errorCode, $this->errorMsg);
            return true;
        }

        // 百度错误处理
        if (isset($complete->error_code)) {
            $this->errorCode = $complete->error_code;
            $this->errorMsg = $complete->error_msg;
            if ((($this->errorCode == 110) || ($this->errorCode == 111)) && $this->context->firstError) {
                $this->context->firstError = false;
                // 重新获取token并重试
                return false;
            } else {
                $this->sendError($this->errorCode, $this->errorMsg);
                return true;
            }
        }

        // 腾讯混元错误处理
        if (isset($complete->Response) && isset($complete->Response->Error)) {
            $this->errorCode = $complete->Response->Error->Code;
            $this->errorMsg = $complete->Response->Error->Message;
            $this->sendError($this->errorCode, $this->errorMsg);
            return true;
        }

        return false;
    }

    /**
     * 处理OpenAI响应
     * @param string $data 响应数据
     * @return int 数据长度
     */
    private function handleOpenAI(string $data): int
    {
        // 标准OpenAI格式处理
        $lines = explode("\n", $data);
        foreach ($lines as $line) {
            if (strpos($line, 'data: ') === 0) {
                $jsonData = substr($line, 6);
                if ($jsonData === '[DONE]') {
                    $this->sendDone();
                    break;
                }
                
                $parsed = json_decode($jsonData);
                if ($parsed && isset($parsed->choices[0]->delta->content)) {
                    echo $line . "\n\n";
                    $this->responseData .= $line . "\n\n";
                }
            }
        }
        return strlen($data);
    }

    /**
     * 处理通义千问响应
     * @param string $data 响应数据
     * @return int 数据长度
     */
    private function handleTongyiQianwen(string $data): int
    {
        if (substr($data, 0, 3) === "id:") {
            $msg = preg_replace('/id:[\s\S]*?data:/', '', $data);
            $contentarr = json_decode(trim($msg));
            
            if (isset($contentarr->code)) {
                $this->sendError($contentarr->code, $contentarr->message);
                return strlen($data);
            }

            return $this->processTongyiContent($contentarr, $data);
        }
        return strlen($data);
    }

    /**
     * 处理通义千问内容
     * @param mixed $contentarr 解析后的内容
     * @param string $rawData 原始数据
     * @return int 数据长度
     */
    private function processTongyiContent($contentarr, string $rawData): int
    {
        if (isset($contentarr->output)) {
            // 文本模型
            if (isset($contentarr->output->text)) {
                $content = $contentarr->output->text;
                $this->sendDeltaContent($content, $this->context->tongyiNowText, 'TongyiText');
                $this->context->tongyiNowText = $content;
            }
            
            // 识图模型
            if (isset($contentarr->output->choices[0]->message->content[0]->text)) {
                $content = $contentarr->output->choices[0]->message->content[0]->text;
                $this->sendDeltaContent($content, $this->context->tongyiNowText, 'TongyiImage');
                $this->context->tongyiNowText = $content;
            }
            
            // 结束标识
            if (isset($contentarr->output->finish_reason) && $contentarr->output->finish_reason === "stop") {
                preg_match('/id:(\d+)/', $rawData, $matches);
                $this->sendFinish($matches[1] ?? 'tongyi');
            }
        }
        
        if ($this->isStart) {
            $this->isStart = false;
        }
        
        return strlen($rawData);
    }

    /**
     * 处理Claude响应
     * @param string $data 响应数据
     * @return int 数据长度
     */
    private function handleClaude(string $data): int
    {
        if (substr($data, 0, 6) === "event:") {
            if (substr($data, 0, 12) === "event: error") {
                $complete = json_decode("{" . explode("data: {", $data)[1]);
                $this->sendError($complete->error->type, $complete->error->message);
                return strlen($data);
            }

            return $this->processClaudeEvents($data);
        }
        return strlen($data);
    }

    /**
     * 处理Claude事件
     * @param string $data 响应数据
     * @return int 数据长度
     */
    private function processClaudeEvents(string $data): int
    {
        if ($this->isStart) {
            $this->isStart = false;
        }

        foreach (explode("\n\nevent: ", "\n\n" . $data) as $msg) {
            preg_match_all('/data:(.*)/', $msg, $matches);
            $msg = implode('', $matches[1]);
            $msgjson = json_decode($msg, true);
            
            if ($msgjson["type"] == "message_start") {
                $this->context->claudeMsgId = $msgjson["message"]["id"];
            }
            
            if ($msgjson["type"] == "content_block_delta") {
                $content = $msgjson["delta"]["text"];
                $this->sendDeltaContent($content, '', $this->context->claudeMsgId, 'Claude');
            }
            
            if ($msgjson["type"] == "content_block_stop") {
                $this->sendFinish($this->context->claudeMsgId);
            }
        }
        
        return strlen($data);
    }

    /**
     * 处理百度响应
     * @param string $data 响应数据
     * @return int 数据长度
     */
    private function handleBaidu(string $data): int
    {
        if ($this->modelValue == "fuyu_8b") {
            return $this->handleBaiduFuyu($data);
        }
        
        return $this->handleBaiduStream($data);
    }

    /**
     * 处理百度Fuyu响应
     * @param string $data 响应数据
     * @return int 数据长度
     */
    private function handleBaiduFuyu(string $data): int
    {
        $contentarr = json_decode(trim($data));
        if (isset($contentarr->result)) {
            $this->sendDeltaContent($contentarr->result, '', 'BaiduFuyu');
            $this->sendFinish($contentarr->id ?? 'baidu');
        }
        return strlen($data);
    }

    /**
     * 处理百度流式响应
     * @param string $data 响应数据
     * @return int 数据长度
     */
    private function handleBaiduStream(string $data): int
    {
        foreach (explode("\n\ndata: ", "\n\n" . $this->context->bufferData . $data) as $msg) {
            if (strlen($msg)) {
                $contentarr = json_decode(trim($msg));
                if (is_null($contentarr)) {
                    $this->context->bufferData = "data: " . $msg;
                    continue;
                }
                
                $this->context->bufferData = "";
                if (isset($contentarr->result)) {
                    $this->sendDeltaContent($contentarr->result, '', 'BaiduStream');
                }
                
                if (isset($contentarr->is_end) && $contentarr->is_end === true) {
                    $this->sendFinish($contentarr->id ?? 'baidu');
                }
            }
        }
        return strlen($data);
    }

    /**
     * 处理腾讯混元响应
     * @param string $data 响应数据
     * @return int 数据长度
     */
    private function handleTencent(string $data): int
    {
        foreach (explode("\n\ndata: ", "\n\n" . $data) as $msg) {
            if (strlen(trim($msg))) {
                $contentarr = json_decode(trim($msg));
                
                if (isset($contentarr->Choices)) {
                    $this->sendDeltaContent($contentarr->Choices[0]->Delta->Content, '', 'Tencent');
                }
                
                if (isset($contentarr->Choices[0]->FinishReason) && $contentarr->Choices[0]->FinishReason == "stop") {
                    $this->sendFinish($contentarr->Id ?? 'tencent');
                }
            }
        }
        return strlen($data);
    }

    /**
     * 处理MiniMax响应
     * @param string $data 响应数据
     * @return int 数据长度
     */
    private function handleMiniMax(string $data): int
    {
        foreach (explode("\n\ndata: ", "\n\n" . $data) as $msg) {
            if (strlen(trim($msg))) {
                $contentarr = json_decode(trim($msg));
                
                if (isset($contentarr->choices[0]->finish_reason)) {
                    $this->sendFinish('MiniMax');
                } else if (isset($contentarr->choices) && !empty($contentarr->choices[0]->messages[0]->text)) {
                    $this->sendDeltaContent($contentarr->choices[0]->messages[0]->text, '', 'MiniMax');
                }
            }
        }
        return strlen($data);
    }

    /**
     * 处理无问芯穹响应
     * @param string $data 响应数据
     * @return int 数据长度
     */
    private function handleWuwen(string $data): int
    {
        foreach (explode("\n\ndata: ", "\n\n" . $this->context->bufferData . $data) as $msg) {
            if (strlen(trim($msg))) {
                $contentarr = json_decode(trim($msg));
                if (is_null($contentarr)) {
                    $this->context->bufferData = "data: " . $msg;
                    continue;
                }
                
                $this->context->bufferData = "";
                echo "data: " . json_encode($contentarr) . "\n\n";
                
                if (isset($contentarr->choices[0]->finish_reason)) {
                    $this->sendDone();
                }
            }
        }
        return strlen($data);
    }

    /**
     * 处理Bard响应
     * @param string $data 响应数据
     * @return int 数据长度
     */
    private function handleBard(string $data): int
    {
        if ($data == "[]") {
            return 0; // 触发重试
        }

        $errorcheck = json_decode($data);
        if ($this->handleBardErrors($errorcheck, $data)) {
            return strlen($data);
        }

        return $this->processBardContent($data);
    }

    /**
     * 处理Bard错误
     * @param mixed $errorcheck 解析后的错误数据
     * @param string $data 原始数据
     * @return bool 是否发生错误
     */
    private function handleBardErrors($errorcheck, string $data): bool
    {
        if (is_array($errorcheck) && isset($errorcheck[0]) && isset($errorcheck[0]->error)) {
            $this->sendError($errorcheck[0]->error->code, $errorcheck[0]->error->message);
            return true;
        }
        
        if (isset($errorcheck->error)) {
            $this->sendError($errorcheck->error->code, $errorcheck->error->message);
            return true;
        }
        
        return false;
    }

    /**
     * 处理Bard内容
     * @param string $data 响应数据
     * @return int 数据长度
     */
    private function processBardContent(string $data): int
    {
        if ($this->isStart) {
            $this->isStart = false;
        }

        $this->context->bardBuffer .= $data;
        $candidatesarr = explode("{\n" . '  "candidates": [', $this->context->bardBuffer);
        $candidatescount = count($candidatesarr);
        
        for ($i = $this->context->bardBufferCount; $i < $candidatescount - 1; $i++) {
            $contentarr = json_decode('{"candidates":[' . substr(trim($candidatesarr[$i]), 0, -1));
            if ($contentarr && isset($contentarr->candidates[0]->content->parts[0]->text)) {
                $this->sendDeltaContent($contentarr->candidates[0]->content->parts[0]->text, '', 'Bard');
            }
        }
        
        $this->context->bardBufferCount = $candidatescount - 1;
        return strlen($data);
    }

    /**
     * 处理通用响应
     * @param string $data 响应数据
     * @return int 数据长度
     */
    private function handleGeneric(string $data): int
    {
        echo $data;
        $this->responseData .= $data;
        return strlen($data);
    }

    /**
     * 映射OpenAI错误码
     * @param string $message 错误信息
     * @return string 映射后的错误码
     */
    private function mapOpenAIErrorCode(string $message): string
    {
        $errorMap = [
            'Rate limit reached' => 'rate_limit_reached',
            'Your access was terminated' => 'access_terminated',
            'You didn\'t provide an API key' => 'insufficient_quota',
            'You exceeded your current quota' => 'insufficient_quota',
            'That model is currently overloaded' => 'model_overloaded',
            'The server had an error' => 'server_overloaded',
            'Your account is not active' => 'account_deactivated'
        ];
        
        foreach ($errorMap as $key => $value) {
            if (strpos($message, $key) === 0) {
                return $value;
            }
        }
        
        return 'unknown_error';
    }

    /**
     * 发送错误响应
     * @param string $code 错误码
     * @param string $message 错误信息
     */
    private function sendError(string $code, string $message): void
    {
        $error = json_encode(['error' => ['code' => $code, 'message' => $message]]);
        echo "data: $error\n\n";
        $this->isError = true;
    }

    /**
     * 发送增量内容
     * @param string $content 完整内容
     * @param string $previousContent 之前的内容
     * @param string $id 响应ID
     * @param string $model 模型名称
     */
    private function sendDeltaContent(string $content, string $previousContent = '', string $id = 'default', string $model = ''): void
    {
        $delta = str_replace($previousContent, '', $content);
        if (!empty($delta)) {
            $response = [
                'id' => $id,
                'object' => 'chat.completion.chunk',
                'created' => time(),
                'choices' => [[
                    'index' => 0,
                    'delta' => ['content' => $delta],
                    'finish_reason' => null
                ]]
            ];
            echo 'data: ' . json_encode($response) . "\n\n";
            $this->responseData .= 'data: ' . json_encode($response) . "\n\n";
        }
    }

    /**
     * 发送完成响应
     * @param string $id 响应ID
     */
    private function sendFinish(string $id = 'default'): void
    {
        $response = [
            'id' => $id,
            'object' => 'chat.completion.chunk',
            'created' => time(),
            'choices' => [[
                'index' => 0,
                'delta' => new \stdClass(),
                'finish_reason' => 'stop'
            ]]
        ];
        echo 'data: ' . json_encode($response) . "\n\n";
        $this->sendDone();
    }

    /**
     * 发送完成标识
     */
    private function sendDone(): void
    {
        echo "data: [DONE]\n\n";
    }

    /**
     * 获取响应数据
     * @return string 响应数据
     */
    public function getResponseData(): string
    {
        return $this->responseData;
    }

    /**
     * 是否发生错误
     * @return bool 是否发生错误
     */
    public function hasError(): bool
    {
        return $this->isError;
    }

    /**
     * 获取错误信息
     * @return array 错误信息
     */
    public function getError(): array
    {
        return ['code' => $this->errorCode, 'message' => $this->errorMsg];
    }
}