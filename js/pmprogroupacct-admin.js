jQuery( document ).ready( function( $ ) {
	// Handle showing/hiding the Group Account settings.
	function pmprogroupacct_update_edit_level_field_visibility() {
		if ( $( '#pmprogroupacct_child_level_ids' ).val() && $( '#pmprogroupacct_child_level_ids' ).val().length == 0 ) {
			$( '.pmprogroupacct_setting' ).hide();
		} else {
			$( '.pmprogroupacct_setting' ).show();
			pmprogroupacct_update_edit_level_pricing_model_field_visibility();
		}
	}
	pmprogroupacct_update_edit_level_field_visibility();
	$( '#pmprogroupacct_child_level_ids' ).select2();
	$( '#pmprogroupacct_child_level_ids' ).change( function() {
		pmprogroupacct_update_edit_level_field_visibility();
	} );

	// Handle showing/hiding the settings for indiviual pricing models.
	function pmprogroupacct_update_edit_level_pricing_model_field_visibility() {
		// Hide all pricing models.
		$( '.pmprogroupacct_pricing_setting' ).hide();
		// Show the pricing model settings for the selected pricing model.
		$( '.pmprogroupacct_pricing_setting_' + $( '#pmprogroupacct_pricing_model' ).val() ).show();
		// If the pricing model is not "none", show 'pmprogroupacct_pricing_setting_paid'.
		if ( $( '#pmprogroupacct_pricing_model' ).val() != 'none' ) {
			$( '.pmprogroupacct_pricing_setting_paid' ).show();
		}
	}
	pmprogroupacct_update_edit_level_pricing_model_field_visibility();
	$( '#pmprogroupacct_pricing_model' ).change( function() {
		pmprogroupacct_update_edit_level_pricing_model_field_visibility();
	} );
} );
