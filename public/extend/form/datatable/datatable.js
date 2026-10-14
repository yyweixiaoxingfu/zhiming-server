$(document).ready(function () {
    // 新增行
    App.initHelpers(['datetimepicker','datepicker','select-ajax']);
    $(document).on('click','.js-add-new',function(){
        const table_id = $(this).data('table');
        const table = $('#table-'+table_id);
        const fields = $(this).data('fields');
        let max = $(this).data('max');
        const arr = Object.keys(fields);
        let tr = '<tr>';
        max ++ ;
        table.find('tr.empty').remove();
        var img = $('#kadir_img').html();

        if (arr.length > 0) {
            $.each(fields, function (i, e) {
                if (e.type === 'text') {
                    tr += '<td><input class="form-control" type="text" name="'+table_id+'['+ max+']['+i+']" placeholder="请填写..."></td>';
                }else if (e.type === 'hidden') {
                    tr += '<td style="display: none"><input class="form-control" type="hidden" name="'+table_id+'['+ max+']['+i+']" placeholder="请填写..."></td>';
                }else if (e.type === 'static') {
                    tr += '<td><input class="form-control" type="text" name="'+table_id+'['+ max+']['+i+']" placeholder="" readonly></td>';
                }else if (e.type === 'number') {
                    tr += '<td><input class="form-control" type="number" name="'+table_id+'['+ max+']['+i+']" placeholder="请填写..." ' + e.extra_attr + '></td>';
                } else if (e.type === 'time') {
                    td_id = table_id+'['+ max +']['+i+']';
                    tr += '<td>';
                    tr += '<input class="js-datetimepicker form-control" type="text" name="'+ td_id + '" value="" placeholder=""\n' +
                        '                                   data-locale="zh-cn"\n' +
                        '                                   data-format="' + e.format + ' ' + e.extra_attr + '"\n';
                    tr += '</td>';
                } else if (e.type === 'date') {
                    td_id = table_id+'['+ max +']['+i+']';
                    tr += '<td>';
                    tr += '<input class="js-datepicker form-control" type="text" name="'+ td_id + '" value="" placeholder=""\n' +
                        '                                   data-date-format="' + e.format + ' ' + e.extra_attr + '"\n';
                    tr += '</td>';
                } else if (e.type === 'image') {
                    tr += '<td><input type="hidden"  class="form-control image" name="'+table_id+'['+ max+']['+i+']"/>'+img+'</td>';
                }else if (e.type === 'select_ajax') {
                    tr += '<td><select class="js-select-ajax form-control" data-url="'+ e.url +'" data-token="'+ e.token +'" ' +
                        'name="'+table_id+'['+ max +']['+i+']' + (e.multiple ? '[]' : '') + '" ' + (e.multiple ? 'multiple ' : '') +
                        'data-list-rows="15" data-allow-clear="true" data-parent_items="" data-next-items="" ' + e.extra_attr + '>' +
                        '<option value=""></option>';
                    tr += '</select></td>';
                }else {
                    tr += '<td><select class="form-control" name="'+table_id+'['+ max +']['+i+']" ' + e.extra_attr + '><option value="">请选择...</option>';
                    $.each(e.options, function (key, value) {
                        tr += '<option value="'+key+'">'+value+'</option>';
                    });
                    tr += '</select></td>';
                }
            });
            tr += '<td><button class="btn btn-sm btn-danger js-delete-line" type="button"><i class="fa fa-times"></i> 删除</button></td>';
        }

        tr += '</tr>';
        table.find('tbody').append(tr);
        $(this).data('max',max);
        App.initHelpers(['datetimepicker','datepicker','select-ajax']);
    });

    // 删除数据
    $(".table").on("click" ,'.js-delete-line' ,function(){
        var tr = $(this).closest('tr');
        layer.confirm('确定要删除吗?', {icon: 3, title:'提示'}, function(index){
            tr.remove();
            layer.close(index)
        });
    });

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
        $(this).parents('td').find('.image').val('');
    })
    // 鼠标移出 删除按钮事件
    $(document).on('mouseout','.imgDel',function(){
        $(this).css('background-color','')
        $(this).parents('td').find('.image').val('');
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
                    img_obj.parents('td').find('.image').val(res.id);
                    return
                }
                layer.msg(res.info)
            }
        });
    }
});
