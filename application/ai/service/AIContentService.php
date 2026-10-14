<?php

namespace app\ai\service;

use think\Db;
use app\ai\library\BaiduContentSensor;
use app\ai\library\BaiduTranslate;

/**
 * AI内容服务类
 * 处理内容过滤和安全
 */
class AIContentService
{
    /**
     * 关键词过滤
     * @param string $text 文本内容
     * @return string
     */
    public static function keywordFilter($text)
    {
        // 修复正则表达式安全隐患，使用更安全的方式
        $filteredText = $text;
        $keywords = Db::name('ai_myfilter')->column('keyword');
        
        if (!empty($keywords)) {
            foreach ($keywords as $keyword) {
                if (strpos($filteredText, $keyword) !== false) {
                    $filteredText = str_replace($keyword, str_repeat("*", strlen($keyword)), $filteredText);
                }
            }
        }
        
        return $filteredText;
    }
    
    /**
     * 安全处理角色代码
     * @param string $roleCode 角色代码
     * @param array &$postdata 引用传递的post数据
     */
    private static function processRoleCode(string $roleCode, array &$postdata): void
    {
        // 解析角色代码，只允许特定格式的操作
        // 支持的格式：$postdata['messages'][] = ['role' => 'system', 'content' => 'xxx'];
        
        // 移除可能的危险代码
        $safeCode = preg_replace('/[^\$\[\]\'"\w\s,=>:\/\.\-]/', '', $roleCode);
        
        // 只允许特定的数组操作
        if (preg_match('/^\$postdata\[\'messages\'\]\[\]\s*=\s*\[\s*\'role\'\s*=>\s*\'[\w]+\'\s*,\s*\'content\'\s*=>\s*\'[^\']*\'\s*\];$/', $safeCode)) {
            // 使用eval()但只允许特定格式的代码
            eval($safeCode);
        }
    }

    /**
     * 百度内容审核
     * @param string $text 文本内容
     * @return bool
     */
    public static function baiduContentSensor(string $text): bool
    {
        $baiduContentSensor = new BaiduContentSensor();
        return $baiduContentSensor->checkContent($text);
    }

    /**
     * 百度翻译
     * @param string $text 文本内容
     * @return string
     */
    public static function baiduTranslate($text)
    {
        $BaiduTranslate = new BaiduTranslate();
        if (preg_match("/[\x{4e00}-\x{9fa5}]/u", $text)) {
            return $BaiduTranslate->baidutranslatezhtoen($text);
        } else {
            return $text;
        }
    }

    /**
     * 计算token数量
     * @param string $content 内容
     * @return int
     */
    public static function calculateTokens($content)
    {
        $tokens = 0;
        // 匹配中文字符
        preg_match_all('/[\x{4e00}-\x{9fa5}]/u', $content, $chineseMatches);
        $tokens += count($chineseMatches[0]) * 2; // 每个汉字算作 2 个 token
        // 匹配英文单词
        preg_match_all('/\b\w+\b/', $content, $englishMatches);
        $tokens += count($englishMatches[0]); // 每个英文单词算作 1 个 token
        // 匹配中文标点符号
        preg_match_all('/[，。！？；：“”‘’]/u', $content, $chinesePunctuationMatches);
        $tokens += count($chinesePunctuationMatches[0]) * 2; // 每个中文标点算作 2 个 token
        // 匹配英文标点符号
        preg_match_all('/[.,!?;:\'"()]/', $content, $englishPunctuationMatches);
        $tokens += count($englishPunctuationMatches[0]); // 每个英文标点算作 1 个 token
        return $tokens;
    }

    /**
     * 构建Post数据
     * @param int $isimage 图片类型
     * @param string $modelvalue 模型值
     * @param string $modeltype 模型类型
     * @param string $prompt 提示词
     * @param string $imageurl 图片URL
     * @param int $userid 用户ID
     * @param array $roleArray 角色数组
     * @param array $context 上下文
     * @param int $maxcontexttokens 最大上下文token数
     * @param string $post_role 角色
     * @param string $midjourneyaction midjourney动作
     * @param string $blendimageurl 混合图片URL
     * @return array
     */
    public static function buildPostData($isimage, $modelvalue, $modeltype, $prompt, $imageurl, $userid, $roleArray, $context, $maxcontexttokens, $post_role, $midjourneyaction, $blendimageurl)
    {
        $main_data = AIConfigService::getMainConfig();

        // 识图模型
        if ($isimage == 4) {
            return self::buildImageRecognitionPostData($modeltype, $modelvalue, $prompt, $imageurl, $userid);
        }

        // 改图模型
        if ($isimage == 2) {
            return self::buildImageEditPostData($modeltype, $modelvalue, $prompt, $imageurl, $userid, $midjourneyaction, $blendimageurl);
        }

        // 文本或画图模型
        if (
            ($modelvalue == 'openai_image') ||
            (in_array($modeltype, ["stablediffusion", "openai", "azure"]) && $isimage)
        ) {
            return [
                "prompt" => $prompt,
                "model" => $modelvalue,
                "n" => 1,
                "size" => "1024x1024"
            ];
        }

        if ($modeltype == "清华智谱" && $modelvalue == "cogview-3" && $isimage) {
            return [
                "prompt" => $prompt,
                "model" => $modelvalue
            ];
        }

        if ($modeltype == "chimeragpt" && $isimage) {
            return [
                "prompt" => $prompt,
                "model" => $modelvalue,
                "n" => 1,
                "size" => "1024x1024",
                "response_format" => "url"
            ];
        }

        if ($modeltype == "腾讯混元" && $modelvalue == "TextToImageLite" && $isimage) {
            return [
                "Prompt" => $prompt,
                "Resolution" => "1024:1024",
                "RspImgType" => "url"
            ];
        }

        if ($modeltype == "讯飞星火" && $isimage) {
            return [
                "header" => ["app_id" => ""],
                "parameter" => ["chat" => ["domain" => "general", "width" => 1024, "height" => 1024]],
                "payload" => [
                    "message" => [
                        "text" => [["role" => "user", "content" => $prompt]]
                    ]
                ]
            ];
        }

        if ($modeltype == "360智脑" && $isimage) {
            return [
                "model" => $modelvalue,
                "style" => "realistic",
                "prompt" => $prompt,
                "height" => 1024,
                "width" => 1024
            ];
        }

        if ($modeltype == "通义千问" && $isimage) {
            return [
                "model" => $modelvalue,
                "input" => ["prompt" => $prompt],
                "parameters" => ["size" => "1024*1024", "n" => 1]
            ];
        }

        if ($modeltype == "文心千帆" && $modelvalue == "Stable-Diffusion-XL" && $isimage) {
            return [
                "prompt" => $prompt,
                "steps" => 50,
                "sampler_index" => "DPM++ SDE Karras"
            ];
        }

        if ($modeltype == "midjourney") {
            $url = request()->isSsl() ? 'https://' : 'http://';
            $url .= request()->host() . '/static/ai/plugins/mj/callback.php';

            if (strpos($prompt, " --v 6.0") === false) {
                $prompt .= " --v 6.0";
            }
            return [
                "base64"     => "",
                "notifyHook" => $url,
                "prompt"     => $prompt,
                "state"      => $userid
            ];
        }

        // 文本模型
        return self::buildTextPostData($modeltype, $modelvalue, $prompt, $roleArray, $context, $maxcontexttokens, $post_role, $main_data);
    }

    /**
     * 构建文本Post数据
     * @param string $modeltype 模型类型
     * @param string $modelvalue 模型值
     * @param string $prompt 提示词
     * @param array $roleArray 角色数组
     * @param array $context 上下文
     * @param int $maxcontexttokens 最大上下文token数
     * @param string $post_role 角色
     * @param array $main_data 主配置
     * @return array
     */
    private static function buildTextPostData($modeltype, $modelvalue, $prompt, $roleArray, $context, $maxcontexttokens, $post_role, $main_data)
    {
        if ($modeltype == "LinkAI") {
            $modelParts = explode(",", $modelvalue);
            $postdata = [
                "app_code" => $modelParts[1] ?? '',
                "model" => $modelParts[0] ?? '',
                "temperature" => 0,
                "stream" => true,
                "messages" => []
            ];
        } else {
            $postdata = [
                "model" => $modelvalue,
                "temperature" => 0,
                "stream" => true,
                "messages" => []
            ];
        }

        // 处理角色代码 - 修复eval()安全隐患
        if (!empty($post_role)) {
            foreach ($roleArray as $role) {
                if ($post_role == $role['rolevalue']) {
                    // 使用安全的方式处理角色代码，避免eval()注入风险
                    // 解析角色代码，只允许特定格式的操作
                    self::processRoleCode($role['rolecode'], $postdata);
                }
            }
        }

        // 处理假提示词
        if (!empty($main_data['fakeprompt'])) {
            $fakeprompt = explode("\n", $main_data['fakeprompt']);
            $postdata['messages'][] = ['role' => 'user', 'content' => $fakeprompt[0]];
            $postdata['messages'][] = ['role' => 'assistant', 'content' => $fakeprompt[1]];
        }
        
        // 处理上下文
        if (!empty($context)) {
            $context = array_reverse($context);
            $accumulatedContent = '';
            $reversedPostdata = [];
            foreach ($context as $message) {
                $userContent      = urldecode($message[0]);
                $assistantContent = urldecode($message[1]);
                $combinedContent  = $userContent . $assistantContent;
                if (self::calculateTokens($accumulatedContent . $combinedContent) > $maxcontexttokens) {
                    break;
                }
                $accumulatedContent .= $combinedContent;
                array_unshift($reversedPostdata, ['role' => 'assistant', 'content' => $assistantContent]);
                array_unshift($reversedPostdata, ['role' => 'user', 'content' => $userContent]);
            }
            $postdata['messages'] = array_merge($reversedPostdata, $postdata['messages'] ?? []);
        }
        
        $postdata['messages'][] = ['role' => 'user', 'content' => $prompt];
        return $postdata;
    }

    /**
     * 构建图片识别Post数据
     * @param string $modeltype 模型类型
     * @param string $modelvalue 模型值
     * @param string $prompt 提示词
     * @param string $imageurl 图片URL
     * @param int $userid 用户ID
     * @return array
     */
    private static function buildImageRecognitionPostData($modeltype, $modelvalue, $prompt, $imageurl, $userid = 0)
    {
        switch ($modeltype) {
            case "通义千问":
                return [
                    "model" => $modelvalue,
                    "input" => [
                        "messages" => [
                            [
                                "role" => "user",
                                "content" => [
                                    ["image" => $imageurl],
                                    ["text" => $prompt]
                                ]
                            ]
                        ]
                    ]
                ];
            case "硅基流动":
                return [
                    "model" => $modelvalue,
                    "messages" => [
                                        [
                                            "role" => "user",
                                            "content" =>[
                                                [
                                                    'type'=>'text',
                                                    'text'=>$prompt
                                                ],[
                                                'type'=>'image_url',
                                                'image_url'=>[
                                                    'url'=>$imageurl,
                                                    'detail'=>'high'
                                                    ]
                                                ]
                                            ]
                                        ]
                                ],
                    "stream"    => True,
                    "max_tokens"=> 4096,    # 按需添加
                    "top_k"     => 50,           # 按需添加
                    "top_p"     => 0.7,           # 按需添加
                ];
            case "腾讯混元":
                return  [
                    "Model" => $modelvalue,
                    "Messages" => [
                        [
                            "Role" => "user",
                            "Contents" => [
                                ["Type" => "text", "Text" => $prompt],
                                ["Type" => "image_url", "ImageUrl" => ["Url" => $imageurl]]
                            ]
                        ]
                    ],
                    "Stream" => true
                ];
            case "讯飞星火":
                return [
                    "header" => ["app_id" => ""],
                    "parameter" => ["chat" => ["domain" => "general"]],
                    "payload" => [
                        "message" => [
                            "text" => [
                                ["role" => "user", "content" => "base64", "content_type" => "image"],
                                ["role" => "user", "content" => $prompt, "content_type" => "text"]
                            ]
                        ]
                    ],
                    "imageurl" => $imageurl
                ];
            case "midjourney":
                $url  = request()->isSsl() ? 'https://' : 'http://';
                $url .= request()->host() . '/static/ai/plugins/mj/callback_describe.php';
                return [
                    "base64" => "",
                    "notifyHook"    => $url,
                    "state"         => $userid,
                    "imageurl"      => $imageurl
                ];
            case "bard":
                return [
                    "contents" => [
                        [
                            "parts" => [
                                ["text" => $prompt],
                                ["inline_data" => ["mime_type" => "image/jpeg", "data" => ""]]
                            ]
                        ]
                    ],
                    "imageurl" => $imageurl
                ];
            case "claude":
                return [
                    "model" => $modelvalue,
                    "max_tokens" => 2000,
                    "stream" => true,
                    "messages" => [
                        [
                            "role" => "user",
                            "content" => [
                                ["type" => "text", "text" => $prompt],
                                ["type" => "image", "source" => ["type" => "base64", "media_type" => "image/jpeg", "data" => ""]]
                            ],
                            "imageurl" => $imageurl
                        ]
                    ]
                ];
            case "fuyu_8b":
                return [
                    "prompt" => $prompt,
                    "image" => "",
                    "imageurl" => $imageurl
                ];
            default:
                if (in_array($modelvalue, ["gpt-4-vision-preview", "gpt-4o", "glm-4v"])) {
                    return [
                        "model" => $modelvalue,
                        "temperature" => 0,
                        "stream" => true,
                        "messages" => [
                            [
                                "role" => "user",
                                "content" => [
                                    ["type" => "text", "text" => $prompt],
                                    ["type" => "image_url", "image_url" => ["url" => $imageurl, "detail" => "high"]]
                                ]
                            ]
                        ],
                        "max_tokens" => 2000
                    ];
                }
        }
    }

    /**
     * 构建图片编辑Post数据
     * @param string $modeltype 模型类型
     * @param string $modelvalue 模型值
     * @param string $prompt 提示词
     * @param string $imageurl 图片URL
     * @param int $userid 用户ID
     * @param string $midjourneyaction midjourney动作
     * @param string $blendimageurl 混合图片URL
     * @return array
     */
    private static function buildImageEditPostData($modeltype, $modelvalue, $prompt, $imageurl, $userid, $midjourneyaction, $blendimageurl)
    {
        if ($modeltype == "midjourney") {
            $url = request()->isSsl() ? 'https://' : 'http://';
            $url .= request()->host() . '/static/ai/plugins/mj/callback.php';

            if (!empty($midjourneyaction)) {
                $mjstr = explode(".", $midjourneyaction);
                return [
                    "customId" => $mjstr[0] . "::" . $mjstr[1] . "::" . $mjstr[3],
                    "notifyHook" => $url,
                    "state" => $userid,
                    "taskId" => $mjstr[2],
                    "prompt" => $prompt
                ];
            } elseif (!empty($blendimageurl)) {
                return [
                    "base64Array" => [],
                    "dimensions" => "SQUARE",
                    "notifyHook" => $url,
                    "state" => $userid,
                    "imageurl" => $imageurl,
                    "blendimageurl" => $blendimageurl,
                    "prompt" => "![IMG](" . $imageurl . ")![IMG](" . $blendimageurl . ")混合图片"
                ];
            } else {
                return [
                    "base64" => "",
                    "notifyHook" => $url,
                    "prompt" => $imageurl . " " . $prompt,
                    "state" => $userid
                ];
            }
        } else {
            return [
                "model" => $modelvalue,
                "n" => 1,
                "size" => "1024x1024",
                "url" => $imageurl,
                "prompt" => $prompt
            ];
        }
    }
}
