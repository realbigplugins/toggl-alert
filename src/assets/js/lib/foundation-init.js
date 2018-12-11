/**
 * Just a quick and dirty way to know if a Modal is open
 * 
 * @since		1.2.0
 */ 
window.togglAlertModalOpen = false;

/**
 * Normally something like this would be handled by $( document ).foundation(), but doing it manually lets us call this whenever we'd like and dynamically create Button->Modal associations
 * 
 * @since	  1.0.0
 * @return	  void
 */
window.togglAlertInitModals = function() {

	jQuery( '.toggl-alert-rbm-repeater .toggl-alert-rbm-repeater-item' ).each( function( index, item ) {

		var $modal = jQuery( item ).find( '.toggl-alert-rbm-repeater-content.reveal' );

		if ( $modal.attr( 'data-reveal' ) !== '' ) return true;

		// Copy of how Foundation creates UUIDs
		var uuid = Math.round( Math.pow( 36, 7 ) - Math.random() * Math.pow( 36, 6 ) ).toString( 36 ).slice( 1 ) + '-reveal';

		$modal.attr( 'data-reveal', uuid );

		var $editButton = jQuery( item ).find( 'input[data-repeater-edit]' ).attr( 'data-open', uuid );

		$modal = new Foundation.Reveal( $modal );

	} );

};

/**
 * Opens a Modal because Foundation isn't able to do things quite how I need
 * 
 * @param	  {Event|String} uuid Either the Event from creating a new Row or a UUID
 * @param	  {object}	  row  DOM Object of the Row if called from an Event
 *							
 * @since	  1.0.0
 * @return	  void
 */
window.togglAlertOpenModal = function( uuid, row = undefined ) {

	// Handle newly created Rows
	if ( uuid.type == 'toggl-alert-rbm-repeater-add' ) {

		var $row = jQuery( row );

		uuid = $row.find( 'input[data-repeater-edit]' ).data( 'open' );

	}

	var $modal = jQuery( '[data-reveal="' + uuid + '"]' );
	
	var repeaterList = jQuery( '.toggl-alert-rbm-repeater-list' ).data( 'repeater-list' ),
		regex = new RegExp( repeaterList.replace( /[-\/\\^$*+?.()|[\]{}]/g, '\\$&' ) + '\\[\\d\\]\\[(.*)\\]', 'gi' );
	
	$modal.find( '.toggl-alert-select-multiple' ).each( function( index, select ) {
		
		var name = jQuery( select ).attr( 'name' ),
			match = regex.exec( name ),
			value = jQuery( select ).val();
		
		name = match[1].replace( '][', '' );
		
		regex.lastIndex = 0;
		
		// If there is no value, set it to an empty Array
		togglAlertSelected[ name ] = ( value === null ) ? [] : value;
		
	} );

	$modal.foundation( 'open' );

	// Ensure we're looking at the top of the Modal
	$modal.closest( '.reveal-overlay' ).scrollTop( 0 );

};

/**
 * Closes a Modal by its UUID
 * 
 * @param	  {string} uuid UUID of the Modal
 *					  
 * @since	  1.0.0
 * @return	  void
 */
window.togglAlertCloseModal = function( uuid ) {

	var $modal = jQuery( '[data-reveal="' + uuid + '"]' );

	$modal.foundation( 'close' );

};

( function( $ ) {
	'use strict';

	$( document ).ready( function() {

		window.togglAlertInitModals();

		// This JavaScript only loads on our custom Page, so we're fine doing this
		var $repeaters = $( '[data-toggl-alert-rbm-repeater]' );

		if ( $repeaters.length ) {
			
			$repeaters.on( 'toggl-alert-rbm-repeater-add', togglAlertInitModals );
			$repeaters.on( 'toggl-alert-rbm-repeater-add', togglAlertOpenModal );
			
		}

	} );
	
	$( document ).on( 'click touched', '[data-repeater-edit]', function() {
		
		togglAlertOpenModal( $( this ).data( 'open' ) );
		
	} );
	
	$( document ).on( 'open.zf.reveal', '.toggl-alert-rbm-repeater-content.reveal', function() {
		
		window.rbmFHinitField( jQuery( this ) );
		
		window.inittogglAlertRepeaterColorpickers( this );
		window.initTogglAlertSelect2( this );
		window.inittogglAlertTextarea( this );
		window.fixtogglAlertSelect( this );
		
		togglAlertModalOpen = true;
		
	} );
	
	$( document ).on( 'closed.zf.reveal', '.toggl-alert-rbm-repeater-content.reveal', function() {
		
		togglAlertModalOpen = false;
		
	} );
	
	var fieldTimeout;
	
	// Save the Timestamp Format since it would otherwise be the only thing not saving via Ajax
	$( document ).on( 'change blur', '.toggl-alert-timestamp-format', function() {

		clearTimeout( fieldTimeout );

		var $field = $( this ),
			$row = $field.closest( 'td' );

		// Prevent firing multiple times if Blur and Change happen at basically the same time
		fieldTimeout = setTimeout( function() {

			var value = $field.val().trim();

			if ( value !== '' ) {

				$field.attr( 'readyonly', true );

				var data = {
					action: 'insert_toggl_alert_rbm_timestamp_format',
					format: value
				};
				
				data.toggl_alert_settings_nonce = $( '#toggl_alert_settings_nonce' ).val();
				
				$row.find( '.saving' ).show();

				$.ajax( {
					'type' : 'POST',
					'url' : togglAlert.ajax,
					'data' : data,
					success : function( response ) {

						$field.attr( 'readyonly', false );
						
						$row.find( '.saving' ).hide();

						// Highlight Green
						$row.effect( 'highlight', { color : '#DFF2BF' }, 1000 );

						$( document ).trigger( 'toggl-alert-rbm-timestamp-format-updated', [value] );

					},
					error : function( request, status, error ) {
						$field.attr( 'readyonly', false );
						$row.find( '.saving' ).hide();
					}
				} );

			}

		} );

	} );
	
	window.onbeforeunload = function ( event ) {

		if ( togglAlertModalOpen ) {
			
			var confirmationMessage = togglAlert.i18n.onbeforeunload;

			event.returnValue = confirmationMessage; // Gecko, Trident, Chrome 34+
			return confirmationMessage; // Gecko, WebKit, Chrome <34
			
		}
	
	};

} )( jQuery );