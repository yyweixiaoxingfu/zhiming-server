<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2017 河源市卓锐科技有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------
// | 开源协议 ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------

namespace plugins\Wxpush\controller;

use app\common\controller\Common;
use plugins\Wxpush\model\Wxpush as WxpushModel;   
use plugins\Wxpush\model\Api;
/** 
 *Wxpush控制器
 * @package plugins\Sms\controller
 */
class Wxpush extends Common
{
    /**
     * 发送短信
     * @param string $name 模板名称 
     * @param string $openid OPENID 
    * @param string,array $data 模板参数, json字符串或数据 
     * @param string $url 跳转链接 (可选) 
     * @return array 
     *
     * $result = plugin_action('Wxpush/Wxpush/send', ['提现失败', 'xsxasdfsad*******', $param ]);
     * if($result['code']>1){
     *     $this->error('发送失败，错误代码：'. $result['code']. ' 错误信息：'. $result['msg']);
     * } else {
     *     $this->success('发送成功');
     * }
     * data参数格式示例 :
        {
           "first": {
               "value":"订单已发货",
               "color":"#173177"
           },
           "keyword1":{
               "value":"您的订单已发货",
               "color":"#173177"
           }, 
           "remark":{
               "value":"2020-01-01 09:00",
               "color":"#173177"
           } 
        }
     */
    public function send(string $name = '',string $openid,$data=[],string $url="")
    {
        $config = plugin_config('wxpush');

        if(gettype($data)=='string'){
            $data=json_decode($data,true); 
        }
        
        if (empty($config['appid'])) {
            return array('code' => 2, 'msg' => '请填写APPKEY');
        }
        if (empty($config['secret'])) {
            return array('code' => 3, 'msg' => '请填写SECRET');
        } 
         
        $template = WxpushModel::getTemplate($name); 
        if (!$template) {
            return array('code' => 7, 'msg' => '找不到推送模板');
        }

        // 模板参数
        if ($template['status'] == '0') {
            return array('code' => 8, 'msg' => '推送模板已禁用');
        }
        if ($template['tpl_id'] == '') {
            return array('code' => 9, 'msg' => '请设置模板ID');
        } 
        $param=[
            'touser'=>trim($openid),
            "url"=>trim($url),
            'data'=>$data, 
            "template_id"=>trim($template['tpl_id'])
        ];   
        $api=new Api($config['appid'],$config['secret']);
        $access_token=$api->getToken(); 
        if(gettype($access_token)!="string"){
            return ['errcode'=>500,'data'=>$param,"msg"=>$access_token];
        }
         $url='https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$access_token; 
         $ret=$api->curl($url,json_encode($param,JSON_UNESCAPED_UNICODE)); 
        if(isset($ret['errcode'])&& intval($ret['errcode'])>0){ 
          return ['errcode'=>500,'data'=>$param,"msg"=>$ret];
        }else{  
          return ['errcode'=>0,'data'=>$param];
        }
         
    }

    
        
}