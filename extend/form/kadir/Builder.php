<?php
//

namespace form\kadir;

/**
 * Class Builder
 * @package form\selectTable
 */
class Builder
{
    /**
     * 显示表格
     * @param string $name 表单项名
     * @param string $title 标题
     * @param string $tips 提示
     * @param array $columns 表格列头
     * @param array $data 表格数据
     * @param string $url 选择内容的url
     * @author  
     * @return array
     */
    public function item($name = '', $title = '', $tips = '', $fields = [],$spece=[], $data = [])
    {
        foreach ($fields as $key => $field) {
            if (is_string($field)) {
                $fields[$key] = [
                    'type'  => 'text',
                    'title' => $field,
                ];
            }
        }
        if($spece){
            foreach ($spece as $kp=>$spe){
                if(isset($spe['sku_item_value']) && $spe['sku_item_value']){
                    $spece[$kp]['sku_item_value_str'] = implode(',',$spece[$kp]['sku_item_value']);
                }
            }
        }
        if($data){
            foreach ($data as $ks=>$item){
                if(isset($item['sku_item_name']) && $item['sku_item_name']){
                    $data[$ks]['sku_item_name_value'] = implode(',',$data[$ks]['sku_item_name']);
                    $data[$ks]['sku_item_id'] = implode('_',$data[$ks]['sku_item_name']);
                }
            }
        }
        return [
            'name'   => $name,
            'title'  => $title,
            'tips'   => $tips,
            'fields' => $fields,
            'spece'  => $spece,
            'data'   => $data
        ];
    }
    /**
     * @var array 需要加载的js
     */
    public $js = [
        '__LIBS__/jquery-ui/jquery-ui.min.js',
        'kadir.js',
    ];

    /**
     * @var array 需要加载的css
     */
    public $css = [
        'kadir.css',
    ];
}
