<?php
namespace form\amap;

class Builder
{
    public function item($name = '', $title = '', $ak = '',$secret = '', $tips = '', $default = '', $address = '', $level = '', $extra_class = '')
    {
        return [
            'type'        => 'amap',
            'name'        => $name,
            'title'       => $title,
            'ak'          => $ak,
            'secret'      => $secret,
            'tips'        => $tips,
            'value'       => $default,
            'address'     => $address,
            'level'       => $level == '' ? 12 : $level,
            'extra_class' => $extra_class,
        ];
    }

    /**
     * @var array 需要加载的js
     */
    public $js = [
        'amap.js'
    ];

    /**
     * @var array 需要加载的css
     */
    public $css = [

    ];

}