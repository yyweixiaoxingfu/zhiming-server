<?php

function savelog($msg, $filename)
{
    error_log(date('Y-m-d H:i:s') . " {$msg}\n", 3, $filename);
}

function parseMarkdownImage($str)
{
    if (substr($str, 0, 6) === '![IMG]') {
        $regex = '/!\[IMG]\((.*?)\)/';
        return preg_replace($regex, '[图片]', $str);
    } else {
        return $str;
    }
}

function uploadAndGetURL($imageurlorresource, $isimageresource = false)
{
    global $imagesiteurl, $imagesitetoken;
    $timestamp = file_get_contents($imagesiteurl . "?action=getnonce");
    $sign = md5(md5($timestamp) . $imagesitetoken);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_URL, $imagesiteurl);
    curl_setopt($ch, CURLOPT_POST, true);
    $postFields = [
        'action' => 'upload',
        'nonce' => $timestamp,
        'sign' => $sign,
    ];
    $tmpFilePath = tempnam(sys_get_temp_dir(), 'image');
    if ($isimageresource) {
        imagejpeg($imageurlorresource, $tmpFilePath);
    } else {
        file_put_contents($tmpFilePath, file_get_contents($imageurlorresource));
    }
    $postFields['image'] = new CURLFile($tmpFilePath, 'image/jpeg', 'image.jpg');

    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        return "图片上传失败：" . curl_error($ch);
    } else {
        return $response;
    }
    curl_close($ch);
    unlink($tmpFilePath);
}

// 从 POST 请求中获取处理结果
$postdata = file_get_contents("php://input");
$data = json_decode($postdata, true);
savelog($postdata, "callback.log");
if ($data['status'] == 'SUBMITTED') {
    file_put_contents($data['state'] . ".log", '');
} elseif ($data['status'] == 'FAILURE') {
    file_put_contents($data['state'] . ".log", '{"error":{"code":"mj_fail","message":"' . $data['failReason'] . '"}}');
} elseif ($data['status'] == 'IN_PROGRESS') {
    if (!empty($data['imageUrl'])) {
        file_put_contents($data['state'] . ".log", '{"data": [{"url": "' . $data['imageUrl'] . '"}]}');
    }
} elseif ($data['status'] == 'SUCCESS') {
    require_once('../../admin/mysqlconn.php');
    $row = $conn->get('main', '*', ['id' => 1]);
    $imagesiteurl = $row["imagesiteurl"];
    $imagesitetoken = $row["imagesitetoken"];

    if ($data['action'] == 'UPSCALE') {
        $goodanswer = uploadAndGetURL($data['imageUrl']);
        if (preg_match('/^(http|https):\/\/[^ "]+$/', $goodanswer)) {
            file_put_contents($data['state'] . ".log", '{"data":[{"url":"' . $goodanswer . '"}]}');
            $goodanswer = '![IMG](' . $goodanswer . ')';
        } else {
            $goodanswer = "图片上传失败：" . $goodanswer;
            file_put_contents($data['state'] . ".log", '{"error":{"code":"mj_fail","message":"' . $goodanswer . '"}}');
        }
    } else {
        $sourceImageContent = file_get_contents($data['imageUrl']);
        $sourceImage = imagecreatefromstring($sourceImageContent);
        $sourceWidth = imagesx($sourceImage);
        $sourceHeight = imagesy($sourceImage);
        $targetWidth = $sourceWidth / 2;
        $targetHeight = $sourceHeight / 2;
        $targetImages = [];
        for ($i = 0; $i < 4; $i++) {
            $targetImages[$i] = imagecreatetruecolor($targetWidth, $targetHeight);
        }
        imagecopy($targetImages[0], $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight);
        imagecopy($targetImages[1], $sourceImage, 0, 0, $targetWidth, 0, $targetWidth, $targetHeight);
        imagecopy($targetImages[2], $sourceImage, 0, 0, 0, $targetHeight, $targetWidth, $targetHeight);
        imagecopy($targetImages[3], $sourceImage, 0, 0, $targetWidth, $targetHeight, $targetWidth, $targetHeight);
        $urls = [];
        for ($i = 0; $i < 4; $i++) {
            $urls[$i] = uploadAndGetURL($targetImages[$i], true) . '?taskid=' . $data['id'] . '&customid=' . end(explode(":", $data['buttons'][0]['customId'])) . '&id=' . ($i + 1);
            imagedestroy($targetImages[$i]);
        }
        imagedestroy($sourceImage);
        if (preg_match('/^(http|https):\/\/[^ "]+$/', $urls[0])) {
            file_put_contents($data['state'] . ".log", '{"data": [{"url": "' . $urls[0] . '"},{"url": "' . $urls[1] . '"},{"url": "' . $urls[2] . '"},{"url": "' . $urls[3] . '"}]}');
            $goodanswer = '![IMG](' . $urls[0] . ')' . "\n" . '![IMG](' . $urls[1] . ')' . "\n" . '![IMG](' . $urls[2] . ')' . "\n" . '![IMG](' . $urls[3] . ')';
        } else {
            $goodanswer = "图片上传失败：" . $urls[0];
            file_put_contents($data['state'] . ".log", '{"error":{"code":"mj_fail","message":"' . $goodanswer . '"}}');
        }
    }
    $row = $conn->get('user', '*', ['userid' => $data['state']]);
    if (empty($row)) {
        echo '{"error":{"code":"invalid_user","message":""}}';
        exit;
    }
    $conversationid = explode(",", $row["lastquestion"])[0];
    $prompt = json_decode(substr($row["lastquestion"], strlen($conversationid) + 1), true);
    $lastmodelid = $row["lastmodelid"];
    $goodquestion = $prompt['prompt'];
    if (substr($goodquestion, 0, 4) === "http") {
        $goodquestion = "![IMG](" . $goodquestion;
        $pos = strpos($goodquestion, ' ');

        if ($pos !== false) {
            $goodquestion = substr_replace($goodquestion, ')', $pos, 0);
        } else {
            $goodquestion .= ")";
        }
    }

    $goodquestion = addslashes(trim($goodquestion));
    $goodanswer = addslashes(trim($goodanswer));
    $conn->insert('chathistory', ['title' => substr(parseMarkdownImage($goodquestion), 0, 100), 'question' => $goodquestion, 'answer' => $goodanswer, 'conversationid' => $conversationid, 'modelid' => $lastmodelid, 'realtime' => date('Y-m-d H:i:s'), 'userid' => $row["id"], 'iserror' => 0]);
    $conn->update('user', ['lastquestion' => '', 'lastmodelid' => NULL], ['userid' => $data['state']]);
}
