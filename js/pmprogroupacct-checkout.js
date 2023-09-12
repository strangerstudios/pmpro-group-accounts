jQuery( document ).ready( function( $ ) {
	// Check when the #pmprogroupacct_apply_group_code button is clicked.
	$( '#pmprogroupacct_apply_group_code' ).click( function() {
		// Get the group code.
		var group_code = $( '#pmprogroupacct_group_code' ).val();

		// If the group code is empty, do nothing.
		if ( group_code == '' ) {
			return;
		}

		// Get the current URL.
		var url = window.location.href;

		// Add the group code to the URL.
		if ( url.indexOf( 'pmprogroupacct_group_code=' ) > 0 ) {
			// If the URL already has a group code, replace it with the new group code.
			url = url.replace( /pmprogroupacct_group_code=[^&]+/, 'pmprogroupacct_group_code=' + group_code );
		} else if ( url.indexOf( '?' ) > 0 ) {
			// If the URL already has a query string, append the group code to it.
			url += '&pmprogroupacct_group_code=' + group_code;
		} else {
			// If the URL does not have a query string, add one with the group code.
			url += '?pmprogroupacct_group_code=' + group_code;
		}

		// Redirect to the new URL.
		window.location.replace( url );
	} );
} );
