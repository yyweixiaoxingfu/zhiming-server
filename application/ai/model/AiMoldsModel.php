<?php
namespace app\ai\model;

use think\Model;

/**
 * 智能模型
 */
class AiMoldsModel extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $name = 'ai_molds';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    public $model_type = [
            'openai'                => 'OpenAI官方',
            'claude'                => 'Claude官方',
            'bard'                  => '谷歌Bard',
            'azure'                 => '微软Azure',
            'chimeragpt'            => '免费国外接口',
            '文心千帆'               => '文心千帆',
            '通义千问'               => '通义千问',
            '腾讯混元'               => '腾讯混元',
            '清华智谱'               => '清华智谱',
            '讯飞星火'               => '讯飞星火',
            '360智脑'               => '360智脑',
            '百川智能'               => '百川智能',
            '月之暗面'               => '月之暗面',
            '火山方舟'               => '火山方舟',
            'MiniMax'               => 'MiniMax',
            '零一万物'               => '零一万物',
            'DeepSeek'              =>'DeepSeek',
            '无问芯穹'               =>'无问芯穹',
            '硅基流动'               =>'硅基流动',
            'midjourney'            =>'MidJourney',
            'stablediffusion'       =>'StableDiffusion',
            'alistablediffusion'    =>'阿里StableDiffusion',
            'LinkAI'                =>'LinkAI',
            '本地知识库'             =>'本地知识库'
        ];
    public $image_type = [0=>'文本模型',1=>'仅画图',2=>'仅改图',3=>'画图+改图',4=>'仅识图',5=>'画图+识图',6=>'改图+识图',7=>'画图+改图+识图'];
    
    /**
     * 获取所有有效模型
     * @return array
     */
    public static function getValidModels()
    {
        return self::where('isvalid', 1)->order('sequenceid asc,isimage asc,id asc')->select();
    }
    
    /**
     * 获取默认模型
     * @return array|false
     */
    public static function getDefaultModel()
    {
        return self::where('isvalid', 1)->order('sequenceid asc')->find();
    }
    
    /**
     * 根据ID获取模型
     * @param int $id 模型ID
     * @return array|false
     */
    public static function getModelById($id)
    {
        return self::where('id', $id)->find();
    }
    
    /**
     * 根据模型值获取模型
     * @param string $modelValue 模型值
     * @return array|false
     */
    public static function getModelByValue($modelValue)
    {
        return self::where('modelvalue', $modelValue)->where('isvalid', 1)->find();
    }
    
    /**
     * 获取模型类型列表
     * @return array
     */
    public static function getModelTypes()
    {
        $model = new self();
        return $model->model_type;
    }
    
    /**
     * 获取图片类型列表
     * @return array
     */
    public static function getImageTypes()
    {
        $model = new self();
        return $model->image_type;
    }
}
