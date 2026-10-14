<?php
function checkUrl($url)
{
    $options = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ];
    $context = stream_context_create($options);
    $headers = get_headers($url, false, $context);
    if ($headers && strpos($headers[0], '200') !== false) {
        return true;
    } else {
        return false;
    }
}

$filePath = "./" . $_GET['user'] . ".log";

while (true) {
    $fileContent = file_get_contents($filePath);
    if (!empty($fileContent)) {
        $data = json_decode($fileContent, true);
        if ((isset($data["error"])) || (isset($data["data"]) && (checkUrl($data["data"][0]["url"])))) {
            echo $fileContent;
            exit;
        }
    }
    sleep(1);
}
