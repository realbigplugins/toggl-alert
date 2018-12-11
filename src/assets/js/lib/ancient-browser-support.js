if ( ! HTMLFormElement.prototype.reportValidity ) {
	
	// Quick and dirty global to know what I'm working with
	window.togglAlertAncientBrowser = true;

	/**
	 * Wait, people use IE and Safari outside of downloading Chrome?
	 * 
	 * @since	  1.1.0
	 * @return	  void
	 */
	HTMLFormElement.prototype.reportValidity = function () {
		
		var requiredError = togglAlert.i18n.validationError,
			valid = true;
		
		// Remove all old Validation Errors
		jQuery( this ).find( '.validation-error' ).remove();
		
		jQuery( this ).find( '.required' ).each( function( index, element ) {
			
			// Reset Custom Validity Message
			element.setCustomValidity( '' );
			
			if ( ! jQuery( element ).closest( 'td' ).hasClass( 'hidden') && 
				( jQuery( element ).val() === null || jQuery( element ).val() == '' ) ) {
				
				element.setCustomValidity( requiredError );
				jQuery( element ).before( '<span class="validation-error">' + requiredError + '</span>' );
				
				valid = false;
				
			}
			
		} );
		
		if ( ! valid ) {
			
			jQuery( this ).closest( '.reveal-overlay' ).scrollTop( jQuery( this ).find( '.validation-error:first-of-type' ) );
			return valid;
			
		}
		
		return valid;
		
	};
	
};