'use strict';

(function ($) {
    'use strict';

    var gotoSection = function gotoSection(e) {
        e.preventDefault();

        var $that = $(this);
        var target = $that.attr('href');
        var $content = $('body, html');
        var $target = $(target);

        $content.stop().animate({
            'scrollTop': $target.position().top - 50
        });
    };

    $('.smooth-scroll a').on('click', gotoSection);
})(jQuery);