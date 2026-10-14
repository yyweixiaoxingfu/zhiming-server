$(document).ready(function () {
    // 选择数据选择框
    $('.js-select-table-select').each(function () {
        $(this).click(function () {
            let url = $(this).data('url');
            if (url === '') {
                Dolphin.notify('未设置url', 'danger');return false;
            }
            layer.open({
                type:  2,
                area: ['90%', '90%'],
                shadeClose: true,
                isOutAnim: false,
                anim: -1,
                title: '选择数据',
                content: $(this).data('url')+'?_pop=1'
            });
        });
    });

    // 确定选择数据
    $('#js-confirm').click(function () {
        var table = $('#builder-table-main');
        var trs = table.find('tr');
        var index = parent.layer.getFrameIndex(window.name); //获取窗口索引
        var num = 0;
        var name = $(this).data('name');

        if (trs.length === 0) {
            Dolphin.notify('没有可选数据');return false;
        }

        var result = [];
        $.each(trs, function (index, ele) {
            var first_td = $(this).find('td:first-child');
            var checkbox = first_td.find('input.ids');

            if (checkbox.prop('checked')) {
                num++;
                $(this).append('<td>' +
                    '<button class="btn btn-xs btn-danger js-delete-line" type="button"><i class="fa fa-times"></i> 删除</button>' +
                    '<input type="hidden" name="'+name+'[]" value="'+checkbox.val()+'">' +
                    '</td>');
                first_td.remove();
                $(this).prepend('<td class="text-center"><i class="fa fa-fw fa-arrows"></i></td>');
                result.push($(this))
            }
        });

        if (num > 0) {
            parent.$('#table-'+name+' tbody .empty').remove();
            parent.$('#table-'+name+' tbody').append(result);
            // 拖拽排序
            parent.$('#table-'+name+' tbody').sortable({
            }).disableSelection();
        } else {
            Dolphin.notify('请至少选择一项数据', 'danger');
            return false;
        }

        parent.layer.close(index);
        return false;
    });

    // 删除数据
    $(".table").on("click" ,'.js-delete-line' ,function(){
        var tr = $(this).closest('tr');
        layer.confirm('确定要删除吗?', {icon: 3, title:'提示'}, function(index){
            tr.remove();
            layer.close(index)
        });
    });
});