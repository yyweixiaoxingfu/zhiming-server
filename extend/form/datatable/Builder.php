<?php
//

namespace form\dataTable;

/**
 * Class Builder
 * @package form\dataTable
 */
class Builder
{
    /**
     * 显示表格
     * @param string $name 表单项名
     * @param string $title 标题
     * @param string $tips 提示
     * @param array $fields 字段
     * @param array $data 数据
     * @author  
     * @return array
     */
    public function item($name = '', $title = '', $tips = '', $fields = [], $data = [], $is_new = true)
    {
        foreach ($fields as $key => $field) {
            if (is_string($field)) {
                $fields[$key] = [
                    'type'  => 'text',
                    'title' => $field,
                ];
            }elseif ($field['type'] == 'time'){
                $fields[$key] = [
                    'type' => $field['type'],
                    'title' => $field['title'],
                    'format' => $field['format'] ?? 'HH:mm:ss',
                    'extra_class' => $field['extra_class'] ?? '',
                    'extra_attr' => $field['extra_attr'] ?? '',
                ];
            }elseif ($field['type'] == 'date'){
                $fields[$key] = [
                    'type' => $field['type'],
                    'title' => $field['title'],
                    'format' => $field['format'] ?? 'yyyy-mm-dd',
                    'extra_class' => $field['extra_class'] ?? '',
                    'extra_attr' => $field['extra_attr'] ?? '',
                ];
            }elseif($field['type'] == 'select_ajax' || $field['type'] == 'selectAjax'){
                if (is_array($field['params'])) {
                    $url = url('admin/ajax/getSelectAjax');
                    $token = $this->createSelectAjaxToken($field['params']);
                } else {
                    $token = '';
                    $url   = $field['params'];
                }
                $multiple = false;
                if ((isset($field['multiple']) && $field['multiple']) || (!empty($field['extra_attr']) && in_array('multiple', explode(' ', $field['extra_attr'])))){
                    $multiple = true;
                }
                $fields[$key] = [
                    'type' => 'select_ajax',
                    'title' => $field['title'],
                    'url' => $url,
                    'extra_attr' => $field['extra_attr'] ?? '',
                    'multiple' => $multiple,
                    'token' => $token,
                ];
            }
        }
        return [
            'name'   => $name,
            'title'  => $title,
            'tips'   => $tips,
            'fields' => $fields,
            'data'   => $data,
            'is_new' => $is_new,
        ];
    }

    /**
     * @var array 需要加载的js
     */
    public $js = [
        "__LIBS__/bootstrap-datetimepicker/moment.min.js",
        "__LIBS__/bootstrap-datetimepicker/bootstrap-datetimepicker.min.js",
        "__LIBS__/bootstrap-datetimepicker/locale/zh-cn.js",
        "__LIBS__/bootstrap-datepicker/bootstrap-datepicker.min.js",
        "__LIBS__/bootstrap-datepicker/locales/bootstrap-datepicker.zh-CN.min.js",
        '__STATIC__/libs/select2/select2.full.min.js',
        '__STATIC__/libs/select2/i18n/zh-CN.js',
        'datatable.js',
    ];

    /**
     * @var array 需要加载的css
     */
    public $css = [
        "__LIBS__/bootstrap-datetimepicker/bootstrap-datetimepicker.min.css",
        '__STATIC__/libs/select2/select2.min.css',
        '__STATIC__/libs/select2/select2-bootstrap.min.css',
        "__LIBS__/bootstrap-datepicker/bootstrap-datepicker3.min.css",
        'datatable.css',
    ];

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