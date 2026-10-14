<?php
namespace form\tmap;

class Builder
{
    public function item($name = '', $title = '', $ak = '', $tips = '', $default = '', $address = '', $level = '', $extra_class = '')
    {
        return [
            'type'        => 'tmap',
            'name'        => $name,
            'title'       => $title,
            'ak'          => $ak,
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
        'tmap.js'
    ];

    /**
     * @var array 需要加载的css
     */
    public $css = [

    ];

}