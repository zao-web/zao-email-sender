jQuery( document ).ready(function( $ ) {
	$( '#zes-template' ).change( function() {

	    if( $('#zes-template').val() == 'welcome' ) {
	        $( '.zes-name' ).show();
	    } else {
	        $( '.zes-name' ).hide();
	    }

	});
});