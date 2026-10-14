<?php

namespace app\ai\home;

use think\Controller;
use app\member\model\MemberModel;
use think\Db;
use think\facade\Cookie;
use app\ai\library\WebSearchApi;
use app\ai\service\AIConfigService;
use app\ai\service\AIModelService;
use app\ai\service\AIAuthService;
use app\ai\service\AIContentService;
use app\ai\model\AiSceneModel;
use app\ai\model\AiRoleModel;

header("Access-Control-Allow-Origin: *");
/**
 * 前台首页控制器
 * @package app\index\controller
 */
class Index extends Controller
{
    public function index()
    {
        $main_data                          = AIConfigService::getMainConfig();
        $main_data['websiterndstr']         = '';
        $main_data['websiteinfotime']       = '';
        $rechargetext                       = AIConfigService::buildRechargePanel($main_data['payment_type']);
        $role_js = AIModelService::processRoleData();
        $molds_data = AIModelService::getValidModels();
        $modelArray = array();
        foreach ($molds_data as $row) {
            $main_data['midjourneymodelid'] ='';
            $main_data['midjourneymodelname'] ='';
            if ($row["modelvalue"] == "midjourney_image") {
                $main_data['midjourneymodelid']      = $row["id"];
                $main_data['midjourneymodelname']    = $row["modelname"];
            }
            if (!$row["isimage"]) {
                $modelArray[] = AIModelService::buildModelItem($row, 0);
            } else {
                if ($row["isimage"] % 2 == 1) {
                    $item = AIModelService::buildModelItem($row, 1);
                    if ($item) $modelArray[] = $item;
                }
                if (intval(abs(intval(abs($row["isimage"] - 4.5)) - 1.5)) == 0) {
                    $item = AIModelService::buildModelItem($row, 2);
                    if ($item) $modelArray[] = $item;
                }
                if ($row["isimage"] >= 4) {
                    $item = AIModelService::buildModelItem($row, 4);
                    if ($item) $modelArray[] = $item;
                }
            }
        }
        usort($modelArray, function ($a, $b) use ($modelArray) {
            $indexA = array_search($a, $modelArray);
            $indexB = array_search($b, $modelArray);
            if ($a['modelisimage'] == $b['modelisimage']) {
                return $indexA - $indexB; // 按照原始顺序排序
            }
            return $a['modelisimage'] - $b['modelisimage']; // 按照modelisimage排序
        });
        $scene_data = AiSceneModel::where('isvalid', 1)->order('id asc')->select();
        $sceneArray = array();
        foreach ($scene_data as $row) {
            $sceneArray[] = array('scenename' => $row['scenename'], 'scenevalue' => $row['scenevalue']);
        };
        $molds_data_find = AIModelService::getDefaultModel();
        $defaultmodelid         = $molds_data_find["id"];
        $defaultmodelisimage    = $molds_data_find["isimage"];
        $defaultmodelvalue      = $molds_data_find["modelvalue"];
        $defaultmodelname       = $molds_data_find["modelname"];
        $websiterndstr          = AIConfigService::generateRandomSeed();
        $this->assign('role_js', $role_js);
        $this->assign('websiterndstr', $websiterndstr);
        $this->assign('rechargetext', $rechargetext);
        $this->assign('defaultmodelid', $defaultmodelid);
        $this->assign('defaultmodelisimage', $defaultmodelisimage);
        $this->assign('defaultmodelvalue', $defaultmodelvalue);
        $this->assign('defaultmodelname', $defaultmodelname);
        $this->assign('sceneArray', $sceneArray);
        $this->assign('modelArray', $modelArray);
        $this->assign('main_data', $main_data);
        $this->assign('web_site_name', config('web_site_title'));
        return $this->fetch();
    }

    public function user_login()
    {
        $userrndstr  = input('userrndstr', '');
        $main_data   = AIConfigService::getMainConfig();
        $this->assign('main_data', $main_data);
        $this->assign('userrndstr', $userrndstr);
        $this->assign('web_site_name', config('web_site_title'));
        return $this->fetch();
    }

    public function user_register()
    {
        $userrndstr  = input('userrndstr', '');
        $main_data   = AIConfigService::getMainConfig();
        $this->assign('main_data', $main_data);
        $this->assign('userrndstr', $userrndstr);
        $this->assign('web_site_name', config('web_site_title'));
        return $this->fetch();
    }

    public function logout()
    {
        if ($this->request->isPost()) {
            $post_data   = SafeFilter(input('post.'));
            $userrndstr  = $post_data['userrndstr'];
            AIAuthService::logout($userrndstr);
            return json(['success' => true, 'message' => '退出成功', 'userrndstr' => Cookie::get('userrndstr')]);
        }
    }

    public function keywordfilter()
    {
        if ($this->request->isPost()) {
            $post_data   = SafeFilter(input('post.'));
            $str         = urldecode($post_data["text"]);
            $filteredText = AIContentService::keywordFilter($str);
            return json(['success' => true, 'newtext' => rawurlencode($filteredText)]);
        }
    }

    public function baidu_content_sensor()
    {
        if ($this->request->isPost()) {
            $post_data   = SafeFilter(input('post.'));
            $isValid = AIContentService::baiduContentSensor($post_data["text"]);
            return json(['isvalid' => $isValid]);
        }
    }

    //
    public function set_session()
    {
        error_reporting(E_ERROR);
        if ($this->request->isPost()) {
            $main_data   = AIConfigService::getMainConfig();
            $post_data   = SafeFilter(input('post.'));
            $userrndstr  = isset($post_data['user']) ? $post_data['user'] : '';
            $user        = AIAuthService::verifyUser($userrndstr);
            if (empty($user)) {
                return json(['success' => false, 'message' => '当前用户登录状态已过期，请刷新页面重新登录']);
            }
            $user_id = $user['id'];
            
            // 获取角色数据
            $roles = AiRoleModel::where('isvalid', true)->order('id desc')->select()->toArray();
            $roleArray = [];
            foreach ($roles as $role) {
                $roleArray[] = [
                    'rolevalue' => $role['rolevalue'],
                    'rolecode'  => $role['rolecode']
                ];
            }
            $post_role          = isset($post_data['role']) ? $post_data['role'] : '';
            $message            = isset($post_data['message']) ? $post_data['message'] : '';
            $model              = isset($post_data['model']) ? $post_data['model'] : '';
            $conversationid     = isset($post_data['conversationid']) ? $post_data['conversationid'] : '';
            $midjourneyaction   = isset($post_data['midjourneyaction']) ? $post_data['midjourneyaction'] : '';
            $blendimageurl      = isset($post_data['blendimageurl']) ? $post_data['blendimageurl'] : '';

            // 获取参数
            $prompt         = urldecode($message);
            $modelInfo      = AIModelService::getModelById($model);
            $modelvalue     = $modelInfo['modelvalue'];
            $modeltype      = $modelInfo['modeltype'];
            $istranslate    = $modelInfo['istranslate'];
            $isonline       = $modelInfo['isonline'];
            $isimage        = isset($post_data['isimage']) ? $post_data['isimage'] : '';
            $imageurl       = isset($post_data['imageurl']) ? $post_data['imageurl'] : '';
            
            // 在线搜索处理
            if ($isonline) {
                $WebSearchApi = new WebSearchApi();
                $responsedata = $WebSearchApi->search_send_post($message);
                $jsondata     = json_decode($responsedata);

                if ($jsondata && isset($jsondata->code) && $jsondata->code == 200) {
                    $prompt = $jsondata->online;
                }
            }
            
            $context            = json_decode(input('post.context') ?: "[]") ?: [];
            $maxcontexttokens   = $main_data['maxcontexttokens'];
            
            // 翻译处理
            if ($main_data['isdrawtranslate'] && $istranslate) {
                $prompt = AIContentService::baiduTranslate($prompt);
            }
            
            // 构建postdata
            $postdata = AIContentService::buildPostData($isimage, $modelvalue, $modeltype, $prompt, $imageurl, $user_id, $roleArray, $context, $maxcontexttokens, $post_role, $midjourneyaction, $blendimageurl);
            // 更新用户最后问题
            AIAuthService::updateUserLastQuestion($user_id, $conversationid . ',' . json_encode($postdata), $model);
            
            return json(['success' => true]);
        }
    }

    public function get_upload_token()
    {
        $get_data       = safeFilter(input('get.'));
        $userrndstr     = isset($get_data['userrndstr']) ? $get_data['userrndstr'] : '';
        if (!$userrndstr) {
            http_response_code(403); // 返回403错误，表示禁止访问
        }
        $user            = AIAuthService::verifyUser($userrndstr);
        if (($userrndstr != 'admin') && empty($user)) {
            http_response_code(403); // 返回403错误，表示禁止访问
        }
        $main_data      = AIConfigService::getMainConfig();
        $imagesiteurl   = $main_data["imagesiteurl"];
        $imagesitetoken = $main_data["imagesitetoken"];
        $timestamp      = file_get_contents($imagesiteurl . "?action=getnonce");
        $sign           = md5(md5($timestamp) . $imagesitetoken);
        echo '{"nonce":"' . $timestamp . '","sign":"' . $sign . '"}';
        exit;
    }

    public function upload()
    {
        $daysToKeep = 7; // 保留文件天数
        $token      = "NewBee"; // 后台配置好的token
        $uploadDir  = 'uploads/ai/u/'; // 上传目录，注意建立好这个目录并打开写权限


        $get_data   = safeFilter(input('get.'));
        $post_data  = safeFilter(input('post.'));
        if ((!isset($post_data["action"])) && (!isset($get_data["action"]))) {
            header('HTTP/1.1 404 Not Found');
            exit;
        }
        if (((isset($post_data["action"])) && ($post_data["action"] == "getnonce")) || ((isset($get_data["action"])) && ($get_data["action"] == "getnonce"))) {
            echo time();
            exit;
        }
        if ((isset($post_data["action"])) && ($post_data["action"] == "upload")) {
            $nonce = $post_data['nonce'];
            $sign  = $post_data['sign'];
        }
        if (!AIAuthService::verifyUploadToken($nonce, $sign, $token)) {
            http_response_code(403); // 返回403错误，表示禁止访问
            echo "Paramter nonce is out of date or sign is invalid";
            exit;
        }
        
        // 删除超过保留天数的文件
        $files = glob($uploadDir . '*'); // 获取所有文件
        foreach ($files as $file) {
            if (is_file($file)) {
                $fileCreationTime = filemtime($file);
                $fileAge = time() - $fileCreationTime; // 文件年龄，以秒为单位
                if ($fileAge > ($daysToKeep * 24 * 60 * 60)) { // 如果文件年龄超过保留天数
                    unlink($file); // 删除文件
                }
            }
        }
        
        // 处理上传的图片
        if (isset($_FILES['image']) && isset($_FILES['image']['error']) && $_FILES['image']['error'] == 0) {
            $filename   = isset($_FILES['image']['name']) ? $_FILES['image']['name'] : '';
            $extension  = pathinfo($filename, PATHINFO_EXTENSION);
            
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $uploadFile = $uploadDir . time() . '.' . $extension;   // 以时间戳为文件名
            // 如果文件已存在，则加上后缀_1、_2等后缀
            $i = 1;
            while (file_exists($uploadFile)) {
                $uploadFile = $uploadDir . time() . '_' . $i . '.' . $extension;
                $i++;
            }
            
            // 判断文件类型是否为图片
            $imageInfo = getimagesize($_FILES['image']['tmp_name']);
            if ($imageInfo === false) {
                http_response_code(400); // 返回400错误，表示客户端请求错误
                echo "Invalid file type";
                exit;
            }

            // 允许的图片类型
            $allowedTypes = array(IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF);
            if (!in_array($imageInfo[2], $allowedTypes)) {
                http_response_code(400); // 返回400错误，表示客户端请求错误
                echo "Unsupported file type";
                exit;
            }

            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
                $httpprotocol = "http://";
                if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
                    $httpprotocol = "https://";
                }
                $imageUrl = $httpprotocol . $_SERVER['HTTP_HOST'] . "/" . $uploadFile; // 图片链接
                echo $imageUrl;
            } else {
                http_response_code(500); // 返回500错误，表示服务器内部错误
                echo "Failed to move the uploaded file";
            }
        } else {
            http_response_code(400); // 返回400错误，表示客户端请求错误
            echo "Failed to upload the file";
        }
    }

    public function update_conversation_title()
    {
        $get_data       = safeFilter(input('get.'));
        $userrndstr     = isset($get_data["userrndstr"]) ? $get_data["userrndstr"] : '';
        $conversationid = isset($get_data["conversationid"]) ? $get_data["conversationid"] : 0;
        $user           = AIAuthService::verifyUser($userrndstr);
        if (empty($user)) {
            return json(['success' => false, 'message' => '当前用户登录状态已过期，请刷新页面重新登录']);
        }
        $title      = isset($get_data["title"]) ? $get_data["title"] : '';
        if (!$title) {
            return json(['success' => false, 'message' => '标题不能为空']);
        }
        $newtitle   = addslashes($get_data["title"]);
        $chathistory_data = Db::name('ai_chathistory')->where('userid', $user['id'])->where('conversationid', $conversationid)->find();
        if (empty($chathistory_data)) {
            return json(['success' => false, 'message' => '会话不存在']);
        }
        $res = Db::name('ai_chathistory')->where('userid', $user['id'])->where('conversationid', $conversationid)->update(['title' => $newtitle]);
        if ($res) {
            return json(['success' => true]);
        } else {
            return json(['success' => false, 'message' => '找不到该对话记录']);
        }
    }

    public function picture_proxy()
    {
        $get_data       = safeFilter(input('get.'));
        if ((isset($get_data['url'])) && (strpos($get_data['url'], 'http') === 0)) {
            $headers = get_headers($get_data['url'], 1);
            if (isset($headers['Content-Type']) && strpos($headers['Content-Type'], 'image/') !== false) {
                $context = stream_context_create([
                    'http' => [
                        'timeout' => 300, // 设置超时时间为300秒
                    ],
                    "ssl" => [
                        "verify_peer" => false,
                        "verify_peer_name" => false,
                    ],
                ]);
                $image = file_get_contents($get_data['url'], false, $context);
                header("Content-type: image/jpeg");
                echo $image;
            } else {
                echo "Invalid request: not an image file";
            }
        } else {
            echo "Invalid request";
        }
    }
}