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
});