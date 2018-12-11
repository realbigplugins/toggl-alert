// Initialize special fields if they exist
window.inittogglAlertRepeaterColorpickers = function( modal ) {

	var regex = /value="(#(?:[0-9a-f]{3}){1,2})"/i;

	// Only try to run if there are any Color Pickers within a Toggl Alert Repeater
	if ( jQuery( modal ).find( '.wp-colorpicker' ).length > 0 ) {

		// Hit each colorpicker individually to ensure its settings are properly used
		jQuery( modal ).find( '.wp-colorpicker' ).each( function( index, colorPicker ) {

			// Value exists in HTML but is inaccessable via JavaScript. No idea why.
			var value = regex.exec( jQuery( colorPicker )[0].outerHTML );
			
			if ( value !== null ) {
				value = value[1]; // Set to Regex Match
			}
			else {
				value = jQuery( colorPicker ).data( 'defaultColor' ); // Set to default
			}

			jQuery( colorPicker ).val( value ).attr( 'value', value ).wpColorPicker();

		} );

	}

};

// Initialize Select2 if it exists
window.initTogglAlertSelect2 = function( modal ) {

	// Only try to run if there are any Select2 Fields within a Toggl Alert Repeater
	if ( jQuery( modal ).find( 'select.select2' ).length ) {
		
		jQuery( modal ).find( 'select.select2' ).each( function( index, select ) {
			
			var $container = jQuery( select ).closest( '.fieldhelpers-field' ),
				instance = $container.attr( 'data-fieldhelpers-instance' ),
				id = jQuery( select ).attr( 'id' );
			
			jQuery( select ).rbmfhselect2( RBM_FieldHelpers[ instance ].select[ id ].select2Options );
			
		} );

	}

};

// Select Fields within Repeaters aren't properly set to have their selected value shown
window.fixtogglAlertSelect = function( modal ) {
	
	// Only try to run if there are any non-Select2 Selects within a Toggl Alert Repeater
	if ( jQuery( modal ).find( 'select:not(.select2)' ).length ) {

		// Fix Selected Value on load
		var $select = jQuery( modal ).find( 'select:not(.select2)' ),
			value = $select.find( 'option[selected]' ).val();
		
		$select.val( value );

	}
	
};

window.togglAlertExpandTextarea = function( event, textarea = false ) {
	
	// If no event was passed, then the "event" is the textarea
	if ( ! textarea ) textarea = event;
	
	// Only calculate this out if no Base Scroll Height is saved
	if ( ! textarea.baseScrollHeight ) {
		
		var savedValue = textarea.value;
        textarea.value = '';
        textarea.baseScrollHeight = textarea.scrollHeight;
        textarea.value = savedValue;
		
	}
	
	var minRows = jQuery( textarea ).data( 'min-rows' ) | 3,
		rows;
	
	jQuery( textarea ).attr( 'rows', minRows );
	
	rows = Math.ceil( ( textarea.scrollHeight - textarea.baseScrollHeight ) / 17 );
	
	console.log( minRows );
	console.log( rows );
	
	jQuery( textarea ).attr( 'rows', minRows + rows );
	
};

// Initialize Auto-Expanding Textareas
window.inittogglAlertTextarea = function( modal ) {
	
	// Only try to run if there are any Auto-Expanding Textareas within a Toggl Alert Repeater
	if ( jQuery( modal ).find( 'textarea' ).length ) {
		
		jQuery( modal ).find( 'textarea' ).each( function( index, textarea ) {
			
			window.togglAlertExpandTextarea( textarea );
			
		} );
		
	}
	
};

// Repeaters
( function ( $ ) {

	var $repeaters = $( '[data-toggl-alert-rbm-repeater]' );

	if ( ! $repeaters.length ) {
		return;
	}

	var toggl_alert_repeater_show = function() {
		
		var $apiToken = $( '.toggl-alert-api-token' ),
			message = $apiToken.attr( 'title' );
		
		$apiToken[0].setCustomValidity( '' );
		
		if ( $apiToken.val().trim() == '' ||
		   ! $apiToken[0].checkValidity() ) {
			
			$apiToken[0].setCustomValidity( message );
			
			if ( window.togglAlertAncientBrowser ) {
					
				$apiToken.before( '<span class="validation-error">' + message + '<br /></span>' );

			}
			
			$apiToken[0].reportValidity();
			
			return false;
			
		}
		
		var repeater = $( this ).closest( '[data-toggl-alert-rbm-repeater]' );

		// Hide current title for new item and show default title
		$( this ).find( '.repeater-header div.title' ).html( $( this ).find( '.repeater-header div.title' ).data( 'repeater-default-title' ) );

		$( this ).stop().slideDown();

		$( repeater ).trigger( 'toggl-alert-rbm-repeater-add', [$( this )] );

	}

	var toggl_alert_repeater_hide = function() {

		var repeater = $( this ).closest( '[data-toggl-alert-rbm-repeater]' ),
			confirmDeletion = confirm( togglAlert.i18n.confirmNotificationDeletion );
			
		if ( confirmDeletion ) {

			var $row = $( this ),
				uuid = $row.find( '[data-repeater-edit]' ).data( 'open' ),
				$modal = $( '[data-reveal="' + uuid + '"]' ),
				postID = $modal.find( '[name$="[email_post_id]"]' ).val();
			
			$.ajax( {
				'type' : 'POST',
				'url' : togglAlert.ajax,
				'data' : {
					'action' : 'delete_toggl_alert_rbm_notification',
					'post_id' : postID,
					'toggl_alert_settings_nonce' : $( '#toggl_alert_settings_nonce' ).val(),
				},
				success : function( response ) {
					
					// Remove whole DOM tree for the Modal.
					$modal.parent().remove();

					// Remove DOM Tree for the Notification "Header"
					$row.stop();
					setTimeout( function() {
						
						$row.effect( 'highlight', { color : '#FFBABA' }, 300 ).dequeue().slideUp( 300, function () {
							$row.remove();
						} );
						
					} );

					$( repeater ).trigger( 'toggl-alert-rbm-repeater-remove', [$row] );
					
				},
				error : function( request, status, error ) {
					
					console.error( request );
					console.error( status );
					console.error( error );
					
				}
			} );

		}

	}

	$repeaters.each( function () {

		var $repeater = $( this ),
			$dummy = $repeater.find( '[data-repeater-dummy]' );

		// Repeater
		$repeater.repeater( {

			repeaters: [ {
				show: toggl_alert_repeater_show,
				hide: toggl_alert_repeater_hide,
			} ],
			show: toggl_alert_repeater_show,
			hide: toggl_alert_repeater_hide,
			ready: function ( setIndexes ) {
				$repeater.find( 'tbody' ).on( 'sortupdate', setIndexes );
			}

		} );

		if ( $dummy.length ) {
			$dummy.remove();
		}
		
		$( document ).on( 'closed.zf.reveal', '.toggl-alert-rbm-repeater-content.reveal', function() {
			
			var title = $( this ).find( '[name$="[admin_title]"]' ),
				uuid = $( this ).closest( '.toggl-alert-rbm-repeater-content.reveal' ).data( 'reveal' ),
				$row = $( '[data-open="' + uuid + '"]' );
			
			if ( $( title ).val() !== '' ) {
				$row.closest( '.toggl-alert-rbm-repeater-item' ).find( '.repeater-header div.title' ).html( $( title ).val() );
			}
			else {
				var defaultValue = $row.closest( '.toggl-alert-rbm-repeater-item' ).find( '.repeater-header div.title' ).data( 'repeater-default-title' );
				$row.closest( '.toggl-alert-rbm-repeater-item' ).find( '.repeater-header div.title' ).html( defaultValue );
			}
			
		} );

	} );

} )( jQuery );