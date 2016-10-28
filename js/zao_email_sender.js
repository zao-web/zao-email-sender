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

	function getVals() {
		var $zesName = $( '#zes-name' ).blur().val();
		var $zesTemplate = $( '#zes-template' ).select().val();
		var $zesMessage = $( '#zes-message' ).blur().val();
	}

	$( '#zes-sender' ).change( function() {
		data = {
			action 		 : 'zes_get_ajax',
			//zes_nonce	 : zes_vars.zes_nonce,
	        zes_template : 'zes-template',
	        zes_name 	 : 'zes-name',
	        zes_message  : 'zes-message'
		}

		$.post(ajaxurl, data, function( response ){
			$( '.viewer' ).html(response);
		});
	})
});