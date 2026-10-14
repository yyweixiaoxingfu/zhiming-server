<?php
//

namespace form\selectGroup;

/**
 * Class Builder
 * @package form\selectGroup
 */
class Builder
{
    /**
     * 添加下拉分组菜单
     * @param string $name 下拉菜单名
     * @param string $title 标题
     * @param string $tips 提示
     * @param array $options 选项
     * @param string $default 默认值
     * @param string $extra_attr 额外属性
     * @param string $extra_class 额外css类名
     * @author  
     * @return mixed
     */
    public function item($name = '', $title = '', $tips = '', $options = [], $default = '', $extra_attr = '', $extra_class = '')
    {
        $multiple = false;

        if ($extra_attr != '' && in_array('multiple', explode(' ', $extra_attr))) {
            $multiple = true;
        }

        $placeholder = $multiple ? '请选择一项或多项' : '请选择一项';
        if (preg_match('/(.*)\[:(.*)\]/', $title, $matches)) {
            $title       = $matches[1];
            $placeholder = $matches[2];
        }

        return [
            'name'        => $name,
            'title'       => $title,
            'tips'        => $tips,
            'options'     => $options,
            'value'       => $default,
            'extra_class' => $extra_class,
            'extra_attr'  => $extra_attr,
            'placeholder' => $placeholder,
            'multiple'    => $multiple,
        ];
    }

    /**
     * @var array 需要加载的js
     */
    public $js = [
        "__LIBS__/select2/select2.full.min.js",
        "__LIBS__/select2/i18n/zh-CN.js",
        "selectgroup.js",
    ];

    /**
     * @var array 需要加载的css
     */
    public $css = [
        "__LIBS__/select2/select2.min.css",
        "__LIBS__/select2/select2-bootstrap.min.css",
    ];
}