jQuery(function ($) {
    $('#wpr-dashboard-limit').val(10);

    var ranking_id = WPR_Dashboard.ranking_id;
    var page = 1;

    var update_ranking = function() {
        var limit = $('#wpr-dashboard-limit').val();

        switch ($(this).attr('id')) {
            case 'wpr-dashboard-prev-page':
                page--;
                break;

            case 'wpr-dashboard-next-page':
                page++;
                break;

            default:
                page = 1;
        }

        $.get(ajaxurl, {
            action : WPR_Dashboard.action_fetch,
            id     : ranking_id,
            limit  : limit,
            page   : page
        }, function (data) {
            $('.wpr-ranking').html(data.html);

            page > 1
                ? $('#wpr-dashboard-prev-page').show()
                : $('#wpr-dashboard-prev-page').hide() ;

            data.has_next
                ? $('#wpr-dashboard-next-page').show()
                : $('#wpr-dashboard-next-page').hide() ;
        });
    };

    $('#wpr-dashboard-limit').change(update_ranking);
    $('.wpr-dashboard-update-ranking').click(update_ranking);

    $('.wpr-menu a').click(function () {
        if (ranking_id == $(this).data('wpr-id')) {
            return false;
        }

        ranking_id = $(this).data('wpr-id');
        page = 1;

        update_ranking();

        $('.wpr-menu a').each(function () { $(this).removeClass('wpr-current') });
        $(this).addClass('wpr-current');

        return false;
    });

    $('#wpr-dashboard-remove-ranking').click(function () {
        $.get(ajaxurl, {
            action : WPR_Dashboard.action_remove,
            id     : ranking_id
        }, function (results) {
            if (results.is_success) {
                $('.wpr-menu a').each(function () {
                    if (ranking_id == $(this).data('wpr-id')) {
                        $(this).closest('li').remove();
                    }
                });

                ranking_id = results.next_ranking_id;

                update_ranking();

                $('.wpr-menu a').each(function () {
                    if (ranking_id == $(this).data('wpr-id')) {
                        $(this).addClass('wpr-current');
                    }
                    else {
                        $(this).removeClass('wpr-current');
                    }
                });
            }
            else {
                alert(results.message);
            }
        });
    });
});
