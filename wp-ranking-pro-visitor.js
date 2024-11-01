jQuery(function ($) {
    $.get(WPR_Visitor.url, {
        action       : WPR_Visitor.action,
        post_id      : WPR_Visitor.post_id,
        http_referer : WPR_Visitor.http_referer
    });
});
