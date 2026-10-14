<?php
error_reporting(E_ALL ^ E_WARNING);
header("Access-Control-Allow-Origin: *");
set_time_limit(0);
require_once('../../admin/mysqlconn.php');
file_put_contents($_GET["user"] . '.log', '');
$proxyaddress = $conn->get('main','proxyaddress',['id'=>1]);;
$row = $conn->get('user','*',['userid'=>$_GET["user"]]);
if (empty($row)) {
    echo '{"error":{"code":"invalid_user","message":""}}';
    exit;
}
$userid = $row["id"];
$quota = $row["quota"];
$conversationid = explode(",", $row["lastquestion"])[0];
$postdata = substr($row["lastquestion"], strlen($conversationid) + 1);
$postdatajson = json_decode($postdata, true);
$img = file_get_contents($postdatajson['imageurl']);
$size = getimagesize($postdatajson['imageurl']);
$mimeType = $size['mime'];
$base64_img = 'data:' . $mimeType . ';base64,' . base64_encode($img);
$postdatajson['base64'] = $base64_img;
unset($postdatajson['imageurl']);
$postdata = json_encode($postdatajson);
$lastmodelid = $row["lastmodelid"];
$row = $conn->get('model','*',['id'=>$lastmodelid]);
$modelprice = $row["modelprice"];
$modeltype = $row["modeltype"];

if ($quota < $modelprice) {
    echo '{"error":{"code":"out_of_money","message":""}}';
    exit;
}
$responsedata = "";
$OPENAI_API_KEY = "";

$row = $conn->get('apikey','*',['isvalid'=>true,'keytype'=>$lastmodelid,'ORDER'=>['lasttime','id']]);
if (empty($row)) {
    echo '{"error":{"code":"no_valid_apikey","message":""}}';
    exit;
}
$OPENAI_API_KEY = $row["apikey"];
$apikeyid = $row["id"];
$mjapiaddress = $row["apiaddress"];
$conn->update('apikey',['lasttime'=>date('Y-m-d H:i:s')],['id'=>$apikeyid]);

$headers  = [
    'Accept: application/json',
    'Content-Type: application/json',
    'mj-api-secret: ' . $OPENAI_API_KEY
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
curl_setopt($ch, CURLOPT_URL, $mjapiaddress . "/submit/describe");
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

$complete = json_decode($responsedata);
if (($complete->code) && ($complete->code != 1)) {
    echo '{"error":{"code":"mj_fail","message":""}}';
    
    exit;
} else {
    $filePath = "./" . $_GET['user'] . ".log";

    while (true) {
        $fileContent = file_get_contents($filePath);
        if (!empty($fileContent)) {
            echo $fileContent;
            exit;
        }
        sleep(1);
    }
}
