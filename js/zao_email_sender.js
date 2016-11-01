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

	$( '#zes-sender' ).change( function() {
		data = {
			action 		 : 'zes_get_ajax',
			zes_nonce	 : zes_vars.zes_nonce,
	        zes_template : $( '#zes-template' ).select().val(),
	        zes_name 	 : $( '#zes-name' ).blur().val(),
	        zes_message  : $( '#zes-message' ).blur().val()
		}

		$.post(ajaxurl, data, function( response ){
			$( '.zes-email-template' ).html(response);
		});
	})
});