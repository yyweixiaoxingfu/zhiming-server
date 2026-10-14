$(function(){
    $('.js-amap').each(function () {
        var $self         = $(this);
        var map_canvas    = $self.find('.amap').attr('id');
        var address       = $self.find('.amap-address');
        var address_id    = address.attr('id');
        var map_point     = $self.find('.amap-point');
        var search_result = $self.find('.searchResultPanel');
        var point_lng     = 116.331398;
        var point_lat     = 39.897445;
        var map_level     = $self.data('level');
        // 高德地图API功能
        var map = new AMap.Map(map_canvas, {
            viewMode: '2D', // 默认使用 2D 模式，如果希望使用带有俯仰角的 3D 模式，请设置 viewMode: '3D',
            zoom:11, //初始化地图层级
            resizeEnable: true,
        });
        // 添加地图控件
        AMap.plugin('AMap.Scale,AMap.ToolBar,AMap.ControlBar',function(){//异步加载插件
            map.addControl(new AMap.Scale());
            map.addControl(new AMap.ToolBar({
                position: {
                    top: '110px',
                    right: '40px'
                }
            }));
            map.addControl(new AMap.ControlBar({
                position: {
                    top: '10px',
                    right: '10px',
                }
            }));
        });

        // 监听点击地图时间
        map.on('click', function (e) {
            console.log(e.lnglat);
            // 创建标注
            create_mark(e.lnglat);
        });
        // 创建标注
        var marker = null;
        var create_mark = function (point) {
            clearMarker();
            marker = new AMap.Marker({
                icon: "//a.amap.com/jsapi_demos/static/demo-center/icons/poi-marker-red.png",
                position: [point.lng, point.lat]
            });
            marker.setMap(map);
            // 写入坐标
            map_point.val(point.lng + "," + point.lat);
        };
        function clearMarker() {
            if (marker) {
                marker.setMap(null);
                marker = null;
            }
        }

        // 智能搜索
        var placeSearch = function(add_value){
            AMap.plugin('AMap.PlaceSearch', function(){
                var placeSearch = new AMap.PlaceSearch();
                placeSearch.search(add_value, function (status, result) {
                    // 查询成功时，result即对应匹配的POI信息
                    point = result.poiList.pois[0].location;    //获取第一个智能搜索的结果
                    map.setCenter(point);
                    // 创建标注
                    create_mark(point);
                });
            });
        }

        // 建立一个自动完成的对象
        AMap.plugin('AMap.AutoComplete', function(){
            var auto = new AMap.AutoComplete({
                input: address_id
            });
            //注册监听，当选中某条记录时会触发
            auto.on("select", function (e) {
                point = e.poi.location;
                map.setCenter(point)
                create_mark(point)
            });
        });


        // 初始化地图
        if (map_point.val() != '') {
            var curr_point = map_point.val().split(',');
            point_lng = curr_point[0];
            point_lat = curr_point[1];
        } else if(address.val() != '') {
            placeSearch(address.val());
        } else {
            // 默认定位当前城市，无需处理
        }

        var point = new AMap.LngLat(point_lng, point_lat);
        if (map_point.val() != '') {
            map.setCenter(point)
            // 创建标注
            create_mark(point);
        }
    });

});
