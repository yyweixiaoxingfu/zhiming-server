<?php
//

namespace form\selectTable;

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
    public function item($name = '', $title = '', $tips = '', $columns = [], $data = [], $url = '')
    {
        return [
            'name'    => $name,
            'title'   => $title,
            'tips'    => $tips,
            'columns' => $columns,
            'data'    => $data,
            'url'     => $url,
        ];
    }

    /**
     * @var array 需要加载的js
     */
    public $js = [
        '__LIBS__/jquery-ui/jquery-ui.min.js',
        'selecttable.js',
    ];

    /**
     * @var array 需要加载的css
     */
    public $css = [];
}
