<?php

//算命控制器
namespace app\index\model;
use think\Model;
use think\Db;
use app\common\library\sm\cls_strokeData;
use app\common\library\sm\cls_almanacData;
use app\common\library\sm\cls_wedlockData;
use app\common\library\sm\cls_rowDish;
use app\common\library\sm\cls_rowDishFunction;
use app\common\library\sm\cls_dayLandData;
use app\common\library\sm\cls_ancientCalendarData;
use app\common\library\sm\cls_lunarData;
use app\common\library\phpu\SolarTerm;
use app\common\library\phpu\Calendar;
use app\common\library\phpu\ChineseCalendar;

class Sm extends Model
{
    /**
     * 静态缓存数组，替代全局变量$sm_cache
     * 用于缓存数据库查询结果，提高性能
     * @var array
     */
    private static $smCache = [];


     /**
     * 根据汉字查询字典表获取拼音、繁体、五行等信息
     * @param string $character 汉字
     * @return array 包含汉字信息的数组
     */
    public static function getCharacterInfo($character) {
        // 参数验证
        if (empty($character) || !is_string($character)) {
            return [
                'pinyin'  => '',
                'bihua'   => '',
                'wuxing'  => '水',
                'fan'     => $character
            ];
        }
        
        // 检查缓存
        $cacheKey = 'char_info_' . $character;
        if (isset(self::$smCache[$cacheKey])) {
            return self::$smCache[$cacheKey];
        }
        
        try {
            // 从字典表查询
            $info = Db::name('qm_zidians')
                ->where('zi', 'like',$character)
                ->field('pinyin,pinyin2,zi_fan,bihua, wuxing')
                ->order('star_value desc')
                ->find();
            
            if (!empty($info)) {
                // 查询成功，返回信息
                $result = [
                    'pinyin'    => $info['pinyin2'] ?? '',
                    'bihua'     => $info['bihua'] ?? '',
                    'wuxing'    => $info['wuxing'] ?? '水',
                    'fan'       => $info['zi_fan'] ?? $character
                ];
            } else {
                // 查询失败，使用原有函数作为备用
                $result = [
                    'pinyin' => Pinyin_sm($character, 1),
                    'bihua'  => getnumft($character),
                    'wuxing' => getzywh($character),
                    'fan'    => gb_big5($character)
                ];
            }
            
            // 缓存结果
            self::$smCache[$cacheKey] = $result;
            return $result;
            
        } catch (Exception $e) {
            // 错误处理，使用原有函数
            return [
                'pinyin'    => Pinyin_sm($character, 1),
                'bihua'     => getnumft($character),
                'wuxing'    => getzywh($character),
                'fan'       => gb_big5($character)
            ];
        }
    }
    
    //姓名匹配模块方法
    public function matchingName($nan_xing,$nan_name,$nv_xing,$nv_name){
        try {
            // 参数验证
            if (empty($nan_xing) || empty($nan_name) || empty($nv_xing) || empty($nv_name)) {
                return array();
            }
            
            $return_data = array();
            $nan_xm      = $nan_xing.$nan_name;      //男的姓名
            $nan_tiange=0; $nan_dige=0;$nan_renge=0; //男 天地人格
            //定义男的基本参数                        
            $nan_xing1="";$nan_ming1="";
            $nan_ming2="";$nan_xing2="";
            $nan_renge=0;$nan_renge1=0;$nan_renge2=0;
            $nan_x_bihua1=0;$nan_x_bihua2=0; //姓氏笔画

            $return_data['nan_xing1_data'] = '';
            $nan_xing1     = substr($nan_xing,0,3);      //男子姓氏第一个字
           
            if($nan_xing1){
                $charInfo        = self::getCharacterInfo($nan_xing1);
                $nan_x_bihua1    = $charInfo['bihua'];
                //$nan_x_bihua1   = getnumft($nan_xing1);    //得到繁体笔画
                $nan_xing1_data = array(
                    'nan_xing1'           =>$nan_xing1,
                    'nan_xing_bihua1'     =>$nan_x_bihua1,
                    'nan_xing_py1'        =>$charInfo['pinyin'],     //获取拼音
                    'nan_xing_gb1'        =>$charInfo['fan'],        //获取gb2312
                    'nan_xing_wh1'        =>$charInfo['wuxing']);    //获取五行
                $return_data['nan_xing1_data'] = $nan_xing1_data;
                $nan_tiange     =$nan_x_bihua1+1;
                $nan_renge1     =$nan_x_bihua1;
            }

            //检查男性是否是复姓
            $return_data['nan_xing2_data'] = '';
            $nan_xing2    = substr($nan_xing,3,3);
            if($nan_xing2){ 
                $charInfo        = self::getCharacterInfo($nan_xing2);
                $nan_x_bihua2    = $charInfo['bihua'];
                //$nan_x_bihua2    = getnumft($nan_xing2);
               
                $nan_xing2_data = array(
                    'nan_xing2'           =>$nan_xing2,
                    'nan_xing_bihua2'     =>$nan_x_bihua2,
                    'nan_xing_py2'        =>$charInfo['pinyin'],     //获取拼音
                    'nan_xing_gb2'        =>$charInfo['fan'],        //获取gb2312
                    'nan_xing_wh2'        =>$charInfo['wuxing']);    //获取五行
                $return_data['nan_xing2_data'] = $nan_xing2_data;
                $nan_tiange      = $nan_x_bihua1+$nan_x_bihua2;
                $nan_renge1      = $nan_x_bihua2;                        
            }
            //检查男性名字第一个字
            $nan_m_bihua1=0;$nan_m_bihua2=0; //名字笔画
            $return_data['nan_ming1_data'] = '';
            $nan_ming1    = substr($nan_name,0,3);
            if($nan_ming1){
                $charInfo        = self::getCharacterInfo($nan_ming1);
                $nan_m_bihua1    = $charInfo['bihua'];
                //$nan_m_bihua1    = getnumft($nan_ming1);
                
                $nan_ming1_data = array(
                    'nan_ming1'           =>$nan_ming1,
                    'nan_ming_bihua1'     =>$nan_m_bihua1,
                    'nan_ming_py1'        =>$charInfo['pinyin'],     //获取拼音
                    'nan_ming_gb1'        =>$charInfo['fan'],        //获取gb2312
                    'nan_ming_wh1'        =>$charInfo['wuxing']);    //获取五行
                $return_data['nan_ming1_data'] = $nan_ming1_data;
                $nan_dige        =$nan_m_bihua1+1;
                $nan_renge2      =$nan_m_bihua1;
            }
                    
            //检查男性名字第二个字
            $return_data['nan_ming2_data'] = '';
            $nan_ming2    = substr($nan_name,3,3);
            if($nan_ming2){
                $charInfo        = self::getCharacterInfo($nan_ming2);
                $nan_m_bihua2    = $charInfo['bihua'];
                
                $nan_ming2_data = array(
                    'nan_ming2'           =>$nan_ming2,
                    'nan_ming_bihua2'     =>$nan_m_bihua2,
                    'nan_ming_py2'        =>$charInfo['pinyin'],     //获取拼音
                    'nan_ming_gb2'        =>$charInfo['fan'],        //获取gb2312
                    'nan_ming_wh2'        =>$charInfo['wuxing']);    //获取五行
                $return_data['nan_ming2_data'] = $nan_ming2_data;
                $nan_dige        =$nan_m_bihua1+$nan_m_bihua2;
                    $nan_renge2      =$nan_m_bihua1;       //修正：人格计算应为姓氏笔画数+名字第一个字的笔画数，nan_renge1已有姓氏笔画数，这里只需名字第一个字笔画数
            }
            //计算天地人格
            $nan_zongge = $nan_x_bihua1+$nan_x_bihua2+$nan_m_bihua1+$nan_m_bihua2;  //通过笔画计算 总格
            $nan_renge  = $nan_renge1+=$nan_renge2;
            $nan_waige  = $nan_zongge-$nan_renge;

            if ($nan_xing2 == ""){
                $nan_waige++;
            }
            if ($nan_ming2 =="" ){
                $nan_waige++;
            }
               
            $nan_tgjx_data = getmgft($nan_tiange);      //男  天格吉凶
            $nan_tgjx      = $nan_tgjx_data['jx'];
            $nan_tgf       = getpf_133($nan_tgjx);
            $nan_dgjx_data = getmgft($nan_dige);        //男  地格吉凶
            
            $nan_dgjx      = $nan_dgjx_data['jx'];
            $nan_dgf       = getpf_133($nan_dgjx);
            $nan_rgjx_data = getmgft($nan_renge);       //男  人格吉凶
            $nan_rgjx      = $nan_rgjx_data['jx'];
            
            $nan_rgf       = getpf_133($nan_rgjx);
               
            $nan_zgjx_data = getmgft($nan_zongge);      //男  总格吉凶
            
            $nan_zgjx      = $nan_zgjx_data['jx'];
            $nan_zgf       = getpf_133($nan_zgjx);
            $nan_wgjx_data = getmgft($nan_waige);       //男  外格吉凶
            $nan_wgjx      = $nan_wgjx_data['jx'];
            $nan_wgf       = getpf_133($nan_wgjx);
           
            $nan_sancai    = getsancai_133($nan_tiange).getsancai_133($nan_renge).getsancai_133($nan_dige);     //计算三才和性格
            $sancai_data   = getsanCaiData($nan_sancai);
            $nan_xg        = $sancai_data['xg']; 
            $nan_data      = array('nan_name'=>$nan_xm,'sex'=>'男','nan_xg'=>$nan_xg);
            $return_data['nan_data'] = $nan_data;

            $nan_tian_sancai        = getsancai($nan_tiange);
            $nan_ren_sancai         = getsancai($nan_renge);
            $nan_di_sancai          = getsancai($nan_dige);
            $n_sancai               = $nan_tian_sancai.$nan_ren_sancai.$nan_di_sancai;
            $n_rssancai               = getsanCaiData($n_sancai);
            $n_rssancai['nan_sancai'] = $n_sancai;
            $tdrh_ge_arr_nan        = array( 
                'nan_tiange'            =>$nan_tiange,
                'nan_tiange_sancai'     =>getsancai($nan_tiange),
                'nan_renge'             =>$nan_renge,
                'nan_renge_sancai'      =>getsancai_133($nan_renge),
                'nan_dige'              =>$nan_dige,
                'nan_dige_sancai'       =>getsancai_133($nan_dige),
                'nan_waige'             =>$nan_waige,
                'nan_waige_sancai'      =>getsancai_133($nan_waige),
                'nan_zongge'            =>$nan_zongge,
                'nan_zongge_sancai'     =>getsancai_133($nan_zongge),
                'nan_yuanshen'          =>get_yuanshen($nan_renge),
                'nan_sancai'            =>$n_rssancai);
            $return_data['nan_tdrh_ge_data'] = $tdrh_ge_arr_nan;

                       
        $nv_xm      = $nv_xing.$nv_name;            //女的姓名
        $nv_tiange=0; $nv_dige=0;$nv_renge=0;       //女 天地人格
        //定义男的基本参数                        
        $nv_xing1="";$nv_ming1="";
        $nv_ming2="";$nv_xing2="";
        $nv_renge=0;$nv_renge1=0;$nv_renge2=0;
        $nv_x_bihua1=0;$nv_x_bihua2=0; //姓氏笔画

        $return_data['nv_xing1_data'] = '';
        $nv_xing1     = substr($nv_xing,0,3);      //男子姓氏第一个字
        if($nv_xing1){
            $charInfo      = self::getCharacterInfo($nv_xing1);
            $nv_x_bihua1   = $charInfo['bihua'];    //得到繁体笔画
            
            $nv_xing1_data = array(
                'nv_xing1'           =>$nv_xing1,
                'nv_xing_bihua1'     =>$nv_x_bihua1,
                'nv_xing_py1'        =>$charInfo['pinyin'],     //获取拼音
                'nv_xing_gb1'        =>$charInfo['fan'],        //获取gb2312
                'nv_xing_wh1'        =>$charInfo['wuxing']);    //获取五行
            $return_data['nv_xing1_data'] = $nv_xing1_data;
            $nv_tiange     =$nv_x_bihua1+1;
            $nv_renge1     =$nv_x_bihua1;
        }

        //检查女是否是复姓
        $return_data['nv_xing2_data'] = '';
        $nv_xing2    = substr($nv_xing,3,3);
        if($nv_xing2){ 
            $charInfo      = self::getCharacterInfo($nv_xing2);
            $nv_x_bihua2     = $charInfo['bihua'];
            $nv_xing2_data = array(
                'nv_xing2'           =>$nv_xing2,
                'nv_xing_bihua2'     =>$nv_x_bihua2,
                'nv_xing_py2'        =>$charInfo['pinyin'],     //获取拼音
                'nv_xing_gb2'        =>$charInfo['fan'],        //获取gb2312
                'nv_xing_wh2'        =>$charInfo['wuxing']);    //获取五行
            $return_data['nv_xing2_data'] = $nv_xing2_data;
            $nv_tiange      = $nv_x_bihua1+$nv_x_bihua2;
            $nv_renge1      = $nv_x_bihua2;
        }

        //检查女性名字第一个字
        $nv_m_bihua1=0;$nv_m_bihua2=0; //名字笔画
        $return_data['nv_ming1_data'] = '';
        $nv_ming1    = substr($nv_name,0,3);
        if($nv_ming1){
            $charInfo       = self::getCharacterInfo($nv_ming1);  
            $nv_m_bihua1    = $charInfo['bihua'];
              
            $nv_ming1_data = array(
                'nv_ming1'           =>$nv_ming1,
                'nv_ming_bihua1'     =>$nv_m_bihua1,
                'nv_ming_py1'        =>$charInfo['pinyin'],     //获取拼音
                'nv_ming_gb1'        =>$charInfo['fan'],        //获取gb2312
                'nv_ming_wh1'        =>$charInfo['wuxing']);    //获取五行
            $return_data['nv_ming1_data'] = $nv_ming1_data;
            $nv_dige        =$nv_m_bihua1+1;
            $nv_renge2      =$nv_m_bihua1;
        }
        //检查女性名字第二个字
        $return_data['nv_ming2_data'] = '';
        $nv_ming2    = substr($nv_name,3,3);
        if($nv_ming2){
            $charInfo      = self::getCharacterInfo($nv_ming2);
            $nv_m_bihua2    = $charInfo['bihua'];
            
            $nv_ming2_data = array(
                'nv_ming2'           =>$nv_ming2,
                'nv_ming_bihua2'     =>$nv_m_bihua2,
                'nv_ming_py2'        =>$charInfo['pinyin'],     //获取拼音
                'nv_ming_gb2'        =>$charInfo['fan'],        //获取gb2312
                'nv_ming_wh2'        =>$charInfo['wuxing']);    //获取五行
                $return_data['nv_ming2_data'] = $nv_ming2_data;
                $nv_dige        =$nv_m_bihua1+$nv_m_bihua2;
                $nv_renge2      =$nv_m_bihua1;                 //修正：人格计算应为姓氏笔画数+名字第一个字的笔画数，nv_renge1已有姓氏笔画数，这里只需名字第一个字笔画数
                
        }
        $nv_zongge = $nv_x_bihua1+$nv_x_bihua2+$nv_m_bihua1+$nv_m_bihua2;  //通过笔画计算 总格
        $nv_renge  = $nv_renge1+=$nv_renge2;
        $nv_waige  = $nv_zongge-$nv_renge;

        if ($nv_xing2 == ""){
            $nv_waige++;
        }
        if ($nv_ming2 =="" ){
            $nv_waige++;
        }

        $nv_tgjx_data = getmgft($nv_tiange);      //女  天格吉凶
        $nv_tgjx      = $nv_tgjx_data['jx'];
        $nv_tgf       = getpf_133($nv_tgjx);
        $nv_dgjx_data = getmgft($nv_dige);        //女  地格吉凶
        $nv_dgjx      = $nv_dgjx_data['jx'];
        $nv_dgf       = getpf_133($nv_dgjx);
        $nv_rgjx_data = getmgft($nv_renge);       //女  人格吉凶
        $nv_rgjx      = $nv_rgjx_data['jx'];
        $nv_rgf       = getpf_133($nv_rgjx);
        $nv_zgjx_data = getmgft($nv_zongge);      //女  总格吉凶
        $nv_zgjx      = $nv_zgjx_data['jx'];
        $nv_zgf       = getpf_133($nv_zgjx);
        $nv_wgjx_data = getmgft($nv_waige);       //女  外格吉凶
        $nv_wgjx      = $nv_wgjx_data['jx'];
        $nv_wgf       = getpf_133($nv_wgjx);

        $nv_sancai    = getsancai_133($nv_tiange).getsancai_133($nv_renge).getsancai_133($nv_dige);     //计算三才和性格
        $sancai_data  = getsanCaiData($nv_sancai);
        $nv_xg        = $sancai_data['xg'];  

        $nv_tian_sancai          = getsancai($nv_tiange);
        $nv_ren_sancai           = getsancai($nv_renge);
        $nv_di_sancai            = getsancai($nv_dige);
        $v_sancai                = $nv_tian_sancai.$nv_ren_sancai.$nv_di_sancai;
        $v_rssancai              = getsanCaiData($v_sancai);
        $v_rssancai['nv_sancai'] = $v_sancai;
        $tdrh_ge_arr_nv        = array( 
            'nv_tiange'            =>$nv_tiange,
            'nv_tiange_sancai'     =>getsancai($nv_tiange),
            'nv_renge'             =>$nv_renge,
            'nv_renge_sancai'      =>getsancai_133($nv_renge),
            'nv_dige'              =>$nv_dige,
            'nv_dige_sancai'       =>getsancai_133($nv_dige),
            'nv_waige'             =>$nv_waige,
            'nv_waige_sancai'      =>getsancai_133($nv_waige),
            'nv_zongge'            =>$nv_zongge,
            'nv_zongge_sancai'     =>getsancai_133($nv_zongge),
            'nv_yuanshen'          =>get_yuanshen($nv_renge),
            'nv_sancai'            =>$v_rssancai);
        $return_data['nv_tdrh_ge_data'] = $tdrh_ge_arr_nv;
        $nv_data      = array('nv_name'=>$nv_xm,'sex'=>'女','nv_xg'=>$nv_xg);
        $return_data['nv_data'] = $nv_data;

         //配对分数
         $n1=abs($nan_rgf-$nv_rgf);
         $n2=abs($nan_dgf-$nv_dgf);
         $n3=abs($nan_zgf-$nv_zgf);
         $n4=abs($nan_tgf-$nv_tgf);
         $n5=abs($nan_wgf-$nv_wgf);
         $zf=($n1+$n2+$n3)+($n4+$n5)/5;
         $zf=round(100-$zf);
         $return_data['pd_num']     = $zf; //总分
         $return_data['pd_pingfen'] = round($zf/20,1); //总评分

         $gua  = $return_data['nan_tdrh_ge_data']['nan_waige']+$return_data['nan_tdrh_ge_data']['nan_zongge']+$return_data['nv_tdrh_ge_data']['nv_waige']+$return_data['nv_tdrh_ge_data']['nv_zongge'];
         $guas                  = $gua%64;     //匹配姓名64挂
         $gua_data              = getPdGua($guas);
         $return_data['gua']    = $gua_data;
         $return_data['gua2']   = Db::name('sm_gua_ci')->where(['id' => $guas])->find();
         return $return_data;
        } catch (Exception $e) {
            // 异常处理，确保方法始终返回数组
            return $e->getMessage();
        }
    }
    //姓名分析
    public function nameXf($xing,$name,$sex=0){
        try {
            // 参数验证
            if (empty($xing) || empty($name)) {
                return '参数错误';
            }
            
            $sex_data = '男';
            if($sex==1){
                $sex_data    = '女';
            }elseif($sex==2){
                $sex_data    = '未知';
            }
            $return_data = array();
            $xm          = $xing.$name;      //姓名
            $tiange=0; $dige=0;$renge=0;     //天地人格
            //定义基本参数                        
            $xing1="";$ming1="";
            $ming2="";$xing2="";
            $renge=0;$renge1=0;$renge2=0;
            $x_bihua1=0;$x_bihua2=0; //姓氏笔画

            $return_data['xing1_data'] = '';
            $xing1     = substr($xing,0,3);      //姓氏第一个字
            if($xing1){
                $charInfo   = self::getCharacterInfo($xing1);
                $x_bihua1   = $charInfo['bihua'];    //得到繁体笔画
                
                $xing1_data = array(
                    'xing1'           =>$xing1,
                    'xing_bihua1'     =>$x_bihua1,
                    'xing_py1'        =>$charInfo['pinyin'],     //获取拼音
                    'xing_gb1'        =>$charInfo['fan'],        //获取gb2312
                    'xing_wh1'        =>$charInfo['wuxing']);    //获取五行
                $return_data['xing1_data'] = $xing1_data;
                $tiange     =$x_bihua1+1;
                $renge1     =$x_bihua1;
            }

            //检查是否是复姓
            $return_data['xing2_data'] = '';
            $xing2    = substr($xing,3,3);
            if($xing2){ 
                $charInfo     = self::getCharacterInfo($xing2);
                $x_bihua2     = $charInfo['bihua'];
               
                $xing2_data = array(
                    'xing2'           =>$xing2,
                    'xing_bihua2'     =>$x_bihua2,
                    'xing_py2'        =>$charInfo['pinyin'],     //获取拼音
                    'xing_gb2'        =>$charInfo['fan'],        //获取gb2312
                    'xing_wh2'        =>$charInfo['wuxing']);    //获取五行
                $return_data['xing2_data'] = $xing2_data;
                $tiange      = $x_bihua1+$x_bihua2;
                $renge1      = $x_bihua2;                        
            }

            //检查名字第一个字
            $m_bihua1=0;$m_bihua2=0; //名字笔画
            $return_data['ming1_data'] = '';
            $ming1    = substr($name,0,3);
            if($ming1){
                $charInfo    =self::getCharacterInfo($ming1);
                $m_bihua1    = $charInfo['bihua'];
                
                $ming1_data = array(
                    'ming1'           =>$ming1,
                    'ming_bihua1'     =>$m_bihua1,
                    'ming_py1'        =>$charInfo['pinyin'],     //获取拼音
                    'ming_gb1'        =>$charInfo['fan'],        //获取gb2312
                    'ming_wh1'        =>$charInfo['wuxing']);    //获取五行
                $return_data['ming1_data'] = $ming1_data;
                $dige        =$m_bihua1+1;
                $renge2      =$m_bihua1;
            }
            //检查名字第二个字
            $return_data['ming2_data'] = '';
            $ming2    = substr($name,3,3);
            if($ming2){
                $charInfo    =self::getCharacterInfo($ming2);
                $m_bihua2    = $charInfo['bihua'];
                $ming2_data = array(
                    'ming2'           =>$ming2,
                    'ming_bihua2'     =>$m_bihua2,
                    'ming_py2'        =>$charInfo['pinyin'],     //获取拼音
                    'ming_gb2'        =>$charInfo['fan'],        //获取gb2312
                    'ming_wh2'        =>$charInfo['wuxing']);    //获取五行
                $return_data['ming2_data'] = $ming2_data;
                $dige        =$m_bihua1+$m_bihua2;
                    $renge2      =$m_bihua1;       //修正：人格计算应为姓氏笔画数+名字第一个字的笔画数，renge1已有姓氏相关笔画数，这里只需名字第一个字笔画数
            }
            //计算天地人格
            $zongge = $x_bihua1+$x_bihua2+$m_bihua1+$m_bihua2;  //通过笔画计算 总格
           
            $renge  = $renge1+$renge2;
            $waige  = $zongge-$renge;
            if ($xing2 == ""){
                $waige++;
            }
            if ($ming2 =="" ){
                $waige++;
            }
            $tgjx_data = getmgft($tiange);      // 天格吉凶
            $return_data['tgjx_data'] =$tgjx_data;
            $dgjx_data = getmgft($dige);        //  地格吉凶
            $return_data['dgjx_data'] =$dgjx_data;
            $rgjx_data = getmgft($renge);       //  人格吉凶
            $return_data['rgjx_data'] =$rgjx_data;
            $zgjx_data = getmgft($zongge);      //  总格吉凶
            $return_data['zgjx_data'] =$zgjx_data;
            $wgjx_data = getmgft($waige);       // 外格吉凶
            $return_data['wgjx_data'] =$wgjx_data;
            $sancai       = getsancai_133($tiange).getsancai_133($renge).getsancai_133($dige);     //计算三才和性格
        
            $sancai_data  = getsanCaiData($sancai);
            $xg           = $sancai_data['xg']; 
            
            $data         = array('name'=>$xm,'sex'=>$sex_data,'xg'=>$xg);
            $return_data['xm_data'] = $data;
            $tian_sancai        = getsancai($tiange);
            $ren_sancai         = getsancai($renge);
            $di_sancai          = getsancai($dige);
            $n_sancai           = $tian_sancai.$ren_sancai.$di_sancai;
            $n_rssancai         = getsanCaiData($n_sancai);
            $n_rssancai['sancai'] = $n_sancai;
            $tdrh_ge_arr        = array( 
                'tiange'            =>$tiange,
                'tiange_sancai'     =>getsancai($tiange),
                'renge'             =>$renge,
                'renge_sancai'      =>getsancai_133($renge),
                'dige'              =>$dige,
                'dige_sancai'       =>getsancai_133($dige),
                'waige'             =>$waige,
                'waige_sancai'      =>getsancai_133($waige),
                'zongge'            =>$zongge,
                'zongge_sancai'     =>getsancai_133($zongge),
                'yuanshen'          =>get_yuanshen($renge),
                'sancai'            =>$n_rssancai);
            $return_data['tdrh_ge_data'] = $tdrh_ge_arr;

            $xmdf=getpf_133($tgjx_data['jx'])/10+getpf_133($rgjx_data['jx'])+getpf_133($dgjx_data['jx'])+getpf_133($zgjx_data['jx'])+getpf_133($wgjx_data['jx'])/10+getpf_133($n_rssancai['jx'])/4+getpf_133($n_rssancai['jx1'])/4+getpf_133($n_rssancai['jx2'])/4+getpf_133($n_rssancai['jx3'])/4;
            if($zongge>60){
                $xmdf=$xmdf-4;
            }
            $xmdf                = 58+$xmdf;
            $return_data['xmdf'] = $xmdf;
            return $return_data;
        } catch (Exception $e) {
            // 异常处理，确保方法始终返回数组
            return $e->getMessage();
        }
    }

    //姓名分析
    public function xmfx($row){
        $sex            = array(0=>'1',1=>'0');
        $get_bzwh_data  = Sm::get_bzwh($row['xing'],$row['name'],$sex[$row['sex']],$row['year'],$row['month'],$row['day'],$row['hour'],$row['min'],$row['cY'],$row['cM'],$row['cD'],$row['cH'],$row['term1'],$row['term2'],$row['start_term'],$row['end_term'],$row['start_term1'],$row['end_term1'],$row['lDate']);
        $info           = Sm::public_sm($get_bzwh_data);
        $xiyongshen     = Sm::xishen($get_bzwh_data);
        $data['data']['xiyongshen'] = $xiyongshen;
        $data['info']   = $info;
        $data['user']   = $get_bzwh_data;
        // 使用类内静态缓存，替代全局变量$sm_cache
        
        // 生肖总运势
        $zodiac = $get_bzwh_data['zodiac'];
        if (isset(self::$smCache['Zodiac_ztys'][$zodiac])) {
            $sxjj = self::$smCache['Zodiac_ztys'][$zodiac];
        } else {
            $sxjj = Db::name('Zodiac_ztys')->where('sx','=',$zodiac)->find();
            self::$smCache['Zodiac_ztys'][$zodiac] = $sxjj;
        }
        $data['data']['zonghe'] = $sxjj;
        
        //穷通宝鉴
        $tg = $get_bzwh_data['bazi'][4];
        $dz = $get_bzwh_data['bazi'][3];
        $qtbj_key = $tg . '_' . $dz;
        if (isset(self::$smCache['Sm_qtbj'][$qtbj_key])) {
            $qtbj = self::$smCache['Sm_qtbj'][$qtbj_key];
        } else {
            $qtbj = Db::name('Sm_qtbj')->where(array(['tg','=',$tg],['dz','=',$dz]))->find();
            self::$smCache['Sm_qtbj'][$qtbj_key] = $qtbj;
        }
        $data['data']['qtbj'] = $qtbj;
        
        //日干心性
        $rgz = $get_bzwh_data['bazi'][4].$get_bzwh_data['bazi'][5];
        if (isset(self::$smCache['Sm_rgnm'][$rgz])) {
            $rgxx = self::$smCache['Sm_rgnm'][$rgz];
        } else {
            $rgxx = Db::name('Sm_rgnm')->where(array(['rgz','=',$rgz]))->find();
            self::$smCache['Sm_rgnm'][$rgz] = $rgxx;
        }
        $data['data']['rgxx'] = $rgxx;
        
        //三命通会
        $ri = $get_bzwh_data['bazi'][4].$get_bzwh_data['bazi'][5];
        $shi = $get_bzwh_data['bazi'][6].$get_bzwh_data['bazi'][7];
        $sxth_key = $ri . '_' . $shi;
        if (isset(self::$smCache['Sm_smtf'][$sxth_key])) {
            $sxth = self::$smCache['Sm_smtf'][$sxth_key];
        } else {
            $sxth = Db::name('Sm_smtf')->where(array(['ri','=',$ri],['shi','=',$shi]))->find();
            self::$smCache['Sm_smtf'][$sxth_key] = $sxth;
        }
        $data['data']['sxth'] = $sxth;
        
        //月日时命理
        $ml = array();
        $yml_key = $get_bzwh_data['jiuli']['m'] . "月";
        if (isset(self::$smCache['Sm_rysmn'][$yml_key])) {
            $ml['yml'] = self::$smCache['Sm_rysmn'][$yml_key];
        } else {
            $ml['yml'] = Db::name('Sm_rysmn')->where(array(['siceng','=',$yml_key]))->find();
            self::$smCache['Sm_rysmn'][$yml_key] = $ml['yml'];
        }
        
        $rml_key = $get_bzwh_data['jiuli']['d'] . "日";
        if (isset(self::$smCache['Sm_rysmn'][$rml_key])) {
            $ml['rml'] = self::$smCache['Sm_rysmn'][$rml_key];
        } else {
            $ml['rml'] = Db::name('Sm_rysmn')->where(array(['siceng','=',$rml_key]))->find();
            self::$smCache['Sm_rysmn'][$rml_key] = $ml['rml'];
        }
        
        $sml_key = $get_bzwh_data['jiuli']['h'] . "时";
        if (isset(self::$smCache['Sm_rysmn'][$sml_key])) {
            $ml['sml'] = self::$smCache['Sm_rysmn'][$sml_key];
        } else {
            $ml['sml'] = Db::name('Sm_rysmn')->where(array(['siceng','=',$sml_key]))->find();
            self::$smCache['Sm_rysmn'][$sml_key] = $ml['sml'];
        }
        $data['data']['ml'] = $ml;
        $xmfx = Sm::nameXf($row['xing'],$row['name'],$row['sex']);
        $data['xmfx']=$xmfx;
        return $data;
    }

    //八字自测  复用率非常高
    public function bazizh($row){    
        $sex     = array(0=>'1',1=>'0',2=>'2');
        $xing    = $row['xing'];
        $ming    = $row['name'];
        if($xing == $ming){
            $xm_name = splitName($row['name']);
            $xing    = $xm_name[0];
            $ming    = $xm_name[1];
            if($xm_name[1] == ''){
                $ming = $xing;
            }
        }
        $get_bzwh_data  = Sm::get_bzwh($xing,$ming,$sex[$row['sex']],$row['year'],$row['month'],$row['day'],$row['hour'],$row['min'],$row['cY'],$row['cM'],$row['cD'],$row['cH'],$row['term1'],$row['term2'],$row['start_term'],$row['end_term'],$row['start_term1'],$row['end_term1'],$row['lDate']);
        $info           = Sm::public_sm($get_bzwh_data);
        $xiyongshen     = Sm::xishen($get_bzwh_data);
        $data['data']['xiyongshen'] = $xiyongshen;
        $data['info']               = $info;
        $data['user']               = $get_bzwh_data;
        $sx_data                    = array('鼠','牛','虎','兔','龙','蛇','马','羊','猴','鸡','狗','猪');//生肖数据
        $arrkey                     = array_search($get_bzwh_data['zodiac'],$sx_data);
        $arrkey                     = $arrkey+48;
        // 使用类内静态缓存，替代全局变量$sm_cache
        
        // 生肖总运势
        $zodiac = $get_bzwh_data['zodiac'];
        if (isset(self::$smCache['Zodiac_ztys'][$zodiac])) {
            $sxjj = self::$smCache['Zodiac_ztys'][$zodiac];
        } else {
            $sxjj = Db::name('Zodiac_ztys')->where('sx','=',$zodiac)->find();
            self::$smCache['Zodiac_ztys'][$zodiac] = $sxjj;
        }
        $data['data']['zonghe'] = $sxjj;
        
        //四季用神参考
        $dayLandData   = new cls_dayLandData();
        $wh = $get_bzwh_data['wh'][4];
        $sj = $dayLandData->siji($get_bzwh_data["age"]["m"]);
        $sjrs_key = $wh . '_' . $sj;
        if (isset(self::$smCache['Sm_sjrs'][$sjrs_key])) {
            $sjrs = self::$smCache['Sm_sjrs'][$sjrs_key];
        } else {
            $sjrs = Db::name('Sm_sjrs')->where(array(['wh','=',$wh],['sj','=',$sj]))->find();
            self::$smCache['Sm_sjrs'][$sjrs_key] = $sjrs;
        }
        $data['data']['sjrs'] = $sjrs;
        
        //穷通宝鉴
        $tg = $get_bzwh_data['bazi'][4];
        $dz = $get_bzwh_data['bazi'][3];
        $qtbj_key = $tg . '_' . $dz;
        if (isset(self::$smCache['Sm_qtbj'][$qtbj_key])) {
            $qtbj = self::$smCache['Sm_qtbj'][$qtbj_key];
        } else {
            $qtbj = Db::name('Sm_qtbj')->where(array(['tg','=',$tg],['dz','=',$dz]))->find();
            self::$smCache['Sm_qtbj'][$qtbj_key] = $qtbj;
        }
        $data['data']['qtbj'] = $qtbj;
        
        //日干心性
        $rgz = $get_bzwh_data['bazi'][4].$get_bzwh_data['bazi'][5];
        if (isset(self::$smCache['Sm_rgnm'][$rgz])) {
            $rgxx = self::$smCache['Sm_rgnm'][$rgz];
        } else {
            $rgxx = Db::name('Sm_rgnm')->where(array(['rgz','=',$rgz]))->find();
            self::$smCache['Sm_rgnm'][$rgz] = $rgxx;
        }
        $data['data']['rgxx'] = $rgxx;
        
        //三命通会
        $ri = $get_bzwh_data['bazi'][4].$get_bzwh_data['bazi'][5];
        $shi = $get_bzwh_data['bazi'][6].$get_bzwh_data['bazi'][7];
        $sxth_key = $ri . '_' . $shi;
        if (isset(self::$smCache['Sm_smtf'][$sxth_key])) {
            $sxth = self::$smCache['Sm_smtf'][$sxth_key];
        } else {
            $sxth = Db::name('Sm_smtf')->where(array(['ri','=',$ri],['shi','=',$shi]))->find();
            self::$smCache['Sm_smtf'][$sxth_key] = $sxth;
        }
        $data['data']['sxth'] = $sxth;
        
        //月日时命理
        $ml = array();
        $yml_key = $get_bzwh_data['jiuli']['m'] . "月";
        if (isset(self::$smCache['Sm_rysmn'][$yml_key])) {
            $ml['yml'] = self::$smCache['Sm_rysmn'][$yml_key];
        } else {
            $ml['yml'] = Db::name('Sm_rysmn')->where(array(['siceng','=',$yml_key]))->find();
            self::$smCache['Sm_rysmn'][$yml_key] = $ml['yml'];
        }
        
        $rml_key = $get_bzwh_data['jiuli']['d'] . "日";
        if (isset(self::$smCache['Sm_rysmn'][$rml_key])) {
            $ml['rml'] = self::$smCache['Sm_rysmn'][$rml_key];
        } else {
            $ml['rml'] = Db::name('Sm_rysmn')->where(array(['siceng','=',$rml_key]))->find();
            self::$smCache['Sm_rysmn'][$rml_key] = $ml['rml'];
        }
        
        $sml_key = $get_bzwh_data['jiuli']['h'] . "时";
        if (isset(self::$smCache['Sm_rysmn'][$sml_key])) {
            $ml['sml'] = self::$smCache['Sm_rysmn'][$sml_key];
        } else {
            $ml['sml'] = Db::name('Sm_rysmn')->where(array(['siceng','=',$sml_key]))->find();
            self::$smCache['Sm_rysmn'][$sml_key] = $ml['sml'];
        }
        $data['data']['ml'] = $ml;
        
        //获取生肖每月运势
        if (isset(self::$smCache['Zodiac_m'][$zodiac])) {
            $zodiac_ys_list = self::$smCache['Zodiac_m'][$zodiac];
        } else {
            $zodiac_ys_list = Db::name('Zodiac_m')->where(array(['sx','=',$zodiac]))->select();
            self::$smCache['Zodiac_m'][$zodiac] = $zodiac_ys_list;
        }
        $data['zodiac_ys_list'] = $zodiac_ys_list;
        return $data;
    }

    //八字排盘
    //真太阳时就设置为1
    public function bazipp($row){
        $sex    = array(0=>'1',1=>'0',2=>'2');
        $sex    = $sex[$row['sex']];
        $yezis  = 0;
        $nian1  = $row['year'];
        $yue1   = $row['month'];
        $ri1    = $row['day'];
        $hour1  = $row['hour'];
        $minues = $row['min'];
        $taiyang='';
        $tiangan=array("癸","甲","乙","丙","丁","戊","己","庚","辛","壬");
        //$dizhi  =array("亥","子","丑","寅","卯","辰","巳","午","未","申","酉","戌");
        //$jq     =array("立春","惊蛰" ,"清明"  ,"立夏" ,"芒种","小暑","立秋" ,"白露","寒露" ,"立冬","大雪","小寒");
        $jd1    =120;//经度度
        $jd2    =0;//经度分
        $cls_rowDish = new cls_rowDish();
        $cls_rowDishFunction = new cls_rowDishFunction();
        $notydate   =mktime($hour1,$minues,0,$yue1,$ri1,$nian1);
        if($taiyang==0){
            $bzdate   = $notydate;
            //$bzdatess = date('Y年m月d日',$bzdate);
        }else{
            $bzdate=$cls_rowDish->getTaiyang($notydate,$jd1,$jd2);
            $nian1=date("Y",$bzdate);
            $yue1=date("m",$bzdate);
            $ri1=date("d",$bzdate);
            $hour1=date("H",$bzdate);
            $minues=date("i",$bzdate);
        }
        //$nongarr  = $cls_rowDishFunction->getNongli($nian1,$yue1,$ri1,$hour1);
        //$nongdate = $nongarr[0]."年(生肖".$nongarr[5].")".$nongarr[1]."月".$nongarr[2].$nongarr[3]."时";
        $cgp=1;         //req::item('cgp');

        if($yezis==1 && $hour1>=23){
            $yedundate=mktime($hour1,$minues,0,$yue1,$ri1+1,$nian1);
            $nian1=date("Y",$yedundate);
            $yue1=date("m",$yedundate);
            $ri1=date("d",$yedundate);
        }
        $gzx=$cls_rowDishFunction->getSizhu($nian1,$yue1,$ri1,$hour1,'1');
        $ygx=$gzx[0];
        $yzx=$gzx[1];
        $mgx=$gzx[2];
        $mzx=$gzx[3];
        $rgx=$gzx[4];
        $dgx=$gzx[4];
        $rzx=$gzx[5];
        $hgx=$gzx[6];
        $hzx=$gzx[7];
        $gz=$cls_rowDishFunction->getSizhu($nian1,$yue1,$ri1,$hour1,'0',$minues);
        $yg=$gz[0];$yz=$gz[1];$ygz=$yg.$yz;
        $mg=$gz[2];$mz=$gz[3];$mgz=$mg.$mz;
        $rg=$gz[4];$rz=$gz[5];$rgz=$rg.$rz;
        $hg=$gz[6];$hz=$gz[7];$hgz=$hg.$hz;
        //--》以上生辰八字没有精准到节气
        $flag=0;
        if ($ygx%2==1) $flag=1; //阳年
        if(($sex==1 && $flag==1) or ($sex==0 and $flag==0)){
            $bz_sx=1;
        }else{
            $bz_sx=0;
        }
        $taiyuan=$cls_rowDish->getTaiyuan($mgx,$mzx);       //胎元
        $minggong=$cls_rowDish->getMinggong($mzx,$hzx,$ygx);//命宫
        //藏干go
        if($cgp==1){
            $ycg=$cls_rowDish->getDcang($yzx);
            $ycg1=substr($ycg,0,3);
            $ycg1x=array_search($ycg1,$tiangan);
            $ysshen1="(".$cls_rowDish->getShishen($ycg1x,$rgx).")";
            $ycg2='';
            $ycg2x='';
            $ysshen2='';
            if(substr($ycg,3,3)!=''){
                $ycg2=substr($ycg,3,3);
                $ycg2x=array_search($ycg2,$tiangan);
                $ysshen2="(".$cls_rowDish->getShishen($ycg2x,$rgx).")";
            }
            $ycg3='';
            $ycg3x='';
            $ysshen3='';
            if(substr($ycg,6,3)!=''){

                $ycg3=substr($ycg,6,3);
                $ycg3x=array_search($ycg3,$tiangan);
                $ysshen3="(".$cls_rowDish->getShishen($ycg3x,$rgx).")";
            }
            $mcg=$cls_rowDish->getDcang($mzx);
            $mcg1=substr($mcg,0,3);
            $mcg1x=array_search($mcg1,$tiangan);
            $msshen1="(".$cls_rowDish->getShishen($mcg1x,$rgx).")";
            $mcg2='';
            $mcg2x='';
            $msshen2='';
            if(substr($mcg,3,3)!=''){
                $mcg2=substr($mcg,3,3);
                $mcg2x=array_search($mcg2,$tiangan);
                $msshen2="(".$cls_rowDish->getShishen($mcg2x,$rgx).")";
            }
            $mcg3='';
            $mcg3x='';
            $msshen3='';
            if(substr($mcg,6,3)!=''){
                $mcg3=substr($mcg,6,3);
                $mcg3x=array_search($mcg3,$tiangan);
                $msshen3="(".$cls_rowDish->getShishen($mcg3x,$rgx).")";
            }
            $rcg=$cls_rowDish->getDcang($rzx);
            $rcg1=substr($rcg,0,3);
            $rcg1x=array_search($rcg1,$tiangan);
            $rsshen1="(".$cls_rowDish->getShishen($rcg1x,$rgx).")";
            $rcg2='';
            $rcg2x='';
            $rsshen2='';
            if(substr($rcg,3,3)!=''){
                $rcg2=substr($rcg,3,3);
                $rcg2x=array_search($rcg2,$tiangan);
                $rsshen2="(".$cls_rowDish->getShishen($rcg2x,$rgx).")";
            }
            $rcg3='';
            $rcg3x='';
            $rsshen3='';
            if(substr($rcg,6,3)!=''){
                $rcg3=substr($rcg,6,3);
                $rcg3x=array_search($rcg3,$tiangan);
                $rsshen3="(".$cls_rowDish->getShishen($rcg3x,$rgx).")";
            }
            $hcg=$cls_rowDish->getDcang($hzx);
            $hcg1=substr($hcg,0,3);
            $hcg1x=array_search($hcg1,$tiangan);
            $hsshen1="(".$cls_rowDish->getShishen($hcg1x,$rgx).")";
            $hcg2='';
            $hcg2x='';
            $hsshen2='';
            if(substr($hcg,3,3)!=''){
                $hcg2=substr($hcg,3,3);
                $hcg2x=array_search($hcg2,$tiangan);
                $hsshen2="(".$cls_rowDish->getShishen($hcg2x,$rgx).")";
            }
            $hcg3='';
            $hcg3x='';
            $hsshen3='';
            if(substr($hcg,6,3)!=''){
                $hcg3=substr($hcg,6,3);
                $hcg3x=array_search($hcg3,$tiangan);
                $hsshen3="(".$cls_rowDish->getShishen($hcg3x,$rgx).")";
            }
        }
        //藏干end
        $jyDate = array(
            'hour'  =>$hour1,
            'min'   =>$minues,
            'month' =>$yue1,
            'day'   =>$ri1,
            'year'  =>$nian1,
        );
        $jy = Sm::count_jy_time($bzdate,$bz_sx,$jyDate); //计算起运交运时间
        $ygx=$gzx[0];$yzx=$gzx[1];$mgx=$gzx[2];$mzx=$gzx[3];$rgx=$gzx[4];$rzx=$gzx[5];$hgx=$gzx[6];$hzx=$gzx[7];
        $nayintaiyuan = $cls_rowDish->getNayin($taiyuan);
        $nayinminggong = $cls_rowDish->getNayin($minggong);
        $shishen  = $cls_rowDish->getShishen($ygx,$rgx);
        $shishen .= $cls_rowDish->getShishen($mgx,$rgx);
        $riyuan   = $cls_rowDish->getShishen($hgx,$rgx);
        $shishen1 = $cls_rowDish->getShishen($ygx,$rgx);
        $shishen2 = $cls_rowDish->getShishen($mgx,$rgx);
        $shishen3 = $cls_rowDish->getShishen($rgx,$rgx);  //修正：使用日干作为参数，因为日支的十神计算应该基于日干
        $shishen4 = $cls_rowDish->getShishen($hgx,$rgx);
        $shishen1_row = Db::name('sm_ssjs2')->where('shishen',$shishen1)->where('sanzhu',0)->find();
        $shishenfx_y  = '';
        if(!empty($shishen1_row)){
            $shishenfx_y = $shishen1_row['content'];
        }
        $shishen2_row = Db::name('sm_ssjs2')->where('shishen',$shishen2)->where('sanzhu',1)->find();
        $shishenfx_m  = '';
        if(!empty($shishen2_row)){
            $shishenfx_m = $shishen2_row['content'];
        }
        $shishen3_row = Db::name('sm_ssjs2')->where('shishen',$shishen3)->where('sanzhu',2)->find();
        $shishenfx_d  = '';
        if(!empty($shishen3_row)){
            $shishenfx_d = $shishen3_row['content'];
        }
        $shishen4_row = Db::name('sm_ssjs2')->where('shishen',$shishen4)->where('sanzhu',3)->find();
        $shishenfx_h  = '';
        if(!empty($shishen4_row)){
            $shishenfx_h = $shishen4_row['content'];
        }
        $sizhu = $ygz.'&nbsp;'.$mgz.'&nbsp;'.$rgz.'&nbsp;'.$hgz.'&nbsp;空('.$cls_rowDish->xunkong($rgz).')';
        $zanggan1 = $ycg1.$ycg2.$ycg3;
        $zanggan2 = $mcg1.$mcg2.$mcg3;
        $zanggan3 = $rcg1.$rcg2.$rcg3;
        $zanggan4 = $hcg1.$hcg2.$hcg3;
        $nayin1 = $cls_rowDish->getNayin($ygz);
        $nayin2 = $cls_rowDish->getNayin($mgz);
        $nayin3 = $cls_rowDish->getNayin($rgz);
        $nayin4 = $cls_rowDish->getNayin($hgz);
        $data_Array = array(
            'taiyuan'       =>$taiyuan,
            'nayintaiyuan'  =>$nayintaiyuan,
            'minggong'      =>$minggong,
            'zysj'          =>$jy['zysj'],
            'sub_zysj'      =>substr($jy['zysj'],3,1),
            'shishen'       =>$shishen,
            'riyuan'        =>$riyuan,
            'sizhu'         =>$sizhu,
            'jqstr'         =>$jy['jqstr'],
            'qysj'          =>$jy['qysj'],
            'shishen1'      =>$shishen1,
            'shishen2'      =>$shishen2,
            'shishen3'      =>$shishen3,
            'shishen4'      =>$shishen4,
            'shishenfx_y'   =>$shishenfx_y,
            'shishenfx_m'   =>$shishenfx_m,
            'shishenfx_d'   =>$shishenfx_d,
            'shishenfx_h'   =>$shishenfx_h,
            'ygz'           =>$ygz,
            'mgz'           =>$mgz,
            'rgz'           =>$rgz,
            'hgz'           =>$hgz,
            'xkrgz'         =>$cls_rowDish->xunkong($rgz),
            'nayinminggong' =>$nayinminggong,
            'zanggan1'      =>$zanggan1,
            'zanggan2'      =>$zanggan2,
            'zanggan3'      =>$zanggan3,
            'zanggan4'      =>$zanggan4,
            'nayin1'        =>$nayin1,
            'nayin2'        =>$nayin2,
            'nayin3'        =>$nayin3,
            'nayin4'        =>$nayin4,
            'ysshen1'       =>$ysshen1,
            'ysshen2'       =>$ysshen2,
            'ysshen3'       =>$ysshen3,
            'msshen1'       =>$msshen1,
            'msshen2'       =>$msshen2,
            'msshen3'       =>$msshen3,
            'rsshen1'       =>$rsshen1,
            'rsshen2'       =>$rsshen2,
            'rsshen3'       =>$rsshen3,
            'hsshen1'       =>$hsshen1,
            'hsshen2'       =>$hsshen2,
            'hsshen3'       =>$hsshen3,
        );
        return $data_Array;
    }

     //计算起运交运时间go
    public function count_jy_time($bzdate,$bz_sx,$jyDate){
        $jq         = array("立春","惊蛰" ,"清明"  ,"立夏" ,"芒种","小暑","立秋" ,"白露","寒露" ,"立冬","大雪","小寒");
        // $path       = str_replace('index\model','',__DIR__);
        // $file_dir   = $path."/common/library/sm/paipan/jq.txt";
        // $fp         = fopen($file_dir,"r");
		// $content    = fread($fp,filesize($file_dir));
		// fclose($fp);
		// $arrdate=explode("\n",$content);
        $SolarTerms     = new SolarTerm();
        $St        = $SolarTerms->solarTerms(date('Y',$bzdate),'Asia/Shanghai');
        $st_data = [];
        foreach ($St as $key => $value) {
            $st_data[] = $value['d']->format('Y-m-d H:i');
        }
        $arrdate = [];
        foreach ($st_data as $key => $value) {
            if($key%2!=0){
                $arrdate[] = $value;
            }
        }
		$i=0;
        $cls_rowDishFunction = new cls_rowDishFunction();
		while(strtotime($arrdate[$i])<=$bzdate){
			if($bz_sx==1){
				$jqstr=$arrdate[$i+1];
				$qyjs =$cls_rowDishFunction->Datecha($bzdate,strtotime($arrdate[$i+1]),"i");
			}else{
				$jqstr=$arrdate[$i];
				$qyjs  =$cls_rowDishFunction->Datecha($bzdate,strtotime($arrdate[$i]),"i");
			}
		    $i++;
		}
        
        $jqstr.=$jq[(int)(substr($jqstr,5,2)+10)%12];
		$qyjs=$qyjs/60/24;
		$qyjs1=floor($qyjs/3);		    //'整天/3=要加的起运年数	8.126388889
		$qyjs9=$qyjs-$qyjs1*3;		    //'余天		0.379166667
		$qyjs2=floor($qyjs9*4);		    //'余天*4=月	1.516666666
		$qyjs9=($qyjs9-$qyjs2/4)*24;	//'余小时		3.099999998
		$qyjs3=floor($qyjs9*5);		    //'余小时*5=天	15.49999999
		$qyjs4=floor(($qyjs9*5-$qyjs3)*24);	//'余小时
		if ($qyjs1!=0){
			$qysj =$qyjs1."年 ".$qyjs2."月 ".$qyjs3."天 ".$qyjs4."小时";
		}else{
			$qysj = $qyjs2."月 ".$qyjs3."天 ".$qyjs4."小时";
		}
		$zydate= mktime($jyDate['hour']+$qyjs4,$jyDate['min'],0,intval($jyDate['month'])+$qyjs2,intval($jyDate['day'])+$qyjs3,$jyDate['year']+$qyjs1);
		$zysj  = date("Y年m月d日H时i分",$zydate); 
        return array(
            'qysj'  =>$qysj,
            'zysj'  =>$zysj,
            'jqstr' =>$jqstr,
        );
    }
     //起运交运时间end


    public function xishen($row){
        try {
            // 参数验证
            if (empty($row) || !isset($row['bazi']) || !isset($row['bazi']['4']) || !isset($row['bazi']['3'])) {
                return array('xishen' => '', 'shiye' => '', 'sanhe' => '');
            }
            
            $bazihe     = $row['bazi']['4'].$row['bazi']['3'];
            $datahe     ='';                                                                                          
            $jin_where  = array('甲子','甲寅','甲卯','甲辰','乙寅','乙卯','乙辰','乙亥','丙辰','丙寅','丁卯','戊寅','己卯','庚子','庚寅','庚卯','庚辰','庚亥','辛子','辛寅','辛卯','辛辰','壬卯','癸寅');//24
            $mu_where   = array('甲午','甲酉','甲戌','乙巳','乙申','丙卯','丙巳','丙午','丙未','丁寅','丁巳','丁午','丁未','戊辰','戊巳','己午','庚巳','辛午','辛未','壬寅','壬辰','壬巳','壬午','壬未','癸辰','癸巳','癸午');//27
            $shui_where = array('甲丑','甲巳','甲未','甲亥','乙丑','乙午','乙未','乙戌','丁戌','丁亥','戊午','戊未','己辰','己巳','己未','己戌','庚未','辛戌','壬戌','癸卯','癸未'); //21
            $huo_where  = array('甲申','乙酉','丙子','丙丑','丙申','丙酉','丙戌','丁丑','丁辰','丁申','丁酉','戊丑','戊酉','戊戌','戊亥','己丑','己寅','己申','己亥','庚丑','庚申','庚酉','庚戌','辛丑','辛申','辛酉','壬申','癸酉','癸戌'); //28
            $tu_where   = array('乙子','丙亥','丁子','戊子','戊卯','戊申','己子','己酉','庚午','辛巳','辛亥','壬子','壬丑','壬酉','壬亥','癸子','癸丑','癸申','癸亥'); //19
            if(in_array($bazihe,$jin_where)){
                $datahe = '金';
            }elseif(in_array($bazihe,$mu_where)){
                $datahe = '木';
            }elseif(in_array($bazihe,$shui_where)){
                $datahe = '水';
            }elseif(in_array($bazihe,$huo_where)){
                $datahe = '火';
            }elseif(in_array($bazihe,$tu_where)){
                $datahe = '土';
            }
            $data=[];
            $data['xishen']=$datahe;
            $data['shiye'] = Sm::xishensy($data['xishen']);
            $data['sanhe'] = Sm::sanhe($row['zodiac']);
            return $data;
        } catch (Exception $e) {
            // 异常处理，确保方法始终返回完整数组
            return array('xishen' => '', 'shiye' => '', 'sanhe' => '');
        }
    }

    //需要存入数据库
    public function xishensy($xishen){
        try {
            // 参数验证
            if (empty($xishen)) {
                return '参数错误';
            }
            
            // 使用类内静态缓存，替代全局变量$sm_cache
            
            // 检查缓存中是否存在数据
            if (isset(self::$smCache['Sm_xishensy'][$xishen])) {
                return self::$smCache['Sm_xishensy'][$xishen];
            }
            
            $find_data = Db::name('sm_xishensy')->where('xishen', '=', $xishen)->find();
            $result = isset($find_data['sy']) ? $find_data['sy'] : '';
            
            // 将数据存入缓存
            self::$smCache['Sm_xishensy'][$xishen] = $result;
            
            return $result;
        } catch (Exception $e) {
            // 异常处理，确保方法始终返回字符串
            return $e->getMessage();
        }
    }

    public function sanhe($sx){
        if($sx=='鼠'){
            $sanhe=array('鼠','猴','龙');
            $sanhetu=array('shu','hou','long');
        }elseif($sx=='牛'){
            $sanhe=array('牛','蛇','鸡');
            $sanhetu=array('niu','she','ji');
        }elseif($sx=='虎'){
            $sanhe=array('虎','马','狗');
            $sanhetu=array('hu','ma','gou');
        }elseif($sx=='猪'){
            $sanhe=array('猪','兔','羊');
            $sanhetu=array('猪','tu','yang');
        }elseif($sx=='兔'){
            $sanhe=array('兔','猪','羊');
            $sanhetu=array('tu','zhu','yang');
        }elseif($sx=='狗'){
            $sanhe=array('狗','虎','马');
            $sanhetu=array('gou','hu','ma');
        }elseif($sx=='龙'){
            $sanhe=array('龙','猴','鼠');
            $sanhetu=array('long','hou','shu');
        }elseif($sx=='鸡'){
            $sanhe=array('鸡','蛇','牛');
            $sanhetu=array('ji','she','niu');
        }elseif($sx=='蛇'){
            $sanhe=array('蛇','鸡','牛');
            $sanhetu=array('she','ji','niu');
        }elseif($sx=='猴'){
            $sanhe=array('猴','鼠','龙');
            $sanhetu=array('hou','shu','long');
        }elseif($sx=='马'){
            $sanhe=array('马','狗','虎');
            $sanhetu=array('ma','gou','hu');
        }elseif($sx=='羊'){
            $sanhe=array('羊','猪','兔');
            $sanhetu=array('yang','zhu','tu');
        }
        $data['sanhe']   = $sanhe;
        $data['sanhetu'] = $sanhetu;
        return $data;
    }

    /***
	 *获取五行八字
	 所有八字五行都在里面
	 */
    public function get_bzwh($xing,$ming,$sex,$y,$m,$d,$h,$i,$cY,$cM,$cD,$cH,$term1,$term2,$start_term,$end_term,$start_term1,$end_term1,$lDate){
        $gz =  Sm::getSizhu($y,$m,$d,$h,'0',$i);
        $yg=$gz[0];$yz=$gz[1];$b1=$yg.$yz;
        $mg=$gz[2];$mz=$gz[3];$b2=$mg.$mz;
        $rg=$gz[4];$rz=$gz[5];$b3=$rg.$rz;
        $hg=$gz[6];$hz=$gz[7];$b4=$hg.$hz;
        $bazi   =$b1.$b2.$b3.$b4;

        $dayLandData   = new cls_dayLandData();
        $hold          = substr($dayLandData->GetHour($y,$m,$d,$h),3,3);//计算时辰
        $siji          = $dayLandData->siji($m);  //计算季节
        //八字
        $bazi1=substr($bazi,0,3);  //年天干
        $bazi2=substr($bazi,3,3);  //年地支
        $bazi3=substr($bazi,6,3);  //月T
        $bazi4=substr($bazi,9,3);  //月D
        $bazi5=substr($bazi,12,3); //日T
        $bazi6=substr($bazi,15,3); //日D
        $bazi7=substr($bazi,18,3); //时T
        $bazi8=substr($bazi,21,3); //时D
        //五行
        $wh1=$dayLandData->tgdzwh($bazi1);
        $wh2=$dayLandData->tgdzwh($bazi2);
        $wh3=$dayLandData->tgdzwh($bazi3);
        $wh4=$dayLandData->tgdzwh($bazi4);
        $wh5=$dayLandData->tgdzwh($bazi5);
        $wh6=$dayLandData->tgdzwh($bazi6);
        $wh7=$dayLandData->tgdzwh($bazi7);
        $wh8=$dayLandData->tgdzwh($bazi8);
        $ancientCalendarData = new cls_ancientCalendarData(); //农历计算
        $month = $ancientCalendarData->convertSolarToLunar($y,$m,$d);
        // $darray=array("初一","初二","初三","初四","初五","初六","初七","初八","初九","初十","十一","十二","十三","十四","十五","十六","十七","十八","十九","二十","廿一","廿二","廿三","廿四","廿五","廿六","廿七","廿八","廿九","三十");
        // $marray=array('正月','二月','三月','四月','五月','六月','七月','八月','九月','十月','冬月','腊月');
        // $ndarray=array('1','2','3','4','5','6','7','8','9','10','11','12','13','14','15','16','17','18','19','20','21','22','23','24','25','26','27','28','29','30');
        // $nmarray=array('1','2','3','4','5','6','7','8','9','10','11','12');
        //$date = strtotime($month[0].str_replace($marray,$nmarray,$month[1]).str_replace($darray,$ndarray,$month[2]));
        $lunar = new cls_lunarData();
        $sx    = $lunar->get_animal($month[0]);//生肖
        $ayear = explode('年',$lDate);
        $ayue  = explode('月',$ayear[1]);
        $ari   = explode('日',$ayue[1]);
        $nownian =date('Y',time());
        $nianling=$nownian-$y;
        $sex==0?$sex='男':$sex='女';
        $data = array(
            'xm_name'    =>array('xing'=>$xing,'ming'=>$ming),
            'sex'        =>$sex,
            'age'        =>array('y'=>$y,'m'=>$m,'d'=>$d,'h'=>$h,'i'=>$i,'nl'=>$nianling),
            'zodiac'     =>$sx,
            'season'     =>$siji,
            'jieqi'      =>array('term1'=>$term1,'term2'=>$term2),
            'jiuli'      =>array('y'=>$ayear[0],'m'=>$ayue[0],'d'=>$ari[0],'h'=>$hold),
            'bazi'       =>array($bazi1,$bazi2,$bazi3,$bazi4,$bazi5,$bazi6,$bazi7,$bazi8),
            'wh'         =>array($wh1,$wh2,$wh3,$wh4,$wh5,$wh6,$wh7,$wh8),
            'cY'         =>$cY,
            'cM'         =>$cM,
            'cH'         =>$cH,
            'cD'         =>$cD,
            'lDate'      =>$lDate,
            'start_term' =>$start_term,
            'end_term'   =>$end_term,
            'start_term1'=>$start_term1,
            'end_term1'  =>$end_term1,
        );
        return $data;
        
    }
    /**
     * 获取八字干支（四柱）
     * 计算年、月、日、时的天干地支
     * 
     * @param int $bzyear 年份
     * @param int $bzmonth 月份
     * @param int $bzday 日
     * @param int $bzhour 小时
     * @param int|bool $ifx 返回格式标识，0返回天干地支汉字，非0返回数字索引
     * @return array 八字干支数组
     */
    public function getSizhu($bzyear,$bzmonth,$bzday,$bzhour,$ifx=false,$min=0){
		$tiangan        =array("癸","甲","乙","丙","丁","戊","己","庚","辛","壬");
    	$dizhi          =array("亥","子","丑","寅","卯","辰","巳","午","未","申","酉","戌");
    	$bzmonth_data   =$bzmonth;
        $almanacData   = new cls_almanacData();
    	if($ifx==0 || $ifx==false){
            $jjsj   = $almanacData->makejq($bzyear,$bzmonth,$bzday,$bzhour,0,2);
            $jq_data = explode('-',$jjsj);;
            if($jjsj!='' && !empty($jq_data)){
                if($bzyear==1992 && $bzmonth==4){
                    $bzmonth_data = $bzmonth;
                }else{
                    $bzmonth_data = $jq_data[1];
                }
            }
        }
        $md=$bzmonth * 100 +$bzday;
        if($md>=204 && $md<=1231){
            $yg = ($bzyear - 3) % 10;
            $yz = ($bzyear - 3) % 12;
        }
        if($md>=101 && $md<=203 ){
            $yg = ($bzyear - 4) % 10;
            $yz = ($bzyear - 4) % 12;
        }
        $yg1=$tiangan[$yg];
        $yz1=$dizhi[$yz];
        $mz =getMzQyjs($bzmonth_data,$bzday,1);
        if( $mz > 2 && $mz <= 11) {
            $mg = ($yg * 2 + $mz + 8) % 10;
        }else{
            $mg = ($yg * 2 + $mz) % 10;
        }
        $mg1=$tiangan[$mg];
        $mz1=$dizhi[$mz];
        //从公元0年到目前年份的天数 yearlast
        $yearlast = ($bzyear - 1) * 5 + floor(($bzyear - 1) / 4)- floor(($bzyear - 1) / 100) + floor(($bzyear - 1) / 400);
        //计算某月某日与当年1月0日的时间差（以日为单位）yearday
        $yearday=0;
        for ($i = 1 ;$i<= $bzmonth - 1;$i++){
            switch($i){
                case 1: 
                $yearday += 31;
                break;
                case 3:
                $yearday += 31;
                break;
                case 5:
                $yearday += 31;
                break;
                case 7:
                $yearday += 31;
                break;
                case 8:
                $yearday += 31;
                break;
                case 10:
                $yearday += 31;
                break;
                case 12:
                $yearday += 31;
                break;
                case 4: 
                $yearday+=  30;
                break;
                case  6:
                $yearday +=  30;
                break;
                case 9:
                $yearday +=  30;
                break;
                case 11:
                $yearday +=  30;
                break;
                case 2:
                if($bzyear % 4 == 0 && $bzyear % 100 <> 0 || $bzyear % 400 == 0 ){
                    $yearday += 29;
                    break;
                }else{
                    $yearday +=  28;
                    break;
                }
            }
        }
        $yearday = $yearday + $bzday;
        //计算日的六十甲子数 day60
        $day60 = ($yearlast + $yearday + 6015) % 60;
        
        // 计算总分钟数，用于精确判断日期和时辰
        $total_minutes = $bzhour * 60 + $min;
        $adjusted_day60 = $day60;
        
        // 23:00-23:59 属于下一天的子时，需要调整日干支
        if($total_minutes >= 23*60){
            $adjusted_day60 = ($day60 + 1) % 60;
            $dg2 = $adjusted_day60 % 10;
        }else{
            $dg2 = $day60 % 10;
        }
        
        //确定 日干 dg  日支  dz
        $dg = $adjusted_day60 % 10;
        $dz = $adjusted_day60 % 12;
        $dg1=$tiangan[$dg];
        $dz1=$dizhi[$dz];
        // 考虑分钟数计算时干支，提高准确性
        // 计算总分钟数，用于精确判断时辰
        $total_minutes = $bzhour * 60 + $min;
        // 每个时辰2小时，从23:00开始
        if ($total_minutes >= 23*60) {
            // 23:00-23:59 属于下一天的子时
            $tz = 1 % 12;
        } else {
            // 00:00-22:59 正常计算时辰
            $tz = floor(($total_minutes + 60) / 120) % 12;
        }
        
        // 计算时干
        if($tz == 0){
            $tg = ($dg2 * 2 + $tz) % 10;
        }else{
            $tg = ($dg2 * 2 + $tz + 8) % 10;
        }
        $tg1=$tiangan[$tg];
        $tz1=$dizhi[$tz];
        if($ifx==0){
            return array($yg1,$yz1,$mg1,$mz1,$dg1,$dz1,$tg1,$tz1);
            //年干 年支 月干 月支 日干 日支 时干 时支  八字
        }else{
            return array($yg,$yz,$mg,$mz,$dg,$dz,$tg,$tz,getDateNs($yz),getDateNs($mz),getDateNs($dz),getDateNs($tz));
            //年干 年支 月干 月支 日干 日支 时干 时支  年纳支 月纳支 日纳支 时纳支
        }
    }

   
    //公共算命方法 复用率非常高
    public static function public_sm($get_data){
        // 使用类内静态缓存，替代全局变量$sm_cache
        
        $jmsh['jin']    = howstring($get_data['wh'],'金');
        $jmsh['mu']     = howstring($get_data['wh'],'木');
        $jmsh['shui']   = howstring($get_data['wh'],'水');
        $jmsh['huo']    = howstring($get_data['wh'],'火');
        $jmsh['tu']     = howstring($get_data['wh'],'土');
        $wharr = whwq_ff($jmsh['jin'],$jmsh['mu'],$jmsh['shui'],$jmsh['huo'],$jmsh['tu']);
        
        //同类异类五行
        $wh = $get_data['wh'][4];
        if (isset(self::$smCache['Sm_wh'][$wh])) {
            $tywh = self::$smCache['Sm_wh'][$wh];
        } else {
            $tywh = Db::name('Sm_wh')->where('wh','=',$wh)->find();
            self::$smCache['Sm_wh'][$wh] = $tywh;
        }
        
        //纳音
        $nayin = array();
        for ($i = 0; $i < 4; $i++) {
            $jiazi = $get_data['bazi'][$i*2].$get_data['bazi'][$i*2+1];
            if (isset(self::$smCache['Sm_jiazi'][$jiazi])) {
                $jiazi_data = self::$smCache['Sm_jiazi'][$jiazi];
            } else {
                $jiazi_data = Db::name('Sm_jiazi')->where('jiazi','=',$jiazi)->find();
                self::$smCache['Sm_jiazi'][$jiazi] = $jiazi_data;
            }
            $jiazi_data['whsx'] = jiazi_to_wh_sx($jiazi);
            $nayin[] = $jiazi_data;
        }
        
        //生肖个性
        $zodiac = $get_data['zodiac'];
        if (isset(self::$smCache['Sm_sxgx'][$zodiac])) {
            $sxgx = self::$smCache['Sm_sxgx'][$zodiac];
        } else {
            $sxgx = Db::name('Sm_sxgx')->where('sx','=',$zodiac)->find();
            self::$smCache['Sm_sxgx'][$zodiac] = $sxgx;
        }
        
        //节气
        //$solarTerm =array("小寒","大寒 ","立春","雨水 ","惊蛰","春分 ","清明","谷雨 ","立夏","小满 ","芒种","夏至 ","小暑","大暑 ","立秋","处暑 ","白露","秋分 ","寒露","霜降 ","立冬","小雪 ","大雪","冬至 ");
        $info['jmsh'] = $jmsh;
        $info['wharr'] = $wharr;
        $info['tywh'] = $tywh;
        $info['nayin'] = $nayin;
        $info['sxgx'] = $sxgx;
        return $info;
    }

    //合婚的方法
    public function baziMarriage($row){
        $b = array(30=>'猪',31=>'鼠',32=>'牛',33=>'虎',34=>'兔',35=>'龙',36=>'蛇',37=>'马',38=>'羊',39=>'猴',40=>'鸡',41=>'狗'); //生肖
        $c = array(30=>'水',31=>'水',32=>'水',33=>'木',34=>'木',35=>'木',36=>'火',37=>'火',38=>'火',39=>'金',40=>'金',41=>'金'); //五行
        $d = array(20=>'e',21=>'a',22=>'a',23=>'b',24=>'b',25=>'c',26=>'c',27=>'d',28=>'d',29=>'e');
        $da= array(20=>0,21=>1,22=>0,23=>1,24=>0,25=>1,26=>0,27=>1,28=>0,29=>1); #天干
        $e = array(20=>'火',21=>'土',22=>'金',23=>'水',24=>'木',25=>'火',26=>'土',27=>'金',28=>'水',29=>'木');#天干合化
        $a = array(
            0=>'正印',1=>'比肩',2=>'劫财',3=>'食神',4=>'伤官',5=>'偏财',6=>'正财',7=>'七杀',8=>'正官',9=>'偏印',              #十神名称
            20=>'癸',21=>'甲',22=>'乙',23=>'丙',24=>'丁',25=>'戊',26=>'己',27=>'庚',28=>'辛',29=>'壬',                      #10天干
            30=>'亥',31=>'子',32=>'丑',33=>'寅',34=>'卯',35=>'辰',36=>'巳',37=>'午',38=>'未',39=>'申',40=>'酉',41=>'戌');   #12地支
        $cls_wedlockData = new cls_wedlockData();
        #男命
        $m_username = htmlspecialchars($cls_wedlockData->safew($row['m_username'])); #男姓名
        $m_year     = htmlspecialchars($cls_wedlockData->safew($row["m_year"]));     #年干
		$m_month    = htmlspecialchars($cls_wedlockData->safew($row["m_month"]));    #月份
		$m_day      = htmlspecialchars($cls_wedlockData->safew($row["m_day"]));      #日
		$m_hour     = htmlspecialchars($cls_wedlockData->safew($row["m_hour"]));     #时

        #1先算男命
        $yearday='';
        $m_dateData = Sm::getSizhu($m_year,$m_month,$m_day,$m_hour,1);
        $m_yg = $m_dateData[0]; $m_yz = $m_dateData[1]; $m_ynz=$m_dateData[8];
        $m_mg = $m_dateData[2]; $m_mz = $m_dateData[3]; $m_mnz=$m_dateData[9];
        $m_dg = $m_dateData[4]; $m_dz = $m_dateData[5]; $m_dnz=$m_dateData[10];
        $m_hg = $m_dateData[6]; $m_hz = $m_dateData[7]; $m_hnz=$m_dateData[11];
        #确定各支对应的十神
		$m_ygs = (($m_yg + 11 - $m_dg) + (($m_dg + 1) % 2) * (($m_yg + 10 - $m_dg) % 2) * 2) % 10;#年干十神
		$m_mgs = (($m_mg + 11 - $m_dg) + (($m_dg + 1) % 2) * (($m_mg + 10 - $m_dg) % 2) * 2) % 10;#月干十神 
		$m_hgs = (($m_hg + 11 - $m_dg) + (($m_dg + 1) % 2) * (($m_hg + 10 - $m_dg) % 2) * 2) % 10;#时干十神 
		$m_yzs = (($m_ynz + 11 - $m_dg) + (($m_dg + 1) % 2) * (($m_ynz + 10 - $m_dg) % 2) * 2) % 10;#年支十神 
		$m_mzs = (($m_mnz + 11 - $m_dg) + (($m_dg + 1) % 2) * (($m_mnz + 10 - $m_dg) % 2) * 2) % 10;#月支十神 
		$m_dzs = (($m_dnz + 11 - $m_dg) + (($m_dg + 1) % 2) * (($m_dnz + 10 - $m_dg) % 2) * 2) % 10;#日支十神 
		$m_hzs = (($m_hnz + 11 - $m_dg) + (($m_dg + 1) % 2) * (($m_hnz + 10 - $m_dg) % 2) * 2) % 10;#时支十神
		#到此，完成年月日时，各干支十神的确定

        #女命
        $g_username = htmlspecialchars($cls_wedlockData->safew($row['g_username'])); #女姓名
        $g_year     = htmlspecialchars($cls_wedlockData->safew($row["g_year"]));     #年干
		$g_month    = htmlspecialchars($cls_wedlockData->safew($row["g_month"]));    #月份
		$g_day      = htmlspecialchars($cls_wedlockData->safew($row["g_day"]));      #日
		$g_hour     = htmlspecialchars($cls_wedlockData->safew($row["g_hour"]));     #时
        $g_dateData = Sm::getSizhu($g_year,$g_month,$g_day,$g_hour,1);
        $g_qyjs     = getMzQyjs($g_month,$g_day,0);
        $g_yg = $g_dateData[0]; $g_yz = $g_dateData[1]; $g_ynz=$g_dateData[8];
        $g_mg = $g_dateData[2]; $g_mz = $g_dateData[3]; $g_mnz=$g_dateData[9];
        $g_dg = $g_dateData[4]; $g_dz = $g_dateData[5]; $g_dnz=$g_dateData[10];
        $g_hg = $g_dateData[6]; $g_hz = $g_dateData[7]; $g_hnz=$g_dateData[11];
        #确定各支对应的十神
		$g_ygs = (($g_yg + 11 - $g_dg) + (($g_dg + 1) % 2) * (($g_yg + 10 - $g_dg) % 2) * 2) % 10; #年干十神
		$g_mgs = (($g_mg + 11 - $g_dg) + (($g_dg + 1) % 2) * (($g_mg + 10 - $g_dg) % 2) * 2) % 10; #月干十神 
		$g_hgs = (($g_hg + 11 - $g_dg) + (($g_dg + 1) % 2) * (($g_hg + 10 - $g_dg) % 2) * 2) % 10; #时干十神 
		$g_yzs = (($g_ynz + 11 - $g_dg) + (($g_dg + 1) % 2) * (($g_ynz + 10 - $g_dg) % 2) * 2) % 10;#年支十神
		$g_mzs = (($g_mnz + 11 - $g_dg) + (($g_dg + 1) % 2) * (($g_mnz + 10 - $g_dg) % 2) * 2) % 10;#月支十神
		$g_dzs = (($g_dnz + 11 - $g_dg) + (($g_dg + 1) % 2) * (($g_dnz + 10 - $g_dg) % 2) * 2) % 10;#日支十神 
		$g_hzs = (($g_hnz + 11 - $g_dg) + (($g_dg + 1) % 2) * (($g_hnz + 10 - $g_dg) % 2) * 2) % 10;#时支十神
        #到此，完成年月日时，各干支十神的确定
		#女命已算完
        ###求出年份后两位数
        $m_year_s    =substr($m_year, 2); 
		$g_year_s    =substr($g_year, 2);
        ###求出所属宫位(数值表示）
        $m_z_n=$cls_wedlockData->safe_convert($m_year_s);
		$g_z_v=$cls_wedlockData->safe_convert_a($g_year_s);
        if($m_z_n==5) {$m_z_nn="2";} else {$m_z_nn=$m_z_n;}
		if($g_z_v==5) {$g_z_vv="8";} else {$g_z_vv=$g_z_v;}
        if($m_z_n==0) {$m_z_nn="9";} else {$m_z_nn=$m_z_n;}
		if($g_z_v==0) {$g_z_vv="9";} else {$g_z_vv=$g_z_v;}
        ###推出年干年支
        $n_qq_n=$cls_wedlockData->shengchen($m_year_s);
		$n_qq_v=$cls_wedlockData->shengchen($g_year_s);
        ###求出所属宫位(汉字表示）
		$m_m_n=$cls_wedlockData->mgong($m_z_n);
		$g_m_v=$cls_wedlockData->mgong_a($g_z_v);
        $bb=0;
        $get_mg_num = $m_z_nn+$g_z_vv;
        if($m_z_nn!=$g_z_vv){
            if(in_array($get_mg_num,array(4,13,14))){$bb=30;}
            if($get_mg_num==5 && ($m_z_nn==3 || $m_z_nn==4)){$bb=30;}
            if($get_mg_num==7 && ($m_z_nn==3 || $m_z_nn==4)){$bb=30;}
            if($get_mg_num==8 && ($m_z_nn==2 || $m_z_nn==6)){$bb=30;}
            if($get_mg_num==9 && ($m_z_nn==2 || $m_z_nn==7)){$bb=30;}
            if($get_mg_num==10 && ($m_z_nn==1 || $m_z_nn==9 ||$m_z_nn==2 || $m_z_nn==8)){$bb=30;}
            if($get_mg_num==12 && ($m_z_nn==3 || $m_z_nn==9)){$bb=30;}
            if($get_mg_num==15 && ($m_z_nn==7 || $m_z_nn==8)){$bb=30;}
        }else{
            $bb=15;
        }
        ######命宫合婚完
        ###年支合
		if($c[30+$g_yz]==$c[30+$m_yz]) { $c = 20; } else  { $c = 0; } 
		###月令合
		if($a[30+$g_mz]==$a[30+$m_mz]){ $yh = 5; } else  { $yh = 0; } 
		###日合
        if($a[20+$g_dg]<>$a[20+$m_dg]) {
        if($e[20+$g_dg]==$e[20+$m_dg]){ $rh = 5; } else  { $rh = 0; } } else { $rh = 0; }
        if($d[20+$g_dg]==$d[20+$m_dg]){ $rrh = 25; } else  { $rrh = 0; } 
	
        ###子女同步
		if($m_hgs==7 || $m_hgs==8) { $m_children ='男'; }
		if($m_hgs==5 || $m_hgs==6) { $m_children ='女'; }
		if($m_hgs==1 || $m_hgs==2) { $m_children ='女'; }
		if($m_hgs==3 || $m_hgs==4) { $m_children ='女'; }
		if($m_hgs==9 || $m_hgs==0) { $m_children ='女'; }
		if($g_hgs==5 || $g_hgs==6) { $g_children ='男'; }
		if($g_hgs==7 || $g_hgs==8) { $g_children ='女'; }
		if($g_hgs==1 || $g_hgs==2) { $g_children ='女'; }
		if($g_hgs==3 || $g_hgs==4) { $g_children ='女'; }
		if($g_hgs==9 || $g_hgs==0) { $g_children ='女'; }
		if($g_children==$m_children){ $ez = 15; } else  { $ez = 0; } 
        $sea = 0;
		$yza = $m_yz%2;
        if($sea xor $yza){
			$g_qyjs = $g_qyjs-1; 
        }else{
			$g_qyjs = 10-$g_qyjs;
		}

        ######东西四命
		$m_na   =$cls_wedlockData->dxsz($m_z_n);// #东西四宅
		$g_va   =$cls_wedlockData->dxsz($g_z_v); 
		$m_nfw  =$cls_wedlockData->fangwei($m_z_n);
		$g_vfw  =$cls_wedlockData->fangwei($g_z_v); //方位
        $m_gongli       = $m_year.'年'.$m_month.'月'.$m_day.'日'.$m_hour.'时';
        $m_shengxiao    = $b[30+$m_yz];
		$m_shishen1     = $a[$m_ygs];
		$m_shishen2     = $a[$m_mgs];
        $m_rizhu        = $a[$m_hgs];
        $m_qianzao      = $a[20+$m_yg].$a[30+$m_yz].'|'.$a[20+$m_mg].$a[30+$m_mz].'|'.$a[20+$m_dg].$a[30+$m_dz].'|'.$a[20+$m_hg].$a[30+$m_hz];
        $m_zhishishen   = $a[$m_yzs].'|'.$a[$m_mzs].'|'.$a[$m_dzs].'|'.$a[$m_hzs];	
        $m_shishen_for = '';
        for($i=1;$i<9;$i++) {
            $m_sx = (($m_mg+10)-$i)%10;
            $m_xy = (($m_sx + 11 - $m_dg) + (($m_dg + 1) % 2) * (($m_sx + 10 - $m_dg) % 2) * 2) % 10;;
            $m_shishen_for .= $a[$m_xy].'|';
        }		
        $m_shishen_for = trim($m_shishen_for,'|');
        $g_gongli       = $g_year.'年'.$g_month.'月'.$g_day.'日'.$g_hour.'时';
		$g_shengxiao    = $b[30+$g_yz];
		$g_shishen1     = $a[$g_ygs];
		$g_shishen2     = $a[$g_mgs];
		$g_rizhu        = $a[$g_hgs];
		$g_qianzao      = $a[20+$g_yg].$a[30+$g_yz].'|'.$a[20+$g_mg].$a[30+$g_mz].'|'.$a[20+$g_dg].$a[30+$g_dz].'|'.$a[20+$g_hg].$a[30+$g_hz];
		$g_zhishishen   = $a[$g_yzs].'|'.$a[$g_mzs].'|'.$a[$g_dzs].'|'.$a[$g_hzs];
        $g_shishen_for = '';	
        for($i=0;$i<8;$i++) {
            $g_sx = (($g_mg+10)-$i)%10;
            $g_xy = (($g_sx + 11 - $g_dg) + (($g_dg + 1) % 2) * (($g_sx + 10 - $g_dg) % 2) * 2) % 10; 
            $g_shishen_for .=  $a[$g_xy].'|';
        }
        $g_shishen_for = trim($g_shishen_for,'|');
        $zongfen = $rh+$bb+$c+$yh+$rrh+$ez;   
        $data_Array = array(
            'm_username'    =>$m_username,
            'm_gongli'      =>$m_gongli,
            'm_shengxiao'   =>$m_shengxiao,
            'm_m_n'         =>$m_m_n,
            'm_children'    =>$m_children,
            'm_shishen1'    =>$m_shishen1,
            'm_shishen2'    =>$m_shishen2,
            'm_rizhu'       =>$m_rizhu,
            'm_qianzao'     =>$m_qianzao,
            'm_zhishishen'  =>$m_zhishishen,
            'm_shishen_for' =>$m_shishen_for,
            'm_na'          =>$m_na,
            'm_nfw'         =>$m_nfw,
            'g_username'    =>$g_username,
            'g_gongli'      =>$g_gongli,
            'g_shengxiao'   =>$g_shengxiao,
            'g_m_v'         =>$g_m_v,
            'g_children'    =>$g_children,
            'g_shishen1'    =>$g_shishen1,
            'g_shishen2'    =>$g_shishen2,
            'g_rizhu'       =>$g_rizhu,
            'g_qianzao'     =>$g_qianzao,
            'g_zhishishen'  =>$g_zhishishen,
            'g_shishen_for'=>$g_shishen_for,
            'g_va'          =>$g_va,
            'g_vfw'         =>$g_vfw,
            'bb'            =>$bb,
            'c'             =>$c,
            'yh'            =>$yh,
            'rrh'           =>$rrh,
            'rh'            =>$rh,
            'ez'            =>$ez,
            'zongfen'       =>$zongfen
        );

        $m_hh = trim($m_qianzao);
		// $m_hh = strtr($m_hh, ' ', '');
		// $m_hh = str_replace('&nbsp;', '1',$m_hh);
		// $m_hh = htmlspecialchars_decode($m_hh);//把一些预定义的 HTML 实体转换为字符
		// $m_hh = str_replace("&nbsp;","",$m_hh);//将空格替换成空
		// $m_hh = strip_tags($m_hh);
		// $m_hh = preg_replace('# #','',$m_hh); 
		// $m_hh = strip_tags($m_hh);

		$g_hh = trim($g_qianzao);
        // $g_hh = strtr($g_hh, ' ', '');
		// $g_hh = str_replace('&nbsp;', '1',$g_hh);
		// $g_hh = htmlspecialchars_decode($g_hh);//把一些预定义的 HTML 实体转换为字符
		// $g_hh = str_replace("&nbsp;","",$g_hh);//将空格替换成空
		// $g_hh = strip_tags($g_hh);
		// $g_hh = preg_replace('# #','',$g_hh); 
		// $g_hh = strip_tags($g_hh);
        $m_pieces = explode("|",$m_hh);
		$g_pieces = explode("|",$g_hh);
        $haar_data1 = '';
		$haar_data2 = '';
		$haar_data3 = '';
		$haar_data4 = '';
        foreach($m_pieces as $m_key => $m_value){
            foreach($g_pieces as $g_key => $g_value){
                if($m_key==0&&$g_key==0){
                    $m_length = mb_strlen($m_value, 'utf-8');
                    $g_length = mb_strlen($g_value, 'utf-8');
                    $m_data_array = [];
                    $g_data_array = [];
                    for ($i=0; $i<$m_length; $i++){
                        $ssr = mb_substr($m_value, $i, 1, 'utf-8');
                        if(strlen($ssr)==3){
                            $m_data_array[] = $ssr; 
                        }
                    }
                    for ($i=0; $i<$g_length; $i++){
                        $ssr = mb_substr($g_value, $i, 1, 'utf-8');
                        if(strlen($ssr)==3){
                            $g_data_array[] = $ssr; 
                        }
                    }
                    foreach($m_data_array as $m_k => $m_val){
                        foreach($g_data_array as $g_k => $g_val){
                            $haarx = $m_val.$g_val;
                            if($m_k==1&&$g_k==1){
                                $haar1 = Db::name('Match_hehun')->where('name','=',$haarx)->value('content');  
                                if($haar1){
                                    $haar_data1 = $haar1;
                                }
                            }
                        }
                    }
                }
                if($m_key==2&&$g_key==2){
                    $m_length = mb_strlen($m_value, 'utf-8');
                    $g_length = mb_strlen($g_value, 'utf-8');
                    $m_data_array = [];
                    $g_data_array = [];
                    for ($i=0; $i<$m_length; $i++){
                        $ssr = mb_substr($m_value, $i, 1, 'utf-8');
                        if(strlen($ssr)==3){
                            $m_data_array[] = $ssr; 
                        }
                    }
                    for ($i=0; $i<$g_length; $i++){
                        $ssr = mb_substr($g_value, $i, 1, 'utf-8');
                        if(strlen($ssr)==3){
                            $g_data_array[] = $ssr; 
                        }
                    }
                    foreach($m_data_array as $m_k => $m_val){
                        foreach($g_data_array as $g_k => $g_val){
                            $haarx = $m_val.$g_val;
                            if($m_k==0&&$g_k==0){
                                $haar2 = Db::name('Match_hehun3')->where('name','=',$haarx)->value('content');  
                                if($haar2){
                                    $haar_data2 = $haar2;
                                }
                            }
                            if($m_k==1&&$g_k==1){
                                $haar3 = Db::name('Match_hehun1')->where('name','=',$haarx)->value('content');
                                if($haar3){
                                    $haar_data3 = $haar3;
                                }
                            }
                        }
                    }
                }
                if($m_key==3&&$g_key==3){
                    $m_length = mb_strlen($m_value, 'utf-8');
                    $g_length = mb_strlen($g_value, 'utf-8');
                    $m_data_array = [];
                    $g_data_array = [];
                    for ($i=0; $i<$m_length; $i++){
                        $ssr = mb_substr($m_value, $i, 1, 'utf-8');
                        if(strlen($ssr)==3){
                            $m_data_array[] = $ssr; 
                        }
                    }
                    for ($i=0; $i<$g_length; $i++){
                        $ssr = mb_substr($g_value, $i, 1, 'utf-8');
                        if(strlen($ssr)==3){
                            $g_data_array[] = $ssr; 
                        }
                    }
                    foreach($m_data_array as $m_k => $m_val){
                        foreach($g_data_array as $g_k => $g_val){
                            $haarx = $m_val.$g_val;
                            if($m_k==1&&$g_k==1){
                                $haar4 = Db::name('Match_hehun2')->where('name','=',$haarx)->value('content');
                                if($haar4){
                                    $haar_data4 = $haar4;
                                }
                            }
                        }
                    }
                }
            }      
        }
        $data_Array['haar_data1']=$haar_data1;
		$data_Array['haar_data2']=$haar_data2;
		$data_Array['haar_data3']=$haar_data3;
		$data_Array['haar_data4']=$haar_data4;
        return $data_Array;
    }

    //计算文字的五行
    public function wordsWx($str){
        $charInfo= self::getCharacterInfo($str);
        $bihua   = $charInfo['bihua'];
        $hzwh    = $charInfo['wuxing'];
        $py      = $charInfo['pinyin'];
        $gb      = $charInfo['fan'];
        $data    = array('a1'=>$str,'py'=>$py,'gb'=>$gb,'hzwh'=>$hzwh,'bihua'=>$bihua);
        return $data;
    }

    //计算紫薇斗数
    public function get_ziwei($row){
        $name       = $row['username'];
        $sex        = $row['sex'];
        $DateType   = 1;
        $y          = $row['year'];
        $m          = $row['month'];
        $d          = $row['day'];
        $h          = $row['hour'];
        $hIndex     = ceil($h/2)%12;
        $runyue     = 0;//阴历闰月
        $runfen     = 1;//闰月分界
        $segong     = array("兄弟宫","夫妻宫","子女宫","财帛宫","疾厄宫","迁移宫","奴仆宫","官禄宫","田宅宫","福德宫","父母宫");
        $boshi      = array("博士","力士","青龙","小耗","将军","奏书","飞廉","喜神","病符","大耗","伏兵","官府");
        $changsheng = array("长生","沐浴","冠带","临官","帝旺","衰","病","死","墓","绝","胎","养");
        $liunianxing =array("岁建","晦气","丧门","贯索","官符","小耗","大耗","龙德","白虎","天德","吊客","病符");
        $liunianxing2=array("将星","攀鞍","岁驿","息神","华盖","劫煞","灾煞","天煞","指背","咸池","月煞","亡神");
        $mpx        =array(
            0=>array("紫微","平","庙","庙","旺","陷","旺","庙","庙","旺","平","闲","旺"),
            1=>array("天机","庙","陷","旺","旺","庙","平","庙","陷","平","旺","庙","平"),
            2=>array("太阳","陷","陷","旺","庙","旺","旺","庙","平","闲","闲","陷","陷"),
            3=>array("武曲","旺","庙","闲","陷","庙","平","旺","庙","平","旺","庙","平"),
            4=>array("天同","旺","陷","闲","庙","平","庙","陷","陷","旺","平","平","庙"),
            5=>array("廉贞","平","旺","庙","闲","旺","陷","平","庙","庙","平","旺","陷"),
            6=>array("天府","庙","庙","庙","平","庙","平","旺","庙","平","陷","庙","旺"),
            7=>array("太阴","庙","庙","闲","陷","闲","陷","陷","平","平","旺","旺","庙"),
            8=>array("贪狼","旺","庙","平","地","庙","陷","旺","庙","平","平","庙","陷"),
            9=>array("巨门","旺","旺","庙","庙","平","平","旺","陷","庙","庙","旺","旺"),
            10=>array("天相","庙","庙","庙","陷","旺","平","旺","闲","庙","陷","闲","平"),
            11=>array("天粱","庙","旺","庙","庙","旺","陷","庙","旺","陷","地","旺","陷"),
            12=>array("七杀","旺","庙","庙","陷","旺","平","旺","旺","庙","闲","庙","平"),
            13=>array("破军","庙","旺","陷","旺","旺","闲","庙","庙","陷","陷","旺","平"),
            14=>array("天魁","旺","旺",NULL,"庙",NULL,NULL,"庙",NULL,NULL,NULL,NULL,"旺"),
            15=>array("天钺",NULL,NULL,"旺",NULL,NULL,"旺",NULL,"旺","庙","庙",NULL,NULL),
            16=>array("左辅","旺","庙","庙","陷","庙","平","旺","庙","平","陷","庙","闲"),
            17=>array("右弼","庙","庙","旺","陷","庙","平","旺","庙","闲","陷","庙","平"),
            18=>array("文昌","旺","庙","陷","平","旺","庙","陷","平","旺","庙","陷","旺"),
            19=>array("文曲","庙","庙","平","旺","庙","庙","陷","旺","平","庙","陷","旺"),
            20=>array("禄存","旺",NULL,"庙","旺",NULL,"庙","旺",NULL,"庙","旺",NULL,"庙"),
            21=>array("天马",NULL,NULL,"旺",NULL,NULL,"平",NULL,NULL,"旺",NULL,NULL,"平"),
            22=>array("擎羊","陷","庙",NULL,"陷","庙",NULL,"平","庙",NULL,"陷","庙",NULL),
            23=>array("铃星","陷","陷","庙","庙","旺","旺","庙","旺","旺","陷","庙","庙"),
            24=>array("陀罗",NULL,"庙","陷",NULL,"庙","陷",NULL,"庙","陷",NULL,"庙","陷"),
            25=>array("火星","平","旺","庙","平","闲","旺","庙","闲","陷","陷","庙","平"));
            $tg     =array("甲","乙","丙","丁","戊","己","庚","辛","壬","癸");
            $dz     =array("子","丑","寅","卯","辰","巳","午","未","申","酉","戌","亥");
            $mmonth =array(NULL,"正","二","三","四","五","六","七","八","九","十","十一","十二");
            $mday   =array(NULL,"初一","初二","初三","初四","初五","初六","初七","初八","初九","初十","十一","十二","十三","十四","十五","十六","十七","十八","十九","二十","廿一","廿二","廿三","廿四","廿五","廿六","廿七","廿八","廿九","三十");
            //农历转新历
            $cls_rowDish = new cls_rowDish();
            $cls_rowDishFunction = new cls_rowDishFunction();
            $cls_ancientCalendarData = new cls_ancientCalendarData();
            $cls_almanacData   = new cls_almanacData();
            if($DateType==0 ){
                $NongLi     =$y."年".$mmonth[$m]."月".$mday[$d].$dz[$hIndex]."时";
                $ytg        =$tg[($y-1914)%10];
                $ydz        =($y-1912)%12;
                if($runyue==1){
                    $nm=$m+1;
                    $NongLi=$y."年闰".$mmonth[$m]."月".$mday[$d].$dz[$hIndex]."时";
                    if($runfen==1){
                        $m++;
                    }else{
                        if($d>15)$m++;
                    }
                }else{
                    $ifrunyue = $cls_rowDish->getLeapMonth($y);
                    if($ifrunyue>0&&$ifrunyue<$m){
                        $nm=$m+1;
                    }else{
                        $nm=$m;
                    }
                }
                $yangliarr  =$cls_ancientCalendarData->convertLunarToSolar($y,$nm,$d);
                $yy         =(int)$yangliarr[0];
                $ym         =(int)$yangliarr[1];
                $yd         =(int)$yangliarr[2];
                $minues     =0;
                $yangdate   =$yy."年".$ym."月".$yd."日".$h."时";
                $gz         =$cls_rowDishFunction->getSizhu($yy,$ym,$yd,$h,'0');
            }else{
                $gz         =$cls_rowDishFunction->getSizhu($y,$m,$d,$h,'0');
                $yangdate   =$y."年".$m."月".$d."日".$h."时";
                $filed_data         = require_once '../application/common/library/phpu/locales/zh-cn.php';
                $Calendar           = new Calendar();
                $demo               = $Calendar->setConfig('default')->createCalendar($y,$m,$d,$h);
                $mtwelve = array("子", "丑", "寅", "卯", "辰", "巳", "午", "未", "申", "酉", "戌", "亥");
                if ($h >= 23) $nlhour = "夜" . $mtwelve[$demo['gz']['h']['z']];
                if ($h >= 0 && $h < 1) $nlhour = "早" . $mtwelve[$demo['gz']['h']['z']];
                $ntime      = array($demo['gz']['y']['s'],$demo['lunar'][1],$demo['lunar'][2],$nlhour,$demo['lunar'][0],$demo['gz']['y']['sa']);
                $cyue       =(int)$ntime[1];
                $cyear      =(int)$ntime[0];
                $cri        =(int)$ntime[2];
                if(substr($cyue,0,2)=="闰"){
                    $cyuem=substr($cyue,-(strlen($cyue)-2));
                }else{
                    $cyuem=$cyue;
                }
                $m=array_search($cyuem,$mmonth);
                $d=array_search($cri,$mday);
                if(substr($cyue,0,3)=="闰"&&$runfen==1) $m++;
                if(substr($cyue,0,3)=="闰"&&$runfen==0&&$d>15) $m++;
                $NongLi     =$ntime[0].$ntime[5]."年".$ntime[1]."月".$ntime[2].$ntime[3]."时";
                $ytg        =substr($ntime[0],0,3);
                $ydz        =substr($ntime[0],3,3);
                $ydz        =array_search($ydz,$dz);
            }
            $ygz=$gz[0].$gz[1];
            $mgz=$gz[2].$gz[3];
            $dgz=$gz[4].$gz[5];
            $tgz=$gz[6].$gz[7];
            if(($sex==0 &&($cls_almanacData->Tgorder($ytg)%2)==1) || ($sex==1 &&($cls_almanacData->Tgorder($ytg)% 2)==0)){
                $orders=1;
            }else{
                $orders=0;
            }
            //阳男阴女顺俳，阴男阳女逆俳
            //'给12宫配上天干和年上起月法相同
            $tm=array_search($ytg,$tg);
            if($tm<0 ) $tm+=10;
            for($i=0;$i<=11;$i++){
                if( $i > 1 && $i <= 11){
                    $TgIndex = ($tm * 2 + $i + 10) % 10;
                }else{
                    $TgIndex = ($tm * 2 + $i+2) % 10;
                }
                $dptg[$i]=$tg[$TgIndex];
            }
             //'命宫和身宫，
            $minggindex=($m+1-$hIndex+12)% 12;
            $shengindex=($m+1+$hIndex+12)% 12;
            for($i=0;$i<=11;$i++){
                $shengong[$i]="&nbsp&nbsp";
            }
            $minggong[$minggindex]="命宫";//"命宫"
            $shengong[$shengindex]="";//"(身宫)"
            //排12宫，以命宫为基准逆排剩下11宫
            for($i=1;$i<=11;$i++){
                $mgindex=($minggindex-$i+12)% 12;
                $minggong[$mgindex]=$segong[$i-1];
            }
            //五行局和五行纳音
            //$wxIndex=($minggindex+2)%12;
            $wxgz   =$dptg[$minggindex].$dz[$minggindex];
            $wxNayin=$cls_rowDish->getNayin($wxgz);
            $jumingz=substr(trim($wxNayin),-3);
            $arrzwgong=array(
                0=>array("水","丑","寅","寅","卯","卯","辰","辰","巳","巳","午","午","未","未","申","申","酉","酉","戌","戌","亥","亥","子","子","丑","丑","寅","寅","卯","卯","辰","水二局",2),
                1=>array("木","辰","丑","寅","巳","寅","卯","午","卯","辰","未","辰","巳","申","巳","午","酉","午","未","戌","未","申","亥","申","酉","子","酉","戌","丑","戌","亥","木三局",3),
                2=>array("金","亥","辰","丑","寅","子","巳","寅","卯","丑","午","卯","辰","寅","未","辰","巳","卯","申","巳","午","辰","酉","午","未","巳","戌","未","申","午","亥","金四局",4),
                3=>array("土","午","亥","辰","丑","寅","未","子","巳","寅","卯","申","丑","午","卯","辰","酉","寅","未","辰","巳","戌","卯","申","巳","午","亥","辰","酉","午","未","土五局",5),
                4=>array("火","酉","午","亥","辰","丑","寅","戌","未","子","巳","寅","卯","亥","申","丑","午","卯","辰","子","酉","寅","未","辰","巳","丑","戌","卯","申","巳","午","火六局",6)
            );
            $jindex=0;
            $wxju  =$arrzwgong[0][31];
            $juxu  =$arrzwgong[0][32];
            for($i=0;$i<=4;$i++){
                if($arrzwgong[$i][0]==$jumingz){
                    $wxju   =$arrzwgong[$i][31];
                    $juxu   =$arrzwgong[$i][32];
                    $zw     =$arrzwgong[$i][$d];
                    $jindex =array_search($zw,$dz);
                    break;
                }
            }
            $tianjiindex    =($jindex+11)% 12;
            $taiyangindex   =($jindex+9)% 12;
            $wuquindex      =($jindex+8)%12;
            $tiantongindex  =($jindex+7)% 12;
            $lianzhenindex  =($jindex+4)% 12;
            $TfIndex        =(16-$jindex)% 12;

            $taiyinindex    =($TfIndex+1)% 12;
            $tanlangindex   =($TfIndex+2)% 12;
            $jumenindex     =($TfIndex+3)% 12;
            $tianxiangindex =($TfIndex+4)% 12;
            $tianliangindex =($TfIndex+5)% 12;
            $pojunindex     =($TfIndex+10)% 12;
            $wencindex      =(12-$hIndex+10)% 12;
            $wenqindex      =($hIndex+4)% 12;

            $zwxing[$jindex]        ="紫薇";
            $zwxing[$tianjiindex]   ="天机";
            $zwxing[$taiyangindex]  ="太阳";
            $zwxing[$wuquindex]     ="武曲";
            $zwxing[$tiantongindex] ="天同";
            $zwxing[$lianzhenindex] ="廉贞";
            $tfxing[$TfIndex]       ="天府";
            $tfxing[$taiyinindex]   ="太阴";
            $tfxing[$tanlangindex]  ="贪狼";
            $tfxing[$jumenindex]    ="巨门";
            $tfxing[$tianxiangindex]="天相";
            $tfxing[$tianliangindex]="天梁";
            $tfxing[($TfIndex+6)%12]="七杀";
            $tfxing[$pojunindex]    ="破军";
            $wenchang[$wencindex]   ="文昌";
            $wenchang[$wenqindex]   ="文曲";
            switch($ytg){
                case "乙":  
                    $zwxing[$jindex]="紫薇科";
                    $zwxing[$tianjiindex]="天机禄";
                    $tfxing[$tianliangindex]="天梁权";
                    $tfxing[$taiyinindex]="太阴忌";
                    break;
                case "壬":  
                    $tfxing[$tianliangindex]="天梁禄";
                    $zwxing[$jindex]="紫薇权";
                    $zwxing[$wuquindex] ="武曲忌";
                    break;
                case "丙": 
                    $zwxing[$tianjiindex]="天机权";
                    $zwxing[$tiantongindex]="天同禄";
                    $zwxing[$lianzhenindex]="廉贞忌";
                    $wenchang[$wencindex]="文昌科";
                    break;
                case "丁": 
                    $zwxing[$tianjiindex]="天机科";
                    $zwxing[$tiantongindex]="天同权";
                    $tfxing[$taiyinindex]="太阴禄";
                    $tfxing[$jumenindex]="巨门忌";
                    break;
                case "戊": 
                    $zwxing[$tianjiindex]="天机忌";
                    $tfxing[$taiyinindex]="太阴权";
                    $tfxing[$tanlangindex]= "贪狼禄";
                    break;
                case "甲": 
                    $zwxing[$taiyangindex]="太阳忌";
                    $zwxing[$wuquindex] ="武曲科";
                    $zwxing[$lianzhenindex]="廉贞禄";
                    $tfxing[$pojunindex]="破军权";
                    break;
                case "庚": 
                    $zwxing[$taiyangindex]="太阳禄";
                    $zwxing[$wuquindex] ="武曲权";
                    $tfxing[$taiyinindex]="太阴科";
                    $zwxing[$tiantongindex]="天同忌";
                    break;
                case "辛": 
                    $zwxing[$taiyangindex]="太阳权";
                    $tfxing[$jumenindex]="巨门禄";
                    $wenchang[$wencindex]="文昌忌";
                    $wenchang[$wenqindex]="文曲科";
                    break;
                case "己": 
                    $zwxing[$wuquindex] ="武曲禄";
                    $tfxing[$tanlangindex]= "贪狼权";
                    $tfxing[$tianliangindex]="天梁科";
                    $wenchang[$wenqindex]="文曲忌";
                    break;
    
                case "癸": 
                    $tfxing[$taiyinindex]="太阴科";
                    $tfxing[$tanlangindex]= "贪狼忌";
                    $tfxing[$jumenindex]="巨门权";
                    $tfxing[$pojunindex]="破军禄";
                    break;
    
            }
            //恩光序号文昌顺数到生日，退后一步是恩光，文曲顺数到生日，退后一步天贵扬
            $engindex       =($wencindex+$d-2)%12;
            $tianguiindex   =($wenqindex+$d-2)%12;  //天贵序号
            $djindex        =(11+$hIndex)% 12;      //地劫
            $tkindex        =(11-$hIndex+12)% 12;   //天空
            $apindex        =($hIndex+6)% 12;       // 台铺
            $hxindex        =($hIndex+2)% 12;       // 诰乡
            if(isset($wenchang[$apindex])){
                $wenchang[$apindex] .='|'."台铺";
            }else{
                $wenchang[$apindex] ="台铺";
            }
            if(isset($wenchang[$hxindex])){
                $wenchang[$hxindex] .='|'."封诰";
            }else{
                $wenchang[$hxindex] ="封诰";
            }
            if(isset($wenchang[$djindex])){
                $wenchang[$djindex] .='|'."地劫";
            }else{
                $wenchang[$djindex] ='|'."地劫";
            }
            if (isset($wenchang[$tkindex])){
                $wenchang[$tkindex] .='|'."天空";
            }else{
                $wenchang[$tkindex] ="天空";
            }
            //火灵二星
            if($ydz==2||$ydz==6||$ydz==10){
                $hgindex=($hIndex+1)%12;
                $lgindex=($hIndex+3)%12;
                $xiaoxian=4;
            }
            if($ydz==8||$ydz==0||$ydz==4 ){
                $hgindex=($hIndex+2)%12;
                $lgindex=($hIndex+10)%12;
                $xiaoxian=10;
            }
            if($ydz==11||$ydz==3||$ydz==7){
                $hgindex=($hIndex+3)%12;
                $lgindex=($hIndex+10)%12;
                $xiaoxian=1;
            }
            if($ydz==5||$ydz==9||$ydz==1){
                $hgindex=($hIndex+9)%12;
                $lgindex=($hIndex+10)%12;
                $xiaoxian=7;
            }
            $huoling[$hgindex]="火星";
            $huoling[$lgindex]="铃星";
            //起月系星星
            $zpindex    =(3+$m)% 12;//  '左辅
            $youbiindex =(23-$m)% 12;// '右弼
            $tianyindex =$m% 12;
            $tianxindex =(8+$m)% 12;
            if(!isset($huoling[$zpindex])){
                $huoling[$zpindex] ="左辅";
                if($ytg=="壬" ){$huoling[$zpindex] ="左辅科";}
            }else{
                if($ytg=="壬" ){
                    $huoling[$zpindex].='|'."左辅科";
                }else{
                    $huoling[$zpindex].='|'."左辅";
                }
            }
            if(!isset($huoling[$youbiindex])){
                $huoling[$youbiindex]="右弼";
                if($ytg=="戊" ){
                    $huoling[$youbiindex] ="右弼科";
                }
            }else{
                if($ytg=="戊" ){
                    $huoling[$youbiindex] .='|'."右弼科";
                }else{
                    $huoling[$youbiindex] .='|'."右弼";
                }
            }
            if(isset($huoling[$tianyindex])){
                $huoling[$tianyindex] .='|'."天姚";
            }else{
                $huoling[$tianyindex]="天姚";
            }
            if (isset($huoling[$tianxindex])){
                $huoling[$tianxindex] .='|'."天刑";
            }else{
                $huoling[$tianxindex]="天刑";
            }
            if($m%4==1){
                $tianwindex=5;
            }
            if($m%4==3){
                $tianwindex=11;
            }
            if($m%4==0){
                $tianwindex=2;
            }
            if($m%4==2){
                $tianwindex=8;
            }
            if(isset($huoling[$tianwindex])){
                $huoling[$tianwindex] .='|'."天巫";
            }else{
                $huoling[$tianwindex]="天巫";
            }
            $jieshenindex=(22-$ydz)%12;
            $tianyuearr=array(0,10,5,4,2,7,3,11,7,2,6,10,2);
            $tianyueindex=$tianyuearr[$m];
            $yinshaarr=array(0,2,0,10,8,6,4,2,0,10,8,6,4);
            $yinshaindex=$yinshaarr[$m];
            if(isset($huoling[$jieshenindex])){
                $huoling[$jieshenindex].='|'."解神";
            }else{
                $huoling[$jieshenindex]="解神";
            }
            if(isset($huoling[$tianyueindex])){
                switch($ytg)
                {
                    case "乙": $huoling[$tianyueindex].='|'."天月忌";break;
                    case "戊": $huoling[$tianyueindex].='|'."天月权";break;
                    default:$huoling[$tianyueindex].='|'."天月";
                }
            }else{
                switch($ytg)
                {
                    case "乙": $huoling[$tianyueindex]="天月忌";break;
                    case "戊": $huoling[$tianyueindex]="天月权";break;
                    default:$huoling[$tianyueindex]="天月";
                }
            }
            if(isset($huoling[$yinshaindex])){
                $huoling[$yinshaindex].="阴煞";
            }else{
                $huoling[$yinshaindex]="阴煞";
            }
            // '日系星三台从左辅上起初一，顺行至本生日安之
            // '八座从右弼上起初一，逆行至本生日安之
            $santindex=($zpindex+$d-1)% 12;
            $bazuoindex=($youbiindex-(($d-1) % 12)+12)% 12;
            if(isset($wenchang[$santindex])){
                $wenchang[$santindex].='|'."三台";
            }else{
                $wenchang[$santindex]="三台";
            }
            if(isset($wenchang[$bazuoindex])){
                $wenchang[$bazuoindex].='|'."八座";
            }else{
                $wenchang[$bazuoindex]="八座";
            }
            if(isset($wenchang[$engindex])){
                $wenchang[$engindex].='|'."恩光";
            }else{
                $wenchang[$engindex]="恩光";
            }
            if(isset($wenchang[$tianguiindex])){
                $wenchang[$tianguiindex].='|'."天贵";
            }else{
                $wenchang[$tianguiindex]="天贵";
            }
            //'干系星星 甲寅乙卯丙禄巳，丁己午兮禄所至，庚禄居申辛禄酉，壬禄在亥癸禄子
        switch($ytg)
        {
            case  "甲":  $lcindex=2;break;
            case  "乙":  $lcindex=3;break;
            case  "丙":  $lcindex=5;break;
            case  "丁":  $lcindex=6;break;
            case  "戊":  $lcindex=5;break;
            case  "己":  $lcindex=6;break;
            case  "庚":  $lcindex=8;break;
            case  "辛":  $lcindex=9;break;
            case  "壬":  $lcindex=11;break;
            case  "癸":  $lcindex=0;break;
        }
        $qingyindex     =($lcindex+1)% 12;
        $tuoluoindex    =($lcindex+11)% 12;
        $mingzhu        ="禄存";
        if(isset($zwxing[$lcindex])){
            $zwxing[$lcindex].='|'.$mingzhu;
        }else{
            $zwxing[$lcindex]=$mingzhu;
        }
        if(isset($zwxing[$tuoluoindex])){
            $zwxing[$tuoluoindex].='|'."陀罗";
        }else{
            $zwxing[$tuoluoindex]="陀罗";
        }
        if(isset($zwxing[$qingyindex])){
            $zwxing[$qingyindex].='|'."擎羊";
        }else{
            $zwxing[$qingyindex]="擎羊";
        }
        if($ytg=="甲"||$ytg=="戊"||$ytg=="庚"){
            $tiankuiindex=1;
            $tianjiindex=7;
        }
        if($ytg=="乙"||$ytg=="己"){
            $tiankuiindex=0;
            $tianjiindex=8;
        }
        if($ytg=="丙"||$ytg=="丁"){
            $tiankuiindex=11;
            $tianjiindex=9;
        }
        if($ytg=="壬"||$ytg=="癸"){
            $tiankuiindex=3;
            $tianjiindex=5;
        }
        if($ytg=="辛"){
            $tiankuiindex=6;
            $tianjiindex=2;
        }
        if(isset($zwxing[$tiankuiindex])){
            $zwxing[$tiankuiindex].='|'."天魁";
        }else{
            $zwxing[$tiankuiindex]="天魁";
        }
        if(isset($zwxing[$tianjiindex])){
            $zwxing[$tianjiindex].='|'."天钺";
        }else{
            $zwxing[$tianjiindex]="天钺";
        }
        switch($ytg){
            case "甲":  
                $tianguanindex=7;
                $tianfuindex=9;
                break;
            case "乙":  
                $tianguanindex=4;
                $tianfuindex=8;
                break;
            case "丙":  
                $tianguanindex=5;
                $tianfuindex=0;
                break;
            case "丁":  
                $tianguanindex=2;
                $tianfuindex=11;
                break;
            case "戊":  
                $tianguanindex=3;
                $tianfuindex=3;
                break;
            case "己":  
                $tianguanindex=9;
                $tianfuindex=2;
                break;
            case "庚":  
                $tianguanindex=11;
                $tianfuindex=6;
                break;
            case "辛": 
                $tianguanindex=9;
                $tianfuindex=5;
                break;
            case "壬": 
                $tianguanindex=10;
                $tianfuindex=6;
                break;
            case "癸": 
                $tianguanindex=6;
                $tianfuindex=5;
                break;
        }
        if(isset($zwxing[$tianfuindex])){
            $zwxing[$tianfuindex].='|'."天福";
        }else{
            $zwxing[$tianfuindex]="天福";
        }
        if(isset($zwxing[$tianguanindex])){
            $zwxing[$tianguanindex].='|'."天官";
        }else{
            $zwxing[$tianguanindex]="天官";
        }
         //12博士
         if($orders==1){
            for($i=0;$i<=11;$i++){
                $boshis[($lcindex+$i)% 12]=$boshi[$i];
            }
        }else{
            for($i=0;$i<=11;$i++){
                $boshis[($lcindex+12-$i)% 12]=$boshi[$i];
            }
        }
        $tianxuindex    =(6+$ydz)% 12;
        $tiankuindex    =(18-$ydz)% 12;
        $longciindex    =(4+$ydz)% 12;
        $fenggeindex    =(22-$ydz)% 12;
        $hongluanindex  =(15-$ydz)% 12;
        $tianxiindex    =($hongluanindex+6)%12;
        if($ydz%4==0){              //天马
            $tianmindex=2;
        }
        if($ydz%4==1){
            $tianmindex=11;
        }
        if($ydz%4==2){
            $tianmindex=8;
        }
        if($ydz%4==3){
            $tianmindex=5;
        }
        if(isset($huoling[$tianmindex])){
            $huoling[$tianmindex] .='|'."天马";
        }else{
            $huoling[$tianmindex]="天马";
        }
        $boshis[$tianxuindex]   .="|天虚";
        $boshis[$tiankuindex]   .="|天哭";
        $boshis[$longciindex]   .="|龙池";
        $boshis[$fenggeindex]   .="|凤阁";
        $boshis[$hongluanindex] .="|红鸾";
        $boshis[$tianxiindex]   .="|天喜";
        if($ydz==2||$ydz==3||$ydz==4){
            $guchenindex=5;
            $guaxiuindex=1;
        }
        if($ydz==5||$ydz==6||$ydz==7){
            $guchenindex=8;
            $guaxiuindex=4;
        }
        if($ydz==8||$ydz==9||$ydz==10){
            $guchenindex=11;
            $guaxiuindex=7;
        }
        if($ydz==11||$ydz==0||$ydz==1){
            $guchenindex=2;
            $guaxiuindex=10;
        }
        $boshis[$guchenindex] .="|孤辰";
        $boshis[$guaxiuindex] .="|寡宿";
        $beilian        =array(8,9,10,5,6,7,2,3,4,11,0,1);
        $beilianindex   =$beilian[$ydz];
        if($ydz % 3==0) $poshuiindex=5;
        if($ydz % 3==2) $poshuiindex=9;
        if($ydz % 3==1) $poshuiindex=1;
        $boshis[$poshuiindex]   .="|破碎";
        $boshis[$beilianindex]  .="|蜚廉";
        $tiancaiindex           =($minggindex+$ydz)% 12;
        $tianshouindex          =($shengindex+$ydz)% 12;
        $boshis[$tiancaiindex]  .="|天才";
        $boshis[$tianshouindex] .="|天寿";
        switch($wxju){
            case "水二局": $changshIndex=8;break;
            case "木三局": $changshIndex=11;break;
            case "金四局": $changshIndex=5;break;
            case "土五局": $changshIndex=8;break;
            case "火六局": $changshIndex=2;break;
        }
        if($orders==1 ){
            for($i=0;$i<=11;$i++){
                $dptg[($i+$changshIndex)% 12]=$changsheng[$i]." ".$dptg[($i+$changshIndex)% 12];
            }
        }else{
            for($i=0;$i<=11;$i++){
                $dptg[($changshIndex-$i+12)% 12]=$changsheng[$i]." ".$dptg[($changshIndex-$i+12)% 12];
            }
        }
        switch($ytg){
            case "甲": $jiekong="申酉";break;
            case "己": $jiekong="酉申";break;
            case "乙": $jiekong="未午";break;
            case "庚": $jiekong="午未";break;
            case "丙": $jiekong="辰巳";break;
            case "辛": $jiekong="巳辰";break;
            case "丁": $jiekong="卯寅";break;
            case "壬": $jiekong="寅卯";break;
            case "戊": $jiekong="子丑";break;
            case "癸": $jiekong="丑子";break;
        }
        $jieluindex     =$cls_almanacData->Dzorder(substr($jiekong,0,3));
        $kongmangindex  =$cls_almanacData->Dzorder(substr($jiekong,-3));
        if(isset($wenchang[$jieluindex])){
            $wenchang[$jieluindex] .="|截空";
        }else{
            $wenchang[$jieluindex]="截空";
        }
        if(isset($wenchang[$kongmangindex])){
            $wenchang[$kongmangindex] .="|空亡";
        }else{
            $wenchang[$kongmangindex]="空亡";
        }
        //起大限
        $xushuig    =$minggindex;
        $xushui     =$juxu;
        if($orders==1 ){
            for($i=0;$i<=11;$i++){
                $dptg[($i+$xushuig)% 12]=$xushui."－".($xushui+9)."&nbsp;".$dptg[($i+$xushuig)% 12];
                $xushui++;
                $xushui +=9;
            }
        }else{
            for($i=0;$i<=11;$i++){
                $dptg[($xushuig-$i+12)% 12]=$xushui."－".($xushui+9)."".$dptg[($xushuig-$i+12)% 12];
                $xushui++;
                $xushui +=9;
            }
        }
         //流年星
        if($ydz==2||$ydz==6||$ydz==10) $startly=6;
        if($ydz==8||$ydz==0||$ydz==4) $startly=0;
        if($ydz== 5||$ydz==9||$ydz==1) $startly=9;
        if($ydz==11||$ydz==3||$ydz==7) $startly=3;
        for($i=0;$i<=11;$i++){
            $lyxing[($startly+$i)% 12]=$liunianxing2[$i];
        }
        //起小限
        if($sex==1){
            for($i=0;$i<=11;$i++){
                $lyxing[($xiaoxian+$i)% 12]=str_pad($lyxing[($xiaoxian+$i)% 12]." [".($i+1),2-strlen($i+1)," ")."] ";
            }
        }else{
            for($i=0;$i<=11;$i++){
                $lyxing[($xiaoxian-$i+12)% 12]=str_pad($lyxing[($xiaoxian-$i+12)% 12]." [".($i+1),2-strlen($i+1)," ")."] ";
            }
        }
        //太岁一年一替换，岁前首先是晦气，
        for($i=0;$i<=11;$i++){
            $lyxing[($ydz+$i)% 12] .="|".$liunianxing[$i];
        }
         //斗君
        $liunian       =(date("Y")-1912)%12;
        $doujunindex   =((($liunian-$m+13)%12)+$hIndex+1)%12;
        $zidouindex    =(0-$m+13+$hIndex)%12;
        for($j=0;$j<=25;$j++){
            for($i=0;$i<=11;$i++){
                $mpxstr=$mpx[$j][(($i-10+12)%12)+1];
                if(isset($tfxing[$i])&&strpos($tfxing[$i],$mpx[$j][0])>0 &&$mpxstr!=NULL){
                    $tfxing[$i]=str_replace($mpx[$j][0],$mpx[$j][0].$mpxstr,$tfxing[$i]);
                }
                if(isset($zwxing[$i])&&strpos($zwxing[$i],$mpx[$j][0])>0 &&$mpxstr!=NULL){
                    $zwxing[$i]=str_replace($mpx[$j][0],$mpx[$j][0].$mpxstr,$zwxing[$i]);
                }
                $mpxstr2=$mpx[$j][$i+1];
                if(isset($wenchang[$i])&&strpos($wenchang[$i],$mpx[$j][0])>0 &&$mpxstr2!=NULL){
                    $wenchang[$i]=str_replace($mpx[$j][0],$mpx[$j][0].$mpxstr2,$wenchang[$i]);
                }
                if(isset($huoling[$i])&&strpos($huoling[$i],$mpx[$j][0])>0 &&$mpxstr2!=NULL){
                    $huoling[$i]=str_replace($mpx[$j][0],$mpx[$j][0].$mpxstr2,$huoling[$i]);
                }
                if(isset($boshis[$i])&&strpos($boshis[$i],$mpx[$j][0])>0 &&$mpxstr2!=NULL){
                    $boshis[$i]=str_replace($mpx[$j][0],$mpx[$j][0].$mpxstr2,$boshis[$i]);
                }
            }
        }
        for($i=0;$i<=11;$i++){
            if(!isset($zwxing[$i])) $zwxing[$i]="";
            if(!isset($tfxing[$i])) $tfxing[$i]="";
            if(!isset($wenchang[$i])) $wenchang[$i]="";
            if(!isset($huoling[$i])) $huoling[$i]="";
            if(!isset($minggong[$i])){
                $minggong[$i]="";
            }else{
                $minggong[$i]=$minggong[$i];
            }
            if(!isset($shengong[$i])){
                $shengong[$i]="";
            }else{
                $shengong[$i]=$shengong[$i];
            }
            if(!isset($boshis[$i])) $boshis[$i]="";
            if(!isset($lyxing[$i])) $lyxing[$i]="";
        }
        switch($minggindex){
            case 1: $mingzhum="巨门";break;
            case 2: $mingzhum="禄存";break;
            case 3: $mingzhum="文曲";break;
            case 4: $mingzhum="廉贞";break;
            case 5: $mingzhum="武曲";break;
            case 6: $mingzhum="破军";break;
            case 7: $mingzhum="武曲";break;
            case 8: $mingzhum="廉贞";break;
            case 9: $mingzhum="文曲";break;
            case 10: $mingzhum="禄存";break;
            case 11: $mingzhum="巨门";break;
            case 0: $mingzhum="贪狼";break;
        }
        $shenzhuarr=array("铃星","天相","天梁","天同","文昌","天机","火星","天相","天梁","天同","文昌","天机");
        $shenzhum=$shenzhuarr[$ydz];
        // $li_span1 = array('<li>','</li>');
        // $li_span2 = array('<span>','</span>');
        for($i=0;$i<=11;$i++){
            $pan[$i]=$zwxing[$i].'|'.$tfxing[$i].'|'.$wenchang[$i].'|'.$huoling[$i];
            $pan[$i].="|".$minggong[$i].$shengong[$i];
            $pan[$i].="|".$dz[$i];
        }
        $quanzao  = $ygz."　　".$mgz."　　".$dgz."　　".$tgz;
        $nayinarr = $cls_rowDish->getNayin($ygz)."　".$cls_rowDish->getNayin($mgz)."　".$cls_rowDish->getNayin($dgz)."　".$cls_rowDish->getNayin($tgz);
        $mingzhum_data = Db::name('sm_ziwei')->where('ziwei_name',$mingzhum)->find();
        $shenzhum_data = Db::name('sm_ziwei')->where('ziwei_name',$shenzhum)->find();
        $data_Array = array(
            'pan'=>$pan,
            'tg'=>$tg,
            'dz'=>$dz,
            'doujunindex'=>$doujunindex,
            'dz_a1'=>$dz[$doujunindex],
            'zidouindex'=>$zidouindex,
            'dz_a2'=>$dz[$zidouindex],
            'mingzhum'=>$mingzhum,
            'mingzhum_data'=>$mingzhum_data,
            'shenzhum'=>$shenzhum,
            'shenzhum_data'=>$shenzhum_data,
            'wxju'=>$wxju,
            'yangdate'=>$yangdate,
            'NongLi'=>$NongLi,
            'quanzao'=>$quanzao,
            'nayinarr'=>$nayinarr,
        );
        return $data_Array;        
    }

    //称骨算命
    public function cg($year,$month,$day,$hour){
        $gu['y1']       = Db::name('Sm_chenggu1')->where('year','=',$year)->find();
        $gu['m1']       = Db::name('Sm_chenggu2')->where('month','=',$month)->find();
        $gu['d1']       = Db::name('sm_chenggu3')->where('day','=',$day)->find();
        $gu['h1']       = Db::name('sm_chenggu4')->where('time','=',$hour)->find();
        $guall          = $gu['y1']['weight']+$gu['m1']['weight']+$gu['d1']['weight']+$gu['h1']['weight'];//称骨完成
        $gu['all_10']   = $guall*10;
        $gu['info']     = Db::name('sm_chenggu')->where('weight','=',$gu['all_10'])->find();//解答
        return $gu;
    }

    public function yuefen($sx){
        $oinfo = Db::name('zodiac_m')->where('sx',$sx)->select();
        return $oinfo;
    }

    

    public function bazism($row){
        $sex_arr        = array(0=>'1',1=>'0',2=>'1');
        $get_bzwh_data  = Sm::get_bzwh($row['xing'],$row['name'],$sex_arr[$row['sex']],$row['year'],$row['month'],$row['day'],$row['hour'],$row['min'],$row['cY'],$row['cM'],$row['cD'],$row['cH'],$row['term1'],$row['term2'],$row['start_term'],$row['end_term'],$row['start_term1'],$row['end_term1'],$row['lDate']);
        $name           = $get_bzwh_data["xm_name"]["xing"] . $get_bzwh_data["xm_name"]["xing"];
        $y              = $get_bzwh_data['age']["y"];
        $m              = $get_bzwh_data['age']["m"];
        $d              = $get_bzwh_data['age']["d"];
        $i              = $get_bzwh_data['age']["i"];
        $term1          = $get_bzwh_data['jieqi']["term1"];
        $term2          = $get_bzwh_data['jieqi']["term2"];
        $sex            = $get_bzwh_data["sex"];
        $start_term     = $get_bzwh_data["start_term"];
        $end_term       = $get_bzwh_data["end_term"];
        $cY             = $get_bzwh_data["cY"];
        $cM             = $get_bzwh_data["cM"];
        $cD             = $get_bzwh_data["cD"];
        $cH             = $get_bzwh_data["cH"];
        //五行数据
        $wh1            = $get_bzwh_data['wh'][0];
        $wh2            = $get_bzwh_data['wh'][1];
        $ssyq_keys      = Db::name('sm_ssyq')->where('id','>',0)->order('keys asc')->group('keys')->column('keys');
        $yq             = [];
        foreach($ssyq_keys as $k=>$v){
            $yq[$k] = Db::name('sm_ssyq')->where('keys',$v)->order('v_keys asc')->column('ssyq');
        }
        $ssjs       = Db::name('sm_ssjs')->where('id','>',0)->order('id asc')->column('ssjs');
        $Gan        = array("甲", "乙", "丙", "丁", "戊", "己", "庚", "辛", "壬", "癸");
        $solarTerm  = array("小寒", "大寒 ", "立春", "雨水 ", "惊蛰", "春分 ", "清明", "谷雨 ", "立夏", "小满 ", "芒种", "夏至 ", "小暑", "大暑 ", "立秋", "处暑 ", "白露", "秋分 ", "寒露", "霜降 ", "立冬", "小雪 ", "大雪", "冬至 ");
        $shiShen    = array("比劫", "比肩", "正印", "偏印", "正官", "七杀", "伤官", "食神", "偏财", "正财");
        $dz         = array(9, 5, 0, 1, 4, 2, 3, 5, 6, 7, 4, 8);
        $dz         = array(9, 5, 0, 1, 4, 2, 3, 5, 6, 7, 4, 8);
        $riGan      = array(array(1, 0, 7, 6, 8, 9, 5, 4, 3, 2), array(0, 1, 6, 7, 9, 8, 4, 5, 2, 3), array(3, 2, 1, 0, 7, 6, 8, 9, 5, 4), array(2, 3, 0, 1, 6, 7, 9, 8, 4, 5), array(5, 4, 3, 2, 1, 0, 7, 6, 8, 9), array(4, 5, 2, 3, 0, 1, 6, 7, 9, 8), array(8, 9, 5, 4, 3, 2, 1, 0, 7, 6), array(9, 8, 4, 5, 2, 3, 0, 1, 6, 7), array(7, 6, 8, 9, 5, 4, 3, 2, 1, 0), array(6, 7, 9, 8, 4, 5, 2, 3, 0, 1));
        if (!$name || !$y || !$m || !$d || !$term1 || !$term2 || !$cY || !$cM || !$cD || !$cH) {
            $term1 = '0/(1月5日12:33)';
            $term2 = '1/(1月20日5:51)';
            $cY = '148';
            $cM = '1368';
            $cD = '41283';
            $cH = '495276';
        }
        $y10 = $cY % 10;
        $m10 = $cM % 10;
        $d10 = $cD % 10;

        $h10 = $cH % 10;
        $y12 = $cY % 12;
        $m12 = $cM % 12;
        $d12 = $cD % 12;
        $h12 = $cH % 12;
        $ywh = floor($cY % 10 / 2);
        $mwh = floor($cM % 10 / 2);
        $dwh = floor($cD % 10 / 2);
        $hwh = floor($cH % 10 / 2);
        $yyy = $y10 % 2;
        if ($yyy == $sex) {
            $termdate = $end_term;
            $dr = $cM + 1;
            $dr_gz = cyclical($dr) . "|" . cyclical($dr + 1) . "&nbsp;" . cyclical($dr + 2) . "|" . cyclical($dr + 3) . "&nbsp;" . cyclical($dr + 4) . "|" . cyclical($dr + 5) . "&nbsp;" . cyclical($dr + 6) . "|" . cyclical($dr + 7);
            $dr_ss = $shiShen[$riGan[$d10][$dr % 10]] . "|" . $shiShen[$riGan[$d10][($dr + 1) % 10]] . "&nbsp;" . $shiShen[$riGan[$d10][($dr + 2) % 10]] . "|" . $shiShen[$riGan[$d10][($dr + 3) % 10]] . "&nbsp;" . $shiShen[$riGan[$d10][($dr + 4) % 10]] . "|" . $shiShen[$riGan[$d10][($dr + 5) % 10]] . "&nbsp;" . $shiShen[$riGan[$d10][($dr + 6) % 10]] . "|" . $shiShen[$riGan[$d10][($dr + 7) % 10]];
        } else {
            $termdate = $start_term;
            $dr = $cM - 1;
            $dr_gz = cyclical($dr) . "|" . cyclical($dr - 1) . "&nbsp;" . cyclical($dr - 2) . "|" . cyclical($dr - 3) . "&nbsp;" . cyclical($dr - 4) . "|" . cyclical($dr - 5) . "&nbsp;" . cyclical($dr - 6) . "|" . cyclical($dr - 7);
            $dr_ss = $shiShen[$riGan[$d10][$dr % 10]] . "|" . $shiShen[$riGan[$d10][($dr - 1) % 10]] . "&nbsp;" . $shiShen[$riGan[$d10][($dr - 2) % 10]] . "|" . $shiShen[$riGan[$d10][($dr - 3) % 10]] . "&nbsp;" . $shiShen[$riGan[$d10][($dr - 4) % 10]] . "|" . $shiShen[$riGan[$d10][($dr - 5) % 10]] . "&nbsp;" . $shiShen[$riGan[$d10][($dr - 6) % 10]] . "|" . $shiShen[$riGan[$d10][($dr - 7) % 10]];
        }

        $dayday = $start_term / 60 / 60 / 24;
        $allday = $termdate / 60 / 12;
        $nnnn =  floor($allday / 360);
        $mmmm = floor(($allday % 360) / 30);
        $dddd =  ceil(($allday % 360) % 30);
        $term_old = $mmmm * 2 + floor(($dayday + $dddd) / 30) * 2 + 0;
        $term_m = $term_old % 24;
        $add_y = floor($term_old / 24);
        $term_mn = $solarTerm[$term_m];
        $term_d = ceil($dayday + ($allday % 360) % 30) % 30;
        $term_m > 5 ? $term_day = 8 : $term_day = 6;
        $gl_m = ($term_m / 2 + 1 + floor(($term_day + $term_d) / 30)) % 12;
        $gl_d = ($term_day + $term_d) % 30;
        $dr_y = $y + $nnnn + $add_y;
        if ($gl_m < 2 or ($gl_m == 2 and $gl_d < 5)){
            $dr_y = $y + $nnnn + $add_y - 1;
        }
        $dr_cY  = $Gan[($dr_y - 1900 + 36) % 10];
        $dr_cY2 = $Gan[($dr_y - 1900 + 36 + 5) % 10];
        $dayundate = "出生后从" . $nnnn . "岁" . $mmmm . "月" . $dddd . "天上运，逢" . $dr_cY . "、" . $dr_cY2 . "年的" . $term_mn . "后第" . $term_d . "日（公历" . $gl_m . "月" . $gl_d . "日前后）交运。在交运期前后您的命运将有重大的改变和吉凶极端的际遇，凡事宜谨慎!";
        
        $yq_y1   = date("Y") - 3;
        $yq_s1   = 0;
        $yq_text = '';
        if ($dr_y >= $yq_y1){
            $yq_y1 += 10;
        }
        if ($dr_y >= $yq_y1){
            $yq_y1 += 10;
        }
        for ($f = $yq_y1; $f < ($yq_y1 + 11); $f++) {
            $yq_s3 = $yq_s1;
            if ($yyy == $sex){
                $yq_gz = $dr + floor(($f - $dr_y) / 10);
            } else  {
                $yq_gz = $dr - floor(($f - $dr_y) / 10);
            }
            if ((($f - $dr_y) % 10) < 5){
                $yq_g = $yq_gz % 10;
            }else{
                $yq_g = $dz[$yq_gz % 12];   
            } 
            $yq_s1 = $riGan[$d10][$yq_g];
            $yq_s2 = $riGan[$d10][($f - 1900 + 36) % 10];
            if ((($f - $dr_y) % 5) == 0 and ($gl_m > 2 or ($gl_m == 2 and $gl_d > 4))) {
                if ($yq_s3){
                    $yq_text .=  $f . "年 2月 4日～" . $f . "年 " . $gl_m . "月 " . $gl_d . "日：" . $yq[$yq_s3][$yq_s2];
                } 
                $yq_text .= $f . "年 " . $gl_m . "月 " . $gl_d . "日～" . ($f + 1) . "年 2月 4日：" . $yq[$yq_s1][$yq_s2]. $ssjs[$yq_s2];
            } else {
                $yq_text .= $f . "年 2月 4日～" . ($f + 1) . "年 2月 4日：" . $yq[$yq_s1][$yq_s2]. $ssjs[$yq_s2];
            }
        }
        $myq_m    = date("m");
        $myq_y    = date("Y");
        $myq_text = '';
        if ($myq_m <= 0) {
            $myq_y--;
            $myq_m += 12;
        }
        $myq_mm = array(0, 6, 4, 6, 5, 5, 6, 7, 8, 8, 8, 7, 7);
        $b1 = $myq_m;
        $myq_y1 = $myq_y;
        for ($b = $myq_m; $b < ($myq_m + 12); $b++) {
            $myq_y3 = $myq_y1;
            if ($b1 > 12) {
                $b1 = $b1 - 12;
                $myq_y3 = $myq_y1;
                $myq_y1++;
            }
            $b2 = $b1 + 1;
            $myq_y2 = $myq_y1;
            if ($b2 > 12) {
                $b2 = $b2 - 12;
                $myq_y2++;
            }
            $myq_ys = $riGan[$d10][($myq_y3 - 1900 + 36) % 10];
            $myq_ms = $riGan[$d10][(($myq_y1 - 1900) * 12 + $b1 + 12) % 10];
            $myq_text .= $myq_y1 . "年 " . $b1 . "月 " . $myq_mm[$b1] . "日～" . $myq_y2 . "年 " . $b2 . "月 " . $myq_mm[$b2] . "日：".$yq[$myq_ys][$myq_ms].$ssjs[$myq_ms];
            $b1++;
        }

        $ganNum = array(array(1.2, 1.2, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.2, 1.2), array(1.06, 1.06, 1.0, 1.0, 1.1, 1.1, 1.14, 1.14, 1.1, 1.1), array(1.14, 1.14, 1.2, 1.2, 1.06, 1.06, 1.0, 1.0, 1.0, 1.0), array(1.2, 1.2, 1.2, 1.2, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0), array(1.1, 1.1, 1.06, 1.06, 1.1, 1.1, 1.1, 1.1, 1.04, 1.04), array(1.0, 1.0, 1.14, 1.14, 1.14, 1.14, 1.06, 1.06, 1.06, 1.06), array(1.0, 1.0, 1.2, 1.2, 1.2, 1.2, 1.0, 1.0, 1.0, 1.0), array(1.04, 1.04, 1.1, 1.1, 1.16, 1.16, 1.1, 1.1, 1.0, 1.0), array(1.06, 1.06, 1.0, 1.0, 1.0, 1.0, 1.14, 1.14, 1.2, 1.2), array(1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.2, 1.2, 1.2, 1.2), array(1.0, 1.0, 1.04, 1.04, 1.14, 1.14, 1.16, 1.16, 1.06, 1.06), array(1.2, 1.2, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.14, 1.14));
        $whNum  = array(0, 0, 0, 0, 0);
        $yGanNum = $ganNum[$m12][$y10];
        $mGanNum = $ganNum[$m12][$m10];
        $dGanNum = $ganNum[$m12][$d10];
        $hGanNum = $ganNum[$m12][$h10];
        $whNum[$ywh] += $yGanNum;
        $whNum[$mwh] += $mGanNum;
        $whNum[$dwh] += $dGanNum;
        $whNum[$hwh] += $hGanNum;
        $y_whNum = zhi2ganSN($y12,$m12,$whNum);
        $m_whNum = zhi2ganSN($m12,$m12,$y_whNum);
        $d_whNum = zhi2ganSN($d12,$m12,$m_whNum);
        $whNum   = zhi2ganSN($h12,$m12,$d_whNum);
        $whNum[0] *= 10;
        $whNum[1] *= 10;
        $whNum[2] *= 10;
        $whNum[3] *= 10;
        $whNum[4] *= 10;

        $wh1    = "同类：";
        $wh1Num = 0;
        $whh1   = "";
        $whn1   = 0;
        $wh2    = "异类：";
        $wh2Num = 0;
        $whh2   = "";
        $whn2   = 0;
        $zzz1   = 0;
        $zzz2   = 0;
        for ($i=0; $i<5; $i++) {
             $wh_data = whwh($i,$riGan, $d10, $whNum, $wh1, $wh1Num, $wh2, $wh2Num, $whh1, $whn1, $whh2, $whn2, $zzz1, $zzz2); 
             if(!empty($wh_data)){
                 $wh1    = $wh_data['wh1'];
                 $wh1Num = $wh_data['wh1Num'];
                 $whh1   = $wh_data['whh1'];
                 $whn1   = $wh_data['whn1'];
                 $zzz1   = $wh_data['zzz1'];
                 $wh2    = $wh_data['wh2'];
                 $wh2Num = $wh_data['wh2Num'];
                 $whh2   = $wh_data['whh2'];
                 $whn2   = $wh_data['whn2'];
                 $zzz2   = $wh_data['zzz2'];
             }
        }
        $wh12Num = $wh1Num - $wh2Num;
        if ($wh12Num > 7){
            $wh123 = "八字过硬";
        }elseif ($wh12Num < -7) {
            $wh123 = "八字过弱";
        }else {
            $wh123 = "八字比较平衡";
        }
        if($wh12Num > 0){
            $whh = $whh2;
        }elseif($wh12Num < 0){
            $whh = $whh1;
        }else{
            if($whh2 > $whh1){
                $whh = $whh1;
            }else{
                $whh = $whh2;
            } 
        }
        $result = ['dr_gz'=>$dr_gz,'dr_ss'=>$dr_ss,'dayundate'=>$dayundate,'dayundate'=>$dayundate,'yq_text'=>$yq_text,'yq_text'=>$yq_text,'myq_text'=>$myq_text,'wh123'=>$wh123,'whh'=>$whh];
        return $result;
    }
   

}

