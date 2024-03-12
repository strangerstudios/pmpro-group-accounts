jQuery( document ).ready( function( $ ) {

	// Initialize select2 component for the child level select box.
	$( '#pmprogroupacct_child_level_ids' ).select2();

	//
	const isAnyChildLevelSelected = $( '#pmprogroupacct_child_level_ids' ).val() && $( '#pmprogroupacct_child_level_ids' ).val().length > 0

	$( '#pmprogroupacct_child_level_ids' ).change( function() {
		pmprogroupacct_update_edit_level_field_visibility();
	} );

	$( '#pmprogroupacct_group_type' ).change( function() {
		pmprogroupacct_update_edit_level_group_type_field_visibility();
	} );

	$( '#pmprogroupacct_pricing_model' ).change( function() {
		pmprogroupacct_update_edit_level_pricing_model_field_visibility();
	} );

	$('#pmprogroupacct_min_seats').change( function() {
		pmprogroupacct_update_seats_min_attribute();
		pmprogroupacct_toggle_variable_warning();
	} );

	$('#pmprogroupacct_max_seats').change( function() {
		pmprogroupacct_update_seats_max_attribute();
		pmprogroupacct_toggle_variable_warning();
	} );

	$( '#recurring, #pmprogroupacct_price_application' ).change( function() {
		pmprogroupacct_toggle_recurring_warning();
	} );

	$( 'input[name="initial_payment"], #recurring, #pmprogroupacct_pricing_model' ).change( function() {
		pmprogroupacct_toggle_free_level_warning();
		pmprogroupacct_toggle_variable_warning();
	} );

	$( '.pmprogroupacct_pricing_setting_variable_button' ).click( function( ev ) {
		ev.preventDefault();
		pmprogroupacct_new_row();
	} );

	$( '.pmprogroupacct_pricing_setting_variable_button_remove_row' ).click( function( ev ) {
		ev.preventDefault();
		$(this).closest('.pmprogroupacct_pricing_setting_variable_div_wrapper').remove();
	 });

	 // Trigger validation on blur and change
	$('.pmprogroupacct_pricing_setting_variable_from, .pmprogroupacct_pricing_setting_variable_to').on('blur change', function() {
		const $wrapper = $(this).closest('.pmprogroupacct_pricing_setting_variable_div_wrapper');
		if ( !validateSequence( $wrapper ) ) {
			// Show error message or handle validation failure here
			$('#pmprogroupacct_pricing_model_warning_variable_sequencial').show();
		} else {
			// Hide error message or handle validation success here
			$('#pmprogroupacct_pricing_model_warning_variable_sequencial').hide();
		}
	});


	//Initial check to set the correct min attribute when the page loads
	pmprogroupacct_update_seats_min_attribute();

	//Initial check to set the correct max attribute when the page loads
	pmprogroupacct_update_seats_max_attribute();

	//Call edit level visibility functions on page load
	pmprogroupacct_update_edit_level_field_visibility();

	// Initial check to set the correct state when the page loads
	pmprogroupacct_toggle_free_level_warning();

	//Initial check to set the correct state when the page loads
	pmprogroupacct_toggle_recurring_warning();

	//Initial check to set the correct state when the page loads
	pmprogroupacct_toggle_variable_warning();

	// Handle showing/hiding the Group Account settings.
	function pmprogroupacct_update_edit_level_field_visibility() {
		if ( ! isAnyChildLevelSelected ) {
			$( '.pmprogroupacct_setting' ).hide();
		} else {
			$( '.pmprogroupacct_setting' ).show();
			pmprogroupacct_update_edit_level_group_type_field_visibility();
			pmprogroupacct_update_edit_level_pricing_model_field_visibility();
		}
	}
		
	// Handle showing/hiding the settings for group type.
	function pmprogroupacct_update_edit_level_group_type_field_visibility() {
		// Hide all seats settings.
		$( '.pmprogroupacct_group_type_setting' ).hide();
		// Show the seats settings for the selected group type.
		if ( isAnyChildLevelSelected ) {
			const groupType = $( '#pmprogroupacct_group_type' ).val();
			$( '.pmprogroupacct_group_type_setting_' + groupType ).show();
			if ( groupType === 'fixed' ) {
				$( '.pmprogroupacct_pricing_setting_variable_range' ).hide();
				$( '#pmprogroupacct_pricing_model' ).find('option[value="variable"]').hide();
			} else {
				$( '#pmprogroupacct_pricing_model' ).find('option[value="variable"]').show();
			}

		}
	}

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

	// Function to update the min value of max seats based on min seats.
	function pmprogroupacct_update_seats_min_attribute() {
		const minSeats = $( '#pmprogroupacct_min_seats' ).val();
		$( '#pmprogroupacct_max_seats' ).attr( 'min', minSeats );
		$('.pmprogroupacct_pricing_setting_variable_div_wrapper .pmprogroupacct_pricing_setting_variable_from').attr( 'min', minSeats );
		$('.pmprogroupacct_pricing_setting_variable_div_wrapper .pmprogroupacct_pricing_setting_variable_to').attr( 'min', minSeats );
	}

	// Function to update the max value of min seats based on max seats.
	function pmprogroupacct_update_seats_max_attribute() {
		const maxSeats = $( '#pmprogroupacct_max_seats' ).val();
		$( '#pmprogroupacct_min_seats' ).attr( 'max', maxSeats - 1 );
		$('.pmprogroupacct_pricing_setting_variable_div_wrapper').each( function( index, element ) {
			const $element = $( element );
			$element.find( '.pmprogroupacct_pricing_setting_variable_from' ).attr( 'max', maxSeats );
			$element.find( '.pmprogroupacct_pricing_setting_variable_to' ).attr( 'max', maxSeats );
		} );
	}

	// Function to toggle the warning based on the recurring checkbox
	function pmprogroupacct_toggle_recurring_warning() {
		const priceApplication = $('#pmprogroupacct_price_application').val();
		const isRecurringUnchecked = $('#recurring').is(':not(:checked)');

		// Show the warning if price application is 'both' or 'recurring' and the recurring checkbox is unchecked
		if ( ( priceApplication === 'both' || priceApplication === 'recurring' ) && isRecurringUnchecked ) {
			$( '#pmprogroupacct_pricing_model_warning_recurring_billing').show();
		} else {
			$( '#pmprogroupacct_pricing_model_warning_recurring_billing').hide();
		}
	}

	// Function to toggle the warning based on the level pricing
	function pmprogroupacct_toggle_free_level_warning() {
		const priceModel = $('#pmprogroupacct_pricing_model').val();
		const initialPayment = parseFloat( $( 'input[name="initial_payment"]' ).val() );
		const isRecurringUnchecked = $('#recurring').is(':not(:checked)');

		// Show the warning if priceModel is 'fixed' and the level is free.
		if ( priceModel === 'fixed' && initialPayment === 0 && isRecurringUnchecked ) {
			$( '#pmprogroupacct_pricing_model_warning_free_level').show();
		} else {
			$( '#pmprogroupacct_pricing_model_warning_free_level').hide();
		}
	}

	// Function to toggle the warning based on the variable pricing model
	function pmprogroupacct_toggle_variable_warning() {
		const priceModel = $('#pmprogroupacct_pricing_model').val();
		const minSeats = $( '#pmprogroupacct_min_seats' ).val();
		const maxSeats = $( '#pmprogroupacct_max_seats' ).val();
		$( '#pmprogroupacct_pricing_model_warning_variable').hide();
		if ( priceModel === 'variable' && $( 'pmprogroupacct_group_type' ).val() === 'fixed'  ||  minSeats === maxSeats ) {
			$( '#pmprogroupacct_pricing_model_warning_variable').show();
		}
	}

	// Function to add a new row to the variable pricing settings table.
	function pmprogroupacct_new_row() {
		let $newRow = $( '.pmprogroupacct_pricing_setting_variable_div_wrapper' );
		if ($newRow.length > 1) {
			$newRow = $( $newRow[0] );
		}
		$newRow = $newRow.clone();
		$newRow.find('.pmprogroupacct_pricing_setting_variable_from, .pmprogroupacct_pricing_setting_variable_to').on('blur change', function() {
			const $wrapper = $(this).closest('.pmprogroupacct_pricing_setting_variable_div_wrapper');
			if ( !validateSequence( $wrapper ) ) {
				// Show error message or handle validation failure here
				$('#pmprogroupacct_pricing_model_warning_variable_sequencial').show();
			} else {
				// Hide error message or handle validation success here
				$('#pmprogroupacct_pricing_model_warning_variable_sequencial').hide();
			}
		});
		$trash = $('<span/>').addClass("dashicons dashicons-trash");
		$trash.click( function( ev ) {
			ev.preventDefault();
			$(this).closest('.pmprogroupacct_pricing_setting_variable_div_wrapper').remove();
		} );
		$newRow.find( 'button' ).replaceWith($trash);
		$newRow.removeAttr( 'id' );
		$newRow.find( 'input' ).val( '' );
		$newRow.find( 'button' ).click( function( ev ) {
			ev.preventDefault();
			pmprogroupacct_new_row();
		} );
		$newRow.insertBefore( $( '.pmprogroupacct_pricing_setting_variable td .description' ) );
	}

	// Function to validate the sequence for variable model pricing
	function validateSequence( $wrapper ) {
		const $from = $wrapper.find( '.pmprogroupacct_pricing_setting_variable_from' );
		const $to = $wrapper.find( '.pmprogroupacct_pricing_setting_variable_to' );

		// Check if "To" is greater than "From"
		if ( parseInt( $to.val() ) < parseInt( $from.val() ) ) {
			return false;
		}

		// Check if current "From" is greater than previous "To"
		const $prevWrapper = $wrapper.prev( '.pmprogroupacct_pricing_setting_variable_div_wrapper' );
		if ( $prevWrapper.length > 0 ) {
			const $prevTo = $prevWrapper.find( '.pmprogroupacct_pricing_setting_variable_to' );
			if ( parseInt($from.val()) < parseInt($prevTo.val()) ) {
				return false;
			}
		}
		return true;
    }

} );