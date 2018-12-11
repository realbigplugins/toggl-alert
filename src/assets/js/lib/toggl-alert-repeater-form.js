( function( $ ) {
	
	/**
	 * Submit the Form for Creating/Updating Notifications via their Modals
	 * 
	 * @param	  	{object}  event JavaScript Event Object
	 *							  
	 * @since	  	1.1.0
	 * @returns	 	{boolean} Validity of Form
	 */
	var attachNotificationSubmitEvent = function( event ) {
		
		var modal = event.currentTarget,
			$form = $( modal ).find( 'form' ),
			$postIDfield = false;
		
		if ( ! $( modal ).hasClass( 'has-form' ) ) {
			
			// We need to create the Form
			// "novalidate" so that HTML5 doesn't try to take over before we can do our thing
			$form = $( modal ).find( '.toggl-alert-rbm-repeater-form' ).wrap( '<form method="POST" novalidate></form>' ).parent();
			
			$( modal ).addClass( 'has-form' );

			// Normally HTML doesn't like us having nested Forms, so we force it like this
			// By the time the Modal opens and this code runs, the Form isn't nested anymore
			$form.submit( function( event ) {

				event.preventDefault(); // Don't submit the form via PHP
				
				$form[0].reportValidity(); // Report Validity via HTML5 stuff
				
				if ( $form[0].checkValidity() ) { // Only run our code if we've got a Valid Form
					
					// This captures the Submit button
					// activeElement ensures it is the correct one in the event more Submit Buttons get added for some reason
					var $submitButton = $( document.activeElement ),
						submitText = $submitButton.val();
					
					$submitButton.val( $submitButton.data( 'saving_text' ) ).attr( 'disabled', true );

					// Used to construct HTML Name Attribute
					var repeaterList = $( '.toggl-alert-rbm-repeater-list' ).data( 'repeater-list' ),
						regex = new RegExp( repeaterList.replace( /[-\/\\^$*+?.()|[\]{}]/g, '\\$&' ) + '\\[\\d\\]\\[(.*)\\]', 'gi' ),
						data = {};

					$( this ).find( 'input:not([type="submit"]), select, textarea' ).each( function( index, field ) {

						if ( $( field ).parent().hasClass( 'hidden' ) ) return true;

						var name = $( field ).attr( 'name' ),
							match = regex.exec( name ),
							value = $( field ).val();
						
						if ( $( field ).is( 'input[type="checkbox"]' ) ) {
							
							value = ( $( field ).prop( 'checked' ) ) ? 1 : 0;
							
						}

						// Checkboxes don't place nice with my regex and I'm not rewriting it
						data[ match[1].replace( '][', '' ) ] = value;
						
						if ( match[1].replace( '][', '' ) == 'email_post_id' ) {
							$postIDfield = $( field );
						}

						// Reset Interal Pointer for Regex
						regex.lastIndex = 0;

					} );
					
					data.action = 'insert_toggl_alert_rbm_notification';
					
					data.toggl_alert_settings_nonce = $( '#toggl_alert_settings_nonce' ).val();

					$.ajax( {
						'type' : 'POST',
						'url' : togglAlert.ajax,
						'data' : data,
						success : function( response ) {
							
							var uuid = $( modal ).data( 'reveal' ),
								$row = $( '[data-open="' + uuid + '"]' ).closest( '.toggl-alert-rbm-repeater-item' );
							
							// If the Modal started as a New Notification, we need to update the Post ID value to ensure it can be updated
							$postIDfield.val( response.data.post_id );
							
							window.togglAlertCloseModal( uuid );
							
							$submitButton.val( submitText ).attr( 'disabled', false );
							
							// Highlight Green
							$row.effect( 'highlight', { color : '#DFF2BF' }, 1000 );
							
						},
						error : function( request, status, error ) {
							
							console.log( request );
							console.log( status );
							console.error( error );
							
							$submitButton.val( submitText ).attr( 'disabled', false );
							
						}
					} );
					
				}

			} );
			
		}
		
	}
	
	$( document ).ready( function() {
		
		// When a Modal opens, attach the Form Submission Event
		$( document ).on( 'open.zf.reveal', '.toggl-alert-rbm-repeater-content.reveal', function( event ) {
			attachNotificationSubmitEvent( event );
		} );
		
	} );
	
} )( jQuery );