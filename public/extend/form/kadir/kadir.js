$(document).ready(function () {
    var skuIndex = $("#lv_table_con").children('table').children('tbody').children('tr').length;
    skuIndex = skuIndex + 1;
    var imgIndex = '';  // todo 定义有些随意需要更改
    $(document).on('click','.add_gui',function(){
        var j=0;
        $(this).parents('.gg_list_bar').find('.gg_list_xmBar').append(' <div class="gg_list_xmCon attr">' +
            '<input type="text" placeholder="属性名称"  class="v2" data-id="0" >' +
            '<span class="gg_list_xmDel">×</span>' +
            '</div>');
    })
    $(document).on('click','.gg_list_xmDel',function(){
        $(this).parent('.attr').remove();
    })

    $('.add_peces').click(function(){
        var name = $(this).parents('.kadir').data('kname');
        $('.content_peces').append('<div class="col-xs-12 gg_list_bar lv1">\n' +
            '                                                    <div class="gg_list_con">\n' +
            '                                                        <p>规格</p>\n' +
            '                                                        <input type="text" placeholder="规格名称（例如颜色）" name="'+name+'[spu_item][]"  class="v1" data-id="0">\n' +
            '                                                        <div class="gg_btn add_gui">添加规格项</div>\n' +
            '                                                        <div class="gg_btn gg_del_btn">删除</div>\n' +
            '                                                    </div>\n' +
            '                                                    <div class="gg_list_xmBar">\n' +
            '                                                    </div>\n' +
            '                                                </div>');
    })
    $(document).on('click','.gg_del_btn',function(){
        $(this).parents('.gg_list_bar').remove();

    })


    $(document).on('click','#jia',function(){
        const fields = $(this).data('fields');
        var name = $(this).parents('.kadir').data('kname');
        var thead = create_thead();
        // 生成 tr内容
        var tr = create_tr(fields,name);
        // 生成表单
        var tableHTML = create_table(thead, tr,fields);
        // 渲染页面
        $("#lv_table_con").html(tableHTML);

        return false;
    })
    /**
     * 生成thead
     *
     * @returns {string}
     */
    function create_thead()
    {
        var thead = '';
        $('.v1').each(function (){
            thead += '<th>'+$(this).val()+'</th>';
        })
        return thead;
    }

    /**
     * 生成tr
     *
     */
    function create_tr(fields,name)
    {
        var tr = '';
        // 获得属性值数组
        var value_arr = getValueArr();
        // 获得属性值数组
        var value_id_arr = getValueIDArr();

        // 获得属性值数组
        var spec_id_arr = getSpecIDArr();

        // 获得笛卡尔乘积
        var cartesians = cartesian(value_arr)
        $('.sku_item_content').html('');

        value_arr.forEach((item)=>{
            $('.sku_item_content').append('<input type="hidden" name="'+name+'[sku_item_value][]" value="'+item.join(',')+'"/>');

        })
        cartesians.forEach(function (item, index, arr) {
            tr += create_tr_html(item, value_id_arr, index,spec_id_arr,fields,name)
        })
        return tr;
    }
    /**
     * 生成表单的trhtml
     *
     * @param item_arr
     * @returns {string}
     */
    function create_tr_html (item_arr, value_id_arr, sort,spec_id_arr,fields,name)
    {
        var index = skuIndex;
        var date = new Date(); //用于生成sku
        var spec_name = '';
        var item_str=''
        var value_id_str = [];
        var spec_id_str = [];
        if (Array.isArray(item_arr)) {
            item_arr.forEach((item)=>{
                spec_name += "<td>"+item+"</td>";
                value_id_str.push(value_id_arr[item]);

            });
        } else {
            spec_name = "<td>"+item_arr+"</td>";
            value_id_str.push(value_id_arr[item_arr]);
            item_arr = [item_arr];
        }
        item_str = item_arr.join(',');
        item_id = item_arr.join('_');
        var has = $('#'+ item_id).html();
        if(has){
            $('#'+ item_id).find('.sku_sort').val(sort);
            return '<tr id="'+item_id+'">' + $('#'+ item_id).html() + '</tr>';
        }
        spec_name += "<td style='display: none'><input type='hidden' name='"+name+"[items]["+index+"][item_sku]' value='"+item_str+"'></td>";

        // 将标记防护name
        var img = $('#kadir_img').html();
        var row = '';

        row = '<tr id="'+item_id+'">';
        row +=   spec_name;
        $.each(fields, function (i, e){
            if (e.type === 'text') {
                row += '<td><input type="text"  name='+name+'[items]['+index+']'+'['+e.value+']"/></td>';
            }
            if (e.type === 'image') {
                row += '<td><input type="hidden"  class='+e.value+' name='+name+'[items]['+index+']'+'['+e.value+']"/>'+img+'</td>';
            }
        })
        row += '<td style="display: none"><input class="sku_sort" type="hidden"  name='+name+'[items]['+index+']'+'[sort]" value="'+sort+'"/></td>';
        row += '<td><button class="btn btn-sm btn-danger js-delete-line action_del" type="button"><i class="fa fa-times"></i> 删除</button></td>';
        row += '</tr>';
        skuIndex++;
        return row;
    }
    /**
     * 自定义函数名：PrefixZero
     * @param num： 被操作数
     * @param n： 固定的总位数
     */
    function PrefixZero(num, n) {
        return (Array(n).join(0) + num).slice(-n);
    }
    /**
     * 生成表单主体
     *
     */
    function create_table (thead, tr,fields)
    {
        var tableHTML = '';
        tableHTML += '<table class="guige_kucun_bar table table-bordered table-striped ">';
        tableHTML += '    <thead>';
        tableHTML += '        <tr>';
        tableHTML +=  thead;
        $.each(fields, function (i, e){
            tableHTML += '            <th>'+e.title+'</th>';
        })
        tableHTML += '            <th>操作</th>';
        tableHTML += '        </tr>';
        tableHTML += '    </thead>';
        tableHTML +=  tr;
        tableHTML += '    <table>';

        return tableHTML;
    }
    /**
     * 获得属性值数组
     *
     */
    function getValueArr()
    {
        var value_arr = [];

        $('.gg_list_xmBar').each(function () {
            var value = [];
            $(this).find(".v2").each(function (){
                value.push($(this).val());

            })
            value_arr.push(value);
        });

        return value_arr;
    }

    /**
     * 获得属性值数组
     *
     */
    function getValueIDArr()
    {

        var id_arr = {};
        $('.gg_list_xmBar').each(function () {
            var value = [];
            $(this).find(".v2").each(function (){
                id_arr[$(this).val()] = $(this).attr('data-id')
            })
        });

        return id_arr;
    }

    /**
     * 获得属性值数组
     *
     */
    function getSpecIDArr()
    {
        var spec_arr = [];
        $('.gg_list_xmBar').each(function () {

            $(this).find(".v2").each(function (){
                spec_arr[$(this).val()] = $(this).attr('spec-id')
            })
        });
        return spec_arr;
    }
    /*
     * 获得笛卡尔乘积
     *
     * @Author: Mr Jiang.Xu
     * @Date: 2019-08-31 00:05:33
     * @Last Modified by:   Mr Jiang.Xu
     * @Last Modified time: 2019-08-31 00:05:33
     */
    function cartesian(arr) {
        if (arr.length < 2) return arr[0] || [];
        return [].reduce.call(arr, function (col, set) {
            let res = [];
            col.forEach(c => {
                set.forEach(s => {
                    let t = [].concat(Array.isArray(c) ? c : [c]);
                    t.push(s);
                    res.push(t);
                })
            });
            return res;
        });

    }
    $(document).on('click','.imgAdd',function(){
        imgIndex = $(this);
        $(this).parent('.img').find('.file_btn').trigger("click");
        $('#file_btn').trigger("click");
    })
    $('#file_btn').change(function (e){
        var src = window.URL.createObjectURL(this.files[0]);
        imgIndex.parent('.img').find('img').attr('src', src);
        imgIndex.parent('.img').find('img').attr('tt', src);
        imgIndex.parent('.img').find('img').show();
        imgIndex.parent('.img').find('.imgDel').show()
        imgIndex.parent('.img').find('.imgAdd').hide()

        uploadFile(this, imgIndex);
        $('#file_btn').val("");

    })
    // 点击删除事件
    $(document).on('click','.imgDel',function(){
        $(this).parent('.img').find('.imgAdd').show();
        $(this).parent('.img').find('img').hide();
        $(this).parent('.img').find('.imgDel').hide();
    })
    // 鼠标移入 删除按钮事件
    $(document).on('mouseover','.imgDel',function(){
        $(this).css('background-color','red')
    })
    // 鼠标移出 删除按钮事件
    $(document).on('mouseout','.imgDel',function(){
        $(this).css('background-color','')
    })
    // sku 删除
    $(document).on('click','.action_del',function(){
        $(this).parents('tr').hide('normal', function () {
            $(this).remove()
        });
    })
    function uploadFile(obj, img_obj){
        var formData = new FormData();
        formData.append('file', obj.files[0])
        $.ajax({
            url : '/admin.php/admin/attachment/upload?dir=images',//后台文件上传接口
            type : 'POST',
            data : formData,
            cache: false,
            processData : false,
            contentType : false,
            success : function(res) {
                if (res.code == 1) {
                    img_obj.parents('td').find('.thumb').val(res.id);
                    return
                }
                layer.msg(res.info)
            }
        });
    }
});