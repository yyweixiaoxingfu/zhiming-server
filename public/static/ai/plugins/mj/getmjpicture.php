<?php
error_reporting(E_ALL ^ E_WARNING);
header("Access-Control-Allow-Origin: *");
set_time_limit(0);
require_once('../../admin/mysqlconn.php');
file_put_contents($_GET["user"] . '.log', '');
$proxyaddress = $conn->get('main', 'proxyaddress', ['id' => 1]);;
$row = $conn->get('user', '*', ['userid' => $_GET["user"]]);
if (empty($row)) {
    echo '{"error":{"code":"invalid_user","message":""}}';
    exit;
}
$userid = $row["id"];
$quota = $row["quota"];
$conversationid = explode(",", $row["lastquestion"])[0];
$postdata = substr($row["lastquestion"], strlen($conversationid) + 1);
$lastmodelid = $row["lastmodelid"];
$row = $conn->get('model', '*', ['id' => $lastmodelid]);
$modelprice = $row["modelprice"];
$modeltype = $row["modeltype"];

if ($quota < $modelprice) {
    echo '{"error":{"code":"out_of_money","message":""}}';
    exit;
}
$responsedata = "";
$OPENAI_API_KEY = "";

$row = $conn->get('apikey', '*', ['isvalid' => true, 'keytype' => $lastmodelid, 'ORDER' => ['lasttime', 'id']]);
if (empty($row)) {
    echo '{"error":{"code":"no_valid_apikey","message":""}}';
    exit;
}
$OPENAI_API_KEY = $row["apikey"];
$apikeyid = $row["id"];
$mjapiaddress = $row["apiaddress"];
$conn->update('apikey', ['lasttime' => date('Y-m-d H:i:s')], ['id' => $apikeyid]);

$headers  = [
    'Accept: application/json',
    'Content-Type: application/json',
    'mj-api-secret: ' . $OPENAI_API_KEY
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
if ($_GET["isdrawuv"]) {
    curl_setopt($ch, CURLOPT_URL, $mjapiaddress . "/submit/action");
    $postdatajson = json_decode($postdata, true);
    unset($postdatajson['prompt']);
    $postdata = json_encode($postdatajson);
    error_log(date('Y-m-d H:i:s') . " SEND /submit/action:{$postdata}\n", 3, "fromapi.log");
} else if ($_GET["isblend"]) {
    curl_setopt($ch, CURLOPT_URL, $mjapiaddress . "/submit/blend");
    $postdatajson = json_decode($postdata, true);
    $img = file_get_contents($postdatajson['imageurl']);
    $size = getimagesize($postdatajson['imageurl']);
    $mimeType = $size['mime'];
    $base64_img = 'data:' . $mimeType . ';base64,' . base64_encode($img);
    $img = file_get_contents($postdatajson['blendimageurl']);
    $size = getimagesize($postdatajson['blendimageurl']);
    $mimeType = $size['mime'];
    $postdatajson['base64Array'] = [$base64_img, 'data:' . $mimeType . ';base64,' . base64_encode($img)];
    unset($postdatajson['prompt']);
    unset($postdatajson['imageurl']);
    unset($postdatajson['blendimageurl']);
    $postdata = json_encode($postdatajson);
    error_log(date('Y-m-d H:i:s') . " SEND /submit/blend:{$postdata}\n", 3, "fromapi.log");
} else {
    curl_setopt($ch, CURLOPT_URL, $mjapiaddress . "/submit/imagine");
    error_log(date('Y-m-d H:i:s') . " SEND /submit/imagine:{$postdata}\n", 3, "fromapi.log");
}
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 300);
curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_AUTOREFERER, true);
if (!empty($proxyaddress)) {
    curl_setopt($ch, CURLOPT_PROXY, $proxyaddress);
}
$responsedata = curl_exec($ch);
curl_close($ch);
error_log(date('Y-m-d H:i:s') . " {$responsedata}\n", 3, "fromapi.log");

$complete = json_decode($responsedata, true);
if (!is_array($complete) || !isset($complete['code']) || $complete['code'] != 1) {
    echo '{"error":{"code":"mj_fail","message":""}}';
    exit;
} else {
    $url = 'http://' . $_SERVER['HTTP_HOST'] . '/plugins/mj/apiproxy.php?user=' . $_GET["user"];
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
        $url = str_replace('http://', 'https://', $url);
    }
    $context = stream_context_create([
        'http' => [
            'timeout' => 600, // 设置超时时间为600秒
        ],
        "ssl" => [
            "verify_peer" => false,
            "verify_peer_name" => false,
        ],
    ]);
    $data = @file_get_contents($url, false, $context);
    if ($data) {
        echo $data;
        $data = json_decode($data, true);
        if (!isset($data["error"])) {
            $conn->update('user', ['quota[-]' => $modelprice, 'credits[+]' => $modelprice, 'questioncount[+]' => 1], ['id' => $userid]);
        }
    }
}
