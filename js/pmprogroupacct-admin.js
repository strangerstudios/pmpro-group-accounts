jQuery( document ).ready( function( $ ) {
	// Handle showing/hiding the Group Account settings.
	function pmprogroupacct_update_edit_level_field_visibility() {
		if ( $( '#pmprogroupacct_child_level_ids' ).val() && $( '#pmprogroupacct_child_level_ids' ).val().length == 0 ) {
			$( '.pmprogroupacct_setting' ).hide();
		} else {
			$( '.pmprogroupacct_setting' ).show();
			pmprogroupacct_update_edit_level_group_type_field_visibility();
			pmprogroupacct_update_edit_level_pricing_model_field_visibility();
		}
	}
	pmprogroupacct_update_edit_level_field_visibility();
	$( '#pmprogroupacct_child_level_ids' ).select2();
	$( '#pmprogroupacct_child_level_ids' ).change( function() {
		pmprogroupacct_update_edit_level_field_visibility();
	} );

	// Handle showing/hiding the settings for group type.
	function pmprogroupacct_update_edit_level_group_type_field_visibility() {
		// Hide all seats settings.
		$( '.pmprogroupacct_group_type_setting' ).hide();
		// Show the seats settings for the selected group type.
		if ( $( '#pmprogroupacct_child_level_ids' ).val() && $( '#pmprogroupacct_child_level_ids' ).val().length > 0 ) {
			$( '.pmprogroupacct_group_type_setting_' + $( '#pmprogroupacct_group_type' ).val() ).show();
		}
	}
	pmprogroupacct_update_edit_level_group_type_field_visibility();
	$( '#pmprogroupacct_group_type' ).change( function() {
		pmprogroupacct_update_edit_level_group_type_field_visibility();
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

	// Function to toggle the warning based on the recurring checkbox
	function pmprogroupacct_toggle_recurring_warning() {
		var priceApplication = $('#pmprogroupacct_price_application').val();
		var isRecurringUnchecked = $('#recurring').is(':not(:checked)');

		// Show the warning if price application is 'both' or 'recurring' and the recurring checkbox is unchecked
		if ( ( priceApplication === 'both' || priceApplication === 'recurring' ) && isRecurringUnchecked ) {
			$( '#pmprogroupacct_pricing_model_warning_recurring_billing').show();
		} else {
			$( '#pmprogroupacct_pricing_model_warning_recurring_billing').hide();
		}
	}
	$( '#recurring, #pmprogroupacct_price_application' ).change(function() {
		pmprogroupacct_toggle_recurring_warning();
	});

	// Initial check to set the correct state when the page loads
	pmprogroupacct_toggle_recurring_warning();

	// Function to toggle the warning based on the level pricing
	function pmprogroupacct_toggle_free_level_warning() {
		var priceModel = $('#pmprogroupacct_pricing_model').val();
		var initialPayment = parseFloat( $( 'input[name="initial_payment"]' ).val() );
		var isRecurringUnchecked = $('#recurring').is(':not(:checked)');

		// Show the warning if priceModel is 'fixed' and the level is free.
		if ( priceModel === 'fixed' && initialPayment === 0 && isRecurringUnchecked ) {
			$( '#pmprogroupacct_pricing_model_warning_free_level').show();
		} else {
			$( '#pmprogroupacct_pricing_model_warning_free_level').hide();
		}
	}
	$( 'input[name="initial_payment"], #recurring, #pmprogroupacct_pricing_model' ).change(function() {
		pmprogroupacct_toggle_free_level_warning();
	});

	// Initial check to set the correct state when the page loads
	pmprogroupacct_toggle_free_level_warning();
} );
