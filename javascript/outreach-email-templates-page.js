(function( $ ) {	

	/**
	 *
	 *	Event Handlers
	 * 
	 */
	$( '.add-new-template' ).click( function() {
		$( '.new-email-template' ).removeClass( 'hidden' ).find( 'input[name="email_actions[]"]').val( 'insert' );
		$( this ).hide();
	});

	$( '.delete-template' ).click( function(event) {
		event.preventDefault();
		var $template = $( this ).closest( '.email-template' );
		$template.find( 'input[name="email_actions[]"]').val( 'delete' );
		$( 'form.email-templates-form' ).submit();
	});


})( jQuery );