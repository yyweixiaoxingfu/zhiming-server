$(function(){
    $('.js-tmap').each(function () {
        var $self         = $(this);
        var map_canvas    = $self.find('.tmap').attr('id');
        var address       = $self.find('.tmap-address');
        var address_id    = address.attr('id');
        var map_point     = $self.find('.tmap-point');
        var search_result = $self.find('.suggestionList');
        var point_lng     = 116.331398;
        var point_lat     = 39.897445;
        var map_level     = $self.data('level');
        // 腾讯地图API功能
        var center=new TMap.LatLng(point_lat,point_lng)
        //定义map变量，调用 TMap.Map() 构造函数创建地图
        var map = new TMap.Map(document.getElementById(map_canvas), {
            zoom: map_level,   //设置地图缩放级别
        });

        // 监听点击地图时间
        map.on("click", function (e) {
            // 创建标注
            create_mark(e.latLng)
        });

        // 创建标注
        var marker = null;
        var create_mark = function (point) {
            removeMarker();
            marker = new TMap.MultiMarker({
                id: 'marker-layer',
                map: map,
                styles: {
                    "marker": new TMap.MarkerStyle({
                        "width": 25,
                        "height": 35,
                        "anchor": { x: 16, y: 32 },
                        // "src": 'https://mapapi.qq.com/web/lbs/javascriptGL/demo/img/markerDefault.png'
                    })
                },
                geometries: [{
                    "id": 'demo',
                    "styleId": 'marker',
                    "position": new TMap.LatLng(point.lat, point.lng),
                    "properties": {
                        "title": "marker"
                    }
                }]
            });
            // 写入坐标
            map_point.val(point.lng + "," + point.lat);
        }
        //移除marker事件
        function removeMarker() {
            if (marker) {
                marker.setMap(null);
                marker = null;
            }
        }

        // 关键字输入提示
        address.on('input',function () {
            keyword = $(this).val();
            getSuggestions(keyword);
        });
        var suggestionList = [];
        var suggest = new TMap.service.Suggestion({
            // 新建一个关键字输入提示类
            pageSize: 10, // 返回结果每页条目数
        });
        function getSuggestions(keyword) {
            // 使用者在搜索框中输入文字时触发
            search_result.html('');
            if (keyword) {
                suggest
                    .getSuggestions({ keyword: keyword, location: map.getCenter() })
                    .then((result) => {
                        // 以当前所输入关键字获取输入提示
                        str = '';
                        suggestionList = result.data;
                        suggestionList.forEach((item, index) => {
                            str += `<li><a href="#" data-index="${index}">${item.title}<span class="item_info">${item.address}</span></a></li>`;
                        });
                        search_result.html(str);
                        search_result.show();
                        search_result.find('li').each(function () {
                            $(this).on('click',function (index) {
                                index = $(this).find('a').data('index');
                                console.log(suggestionList[index])
                                address.val(suggestionList[index].address + suggestionList[index].title);
                                map.setCenter(suggestionList[index].location);
                                create_mark(suggestionList[index].location)
                                search_result.html('');
                            })
                        })
                    })
                    .catch((error) => {
                        // console.log(error);
                    });
            }
        }

        // 智能搜索
        function searchByKeyword(keyword) {
            var search = new TMap.service.Search({ pageSize: 5 }); // 新建一个地点搜索类
            search.searchRectangle({
                    keyword: keyword,
                    bounds: map.getBounds(),
                })
                .then((result) => {
                    search_point = result.data[0].location;
                    map.setCenter(search_point);
                    create_mark(search_point);
                });
        }

        if (map_point.val() != '') {
            var curr_point = map_point.val().split(',');
            point_lng = curr_point[0];
            point_lat = curr_point[1];
        }else if(address.val() != '') {
            searchByKeyword(address.val())
        }else{
            // 根据ip获取当前城市，并定位到当前城市
            var ipLocation = new TMap.service.IPLocation(); // 新建一个IP定位类
            ipLocation.locate().then((result2) => {
                    // 未给定ip地址则默认使用请求端的ip
                    var { result } = result2;
                    map.setCenter(result.location);
                })
                .catch((error) => {

                });
        }

        // 初始化地图,设置中心点坐标和地图级别
        var point = new TMap.LatLng(point_lat,point_lng);
        if (map_point.val() != '') {
            map.setCenter(point);
            // 创建标注
            create_mark(point);
        }
    });

});
