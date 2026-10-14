$(document).ready(function () {
    $('.js-transfer').each(function () {
        let self = $(this);
        let name = self.data('name');
        let $transferLeft = $('#transfer-'+name+'-list');
        let $transferRight = $('#transfer-'+name);
        let $transferLeftOption = $transferLeft.find('option');
        let btnRight = self.find('.js-move-right');
        let btnLeft = self.find('.js-move-left');
        let btnUp = self.find('.js-move-up');
        let btnDown = self.find('.js-move-down');

        $transferLeftOption.dblclick(function () {
            $(this).prop('selected', false).appendTo($transferRight);
        });
        $transferRight.delegate('option', 'dblclick', function () {
            $(this).prop('selected', false).appendTo($transferLeft);
        });

        btnRight.click(function () {
            $.each($transferLeft.find(':selected'), function () {
                $(this).prop('selected', false).appendTo($transferRight);
            });
        });
        btnLeft.click(function () {
            $.each($transferRight.find(':selected'), function () {
                $(this).prop('selected', false).appendTo($transferLeft);
            });
        });
        btnUp.click(function () {
            if ($transferRight.find(':selected').length === 1) {
                let $curr = $transferRight.find(':selected');
                let $prev = $curr.prev();
                if($prev.length !== 0){
                    $prev.before($curr);
                }
            } else {
                Dolphin.notify('只能选择一项进行上下移动', 'danger');
            }
        });
        btnDown.click(function () {
            if ($transferRight.find(':selected').length === 1) {
                let $curr = $transferRight.find(':selected');
                let $next = $curr.next();
                if($next.length !== 0){
                    $next.after($curr);
                }
            } else {
                Dolphin.notify('只能选择一项进行上下移动', 'danger');
            }
        });

        $('.btn[type=submit]').click(function () {
            $transferRight.find('option').prop('selected', true);
        });
    });
});