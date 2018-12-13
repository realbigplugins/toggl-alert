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

        var $repeaters = $('[data-toggl-alert-rbm-repeater]');

        if ($repeaters.length) {

            notification_tips();
            notification_change_trigger();
			
			$( document ).on( 'open.zf.reveal', '.toggl-alert-rbm-repeater-content.reveal', function() {
				
				$( this ).find( '[name^="toggl_alert_rbm_feeds"][name$="trigger]"]' ).trigger( 'change' );
				
			} );
			
        }
    }

    function notification_tips() {

        $(document).on('focus', '[data-has-tip]', show_tip);
        $(document).on('blur', '[data-has-tip]', hide_tip);
    }

    function notification_change_trigger() {

        $(document).on('change', '[name^="toggl_alert_rbm_feeds"][name$="trigger]"]', notification_tips_change_tags);
    }

    function show_tip() {
		
		var type = $( this ).closest( '.toggl-alert-rbm-repeater-content' ).find( '[name^="toggl_alert_rbm_feeds"][name$="trigger]"]' ).val();

        if ( type == null || 
			! merge_tags[ type ] ) {

            return;
        }

        $(this).closest( '.fieldhelpers-field' ).find('.toggl-alert-notification-tip').addClass('show');
    }

    function hide_tip() {

        $(this).closest( '.fieldhelpers-field' ).find('.toggl-alert-notification-tip').removeClass('show');
    }

    function notification_tips_change_tags() {

        var type = $(this).val();

        if ( type == null || 
			! merge_tags[ type ] ) {

            return;
        }

        var available_tags = merge_tags[type];
        var $tag_containers = $('.toggl-alert-notification-tags');

        $tag_containers = $('.toggl-alert-notification-tags').html('');

        for (var i = 0; i < available_tags.length; i++) {

            $tag_containers.append('<br/><code>' + available_tags[i] + '</code>');
        }
    }

    $(init);
})(jQuery, togglAlert);