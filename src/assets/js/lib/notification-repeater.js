/**
 * Global, overwritten on Modal open. This allows us to reliably determine the most recently selected <option>
 * The Object holds Arrays for any select[multiple="true"] elements
 * Oddly there's no good way to do this
 *									 
 * @since	  1.2.0
 */
var togglAlertSelected = {};

( function( $ ) {
	'use strict';
	
	/**
	 * Conditionally Hide/Show Fields based on the selected Trigger
	 * 
	 * @param	  {Event|String}  row		  Either the Event from creating a new Row or the Slack Trigger Field
	 * @param	  {Object|string} option_class The new Row (unused) or the Value of the Slack Trigger Field
	 *									 
	 * @since	  1.0.0
	 * @return	  void
	 */
	var togglAlertConditionalFields = function( row, option_class ) {
		
		// Handle newly created Rows
		if ( row.type == 'toggl-alert-rbm-repeater-add' ) {
			row = option_class;
			option_class = '';
		}
		else {
			row = $( row ).closest( '.toggl-alert-rbm-repeater-content' );
		}
		
		if ( option_class == '' ) {
			
			$( row ).find( 'select' ).each( function( index, select ) {
				
				$( select ).val( '' );
				
				if ( $( select ).hasClass( 'select2' ) ) {
					$( select ).trigger( 'change' );
				}
				
			} );
			
			$( row ).find( '.toggl-alert-conditional' ).closest( 'td' ).addClass( 'hidden' );
			
		}
		else {

			$( row ).find( '.toggl-alert-conditional.' + option_class ).closest( 'td.hidden' ).removeClass( 'hidden' );
			$( row ).find( '.toggl-alert-conditional' ).not( '.' + option_class ).closest( 'td' ).addClass( 'hidden' );
			
		}
		
		$( row ).find( 'select.select2' ).each( function( index, field ) {

			// No Tab index for the "hidden" Select field
			$( field ).attr( 'tabindex', -1 );

			// Why would you be unable to tab into it by default?!?!
			//$( field ).siblings( '.select2-container' ).find( '.chosen-single' ).attr( 'tabindex', 0 );
			
		} );
		
		$( row ).find( '.required' ).each( function( index, field ) {
			
			if ( $( field ).closest( 'td' ).hasClass( 'hidden' ) ) {
				$( field ).attr( 'required', false );
			}
			else {
				$( field ).attr( 'required', true );
			}
			
		} );
		
		$( row ).find( 'input[type="checkbox"].default-checked' ).each( function( index, checkbox ) {
			
			$( checkbox ).prop( 'checked', true );
			
		} );
		
		$( document ).trigger( 'toggl-alert-conditional-fields-set', row );

	}
	
	/**
	 * Add Notification Status Indiciators to show whether or not a Notification is "active"
	 * 
	 * @since	  1.0.0
	 * @return	  void
	 */
	var togglAlertNotificationIndicators = function() {
		
		$( '.repeater-header div[data-repeater-default-title]' ).each( function( index, header ) {
			
			var active = true,
				$repeaterItem = $( header ).closest( 'div[data-repeater-item]' ),
				uuid = $repeaterItem.find( '[data-repeater-edit]' ).data( 'open' ),
				$modal = $( '[data-reveal="' + uuid + '"]' );
			
			// Ensure Required Fields are Filled Out
			// This should only apply to Non-Saved Notifications, but if someone gets cheeky and attempts to get around my form validation this will tell them that they dun goof'd
			$modal.find( '.required' ).each( function( valueIndex, field ) {
				
				if ( ! $( field ).closest( 'td' ).hasClass( 'hidden' ) && 
					$( field ).val() === null ) {
					active = false;
					return false; // Break out of execution, we already know we're invalid
				}
				
			} );
			
			// If we're not saved yet
			if ( $modal.find( '.toggl-alert-post-id' ).val() == '' ) {
				active = false;
			}
			
			// Check for a API Token
			if ( $( '.toggl-alert-api-token' ).val() == '' ) {
				active = false;
			}
			
			$( header ).siblings( '.status-indicator' ).remove();
			
			if ( active === true ) {
				
				$( header ).after( '<span class="active status-indicator dashicons dashicons-yes" aria-label="' + togglAlert.i18n.inactiveText + '"></span>' );
				
			}
			else {
				
				$( header ).after( '<span class="inactive status-indicator dashicons dashicons-no" aria-label="' + togglAlert.i18n.inactiveText + '"></span>' );
				
			}
			
		} );
		
	}
	
	/**
	 * Attach Event Handlers for things outside of basic Repeater-scope
	 * 
	 * @since	  1.0.0
	 * @return	  void
	 */
	var inittogglAlertRepeaterFunctionality = function() {
		
		// This JavaScript only loads on our custom Page, so we're fine doing this
		var $repeaters = $( '[data-toggl-alert-rbm-repeater]' );
		
		if ( $repeaters.length ) {
			
			$repeaters.on( 'toggl-alert-rbm-repeater-add', togglAlertConditionalFields );
			
			$( document ).ready( function() {
				togglAlertNotificationIndicators();
			} );
			
			$( document ).on( 'closed.zf.reveal', '.toggl-alert-rbm-repeater-content.reveal', function() {
				togglAlertNotificationIndicators();
			} );
			
			$( document ).on( 'toggl-alert-rbm-api-token-updated', function( event, url ) {
				togglAlertNotificationIndicators();
			} );
			
		}
		
	}
	
	inittogglAlertRepeaterFunctionality();
	
	$( document ).ready( function() {
		
		// Handle conditional fields on Page Load
		$( '.toggl-alert-trigger' ).each( function( index, trigger ) {
			togglAlertConditionalFields( trigger, $( trigger ).val() );
		} );
		
		// And toggle them on Change
		$( document ).on( 'change', '.toggl-alert-trigger', function() {
			togglAlertConditionalFields( $( this ), $( this ).val() );
		} );
		
	} );

} )( jQuery );