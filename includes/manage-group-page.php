<?php
/**
 * Add a page setting for the Manage Group page.
 *
 * @since 1.0
 *
 * @param array $settings Array of settings for the PMPro settings page.
 * @return array $settings Array of settings for the PMPro settings page.
 */
function pmprogroupacct_extra_page_settings( $pages ) {
	$pages['pmprogroupacct_manage_group'] = array(
		'title' => esc_html__( 'Manage Group', 'pmpro-group-accounts' ),
		'content' => '[pmprogroupacct_manage_group]',
		'hint' => esc_html__( 'Include the shortcode [pmprogroupacct_manage_group].', 'pmpro-group-accounts' ),
	);
	return $pages;
}
add_filter( 'pmpro_extra_page_settings', 'pmprogroupacct_extra_page_settings' );

/**
 * Add "Manage Group" as an action link on the frontend Membership Account page if
 * the user has a group for the passed level.
 *
 * @since 1.0
 *
 * @param array $action_links Array of action links for the Membership Account page.
 * @param int   $level_id    ID of the level the action links are being shown for.
 */
function pmprogroupacct_member_action_links( $action_links, $level_id ) {
	global $current_user;

	// If the user isn't logged in, return the default action links.
	if ( ! is_user_logged_in() ) {
		return $action_links;
	}

	// Get the group for this user and level.
	$group = PMProGroupAcct_Group::get_group_by_parent_user_id_and_parent_level_id( $current_user->ID, $level_id );
	if ( empty( $group ) ) {
		return $action_links;
	}

	// Get the URL for the Manage Group page.
	$manage_group_url = pmpro_url( 'pmprogroupacct_manage_group' );
	if ( empty( $manage_group_url ) ) {
		return $action_links;
	}

	// Add the "Manage Group" action link.
	$action_links['manage_group'] = '<a href="' . esc_url( add_query_arg( 'pmprogroupacct_group_id', $group->id, $manage_group_url ) ) . '">' . esc_html__( 'Manage Group', 'pmpro-group-accounts' ) . '</a>';

	return $action_links;
}
add_filter( 'pmpro_member_action_links', 'pmprogroupacct_member_action_links', 10, 2 );

/**
 * Redirect to the Membership Account page if someone tries to access the
 * Manage Group page without a group ID or access.
 *
 * @since 1.0
 * @return void
 */
function pmprogroupacct_manage_group_preheader() {
	if ( ! is_admin() ) {
		global $pmpro_pages;

		// Return if this is not the Manage Group page.
		if ( empty( $pmpro_pages['pmprogroupacct_manage_group'] ) || ! is_page( $pmpro_pages['pmprogroupacct_manage_group'] ) ) {
			return;
		}

		// Set up the $redirect variable.
		$redirect = false;

		// Make sure that a group ID was passed. If not, set $redirect to true.
		if ( empty( $_REQUEST['pmprogroupacct_group_id'] ) ) {
			$redirect = true;
		} else {
			// Get the group.
			$group = new PMProGroupAcct_Group( intval( $_REQUEST['pmprogroupacct_group_id'] ) );

			// If the group doesn't exist, set $redirect to true.
			if ( empty( $group->id ) ) {
				$redirect = true;
			}

			// Check if the current user can view this page.
			$is_admin = current_user_can( apply_filters( 'pmpro_edit_member_capability', 'manage_options' ) );
			if ( ! $is_admin && $group->group_parent_user_id !== get_current_user_id() ) {
				$redirect = true;
			}
		}

		// Redirect if necessary.
		if ( ! empty( $redirect ) ) {
			wp_redirect( pmpro_url( 'account' ) );
			exit;
		}
	}
}
add_action( 'wp', 'pmprogroupacct_manage_group_preheader', 1 );

/**
 * Show the content for the [pmprogroupacct_manage_group] shortcode.
 *
 * @since 1.0
 * @return string $content Content for the shortcode.
 */
function pmprogroupacct_shortcode_manage_group() {
	// Make sure that a group was passed. If not, show an error.
	if ( empty( $_REQUEST['pmprogroupacct_group_id'] ) ) {
		return '<p>' . esc_html__( 'No group was passed.', 'pmpro-group-accounts' ) . '</p>';
	}

	// Get the group.
	$group = new PMProGroupAcct_Group( intval( $_REQUEST['pmprogroupacct_group_id'] ) );

	// If the group doesn't exist, show an error.
	if ( empty( $group->id ) ) {
		return '<p>' . esc_html__( 'You do not have permission to view this group.', 'pmpro-group-accounts' ) . '</p>';
	}

	// Check if the current user can view this page.
	$is_admin = current_user_can( apply_filters( 'pmpro_edit_member_capability', 'manage_options' ) );
	if ( ! $is_admin && $group->group_parent_user_id !== get_current_user_id() ) {
		// The current user doesn't have permission to view this group.
		return '<p>' . esc_html__( 'You do not have permission to view this group.', 'pmpro-group-accounts' ) . '</p>';
	}

	// Get the group settings for the level that this group is for.
	$group_settings = pmprogroupacct_get_settings_for_level( $group->group_parent_level_id );

	// If the user is trying to remove a group member, remove them.
	$removal_message = '';
	if ( ! empty( $_REQUEST['pmprogroupacct_remove_group_members'] ) ) {
		// Make sure that the nonce is valid.
		if ( ! wp_verify_nonce( $_REQUEST['pmprogroupacct_remove_group_members_nonce'], 'pmprogroupacct_remove_group_members' ) ) {
			$removal_message = '<div class="pmpro_message pmpro_error">' . esc_html__( 'Unable to validate your request. The member was not removed.', 'pmpro-group-accounts' ) . '</div>';
		}

		// Get the group members.
		$group_members = array();
		foreach ( $_REQUEST['pmprogroupacct_remove_group_members'] as $group_member_id ) {
			$group_members[] = new PMProGroupAcct_Group_Member( intval( $group_member_id ) );
		}

		// If the group member doesn't exist or the current user doesn't own this group, show an error.
		foreach ( $group_members as $group_member ) {
			if ( empty( $group_member->id ) || $group_member->group_id !== $group->id ) {
				$removal_message = '<div class="pmpro_message pmpro_error">' . esc_html__( 'You do not have permission to remove this group member.', 'pmpro-group-accounts' ) . '</div>';
			}
		}

		// If there wasn't an error, cancel the group member's membership, which will remove them from the group.
		if ( empty( $removal_message ) ) {
			foreach ( $group_members as $group_member ) {
				if ( pmpro_cancelMembershipLevel( $group_member->group_child_level_id, $group_member->group_child_user_id ) ) {
					// Membership cancelled. Force the group removal to happen now.
					pmpro_do_action_after_all_membership_level_changes();
				} else {
					// User must not have had this membership level. Remove them from the group.
					$group_member->update_group_child_status( 'inactive' );
				}
			}
			$removal_message = '<div class="pmpro_message pmpro_success">' . esc_html__( 'Group members removed.', 'pmpro-group-accounts' ) . '</div>';
		}
	}

	// If the user is trying to update the group settings, update them.
	$seats_message = '';
	if ( isset( $_REQUEST['pmprogroupacct_group_total_seats'] ) ) {
		// Make sure that the current user has permission to update this group.
		if ( ! $is_admin ) {
			$seats_message = '<div class="pmpro_message pmpro_error">' . esc_html__( 'You do not have permission to update this group.', 'pmpro-group-accounts' ) . '</div>';
		}

		// Make sure that the nonce is valid.
		if ( ! wp_verify_nonce( $_REQUEST['pmprogroupacct_update_group_settings_nonce'], 'pmprogroupacct_update_group_settings' ) ) {
			$seats_message = '<div class="pmpro_message pmpro_error">' . esc_html__( 'Unable to validate your request. The member was not removed.', 'pmpro-group-accounts' ) . '</div>';
		}

		// Make sure that the total seats is a number.
		if ( ! is_numeric( $_REQUEST['pmprogroupacct_group_total_seats'] ) ) {
			$seats_message = '<div class="pmpro_message pmpro_error">' . esc_html__( 'Total seats must be a number.', 'pmpro-group-accounts' ) . '</div>';
		}

		// Update the group settings.
		$group->update_group_total_seats( (int)$_REQUEST['pmprogroupacct_group_total_seats'] );

		// Show a success message.
		$seats_message = '<div class="pmpro_message pmpro_success">' . esc_html__( 'Group settings updated.', 'pmpro-group-accounts' ) . '</div>';
	}

	// If the user is trying to invite new members, invite them.
	$invite_message = '';
	if ( isset( $_REQUEST['pmprogroupacct_invite_new_members_emails'] ) ) {
		// Make sure that the nonce is valid.
		if ( ! wp_verify_nonce( $_REQUEST['pmprogroupacct_invite_new_members_nonce'], 'pmprogroupacct_invite_new_members' ) ) {
			$invite_message = '<div class="pmpro_message pmpro_error">' . esc_html__( 'Invalid nonce.', 'pmpro-group-accounts' ) . '</div>';
		}

		// Make sure that a level ID was passed.
		if ( ! empty( $invite_message ) && empty( $_REQUEST['pmprogroupacct_invite_new_members_level_id'] ) ) {
			$invite_message = '<div class="pmpro_message pmpro_error">' . esc_html__( 'No level ID was passed.', 'pmpro-group-accounts' ) . '</div>';
		}

		// Make sure that the level ID is an integer.
		if ( ! empty( $invite_message ) && ! is_numeric( $_REQUEST['pmprogroupacct_invite_new_members_level_id'] ) ) {
			$invite_message = '<div class="pmpro_message pmpro_error">' . esc_html__( 'Level ID must be a number.', 'pmpro-group-accounts' ) . '</div>';
		}

		// Make sure that the level ID can be claimed using this group code.
		if ( ! empty( $invite_message ) && ! in_array( (int)$_REQUEST['pmprogroupacct_invite_new_members_level_id'], array_map( 'intval', $group_settings['child_level_ids'] ), true ) ) {
			$invite_message = '<div class="pmpro_message pmpro_error">' . esc_html__( 'This level cannot be claimed using this group code.', 'pmpro-group-accounts' ) . '</div>';
		}

		// Make sure that email addresses were passed.
		if ( ! empty( $invite_message ) && empty( $_REQUEST['pmprogroupacct_invite_new_members_emails'] ) ) {
			$invite_message = '<div class="pmpro_message pmpro_error">' . esc_html__( 'No email addresses were passed.', 'pmpro-group-accounts' ) . '</div>';
		}

		// Make sure that the email addresses are valid. Each should be on a new line.
		if ( empty( $invite_message ) ) {
			$emails = explode( "\n", trim( $_REQUEST['pmprogroupacct_invite_new_members_emails'] ) );
			$valid_emails = array();
			$invalid_emails = array();

			foreach ( $emails as $email ) {
				// Trim whitespace from the email.
				$email = trim( $email );

				// If it's empty after trimming, skip it.
				if ( empty( $email ) ) {
					continue;
				}

				// Check the email and add to the appropriate array.
				if ( is_email( $email ) ) {
					$valid_emails[] = $email;
				} else {
					$invalid_emails[] = $email;
				}
			}
			if ( empty( $valid_emails ) ) {
				$invite_message = '<div class="pmpro_message pmpro_error">' . esc_html__( 'No valid email addresses were passed.', 'pmpro-group-accounts' ) . '</div>';
			}
		}

		// Send the invites.
		if ( ! empty( $valid_emails ) ) {
			$parent_user = get_userdata( $group->group_parent_user_id );
			$email_data = array(
				'pmprogroupacct_parent_display_name' => empty( $parent_user ) ? '[' . esc_html__( 'User Not Found' ) . ']' : $parent_user->display_name,
				'pmprogroupacct_invite_link' => esc_url( add_query_arg( array( 'level' => (int)$_REQUEST['pmprogroupacct_invite_new_members_level_id'], 'pmprogroupacct_group_code' => $group->group_checkout_code ), pmpro_url( 'checkout' ) ) ),
				'blog_name' => get_bloginfo( 'name' ),
			);
			$failed_emails = array();
			foreach ( $valid_emails as $valid_email ) {
				$email           = new PMProEmail();
				$email->template = 'pmprogroupacct_invite';
				$email->email    = $valid_email;
				$email->data     = $email_data;
				$success = $email->sendEmail();
				if ( ! $success ) {
					$failed_emails[] = $valid_email;
				}
			}

			$sent_emails = array_diff( $valid_emails, $failed_emails );
			if ( empty( $sent_emails ) ) {
				$invite_message = '<div class="pmpro_message pmpro_error">' . esc_html__( 'Failed to send emails.', 'pmpro-group-accounts' ) . '</div>';
			} elseif ( empty( $failed_emails ) && empty( $invalid_emails ) ) {
				$invite_message = '<div class="pmpro_message pmpro_success">' . esc_html__( 'Invites sent.', 'pmpro-group-accounts' ) . '</div>';
			} else {
				// Some emails were sent, but others were not. List the emails that were sent, the emails that failed to send, and the invalid emails.
				$invite_message = '<div class="pmpro_message pmpro_success">' . esc_html__( 'Invites sent to the following email addresses:', 'pmpro-group-accounts' ) . '</p><ul>';
				foreach ( $sent_emails as $sent_email ) {
					$invite_message .= '<li>' . esc_html( $sent_email ) . '</li>';
				}
				$invite_message .= '</ul>';
				if ( ! empty( $failed_emails ) ) {
					$invite_message .= '<p>' . esc_html__( 'Failed to send invites to the following email addresses:', 'pmpro-group-accounts' ) . '</p><ul>';
					foreach ( $failed_emails as $failed_email ) {
						$invite_message .= '<li>' . esc_html( $failed_email ) . '</li>';
					}
					$invite_message .= '</ul>';
				}
				if ( ! empty( $invalid_emails ) ) {
					$invite_message .= '<p>' . esc_html__( 'The following email addresses were invalid:', 'pmpro-group-accounts' ) . '</p><ul>';
					foreach ( $invalid_emails as $invalid_email ) {
						$invite_message .= '<li>' . esc_html( $invalid_email ) . '</li>';
					}
					$invite_message .= '</ul>';
				}
				$invite_message .= '</div>';
			}
		}
	}

	// If the user is trying to create a new group member, create them.
	$create_member_message = '';
	if ( ! empty( $_REQUEST['pmprogroupacct_create_member_submit'] ) ) {
		// Make sure that the nonce is valid.
		if ( empty( $_REQUEST['pmprogroupacct_create_member_nonce'] ) || ! wp_verify_nonce( $_REQUEST['pmprogroupacct_create_member_nonce'], 'pmprogroupacct_create_member' ) ) {
			$create_member_message = '<div class="pmpro_message pmpro_error">' . esc_html__( 'Invalid nonce.', 'pmpro-group-accounts' ) . '</div>';
		}

		// Make sure that there is an available seat in the group.
		if ( ! empty( $create_member_message ) && $group->is_accepting_signups() ) {
			$create_member_message = '<div class="pmpro_message pmpro_error">' . esc_html__( 'No available seats in this group.', 'pmpro-group-accounts' ) . '</div>';
		}

		// Make sure that we have an email.
		$email = empty( $_REQUEST['pmprogroupacct_create_member_email'] ) ? '' : sanitize_email( $_REQUEST['pmprogroupacct_create_member_email'] );
		if ( empty( $create_member_message ) && empty( $email ) ) {
			$create_member_message = '<div class="pmpro_message pmpro_error">' . esc_html__( 'No email address provided.', 'pmpro-group-accounts' ) . '</div>';
		}

		// Make sure that the email is valid.
		if ( empty( $create_member_message ) && ! is_email( $email ) ) {
			$create_member_message = '<div class="pmpro_message pmpro_error">' . esc_html__( 'Invalid email address.', 'pmpro-group-accounts' ) . '</div>';
		}

		// Make sure that we have a username.
		$username = empty( $_REQUEST['pmprogroupacct_create_member_username'] ) ? '' : sanitize_text_field( $_REQUEST['pmprogroupacct_create_member_username'] );
		if ( empty( $create_member_message ) && empty( $username ) ) {
			$create_member_message = '<div class="pmpro_message pmpro_error">' . esc_html__( 'Invalid username.', 'pmpro-group-accounts' ) . '</div>';
		}

		// Make sure that we have a password.
		$password = empty( $_REQUEST['pmprogroupacct_create_member_password'] ) ? '' : sanitize_text_field( $_REQUEST['pmprogroupacct_create_member_password'] );
		if ( empty( $create_member_message ) && empty( $password ) ) {
			$create_member_message = '<div class="pmpro_message pmpro_error">' . esc_html__( 'Invalid password.', 'pmpro-group-accounts' ) . '</div>';
		}

		// Make sure we have a valid level ID for this group.
		$level_id = empty( $_REQUEST['pmprogroupacct_create_member_level_id'] ) ? '' : intval( $_REQUEST['pmprogroupacct_create_member_level_id'] );
		if ( empty( $create_member_message ) && ( empty( $level_id ) || ! in_array( $level_id, $group_settings['child_level_ids'], true ) ) ) {
			$create_member_message = '<div class="pmpro_message pmpro_error">' . esc_html__( 'Invalid level ID.', 'pmpro-group-accounts' ) . '</div>';
		}

		// If we still don't have an error, create the user.
		if ( empty( $create_member_message ) ) {
			// Create the user.
			$user_id = wp_create_user( $username, $password, $email );

			// If user creation failed, show an error message.
			if ( is_wp_error( $user_id ) ) {
				$create_member_message = '<div class="pmpro_message pmpro_error">' . esc_html__( 'Error creating user.', 'pmpro-group-accounts' ) . ' ' . $user_id->get_error_message() . '</div>';
			}
		}

		// If we still don't have an error, change the user's level.
		if ( empty( $create_member_message ) ) {
			// Change the user's level.
			$change_level = pmpro_changeMembershipLevel( $level_id, $user_id );

			// If changing the level failed, show an error message.
			if ( empty( $change_level ) ) {
				$create_member_message = '<div class="pmpro_message pmpro_error">' . esc_html__( 'Error changing user level.', 'pmpro-group-accounts' );
			}
		}

		// If we still don't have an error, add the user to the group.
		if ( empty( $create_member_message ) ) {
			PMProGroupAcct_Group_Member::create( $user_id, $level_id, $group->id );
			$create_member_message = '<div class="pmpro_message pmpro_success">' . esc_html__( 'User created and added to group.', 'pmpro-group-accounts' ) . '</div>';
		}
	}

	// If the user is trying to generate a new group code, generate it.
	$generate_code_message = '';
	if ( ! empty( $_REQUEST['pmprogroupacct_generate_new_group_code'] ) ) {
		// Make sure that the nonce is valid.
		if ( empty( $_REQUEST['pmprogroupacct_generate_new_group_code_nonce'] ) || ! wp_verify_nonce( $_REQUEST['pmprogroupacct_generate_new_group_code_nonce'], 'pmprogroupacct_generate_new_group_code' ) ) {
			$generate_code_message = '<div class="pmpro_message pmpro_error">' . esc_html__( 'Invalid nonce.', 'pmpro-group-accounts' ) . '</div>';
		}

		// Generate a new group code.
		if ( empty( $generate_code_message ) ) {
			$group->regenerate_group_checkout_code();

			// Show a success message.
			$generate_code_message = '<div class="pmpro_message pmpro_success">' . esc_html__( 'New group code generated.', 'pmpro-group-accounts' ) . '</div>';
		}
	}

	// Get the active members in this group.
	$active_members = $group->get_active_members();

	// Get the old members in this group.
	$old_member_query_args = array(
		'group_id' => $group->id,
		'group_child_status' => 'inactive',
	);
	$old_members = PMProGroupAcct_Group_Member::get_group_members( $old_member_query_args );

	// Create UI.
	ob_start();
	?>
	<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro' ) ); ?>">
		<section id="pmprogroupacct_manage_group" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_section', 'pmprogroupacct_manage_group' ) ); ?>">
			<?php
			// We want admins to have more settings, like the ability to change the number of seats.
			if ( $is_admin ) {
				?>
				<div id="pmprogroupacct_manage_group_settings" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card', 'pmprogroupacct_manage_group_settings' ) ); ?>">
					<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_title pmpro_font-large' ) ); ?>"><?php esc_html_e( 'Group Settings (Admin Only)', 'pmpro-group-accounts' ); ?></h2>
					<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
						<?php echo wp_kses_post( $seats_message ); ?>
						<form id="pmprogroupacct_manage_group_seats" class="<?php echo pmpro_get_element_class( 'pmpro_form', 'pmprogroupacct_manage_group_seats' ); ?>" action="<?php echo esc_url( add_query_arg( 'pmprogroupacct_group_id', $group->id, pmpro_url( 'pmprogroupacct_manage_group' ) . '#pmprogroupacct_manage_group_settings' ) ) ?>" method="post">
							<fieldset class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fieldset' ) ); ?>">
								<div class="<?php echo pmpro_get_element_class( 'pmpro_form_fields' ); ?>">
									<div class="<?php echo pmpro_get_element_class( 'pmpro_form_field' ); ?>">
										<label for="pmprogroupacct_group_total_seats" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e( 'Total Seats', 'pmpro-group-accounts' ); ?></label>
										<input type="number" max="4294967295" name="pmprogroupacct_group_total_seats" id="pmprogroupacct_group_total_seats" class="<?php echo pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-number', 'pmprogroupacct_group_total_seats' ); ?>" value="<?php echo esc_attr( $group->group_total_seats ); ?>">
										<p class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_hint' ) ); ?>">
										<?php
											// Get the group parent.
											$group_parent = get_userdata( $group->group_parent_user_id );

											/* translators: %1$s is the group ID, %2$s is a link to edit the group owner with their display name. */
											printf( esc_html__( 'Change the settings for group ID %1$s managed by %2$s.', 'pmpro-group-accounts' ), esc_html( $group->id ), '<a href="' . esc_url( pmprogroupacct_member_edit_url_for_user( $group_parent ) ) . '">' . esc_html( $group_parent->display_name ) . '</a>' );
										?>
										</p>
									</div> <!-- end .pmpro_form_field -->
								</div> <!-- end .pmpro_form_fields -->
								<div class="<?php echo pmpro_get_element_class( 'pmpro_form_submit' ); ?>">
									<input type="hidden" name="pmprogroupacct_update_group_settings_nonce" value="<?php echo esc_attr( wp_create_nonce( 'pmprogroupacct_update_group_settings' ) ); ?>">
									<input type="submit" name="pmprogroupacct_update_group_settings_submit" class="<?php echo pmpro_get_element_class( 'pmpro_btn' ); ?>" value="<?php esc_attr_e( 'Update Settings', 'pmpro-group-accounts' ); ?>">
								</div> <!-- end .pmpro_form_submit -->
							</fieldset> <!-- end .pmpro_form_fieldset -->
						</form>
					</div> <!-- end .pmpro_card_content -->
				</div>
				<?php
			}
			?>
			<div id="pmprogroupacct_manage_group_members" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card', 'pmprogroupacct_manage_group_members' ) ); ?>">
				<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_title pmpro_font-large' ) ); ?>"><?php esc_html_e( 'Group Members', 'pmpro-group-accounts' ); ?> (<?php echo esc_html( number_format_i18n( count( $active_members ) ) ) . '/' . esc_html( number_format_i18n( (int)$group->group_total_seats ) ); ?>)</h2>
				<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
					<?php
					echo wp_kses_post( $removal_message );
					if ( empty( $active_members ) ) {
						echo '<p>' . esc_html__( 'There are no active members in this group.', 'pmpro-group-accounts' ) . '</p>';
					} else {
					?>
						<form id="pmprogroupacct_manage_group_change_members" class="<?php echo pmpro_get_element_class( 'pmpro_form', 'pmprogroupacct_manage_group_change_members' ); ?>" action="<?php echo esc_url( add_query_arg( 'pmprogroupacct_group_id', $group->id, pmpro_url( 'pmprogroupacct_manage_group' ) . '#pmprogroupacct_manage_group_members' ) ) ?>" method="post">
							<table class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_table' ) ); ?>">
								<thead>
									<tr>
										<th><?php esc_html_e( 'Username', 'pmpro-group-accounts' ); ?></th>
										<th><?php esc_html_e( 'Level', 'pmpro-group-accounts' ); ?></th>
										<th><?php esc_html_e( 'Remove', 'pmpro-group-accounts' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php
									foreach ( $active_members as $member ) {
										$user  = get_userdata( $member->group_child_user_id );
										$level = pmpro_getLevel( $member->group_child_level_id );
										?>
										<tr>
											<th data-title="<?php esc_attr_e( 'Username', 'pmpro-group-accounts' ); ?>"><?php echo esc_html( $user->user_login ); ?></th>
											<td data-title="<?php esc_attr_e( 'Level', 'pmpro-group-accounts' ); ?>"><?php echo esc_html( $level->name ); ?></td>
											<td data-title="<?php esc_attr_e( 'Remove', 'pmpro-group-accounts' ); ?>"><input type="checkbox" name="pmprogroupacct_remove_group_members[]" class="<?php echo pmpro_get_element_class( 'input' ); ?>" value="<?php echo esc_attr( $member->id ); ?>"></td>
										</tr>
										<?php
									}
									?>
								</tbody>
							</table>
							<div class="<?php echo pmpro_get_element_class( 'pmpro_form_submit' ); ?>">
								<?php wp_nonce_field( 'pmprogroupacct_remove_group_members', 'pmprogroupacct_remove_group_members_nonce' ); ?>
								<input type="submit" name="pmprogroupacct_remove_group_members_submit" class="<?php echo pmpro_get_element_class( 'pmpro_btn' ); ?>" value="<?php esc_attr_e( 'Remove Selected Members', 'pmpro-group-accounts' ); ?>" onclick="return confirm( '<?php esc_html_e( 'Are you sure that you would like to remove these users from your group?', 'pmpro-group-accounts' ); ?>' );">
							</div> <!-- end .pmpro_form_submit -->
						</form>
					<?php
					}
					?>
				</div> <!-- end .pmpro_card_content -->
			</div>
			<?php
			// Make sure that this group code has levels that can be claimed.
			if ( ! empty( $group_settings ) && ! empty( $group_settings['child_level_ids'] ) ) {
				?>
				<div id="pmprogroupacct_invite_new_members" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card' ) ); ?>">
					<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_title pmpro_font-large' ) ); ?>"><?php esc_html_e( 'Invite New Members', 'pmpro-group-accounts' ); ?></h2>
					<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
						<?php
						// Check if this group is accepting signups.
						if ( ! $group->is_accepting_signups() ) {
							echo '<p>' . esc_html__( 'This group is not accepting signups.', 'pmpro-group-accounts' ) . '</p>';
						} else {
							// Show the group code and the levels that can be claimed with links to checkout for those levels.
							?>
							<p><?php printf( esc_html__( 'Your Group Code is: %s', 'pmpro-group-accounts' ), '<span class="' . esc_attr( pmpro_get_element_class( 'pmpro_tag pmpro_tag-discount-code', 'pmpro_tag-discount-code' ) ) . '">' . esc_html( $group->group_checkout_code ) . '</span>' );?></p>
							<p><?php esc_html_e( 'New members can use this code to join your group at no additional cost.', 'pmpro-group-accounts' ); ?></p>
							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_spacer' ) ); ?>"></div>
							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fields' ) ); ?>">
								<?php
								foreach ( $group_settings['child_level_ids'] as $child_level_id ) {
									$child_level = pmpro_getLevel( $child_level_id );
									$checkout_url = add_query_arg( array( 'level' => $child_level->id, 'pmprogroupacct_group_code' => $group->group_checkout_code ), pmpro_url( 'checkout' ) );
									?>
									<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-textarea' ) ); ?>">
										<label for="pmprogroupaccount_link-level-<?php echo esc_attr( $child_level_id ); ?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label', 'pmprogroupaccount_link-level-' . esc_attr( $child_level_id ) ) ); ?>">
											<a href="<?php echo esc_url( $checkout_url ); ?>">
												<?php printf( esc_html__( 'For %s membership:', 'pmpro-group-accounts' ), esc_html( $child_level->name ) ); ?>
											</a>
										</label>
										<textarea id="pmprogroupaccount_link-level-<?php echo esc_attr( $child_level_id ); ?>" readonly class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-textarea', 'pmpro_form_input-textarea' ) ); ?>" rows="2"><?php echo esc_attr( $checkout_url ); ?></textarea>
									</div>
									<?php
								}
								?>
							</div> <!-- end .pmpro_form_fields -->

							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_spacer' ) ); ?>"></div>

							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_divider' ) ); ?>"></div>

							<?php
							// Show the group code and the levels that can be claimed with links to checkout for those levels.
							?>
							<form id="pmprogroupacct_generate_new_group_code" class="<?php echo pmpro_get_element_class( 'pmpro_form', 'pmprogroupacct_generate_new_group_code' ); ?>" action="<?php echo esc_url( add_query_arg( 'pmprogroupacct_group_id', $group->id, pmpro_url( 'pmprogroupacct_manage_group' ) . '#pmprogroupacct_generate_new_group_code' ) ) ?>" method="post">
								<h3 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_font-large' ) ); ?>"><?php esc_html_e( 'Generate a New Group Code', 'pmpro-group-accounts' ); ?></h3>
								<p><?php esc_html_e( 'Generate a new group code to prevent new members from joining your group with the current code. Your existing group members will remain in your group. This action is permanent and cannot be reversed.', 'pmpro-group-accounts' ); ?></p>
								<?php
								// Show error/success message.
								if ( ! empty( $generate_code_message ) ) {
									echo wp_kses_post( $generate_code_message );
								}

								// Create nonce.
								wp_nonce_field( 'pmprogroupacct_generate_new_group_code', 'pmprogroupacct_generate_new_group_code_nonce' );

								// Show group code regenerate button.
								?>
								<div class="<?php echo pmpro_get_element_class( 'pmpro_form_submit' ); ?>">
									<input type="submit" name="pmprogroupacct_generate_new_group_code" class="<?php echo pmpro_get_element_class( 'pmpro_btn' ); ?>" value="<?php esc_attr_e( 'Generate New Group Code', 'pmpro-group-accounts' ); ?>">
								</div> <!-- end .pmpro_form_submit -->
							</form>

							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_spacer' ) ); ?>"></div>

							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_divider' ) ); ?>"></div>

							<?php
							// Show a form to invite new members via email.
							?>
							<div id="pmprogroupacct_manage_group_invite_members">
								<h3 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_font-large' ) ); ?>"><?php esc_html_e( 'Invite New Members via Email', 'pmpro-group-accounts' ); ?></h3>
								<?php echo wp_kses_post( $invite_message ); ?>
								<form id="pmprogroupacct_manage_group_invites" class="<?php echo pmpro_get_element_class( 'pmpro_form', 'pmprogroupacct_manage_group_invites' ); ?>" action="<?php echo esc_url( add_query_arg( 'pmprogroupacct_group_id', $group->id, pmpro_url( 'pmprogroupacct_manage_group' ) . '#pmprogroupacct_manage_group_invite_members' ) ) ?>" method="post">
									<fieldset class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fieldset' ) ); ?>">
										<div class="<?php echo pmpro_get_element_class( 'pmpro_form_fields' ); ?>">
											<div class="<?php echo pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-textarea' ); ?>">
												<label for="pmprogroupacct_invite_new_members_emails" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e( 'Email Addresses', 'pmpro-group-accounts' ); ?></label>
												<textarea rows="5" name="pmprogroupacct_invite_new_members_emails" id="pmprogroupacct_invite_new_members_emails" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-textarea', 'pmprogroupacct_invite_new_members_emails' ) ); ?>"></textarea>
												<p class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_hint' ) ); ?>"><?php esc_html_e( 'Enter one email address per line.', 'pmpro-group-accounts' ); ?></p>
											</div> <!-- end .pmpro_form_field-textarea -->
											<?php
											// Just one child level in the group? Show as a hidden field.
											if ( count( $group_settings['child_level_ids'] ) === 1 ) {
												?>
												<input type="hidden" name="pmprogroupacct_invite_new_members_level_id" id="pmprogroupacct_invite_new_members_level_id" value="<?php echo esc_attr( $group_settings['child_level_ids'][0] ); ?>">
												<?php
											} else {
												?>
												<div class="<?php echo pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-select' ); ?>">
													<label for="pmprogroupacct_invite_new_members_level_id" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label', 'pmprogroupacct_invite_new_members_level_id' ) ); ?>"><?php esc_html_e( 'Level', 'pmpro-group-accounts' ); ?></label>
													<select name="pmprogroupacct_invite_new_members_level_id" id="pmprogroupacct_invite_new_members_level_id" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-select' ) ); ?>">
														<?php
														foreach ( $group_settings['child_level_ids'] as $child_level_id ) {
															$child_level = pmpro_getLevel( $child_level_id );
															?>
															<option value="<?php echo esc_attr( $child_level->id ); ?>"><?php echo esc_html( $child_level->name ); ?></option>
															<?php
														}
														?>
													</select>
												</div> <!-- end .pmpro_form_field-select -->
											<?php
											}
											?>
										</div> <!-- end .pmpro_form_fields -->
									</fieldset> <!-- end .pmpro_form_fieldset -->
									<div class="<?php echo pmpro_get_element_class( 'pmpro_form_submit' ); ?>">
										<input type="hidden" name="pmprogroupacct_invite_new_members_nonce" value="<?php echo esc_attr( wp_create_nonce( 'pmprogroupacct_invite_new_members' ) ); ?>">
										<input type="submit" name="pmprogroupacct_invite_new_members_submit" class="<?php echo pmpro_get_element_class( 'pmpro_btn' ); ?>" value="<?php esc_attr_e( 'Invite New Members', 'pmpro-group-accounts' ); ?>">
									</div> <!-- end .pmpro_form_submit -->
								</form> <!-- end #pmprogroupacct_manage_group_invites -->
							</div> <!-- end #pmprogroupacct_manage_group_invite_members -->
							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_spacer' ) ); ?>"></div>
							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_divider' ) ); ?>"></div>
							<?php
							// Show a form manually create an account for a new group member.
							?>
							<div id="pmprogroupacct_manage_group_create_member">
								<h3 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_font-large' ) ); ?>"><?php esc_html_e( 'Create a New Member', 'pmpro-group-accounts' ); ?></h3>
								<?php echo wp_kses_post( $create_member_message ); ?>
								<form id="pmprogroupacct_create_member" class="<?php echo pmpro_get_element_class( 'pmpro_form', 'pmprogroupacct_manage_group_create_member' ); ?>" action="<?php echo esc_url( add_query_arg( 'pmprogroupacct_group_id', $group->id, pmpro_url( 'pmprogroupacct_manage_group' ) . '#pmprogroupacct_manage_group_create_member' ) ) ?>" method="post">
									<fieldset class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fieldset' ) ); ?>">
										<div class="<?php echo pmpro_get_element_class( 'pmpro_form_fields' ); ?>">
											<div class="<?php echo pmpro_get_element_class( 'pmpro_form_field' ); ?>">
												<label for="pmprogroupacct_create_member_username" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e( 'Username', 'pmpro-group-accounts' ); ?></label>
												<input type="text" name="pmprogroupacct_create_member_username" id="pmprogroupacct_create_member_username" class="<?php echo pmpro_get_element_class( 'pmpro_form_input', 'pmprogroupacct_create_member_username' ); ?>">
											</div> <!-- end .pmpro_form_field -->
											<div class="<?php echo pmpro_get_element_class( 'pmpro_form_field' ); ?>">
												<label for="pmprogroupacct_create_member_email" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e( 'Email', 'pmpro-group-accounts' ); ?></label>
												<input type="email" name="pmprogroupacct_create_member_email" id="pmprogroupacct_create_member_email" class="<?php echo pmpro_get_element_class( 'pmpro_form_input', 'pmprogroupacct_create_member_email' ); ?>">
											</div> <!-- end .pmpro_form_field -->
											<div class="<?php echo pmpro_get_element_class( 'pmpro_form_field' ); ?>">
												<label for="pmprogroupacct_create_member_password" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e( 'Password', 'pmpro-group-accounts' ); ?></label>
												<input type="password" name="pmprogroupacct_create_member_password" id="pmprogroupacct_create_member_password" class="<?php echo pmpro_get_element_class( 'pmpro_form_input', 'pmprogroupacct_create_member_password' ); ?>">
											</div> <!-- end .pmpro_form_field -->
											<?php
											// Just one child level in the group? Show as a hidden field.
											if ( count( $group_settings['child_level_ids'] ) === 1 ) {
												?>
												<input type="hidden" name="pmprogroupacct_create_member_level_id" id="pmprogroupacct_create_member_level_id" value="<?php echo esc_attr( $group_settings['child_level_ids'][0] ); ?>">
												<?php
											} else {
												?>
												<div class="<?php echo pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-select' ); ?>">
													<label for="pmprogroupacct_create_member_level_id" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label', 'pmprogroupacct_create_member_level_id' ) ); ?>"><?php esc_html_e( 'Level', 'pmpro-group-accounts' ); ?></label>
													<select name="pmprogroupacct_create_member_level_id" id="pmprogroupacct_create_member_level_id" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-select' ) ); ?>">
														<?php
														foreach ( $group_settings['child_level_ids'] as $child_level_id ) {
															$child_level = pmpro_getLevel( $child_level_id );
															?>
															<option value="<?php echo esc_attr( $child_level->id ); ?>"><?php echo esc_html( $child_level->name ); ?></option>
															<?php
														}
														?>
													</select>
												</div> <!-- end .pmpro_form_field-select -->
												<?php
											}
											?>
										</div> <!-- end .pmpro_form_fields -->
									</fieldset> <!-- end .pmpro_form_fieldset -->
									<div class="<?php echo pmpro_get_element_class( 'pmpro_form_submit' ); ?>">
										<input type="hidden" name="pmprogroupacct_create_member_nonce" value="<?php echo esc_attr( wp_create_nonce( 'pmprogroupacct_create_member' ) ); ?>">
										<input type="submit" name="pmprogroupacct_create_member_submit" class="<?php echo pmpro_get_element_class( 'pmpro_btn' ); ?>" value="<?php esc_attr_e( 'Create New Member', 'pmpro-group-accounts' ); ?>">
									</div> <!-- end .pmpro_form_submit -->
								</form> <!-- end #pmprogroupacct_create_member -->
							</div> <!-- end #pmprogroupacct_manage_group_create_member -->
							<?php
						}
						?>
					</div> <!-- end .pmpro_card_content -->
				</div>
				<?php
			}
			// Show old members if there are any.
			if ( ! empty( $old_members ) ) {
				?>
				<div id="pmprogroupacct_manage_group_old_members" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card' ) ); ?>">
					<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_title pmpro_font-large' ) ); ?>"><?php esc_html_e( 'Old Members', 'pmpro-group-accounts' ); ?></h2>
					<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
						<table class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_table' ) ); ?>" width="100%" cellpadding="0" cellspacing="0" border="0">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Username', 'pmpro-group-accounts' ); ?></th>
									<th><?php esc_html_e( 'Level', 'pmpro-group-accounts' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php
								foreach ( $old_members as $member ) {
									$user  = get_userdata( $member->group_child_user_id );
									$level = pmpro_getLevel( $member->group_child_level_id );
									?>
									<tr>
										<td><?php echo esc_html( $user->user_login ); ?></td>
										<td><?php echo esc_html( $level->name ); ?></td>
									</tr>
									<?php
								}
								?>
							</tbody>
						</table>
					</div> <!-- end .pmpro_card_content -->
				</div> 
				<?php
			}
			?>
		</section>
	</div>
	<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_actions_nav' ) ); ?>">
		<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_actions_nav-right' ) ); ?>"><a href="<?php echo esc_url( pmpro_url( 'account' ) ) ?>"><?php esc_html_e( 'View Your Membership Account', 'pmpro-group-accounts' );?></a></span>
	</div> <!-- end pmpro_actions_nav -->
	<?php
	$content = ob_get_contents();
	ob_end_clean();

	return $content;
}
add_shortcode( 'pmprogroupacct_manage_group', 'pmprogroupacct_shortcode_manage_group' );
