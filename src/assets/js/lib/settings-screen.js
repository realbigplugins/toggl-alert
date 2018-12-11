/**
 * Provides all functionality for the admin settings creen for Slack Notifications.
 *
 * @since 1.0.0
 */
(function ($, data) {
    'use strict';

    var merge_tags = data['merge_tags'];

    /**
     * Initializes the functionality.
     *
     * @since 1.0.0
     */
    function init() {

        var $repeaters = $('[data-toggl-alert]');

        if ($repeaters.length) {

            notification_tips();
            notification_change_trigger();
        }
    }

    function notification_tips() {

        $(document).on('focus', '[data-has-tip]', show_tip);
        $(document).on('blur', '[data-has-tip]', hide_tip);
    }

    function notification_change_trigger() {

        $(document).on('change', '[name^="toggl_alert_notification_feeds"][name$="notification]"]', notification_tips_change_tags);

        // Initial (timeout because there's no trigger for when repeaters have been initialized)
        setTimeout(function () {

            $('[name^="toggl_alert_notification_feeds"][name$="notification]"]').each(notification_tips_change_tags)
        }, 500);
    }

    function show_tip() {

        $(this).parent().find('.toggl-alert-notification-tip').addClass('show');
    }

    function hide_tip() {

        $(this).parent().find('.toggl-alert-notification-tip').removeClass('show');
    }

    function notification_tips_change_tags() {

        var type = $(this).val();

        if (!merge_tags[type]) {

            return;
        }

        var available_tags = merge_tags[type];
        var $tag_containers = $(this).closest('.toggl-alert-item').find('.toggl-alert-notification-tags');

        $tag_containers.html('');

        for (var i = 0; i < available_tags.length; i++) {

            $tag_containers.append('<br/><code>' + available_tags[i] + '</code>');
        }
    }

    $(init);
})(jQuery, togglAlert);