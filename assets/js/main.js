'use strict';

(function ($) {
    'use strict';

    const gotoSection = function gotoSection(e) {
        e.preventDefault();

        const $that = $(this);
        const target = $that.attr('href');
        const $content = $('body, html');
        const $target = $(target);

        $content.stop().animate({
            'scrollTop': $target.position().top - 50
        });
    };

    $('.smooth-scroll a').on('click', gotoSection);
})(jQuery);