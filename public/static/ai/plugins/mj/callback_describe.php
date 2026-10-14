<?php

function savelog($msg, $filename)
{
    error_log(date('Y-m-d H:i:s') . " {$msg}\n", 3, $filename);
}

// 从 POST 请求中获取处理结果
$postdata = file_get_contents("php://input");
$data = json_decode($postdata, true);
savelog($postdata, "callback_describe.log");
if ($data['status'] == 'SUBMITTED') {
    file_put_contents($data['state'] . ".log", '');
} elseif ($data['status'] == 'FAILURE') {
    file_put_contents($data['state'] . ".log", '{"error":{"code":"mj_fail","message":""}}');
} elseif ($data['status'] == 'SUCCESS') {
    $goodanswer = $data['properties']['finalPrompt'];
    $goodanswer = str_replace("1️⃣", "1.", $goodanswer);
    $goodanswer = str_replace("2️⃣", "2.", $goodanswer);
    $goodanswer = str_replace("3️⃣", "3.", $goodanswer);
    $goodanswer = str_replace("4️⃣", "4.", $goodanswer);

    $formatteddata = '{"id":"1","object":"chat.completion.chunk","created":' . time() . ',"choices":[{"index":0,"delta":{"content":""},"finish_reason":null}]}';
    $formatteddata = json_decode($formatteddata);
    $formatteddata->choices[0]->delta->content = $goodanswer;
    $formatteddata = 'data: ' . json_encode($formatteddata) . "\n\n";
    $formatteddata .= 'data: {"id":"2","object":"chat.completion.chunk","created":' . time() . ',"choices":[{"index":0,"delta":{},"finish_reason":"stop"}]}' . "\n\n" . 'data: [DONE]' . "\n\n";
    file_put_contents($data['state'] . ".log", $formatteddata);
}
