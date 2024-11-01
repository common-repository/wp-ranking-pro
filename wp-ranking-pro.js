jQuery(function ($) {
    // *: 実行確認
    $('.require-confirmation').click(function () {
        return confirm($(this).data('wpr-message') || WPR_Admin.msg_is_execute);
    });

    // 統計: *: 表示件数
    $('.auto-submit').change(function () {
        $(this).closest('form').submit();
    });

    // 統計: *: ダッシュボードに追加
    $('#wpr-add-to-dashboard').click(function () {
        if ($(this).data('wpr-already-added')) {
            alert(WPR_Admin.msg_already_added);

            return false;
        }
        else {
            alert(WPR_Admin.msg_added);
        }

        var ranking_id = $(this).data('wpr-id');

        $.get(ajaxurl, {
            action : WPR_Admin.action,
            id     : ranking_id,
        }, function () {
            // nothing to do
        });

        return false;
    });

    // セッティング: 基本設定: 画像タイプ
    var toggle_image_type_custom_field = function () {
        if ($('#wpr-image-type').val() == '2') { // 2 is custom field
            $('#wpr-image-type-custom-field').removeAttr('disabled');
            $('#wpr-image-type-custom-field').show();
        }
        else {
            $('#wpr-image-type-custom-field').attr('disabled', 'disabled');
            $('#wpr-image-type-custom-field').hide();
        }
    }

    toggle_image_type_custom_field();

    $('#wpr-image-type').change(toggle_image_type_custom_field);

    // カスタムランキング
    var toggle_ranking_mode = function () {
        if ($('#wpr-ranking-mode').val() == 'comment_count') {
            $('.wpr-disable-if-comment-count-mode').find('input, select').attr('disabled', 'disabled');
        }
        else {
            $('.wpr-disable-if-comment-count-mode').find('input, select').removeAttr('disabled');
        }
    }

    toggle_ranking_mode();

    $('#wpr-ranking-mode').change(toggle_ranking_mode);

    $('#acMenu dt').click(function () {
        $(this).next().slideToggle();
        $(this).toggleClass('active');
    });
});

// NOTE: WordPress 3.2.1同梱のjQueryは1.6.1
// on(), off()は1.7以降での実装のため、使えない。
