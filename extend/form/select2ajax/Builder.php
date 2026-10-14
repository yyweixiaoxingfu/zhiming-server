<?php

namespace form\select2ajax;

class Builder
{
    /**
     * 取色器
     * @param string $name 表单项名
     * @param string $title 标题
     * @param string $tips 提示
     * @param string $default 默认值
     * @param string $ajax_url ajax_url
     * @param string $extra_attr 额外属性
     * @param string $extra_class 额外css类名
     * @return mixed
     */
    public function item($name = '', $title = '', $tips = '', $options = [], $default = '',$extra_attr = '', $extra_class = '')
    {
        $multiple = false;
        $ajax_url = url('admin/ajax/getSelectAjax');
        if ($extra_attr != '' && in_array('multiple', explode(' ', $extra_attr))) {
            $multiple = true;
        }
        if (is_array($options)) {
            $options['multiple'] = $multiple;
            $token = $this->createSelectAjaxToken($options);
        } else {
            $token = '';
            $ajax_url   = $options;
        }

        $placeholder = $multiple ? '请选择一项或多项' : '请选择一项';
        if (preg_match('/(.*)\[:(.*)\]/', $title, $matches)) {
            $title       = $matches[1];
            $placeholder = $matches[2];
        }
        return [
            'name'     => $name,
            'title'    => $title,
            'tips'     => $tips,
            'value'    => $default,
            'options'  => $options,
            'ajax_url' => $ajax_url,
            'extra_class' => $extra_class,
            'extra_attr'  => $extra_attr,
            'placeholder' => $placeholder,
            'multiple'    => $multiple,
            'token'       => $token,
        ];
    }

    /**
     * @var array 需要加载的js
     */
    public $js = [
        '__STATIC__/libs/select2/select2.full.min.js',
        '__STATIC__/libs/select2/i18n/zh-CN.js',
        'init.js'
    ];

    /**
     * @var array 需要加载的css
     */
    public $css = [
        '__STATIC__/libs/select2/select2.css',
        '__STATIC__/admin/css/oneui.css',
//        '__STATIC__/libs/select2/select2-bootstrap.min.css',
    ];

    /**
     * 创建SelectAjax Token
     * @param array $params 参数
     * @author  
     * @return bool|string
     */
    private function createSelectAjaxToken($params = [])
    {
        // 排序
        ksort($params);
        $code = http_build_query($params);
        $table_token = substr(sha1($code), 0, 8);
        session($table_token, $params);
        return $table_token;
    }
}