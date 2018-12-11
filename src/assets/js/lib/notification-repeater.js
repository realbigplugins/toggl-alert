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

} )( jQuery );