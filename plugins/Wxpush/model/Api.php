<?php
namespace plugins\Wxpush\model;
use app\common\model\Plugin;
class Api{
	private $appid;
	private $secret;
	private $type=0;//0公众号 1小程序
	private $errcode=0;
	private $access_token=null;
	private $access_expires_in=0;
	function __construct($appid,$secret,$type=0){ 
		$this->appid=$appid;
		$this->secret=$secret;
		$this->type=$type;  
	}
	function getSessionKey($code=null){
		$key=session('session_key');
		if(empty($key)){
			$ret=$this->getOpenID($code);
			if(isset($ret['session_key'])){
				session('session_key',$ret['session_key']);
				return $ret['session_key'];
			}
			
		}
		return $key;
	}
	//解密敏感数据
	function decryptData($encryptedData,$iv,$code=null){
	 	$session_key="";
		$ret=$this->getOpenID($code);
		if(isset($ret['session_key'])){
			session('session_key',$ret['session_key']);
			 $session_key=$ret['session_key'];
		}
		if(empty($session_key)){
			return ['status'=>0,"errcode"=>404,"error"=>"解密失败，session_key not found!"];
		}
		$cry=new WXBizDataCrypt($this->appid,$session_key);
		$error=$cry->decryptData($encryptedData,$iv,$data);
		if(!empty($error)){
			return ['status'=>0,"errcode"=>$error,"error"=>"解密失败"];
		}else{
			if(json_decode($data,true)){
				return ['status'=>1,"data"=>json_decode($data,true)];
			}
			return ['status'=>1,"data"=>$data];
		}
	}
	function cacheToken($token){
		if(isset($token['access_token'])&&intval($token['expires_in'])>1){
			 
			$we['access_token']=$token['access_token'];
			$we['access_expires_in']=time()+intval($token['expires_in']);
			cache("plugin_wxpush_token",$we);
			return true;
		}
		return false;
	}
	function init($appid,$secret){
		$this->appid=$appid;
		$this->secret=$secret;
	}
	function getToken(){
		if($this->access_token!=null&&$this->access_expires_in>(time()+10)){
			return $this->access_token;
		} 
		$we=cache("plugin_wxpush_token");
		if(isset($we['access_token'])&&isset($we['access_expires_in'])){
			if(intval($we['access_expires_in'])<=(time()+10)){//过期了
				$token=$this->getAccessToken(); 
				if(!isset($token['access_token']))return $token;
				$this->cacheToken($token);
				return $token['access_token'];
			}else{
				$this->access_token=$we['access_token'];
	    		$this->access_expires_in=$we['access_expires_in'];
				return $we['access_token'];
			}
		}else{
			$token=$this->getAccessToken(); 
			if(!isset($token['access_token']))return $token;
			$this->cacheToken($token);
			return $token['access_token'];
		}
	} 
	function getAccessToken(){
		//GET https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=APPID&secret=APPSECRET

		$url="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->appid."&secret=".$this->secret; 
		
		$ret=$this->curl($url);
		if(isset($ret['access_token'])){
	    	$this->access_token=$ret['access_token'];
	    	$this->access_expires_in=$ret['expires_in']+time();
	    }  
		return $ret;
	}
		//公众号获取用户信息
	public function getUserInfoByOpenID(string $opendid,string $token){
		//https://api.weixin.qq.com/sns/userinfo?access_token=ACCESS_TOKEN&openid=OPENID&lang=zh_CN
		$url="https://api.weixin.qq.com/sns/userinfo?access_token=".$token."&openid=".$opendid."&lang=zh_CN";
		$ret=$this->curl($url);
		return $ret;
	}
	function getOpenID($code){  
		if($this->type==0){
			$url="https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$this->appid."&secret=".$this->secret."&code=".$code."&grant_type=authorization_code"; 

		}else{
			 $url="https://api.weixin.qq.com/sns/jscode2session?appid=".$this->appid."&secret=".$this->secret."&js_code=".$code."&grant_type=authorization_code";  
		}  
	    $ret= $this->curl($url); 
		return $ret;
	}
	//获取有限时的二维码
	function getUnlimited($post,$format=true){
		//https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=ACCESS_TOKEN
		$token=$this->getToken();
		$url="https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=".$token;
		if(!isset($post['width'])){
			$post['width']=300;
		} 
		$res=$this->curlImg($url,$post,$format);
		return $res;
	}
	function curl($url,$data=[],$headers=[]){
		$ch = curl_init(); 
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		if(!empty($headers)){
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		}	
		if(!empty($data)){
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		// curl_setopt($ch, CURLOPT_SSLVERSION, 1);
		// if (defined('CURL_SSLVERSION_TLSv1')) {
		// 	curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
		// }
		curl_setopt($ch, CURLOPT_TIMEOUT,10);
		$rs=curl_exec($ch);
		if(curl_errno($ch)){//出错则显示错误信息
	       return array("status"=>0,"errcode"=>60,"error"=>"CURL：".curl_error($ch));
	    }else{
			curl_close($ch);   
			$arr = json_decode($rs,true);
			$arr['status']=1;    
			if(isset($arr['errcode'])&&intval($arr['errcode'])>0){
				$arr['status']=0; 
			}
		 	return $arr;
			 
		}
	}
	function curlImg($url,$data,$format=false){
	//发送curl，返回arr 
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type"=>'image/png']);
		 
		if(!empty($data)){
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		 
		$rs=curl_exec($ch); 
		 
		if(curl_errno($ch)){//出错则显示错误信息 
	        return array("errcode"=>curl_errno($ch),"res"=>$rs,"errmsg"=>"curl请求错误");
	    }else{
			curl_close($ch);  
			if(is_null(json_decode($rs))){ 
				if($format){//转换成base64
					$res=base64_encode($rs);
					if(!(strpos($res, "data:image/jpeg;base64,")===false||strpos($res, "data:image/png;base64,"))===false){
						$res="data:image/jpeg;base64,".$res;
					}  
					return array("errcode"=>0,"base64"=>$res);
				}else{//返回文件流
					//header("Content-Type:image/jpeg");
					return $rs;
				}
				
			}else{ 
			  	$arr = json_decode($rs,true);
			  	$arr['url']=$url;
			  	return $arr; // 返回数据 
			}
			return 0;
		}
	} 
}