<?php

namespace app\page\model;

use think\Model;
/**
 * 会员账户流水
 */
class PageModel extends Model
{
    // 定义表名
    protected $name = 'page';
    // 定义主键
    protected $pk = 'page_id';

     /**
     * 页面标题栏默认数据
     * @return array
     */
    public function getDefaultPage(): array
    {
        return [
            'params' => [
                'name' => '页面名称',
                'title' => '页面标题',
                'shareTitle' => '分享标题'
            ],
            'style' => [
                'titleTextColor' => 'black',
                'titleBackgroundColor' => '#ffffff',
            ]
        ];
    }

    /**
     * 店铺页面元素默认数据
     * @return array
     */
    public function getDefaultItems(): array
    {
        return [
            'search' => [
                'name' => '搜索框',
                'type' => 'search',
                'params' => ['placeholder' => '请输入关键字进行搜索', 'sticky' => false],
                'style' => [
                    // 文字对齐方式
                    'textAlign' => 'left',
                    // 搜索框样式 (square方形 radius圆角 round圆弧)
                    'searchStyle' => 'round',
                    // 搜索框背景颜色
                    'searchBg' => '#ffffff',
                    // 搜索框文字颜色
                    'searchFontColor' => '#999999',
                    // 组件背景颜色
                    'background' => '#f1f1f2',
                    // 上下边距
                    'paddingY' => 10,
                    // 左右边距
                    'paddingX' => 10,
                ]
            ],
            'banner' => [
                'name' => '轮播图',
                'type' => 'banner',
                'style' => [
                    'paddingTop' => 0,
                    'paddingLeft' => 0,
                    'background' => '#ffffff',
                    'btnShape' => 'round',          // 指示点形状
                    'btnColor' => '#ffffff',        // 指示点颜色
                    'interval' => 3,                // 切换时间
                    'borderRadius' => 0,            // 圆角尺寸
                ],
                'data' => [
                    [
                        'imgUrl' => base_url() . 'static/diy/banner/01.png',
                        'imgName' => 'image-1.jpg',
                        'link' => null
                    ],
                    [
                        'imgUrl' => base_url() . 'static/diy/banner/01.png',
                        'imgName' => 'image-1.jpg',
                        'link' => null
                    ]
                ]
            ],
            'image' => [
                'name' => '图片组',
                'type' => 'image',
                'style' => [
                    'paddingTop' => 0,
                    'paddingLeft' => 0,
                    'background' => '#ffffff',
                    'borderRadius' => 0,             // 圆角尺寸
                    'itemMargin' => 0,               // 图片间距
                ],
                'data' => [
                    [
                        'imgUrl' => base_url() . 'static/diy/banner/01.png',
                        'imgName' => 'image-1.jpg',
                        'link' => null
                    ]
                ]
            ],
            'navBar' => [
                'name' => '导航组',
                'type' => 'navBar',
                'style' => [
                    'rowsNum' => 4,
                    'background' => '#ffffff',
                    'paddingTop' => 10,
                    // 文字颜色
                    'textColor' => '#666666',
                    // 图片大小
                    'imageSize' => 44,
                ],
                'data' => [
                    [
                        'imgUrl' => base_url() . 'static/diy/navbar/01.png',
                        'imgName' => 'icon-1.png',
                        'link' => null,
                        'text' => '按钮文字1'
                    ],
                    [
                        'imgUrl' => base_url() . 'static/diy/navbar/01.png',
                        'imgName' => 'icon-2.jpg',
                        'link' => null,
                        'text' => '按钮文字2'
                    ],
                    [
                        'imgUrl' => base_url() . 'static/diy/navbar/01.png',
                        'imgName' => 'icon-3.jpg',
                        'link' => null,
                        'text' => '按钮文字3'
                    ],
                    [
                        'imgUrl' => base_url() . 'static/diy/navbar/01.png',
                        'imgName' => 'icon-4.jpg',
                        'link' => null,
                        'text' => '按钮文字4'
                    ]
                ]
            ],
            'blank' => [
                'name' => '辅助空白',
                'type' => 'blank',
                'style' => [
                    'height' => 20,
                    'background' => '#ffffff'
                ]
            ],
            'guide' => [
                'name' => '辅助线',
                'type' => 'guide',
                'style' => [
                    'background' => '#ffffff',
                    'lineStyle' => 'solid',
                    'lineHeight' => 1,
                    'lineColor' => "#000000",
                    'paddingTop' => 10
                ]
            ],
            'video' => [
                'name' => '视频组',
                'type' => 'video',
                'params' => [
                    'videoUrl' => 'http://wxsnsdy.tc.qq.com/105/20210/snsdyvideodownload?filekey=30280201010421301f0201690402534804102ca905ce620b1241b726bc41dcff44e00204012882540400.mp4',
                    'poster' => base_url() . 'static/diy/video_poster.png',
                    'autoplay' => 0
                ],
                'style' => [
                    'background' => '#ffffff',
                    'paddingTop' => 10,
                    'paddingLeft' => 10,
                    'height' => 202
                ]
            ],
            'article' => [
                'name' => '文章组',
                'type' => 'article',
                'params' => [
                    'source' => 'auto',         // 数据来源 (choice手动选择 auto自动获取)
                    'auto' => [
                        'category' => -1,   // -1全部
                        'showNum' => 6
                    ]
                ],
                // '自动获取' => 默认数据
                'defaultData' => [
                    [
                        'title' => '此处显示文章标题',
                        'show_type' => 10,
                        'image' => base_url() . 'static/diy/article/01.png',
                        'views_num' => 309
                    ],
                    [
                        'title' => '此处显示文章标题',
                        'show_type' => 10,
                        'image' => base_url() . 'static/diy/article/01.png',
                        'views_num' => 309
                    ]
                ],
                // '手动选择' => 默认数据
                'data' => []
            ],
            'notice' => [
                'name' => '店铺公告',
                'type' => 'notice',
                'params' => [
                    'text' => '这里是第一条自定义公告的标题', // 公告内容
                    // 'icon' => base_url() . 'static/diy/notice.png',
                    'link' => null,     // 链接
                    'showIcon' => true, // 是否显示图标
                    'scrollable' => true    // 是否滚动
                ],
                'style' => [
                    'paddingTop' => 0,
                    'background' => '#fffbe8',
                    // 文字颜色
                    'textColor' => '#de8c17',
                    // 文字大小
                    'fontSize' => 14,
                ]
            ],
            'richText' => [
                'name' => '富文本',
                'type' => 'richText',
                'params' => [
                    'content' => '<p>这里是文本的内容</p>'
                ],
                'style' => [
                    'paddingTop' => 0,
                    'paddingLeft' => 0,
                    'background' => '#ffffff'
                ]
            ],
            'window' => [
                'name' => '图片橱窗',
                'type' => 'window',
                'style' => [
                    'paddingTop' => 0,
                    'paddingLeft' => 0,
                    'background' => '#ffffff',
                    'layout' => 2   // 布局方式: -1橱窗
                ],
                'data' => [
                    [
                        'imgUrl' => base_url() . 'static/diy/window/01.jpg',
                        'link' => null
                    ],
                    [
                        'imgUrl' => base_url() . 'static/diy/window/02.jpg',
                        'link' => null
                    ],
                    [
                        'imgUrl' => base_url() . 'static/diy/window/03.jpg',
                        'link' => null
                    ],
                    [
                        'imgUrl' => base_url() . 'static/diy/window/04.jpg',
                        'link' => null
                    ]
                ],
                'dataNum' => 4
            ],
            'hotZone' => [
                'name' => '热区组',
                'type' => 'hotZone',
                'style' => [
                    'paddingTop' => 0,
                    'paddingLeft' => 0,
                    'background' => '#ffffff',
                    'borderRadius' => 0,             // 圆角尺寸
                ],
                'data' => [
                    'imgUrl' => base_url() . 'static/diy/banner/01.png',
                    'imgName' => 'image-1.jpg',
                    'maps' => [
                        [
                            'width' => 300,
                            'height' => 100,
                            'left' => 0,
                            'top' => 0,
                            'link' => null,
                            'key' => 1
                        ],
                    ]
                ]
            ],
            'goods' => [
                'name' => '商品组',
                'type' => 'goods',
                'params' => [
                    'source' => 'auto',         // 数据来源 (choice手动选择 auto自动获取)
                    'auto' => [
                        'category' => 0,        // 商品分类 0为全部
                        'goodsSort' => 'all',   // 商品排序 (all默认 sales销量 price价格)
                        'showNum' => 6          // 显示数量
                    ]
                ],
                'style' => [
                    'display' => 'list',        // 显示类型 (list列表平铺 slide横向滑动)
                    'column' => 2,              // 分列数量
                    'show' => [                 // 显示内容
                        'goodsName', 'goodsPrice', 'linePrice', 'sellingPoint', 'goodsSales', 'cartBtn'
                    ],
                    'priceColor' => '#ff1051',       // 商品价格颜色
                    'sellingColor' => '#e3771f',     // 商品卖点颜色
                    'goodsNameRows' => 'two',        // 商品名称行数 (one单行 two双行)
                    'btnCartStyle' => 1,             // 购物车按钮风格
                    'btnCartColor' => '#27c29a',     // 购物车按钮颜色
                    // 'btnFontColor' => '#ffffff',     // 购物车按钮文字颜色
                    'cardType' => 'card',            // 卡片样式：flat扁平  card卡片
                    'borderRadius' => 10,            // 圆角尺寸
                    'itemMargin' => 10,              // 商品间距
                    'background' => '#f6f6f6',       // 背景颜色
                    'paddingY' => 12,                // 上下边距
                    'paddingX' => 12,                // 左右边距
                ],
                // '自动获取' => 默认数据
                'defaultData' => [
                    [
                        'goods_name' => '此处显示商品名称',
                        'goods_image' => base_url() . 'static/diy/goods/01.png',
                        'goods_price_min' => '99.00',
                        'line_price_min' => '139.00',
                        'selling_point' => '此款商品美观大方 不容错过',
                        'goods_sales' => 100,
                    ],
                    [
                        'goods_name' => '此处显示商品名称',
                        'goods_image' => base_url() . 'static/diy/goods/01.png',
                        'goods_price_min' => '99.00',
                        'line_price_min' => '139.00',
                        'selling_point' => '此款商品美观大方 不容错过',
                        'goods_sales' => 100,
                    ],
                    [
                        'goods_name' => '此处显示商品名称',
                        'goods_image' => base_url() . 'static/diy/goods/01.png',
                        'goods_price_min' => '99.00',
                        'line_price_min' => '139.00',
                        'selling_point' => '此款商品美观大方 不容错过',
                        'goods_sales' => 100,
                    ],
                    [
                        'goods_name' => '此处显示商品名称',
                        'goods_image' => base_url() . 'static/diy/goods/01.png',
                        'goods_price_min' => '99.00',
                        'line_price_min' => '139.00',
                        'selling_point' => '此款商品美观大方 不容错过',
                        'goods_sales' => 100,
                    ]
                ],
                // '手动选择' => 默认数据
                'data' => []
            ],
            'service' => [
                'name' => '在线客服',
                'type' => 'service',
                'params' => [
                    'type' => 'chat',     // '客服类型' => chat在线聊天，phone拨打电话
                    'image' => base_url() . 'static/diy/service.png',
                    'tel' => '' // 电话号吗
                ],
                'style' => [
                    'right' => 1,
                    'bottom' => 10,
                    'opacity' => 100
                ]
            ],
            'officialAccount' => [
                'name' => '关注公众号',
                'type' => 'officialAccount',
                'params' => [],
                'style' => []
            ],
            'coupon' => [
                'name' => '优惠券',
                'type' => 'coupon',
                'style' => [
                    'paddingTop' => 10,
                    'background' => '#ffffff',
                    'marginRight' => 20,                // 左右间距
                    'couponBgColor' => '#ffa708',       // 优惠券背景颜色
                    'couponTextColor' => '#ffffff',     // 优惠券文字颜色
                    'receiveBgColor' => '#717070',      // 领取按钮颜色
                    'receiveTextColor' => '#ffffff',    // 领取文字颜色
                ],
                'params' => [
                    'source' => 'auto',         // 数据来源 (choice手动选择 auto自动获取)
                    'showNum' => 5
                ],
                // '自动获取' => 默认数据
                'defaultData' => [
                    [
                        'min_price' => '100.00',
                        'coupon_type' => 10,
                        'reduce_price' => '10',
                        'state' => ['text' => '正常', 'value' => 1]
                    ],
                    [
                        'min_price' => '200.00',
                        'coupon_type' => 10,
                        'reduce_price' => '20',
                        'state' => ['text' => '正常', 'value' => 1]
                    ],
                    [
                        'min_price' => '300.00',
                        'coupon_type' => 10,
                        'reduce_price' => '30',
                        'state' => ['text' => '正常', 'value' => 1]
                    ]
                ],
                // '手动选择' => 默认数据
                'data' => []
            ],
            'special' => [
                'name' => '头条快报',
                'type' => 'special',
                'params' => [
                    'source' => 'auto',
                    'auto' => [
                        'category' => -1, // 文章分类ID -1为全部
                        'showNum' => 6
                    ],
                    'display' => 1, // 显示行数
                    'image' => base_url() . 'static/diy/special.png',
                ],
                'style' => [
                    'background' => '#ffffff', // 背景颜色
                    'textColor' => '#141414', // 文字颜色
                    'paddingTop' => 0, // 上下边距
                ],
                // '自动获取' => 默认数据
                'defaultData' => [
                    ['title' => '张小龙4小时演讲：你和高手之间，隔着“简单”二字'],
                    ['title' => '张小龙4小时演讲：你和高手之间，隔着“简单”二字']
                ],
                // '手动选择' => 默认数据
                'data' => []
            ],
            'ICPLicense' => [
                'name' => '备案号',
                'type' => 'ICPLicense',
                'params' => [
                    'text' => '网站备案号：粤ICP备10000000号-1',
                    'link' => 'https://beian.miit.gov.cn/',
                ],
                'style' => [
                    'fontSize' => '13',         // 文字大小
                    'textAlign' => 'center',    // 文字对齐
                    'textColor' => '#696969',   // 文字颜色
                    'paddingTop' => 6,          // 上下边距
                    'paddingLeft' => 0,         // 左右边距
                    'background' => '#ffffff',  // 背景颜色
                ]
            ],
            'title' => [
                'name' => '标题文本',
                'type' => 'title',
                'params' => [
                    'title' => '这是标题',               // 标题文字内容
                    'desc' => '这里是描述',              // 描述文字内容
                    'titleFontSize' => 15,             // 标题大小: 12px-18px
                    'titleFontWeight' => 'bold',       // 标题字体: normal常规 bold加粗
                    'descFontSize' => 12,              // 描述大小: 12px-16px
                    'descFontWeight' => 'normal',      // 描述字体: normal常规 bold加粗
                    'more' => [
                        'enable' => true,              // 是否显示查看更多
                        'text' => '查看更多',           // 文字内容
                        'enableIcon' => true,         // 是否显示箭头图标
                        'link' => null,               // 跳转链接
                    ]
                ],
                'style' => [
                    // 'textAlign' => 'left',           // 显示位置: left居左、center居中
                    'titleTextColor' => '#323233',      // 标题文字颜色
                    'descTextColor' => '#969799',       // 描述文字颜色
                    'moreTextColor' => '#969799',       // 更多文字颜色
                    'background' => '#ffffff',          // 背景颜色
                    'paddingY' => 8,                    // 上下边距
                ]
            ],
        ];
    }
}
