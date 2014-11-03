jQuery(document).ready(function() {
    if (navigator.userAgent.match(/iPhone/i) || navigator.userAgent.match(/iPod/i) || navigator.userAgent.match(/iPad/i)) {
        jQuery(".aspexifbfacebook").click(function() {
            if(jQuery(this).hasClass('active')) {
                var that = jQuery(this),
                        afbbox = jQuery(this).find('.afbbox');

                if (jQuery.browser.msie && parseInt(jQuery.browser.version) < 8) {
                    jQuery(this).find('.afbbox').hide();
                    jQuery(this).find('.arrow').hide();
                    that.removeClass('active');
                    return false;
                }
                jQuery(this).find('.arrow').stop(false, true).fadeOut(300);
                afbbox.stop(false, true).animate({'width': '0', 'opacity': '0'}, 300, function() {
                    that.removeClass('active');
                    afbbox.width('');
                });
            } else {
                var afbbox = jQuery(this).children('img').next().next();
                afbbox.data('width', afbbox.innerWidth());

                if (jQuery.browser.msie && parseInt(jQuery.browser.version) < 8) {
                    afbbox.show().width(afbbox.attr('data-width'));
                    afbbox.prev().show();
                    jQuery(this).addClass('active');
                    return false;
                }
                jQuery(this).addClass('active');
                jQuery(this).children('img').next().stop(false, true).fadeIn(300);
                if (afbbox.is(':animated')) {
                    afbbox.stop().css('opacity', '1').width(afbbox.attr('data-width'));
                } else {
                    afbbox.stop(false, true).animate({'width': afbbox.data('width'), 'opacity': 1}, 300);
                }
            }
        });
    } else {
        jQuery(".aspexifbfacebook").bind('mouseover', function() {
            var afbbox = jQuery(this).children('img').next().next();
            afbbox.data('width', afbbox.innerWidth());

            if (jQuery.browser.msie && parseInt(jQuery.browser.version) < 8) {
                afbbox.show().width(afbbox.attr('data-width'));
                afbbox.prev().show();
                jQuery(this).addClass('active')
                return false;
            }
            jQuery(this).addClass('active');
            jQuery(this).children('img').next().stop(false, true).fadeIn(300);
            if (afbbox.is(':animated')) {
                afbbox.stop().css('opacity', '1').width(afbbox.attr('data-width'));
            } else {
                afbbox.stop(false, true).animate({'width': afbbox.data('width'), 'opacity': 1}, 300);
            }
        });

        jQuery(".aspexifbfacebook").bind('mouseleave', function() {
            var that = jQuery(this),
                    afbbox = jQuery(this).find('.afbbox');

            if (jQuery.browser.msie && parseInt(jQuery.browser.version) < 8) {
                jQuery(this).find('.afbbox').hide();
                jQuery(this).find('.arrow').hide();
                that.removeClass('active');
                return false;
            }
            jQuery(this).find('.arrow').stop(false, true).fadeOut(300);
            afbbox.stop(false, true).animate({'width': '0', 'opacity': '0'}, 300, function() {
                that.removeClass('active');
                afbbox.width('');
            });
        });
    }
});