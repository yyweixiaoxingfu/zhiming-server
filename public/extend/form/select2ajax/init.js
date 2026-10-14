function formatRepo (repo) {
    if (repo.loading) return repo.text;
    var markup = [
        '<li class="select2-result_option" role="treeitem" id="repo.id">',
        repo.text,
        '</li>'
    ].join('');
    return markup;
}

$(function(){
    $('.select-ajax').each(function(index, el) {
        var $this = $(this);
        var $value     = $this.data('value') || '';
        var $token     = $this.data('token') || '';
        var $url = $this.data('url') || '';
        var $multiple = $this.data('multiple') || '';
        $this.select2({
			language: "zh-CN",
            width: "100%", //设置下拉框的宽度
            ajax: {
                a:$(this),
                b:this,
                c:self,
                url: $url,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        _keyword: params.term,
                        _type: params._type,
                        page: params.page,
                        _token: $token
                    };
                },
                processResults: function (data, params) {
                    // parse the results into the format expected by Select2
                    // since we are using custom formatting functions we do not need to
                    // alter the remote JSON data, except to indicate that infinite
                    // scrolling can be used
                    params.page = params.page || 1;

                    return {
                        results: data.data,
                        pagination: {
                            more: (params.page * 10) < data.total
                        }
                    };
                },
                cache: false
            },
            escapeMarkup: function (markup) { return markup; }, // let our custom formatter work
            minimumInputLength: 0,
            templateResult: formatRepo, // omitted for brevity, see the source of this page
        });
        if ($value !== '') {
            $.post($url, {_type:'default', _token:$token, _default:$value}, function (res) {

            });
        }
    });
})