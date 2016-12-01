console.log( 'zes_vars', zes_vars );
jQuery( document ).ready(function( $ ) {
	$( '#zes-template' ).change( function() {

	    if( $('#zes-template').val() == 'welcome' ) {
	        $( '.zes-name' ).show();
	        $( '.zes-message' ).show();
	    } else {
	        $( '.zes-name' ).hide();
	        $( '.zes-message' ).hide();
	    }
	});

	$( '#zes-sender' ).on( 'keyup', function() {

		var data = {
			action 			: 'zes_get_ajax',
			zes_nonce		: zes_vars.nonce,
	        zes_template	: $( '#zes-template' ).val(),
	        zes_name 	 	: $( '#zes-name' ).val(),
	        zes_message  	: $( '#zes-message' ).val(),
	        zes_email   	: $( '#zes-email' ).val()
		};

		$.post(ajaxurl, data, function( response ){
			$( '.zes-email-template' ).html(response);
		});
	});

	$( '#zes-sender' ).trigger( 'keyup' );
});