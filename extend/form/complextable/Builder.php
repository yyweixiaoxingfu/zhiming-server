<?php
//

namespace form\complexTable;

/**
 * Class Builder
 * @package form\complex_table
 */
class Builder
{
    /**
     * 显示表格
     * @param string $name 表单项名
     * @param string $title 标题
     * @param array $data 数据
     * @param bool $header 是否有表头
     * @author  
     * @return array
     */
    public function item($name = '', $title = '', $data = [], $header = false)
    {
        $head = [];
        $cols = 1;

        if (true === $header) {
            $header = array_shift($data);
            $header = $header === null ? [] : [$header];
        }

        if ($header) {
            foreach ($header as $row) {
                $cols = count($row) > $cols ? count($row) : $cols;
                foreach ($row as $k => $v) {
                    $head[0][] = $this->parseCell($v);
                }
            }
        }

        if (!empty($data)) {
            foreach ($data as $key => $row) {
                foreach ($row as $k => $v) {
                    if (is_array($v)) {
                        // 是数组，表示表格中的表格
                        if (is_string(end($v))) { // 数组最后一个元素是字符串，则表示合并行和合并列的参数
                            $merge   = explode(':', end($v));
                            $colspan = $merge[0];
                            $rowspan = isset($merge[1]) ? $merge[1] : '';
                            array_pop($v);
                        } else {
                            $rowspan = '';
                            $colspan = '';
                        }

                        $data[$key][$k] = [
                            'value'   => $v,
                            'rowspan' => $rowspan,
                            'colspan' => $colspan,
                        ];
                    } else {
                        $data[$key][$k] = $this->parseCell($v);
                    }
                }
            }
        }

        return [
            'name'  => $name,
            'title' => $title,
            'data'  => $data,
            'head'  => $head,
            'cols'  => $cols
        ];
    }

    /**
     * 分析单元格合并
     * @param $v
     * @return array
     * @author  
     */
    private function parseCell($v)
    {
        if (preg_match('/\[(.*)\]/', $v, $matches)) {
            $cell  = str_replace($matches[0], '', $v);
            $merge = explode(':', $matches[1]);
            $result = [
                'value'   => $cell,
                'colspan' => $merge[0],
                'rowspan' => isset($merge[1]) ? $merge[1] : '',
            ];
        } else {
            $result = [
                'value'   => $v,
                'rowspan' => '',
                'colspan' => '',
            ];
        }

        return $result;
    }

    /**
     * @var array 需要加载的css
     */
    public $css = [
        'complextable.css'
    ];
}