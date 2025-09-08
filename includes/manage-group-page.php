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
	global $wpdb;

	// Make sure that PMPro is enabled.
	if ( ! function_exists( 'pmpro_get_element_class' ) ) {
		return '<p>' . esc_html__( 'Paid Memberships Pro must be enabled to use the Group Accounts Add On.', 'pmpro-group-accounts' ) . '</p>';
	}

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
	$action_message = '';
	if ( ! empty( $_REQUEST['pmprogroupacct_bulk_member_action_submit'] ) ) {
		// Make sure that the nonce is valid.
		if ( ! wp_verify_nonce( $_REQUEST['pmprogroupacct_member_action_nonce'], 'pmprogroupacct_member_action' ) ) {
			$action_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_error' ) . '">' . esc_html__( 'Unable to validate your request. No changes were made.', 'pmpro-group-accounts' ) . '</div>';
		}

		// Make sure that we have user IDs.
		if ( empty( $action_message ) && empty( $_REQUEST['pmprogroupacct_action_user_ids'] ) ) {
			$action_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_error' ) . '">' . esc_html__( 'No users selected.', 'pmpro-group-accounts' ) . '</div>';
		}

		// Get the group members.
		if ( empty( $action_message ) ) {
			$group_members_to_update = array();
			foreach ( $_REQUEST['pmprogroupacct_action_user_ids'] as $group_member_id ) {
				$group_members_to_update[] = new PMProGroupAcct_Group_Member( intval( $group_member_id ) );
			}

			// Make sure that each group member exists and that they have the group_id being edited.
			foreach ( $group_members_to_update as $group_member ) {
				if ( empty( $group_member->id ) ) {
					$action_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_error' ) . '">' . esc_html__( 'One or more of the selected records does not exist.', 'pmpro-group-accounts' ) . '</div>';
					break;
				} elseif ( empty( $group_member->group_child_user_id ) ) {
					$action_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_error' ) . '">' . esc_html__( 'One or more of the selected records has an invalid user ID.', 'pmpro-group-accounts' ) . '</div>';
					break;
				} elseif ( empty( $group_member->group_child_level_id ) ) {
					$action_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_error' ) . '">' . esc_html__( 'One or more of the selected records has an invalid level ID.', 'pmpro-group-accounts' ) . '</div>';
					break;
				} elseif ( $group_member->group_id !== $group->id ) {
					$action_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_error' ) . '">' . esc_html__( 'One or more of the selected records is part of a different group.', 'pmpro-group-accounts' ) . '</div>';
					break;
				}
			}
		}

		if ( ! empty( $_REQUEST['pmprogroupacct_bulk_member_action'] ) && $_REQUEST['pmprogroupacct_bulk_member_action'] === 'remove' ) {
			// If there wasn't an error, cancel the group member's membership, which will remove them from the group.
			if ( empty( $action_message ) ) {
				foreach ( $group_members_to_update as $group_member ) {
					if ( pmpro_cancelMembershipLevel( $group_member->group_child_level_id, $group_member->group_child_user_id ) ) {
						// Membership cancelled. Force the group removal to happen now.
						pmpro_do_action_after_all_membership_level_changes();
					} else {
						// User must not have had this membership level. Remove them from the group.
						$group_member->update_group_child_status( 'inactive' );
					}
				}
				$action_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_success' ) . '">' . esc_html__( 'Group members removed.', 'pmpro-group-accounts' ) . '</div>';
			}
		} elseif ( ! empty( $_REQUEST['pmprogroupacct_bulk_member_action'] ) && $_REQUEST['pmprogroupacct_bulk_member_action'] === 'transfer' ) {
			// Make sure the current user is an admin.
			if ( empty( $action_message ) && ! $is_admin ) {
				$action_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_error' ) . '">' . esc_html__( 'You do not have permission to transfer group members.', 'pmpro-group-accounts' ) . '</div>';
			}

			// If there wasn't an error, get the group to transfer to.
			if ( empty( $action_message ) ) {
				// Get the group to transfer to.
				$transfer_group_code = empty( $_REQUEST['pmprogroupacct_transfer_group_code'] ) ? '' : sanitize_text_field( $_REQUEST['pmprogroupacct_transfer_group_code'] );
				$transfer_group = PMProGroupAcct_Group::get_group_by_checkout_code( $transfer_group_code );
				if ( empty( $transfer_group ) ) {
					$action_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_error' ) . '">' . esc_html__( 'Invalid group code.', 'pmpro-group-accounts' ) . '</div>';
				} elseif ( $transfer_group->group_parent_level_id !== $group->group_parent_level_id ) {
					$action_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_error' ) . '">' . esc_html__( 'You can only transfer group members to a group with the same level.', 'pmpro-group-accounts' ) . '</div>';
				} elseif ( $transfer_group->group_total_seats < $transfer_group->get_active_members( true ) + count( $group_members_to_update ) ) {
					$action_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_error' ) . '">' . esc_html__( 'There are not enough seats available in the group you are trying to transfer members to.', 'pmpro-group-accounts' ) . '</div>';
				}
			}

			// If there wasn't an error, transfer the group members.
			if ( empty( $action_message ) ) {
				// Loop through the group members and transfer them to the new group.
				foreach ( $group_members_to_update as $group_member ) {
					// Make sure the user is not the group parent.
					if ( $group_member->group_child_user_id === $transfer_group->group_parent_user_id ) {
						$action_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_error' ) . '">' . esc_html__( 'Some memberships were not transferred. You cannot transfer the membership for the leader of the receiving group.', 'pmpro-group-accounts' ) . '</div>';
						continue;
					}

					// Remove the group member from the old group.
					$group_member->update_group_child_status( 'inactive' );

					// Add the group member to the new group.
					PMProGroupAcct_Group_Member::create( $group_member->group_child_user_id, $group_member->group_child_level_id, $transfer_group->id );
				}

				if ( empty( $action_message ) ) {
					$action_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_success' ) . '">' . esc_html__( 'Group members transferred.', 'pmpro-group-accounts' ) . '</div>';
				}
			}
		} elseif( ! empty( $_REQUEST['pmprogroupacct_bulk_member_action'] ) && $_REQUEST['pmprogroupacct_bulk_member_action'] === 'restore' ) {
			// Make sure the current user is an admin.
			if ( empty( $action_message ) && ! $is_admin ) {
				$action_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_error' ) . '">' . esc_html__( 'You do not have permission to restore group members.', 'pmpro-group-accounts' ) . '</div>';
			}

			// Make sure that there is enough seats available in the group.
			if ( empty( $action_message ) && ( $group->group_total_seats < $group->get_active_members( true ) + count( $group_members_to_update ) ) ) {
				$action_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_error' ) . '">' . esc_html__( 'Not enough seats available in this group.', 'pmpro-group-accounts' ) . '</div>';
			}

			// Process the restore membership action.
			if ( empty( $action_message ) ) {
				foreach ( $group_members_to_update as $group_member ) {
					// Make sure the user is not the group parent.
					if ( $group_member->group_child_user_id === $group->group_parent_user_id ) {
						$action_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_error' ) . '">' . esc_html__( 'Some memberships were not restored. You cannot restore the group leader\'s membership.', 'pmpro-group-accounts' ) . '</div>';
						continue;
					}

					// Restore the user's membership.
					pmpro_changeMembershipLevel( $group_member->group_child_level_id, $group_member->group_child_user_id );
					pmpro_do_action_after_all_membership_level_changes(); // Call the action to process any group removals from levels lost.
					$group_member->update_group_child_status( 'active' );
				}

				// Show a success message.
				if ( empty( $action_message ) ) {
					$action_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_success' ) . '">' . esc_html__( 'Memberships restored for selected users.', 'pmpro-group-accounts' ) . '</div>';
				}
			}
		}
	}

	// If the user is trying to update the group settings, update them.
	$seats_message = '';
	if ( isset( $_REQUEST['pmprogroupacct_group_total_seats'] ) ) {
		// Make sure that the current user has permission to update this group.
		if ( ! $is_admin ) {
			$seats_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_error' ) . '">' . esc_html__( 'You do not have permission to update this group.', 'pmpro-group-accounts' ) . '</div>';
		}

		// Make sure that the nonce is valid.
		if ( ! wp_verify_nonce( $_REQUEST['pmprogroupacct_update_group_settings_nonce'], 'pmprogroupacct_update_group_settings' ) ) {
			$seats_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_error' ) . '">' . esc_html__( 'Unable to validate your request. The number of seats has not been updated.', 'pmpro-group-accounts' ) . '</div>';
		}

		// Make sure that the total seats is a number.
		if ( ! is_numeric( $_REQUEST['pmprogroupacct_group_total_seats'] ) ) {
			$seats_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_error' ) . '">' . esc_html__( 'Total seats must be a number.', 'pmpro-group-accounts' ) . '</div>';
		}

		// Only make changes if the number of seats is different.
		if ( (int)$_REQUEST['pmprogroupacct_group_total_seats'] !== $group->group_total_seats ) {
			// Update the group settings.
			$group->update_group_total_seats( (int)$_REQUEST['pmprogroupacct_group_total_seats'] );

			// Show a success message.
			$seats_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_success' ) . '">' . esc_html__( 'Group seats updated.', 'pmpro-group-accounts' ) . '</div>';
		}
	}

	$group_code_message = '';
	if ( isset( $_REQUEST['pmprogroupacct_group_code'] ) && ! empty( $_REQUEST['pmprogroupacct_group_code'] ) ) {
		// Make sure that the current user has permission to update this group.
		if ( ! $is_admin ) {
			$group_code_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_error' ) . '">' . esc_html__( 'You do not have permission to update this group.', 'pmpro-group-accounts' ) . '</div>';
		}

		// Make sure that the nonce is valid.
		if ( ! wp_verify_nonce( $_REQUEST['pmprogroupacct_update_group_settings_nonce'], 'pmprogroupacct_update_group_settings' ) ) {
			$group_code_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_error' ) . '">' . esc_html__( 'Unable to validate your request. The group code was not updated.', 'pmpro-group-accounts' ) . '</div>';
		}

		// Only make changes if this is a different group code.
		$new_group_code = sanitize_text_field( $_REQUEST['pmprogroupacct_group_code'] );
		if ( $new_group_code !== $group->group_checkout_code ) {
			// Make sure that no other group has this code.
			$existing_group = PMProGroupAcct_Group::get_group_by_checkout_code( $new_group_code );
			if ( empty( $existing_group ) ) {
				// Update the group checkout code.
				$group->update_group_checkout_code( $new_group_code );
				$group_code_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_success' ) . '">' . esc_html__( 'Group code updated.', 'pmpro-group-accounts' ) . '</div>';
			} else {
				// Link to the "manage group" page for the existing group.
				$manage_group_url = pmpro_url( 'pmprogroupacct_manage_group' );
				if ( ! empty( $manage_group_url ) ) {
					$group_code_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_error' ) . '">' . wp_kses_post( sprintf( __( 'The group code "%1$s" is already being used by another group. Please choose a different code or <a href="%2$s">manage group ID %3$d</a>.', 'pmpro-group-accounts' ), esc_html( $new_group_code ), esc_url( add_query_arg( 'pmprogroupacct_group_id', $existing_group->id, $manage_group_url ) ), esc_html( $existing_group->id ) ) ) . '</div>';
				} else {
					$group_code_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_error' ) . '">' . sprintf( esc_html__( 'The group code "%1$s" is already being used by another group. Please choose a different code or manage group ID %2$d.', 'pmpro-group-accounts' ), esc_html( $new_group_code ), esc_html( $existing_group->id ) ) . '</div>';
				}
			}
		}
	}

	// If the user is trying to invite new members, invite them.
	$invite_message = '';
	if ( isset( $_REQUEST['pmprogroupacct_invite_new_members_emails'] ) ) {
		// Make sure that the nonce is valid.
		if ( ! wp_verify_nonce( $_REQUEST['pmprogroupacct_invite_new_members_nonce'], 'pmprogroupacct_invite_new_members' ) ) {
			$invite_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_error' ) . '">' . esc_html__( 'Invalid nonce.', 'pmpro-group-accounts' ) . '</div>';
		}

		// Make sure that a level ID was passed.
		if ( ! empty( $invite_message ) && empty( $_REQUEST['pmprogroupacct_invite_new_members_level_id'] ) ) {
			$invite_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_error' ) . '">' . esc_html__( 'No level ID was passed.', 'pmpro-group-accounts' ) . '</div>';
		}

		// Make sure that the level ID is an integer.
		if ( ! empty( $invite_message ) && ! is_numeric( $_REQUEST['pmprogroupacct_invite_new_members_level_id'] ) ) {
			$invite_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_error' ) . '">' . esc_html__( 'Level ID must be a number.', 'pmpro-group-accounts' ) . '</div>';
		}

		// Make sure that the level ID can be claimed using this group code.
		if ( ! empty( $invite_message ) && ! in_array( (int)$_REQUEST['pmprogroupacct_invite_new_members_level_id'], array_map( 'intval', $group_settings['child_level_ids'] ), true ) ) {
			$invite_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_error' ) . '">' . esc_html__( 'This level cannot be claimed using this group code.', 'pmpro-group-accounts' ) . '</div>';
		}

		// Make sure that email addresses were passed.
		if ( ! empty( $invite_message ) && empty( $_REQUEST['pmprogroupacct_invite_new_members_emails'] ) ) {
			$invite_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_error' ) . '">' . esc_html__( 'No email addresses were passed.', 'pmpro-group-accounts' ) . '</div>';
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
				$invite_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_error' ) . '">' . esc_html__( 'No valid email addresses were passed.', 'pmpro-group-accounts' ) . '</div>';
			}
		}

		// Send the invites.
		if ( ! empty( $valid_emails ) ) {
			$parent_user = get_userdata( $group->group_parent_user_id );
			$level_id = (int)$_REQUEST['pmprogroupacct_invite_new_members_level_id'];
			$email_data = array(
				'pmprogroupacct_parent_display_name' => empty( $parent_user ) ? '[' . esc_html__( 'User Not Found' ) . ']' : $parent_user->display_name,
				'pmprogroupacct_invite_link' => esc_url( add_query_arg( array( 'level' => $level_id, 'pmprogroupacct_group_code' => $group->group_checkout_code ), pmpro_url( 'checkout' ) ) ),
				'blog_name' => get_bloginfo( 'name' ),
			);
			$failed_emails = array();
			foreach ( $valid_emails as $valid_email ) {

				//If the PMPro_Email_Template class exists ( V3.4+ ) use it to send the invite.
				if( class_exists( 'PMPro_Email_Template' ) ) {
					$email = new PMPro_Email_Template_PMProGroupAcct_Invite( $parent_user, $level_id, $group, $valid_email );
					$success = $email->send();
				} else {
					$email           = new PMProEmail();
					$email->template = 'pmprogroupacct_invite';
					$email->email    = $valid_email;
					$email->data     = $email_data;
					$success = $email->sendEmail();
				}

				if ( ! $success ) {
					$failed_emails[] = $valid_email;
				}
			}

			$sent_emails = array_diff( $valid_emails, $failed_emails );
			if ( empty( $sent_emails ) ) {
				$invite_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_error' ) . '">' . esc_html__( 'Failed to send emails.', 'pmpro-group-accounts' ) . '</div>';
			} elseif ( empty( $failed_emails ) && empty( $invalid_emails ) ) {
				$invite_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_success' ) . '">' . esc_html__( 'Invites sent.', 'pmpro-group-accounts' ) . '</div>';
			} else {
				// Some emails were sent, but others were not. List the emails that were sent, the emails that failed to send, and the invalid emails.
				$invite_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_success' ) . '">' . esc_html__( 'Invites sent to the following email addresses:', 'pmpro-group-accounts' ) . '</p><ul>';
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
			$create_member_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_error' ) . '">' . esc_html__( 'Invalid nonce.', 'pmpro-group-accounts' ) . '</div>';
		}

		// Make sure that there is an available seat in the group.
		if ( ! empty( $create_member_message ) && $group->is_accepting_signups() ) {
			$create_member_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_error' ) . '">' . esc_html__( 'No available seats in this group.', 'pmpro-group-accounts' ) . '</div>';
		}

		// Make sure that we have an email.
		$email = empty( $_REQUEST['pmprogroupacct_create_member_email'] ) ? '' : sanitize_email( $_REQUEST['pmprogroupacct_create_member_email'] );
		if ( empty( $create_member_message ) && empty( $email ) ) {
			$create_member_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_error' ) . '">' . esc_html__( 'No email address provided.', 'pmpro-group-accounts' ) . '</div>';
		}

		// Make sure that the email is valid.
		if ( empty( $create_member_message ) && ! is_email( $email ) ) {
			$create_member_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_error' ) . '">' . esc_html__( 'Invalid email address.', 'pmpro-group-accounts' ) . '</div>';
		}

		// Make sure that we have a username.
		$username = empty( $_REQUEST['pmprogroupacct_create_member_username'] ) ? '' : sanitize_text_field( $_REQUEST['pmprogroupacct_create_member_username'] );
		if ( empty( $create_member_message ) && empty( $username ) ) {
			$create_member_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_error' ) . '">' . esc_html__( 'Invalid username.', 'pmpro-group-accounts' ) . '</div>';
		}

		// Make sure that we have a password.
		$password = empty( $_REQUEST['pmprogroupacct_create_member_password'] ) ? '' : sanitize_text_field( $_REQUEST['pmprogroupacct_create_member_password'] );
		if ( empty( $create_member_message ) && empty( $password ) ) {
			$create_member_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_error' ) . '">' . esc_html__( 'Invalid password.', 'pmpro-group-accounts' ) . '</div>';
		}

		// Make sure we have a valid level ID for this group.
		$level_id = empty( $_REQUEST['pmprogroupacct_create_member_level_id'] ) ? '' : intval( $_REQUEST['pmprogroupacct_create_member_level_id'] );
		if ( empty( $create_member_message ) && ( empty( $level_id ) || ! in_array( $level_id, $group_settings['child_level_ids'], true ) ) ) {
			$create_member_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_error' ) . '">' . esc_html__( 'Invalid level ID.', 'pmpro-group-accounts' ) . '</div>';
		}

		// If we still don't have an error, create the user.
		if ( empty( $create_member_message ) ) {
			// Create the user.
			$user_id = wp_create_user( $username, $password, $email );

			// If user creation failed, show an error message.
			if ( is_wp_error( $user_id ) ) {
				$create_member_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_error' ) . '">' . esc_html__( 'Error creating user.', 'pmpro-group-accounts' ) . ' ' . $user_id->get_error_message() . '</div>';
			}
		}

		// If we still don't have an error, change the user's level.
		if ( empty( $create_member_message ) ) {
			// Change the user's level.
			$change_level = pmpro_changeMembershipLevel( $level_id, $user_id );

			// If changing the level failed, show an error message.
			if ( empty( $change_level ) ) {
				$create_member_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_error' ) . '">' . esc_html__( 'Error changing user level.', 'pmpro-group-accounts' );
			}
		}

		// If we still don't have an error, add the user to the group.
		if ( empty( $create_member_message ) ) {
			PMProGroupAcct_Group_Member::create( $user_id, $level_id, $group->id );
			$create_member_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_success' ) . '">' . esc_html__( 'User created and added to group.', 'pmpro-group-accounts' ) . '</div>';
		}
	}

	// If an admin is trying to add an existing user to the group, do that.
	if ( ! empty( $_REQUEST['pmprogroupacct_add_existing_member_submit'] ) ) {
		// Make sure that the nonce is valid.
		if ( empty( $_REQUEST['pmprogroupacct_add_existing_member_nonce'] ) || ! wp_verify_nonce( $_REQUEST['pmprogroupacct_add_existing_member_nonce'], 'pmprogroupacct_add_existing_member' ) ) {
			$create_member_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_error' ) . '">' . esc_html__( 'Invalid nonce.', 'pmpro-group-accounts' ) . '</div>';
		}

		// Make sure that the current user has permission to add existing members.
		if ( empty( $create_member_message ) && ! $is_admin ) {
			$create_member_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_error' ) . '">' . esc_html__( 'You do not have permission to add existing members to this group.', 'pmpro-group-accounts' ) . '</div>';
		}

		// Make sure that a user ID was passed.
		if ( empty( $create_member_message ) && empty( $_REQUEST['pmprogroupacct_add_existing_member_username'] ) ) {
			$create_member_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_error' ) . '">' . esc_html__( 'No username was passed.', 'pmpro-group-accounts' ) . '</div>';
		}

		// Make sure that a level ID was passed.
		if ( empty( $create_member_message ) && empty( $_REQUEST['pmprogroupacct_add_existing_member_level_id'] ) ) {
			$create_member_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_error' ) . '">' . esc_html__( 'No level ID was passed.', 'pmpro-group-accounts' ) . '</div>';
		}

		// Make sure this is a valid user.
		if ( empty( $create_member_message ) ) {
			// Get the user ID.
			$username = sanitize_text_field( $_REQUEST['pmprogroupacct_add_existing_member_username'] );
			$user = get_user_by( 'login', $username );
			if ( empty( $user ) || empty( $user->ID ) ) {
				$create_member_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_error' ) . '">' . esc_html__( 'Invalid user.', 'pmpro-group-accounts' ) . '</div>';
			} else {
				$user_id = $user->ID;
			}
		}

		// Make sure that the user is not the group parent.
		if ( empty( $create_member_message ) && $user_id === $group->group_parent_user_id ) {
			$create_member_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_error' ) . '">' . esc_html__( 'You cannot add the group leader to the group.', 'pmpro-group-accounts' ) . '</div>';
		}

		// Make sure that we have a valid level ID for this group.
		if ( empty( $create_member_message ) && ! in_array( intval( $_REQUEST['pmprogroupacct_add_existing_member_level_id'] ), $group_settings['child_level_ids'], true ) ) {
			$create_member_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_error' ) . '">' . esc_html__( 'Invalid level ID.', 'pmpro-group-accounts' ) . '</div>';
		}

		// Add the user to the group.
		if ( empty( $create_member_message ) ) {
			// Get the level ID.
			$level_id = intval( $_REQUEST['pmprogroupacct_add_existing_member_level_id'] );

			// If the user already has this level, cancel it first to terminate payment subscriptions and other group memberships.
			$user_level = pmpro_getSpecificMembershipLevelForUser( $user_id, $level_id );
			if ( ! empty( $user_level ) ) {
				pmpro_cancelMembershipLevel( $user_level->id, $user_id );
				pmpro_do_action_after_all_membership_level_changes(); // Force the group removal from changing to same level to happen now.
			}

			// Give the user the level without a subscription or expiration date.
			pmpro_changeMembershipLevel( $level_id, $user_id, true );
			pmpro_do_action_after_all_membership_level_changes(); // Force the group removal for changing to different level to happen now.

			// Add the user to the group.
			PMProGroupAcct_Group_Member::create( $user_id, $level_id, $group->id );
		}
	}

	// If the user is trying to generate a new group code, generate it.
	$generate_code_message = '';
	if ( ! empty( $_REQUEST['pmprogroupacct_generate_new_group_code'] ) ) {
		// Make sure that the nonce is valid.
		if ( empty( $_REQUEST['pmprogroupacct_generate_new_group_code_nonce'] ) || ! wp_verify_nonce( $_REQUEST['pmprogroupacct_generate_new_group_code_nonce'], 'pmprogroupacct_generate_new_group_code' ) ) {
			$generate_code_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_error' ) . '">' . esc_html__( 'Invalid nonce.', 'pmpro-group-accounts' ) . '</div>';
		}

		// Generate a new group code.
		if ( empty( $generate_code_message ) ) {
			$group->regenerate_group_checkout_code();

			// Show a success message.
			$generate_code_message = '<div class="' . pmpro_get_element_class( 'pmpro_message pmpro_success' ) . '">' . esc_html__( 'New group code generated.', 'pmpro-group-accounts' ) . '</div>';
		}
	}

	// Create UI.
	ob_start();
	?>
	<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro' ) ); ?>">
		<section id="pmprogroupacct_manage_group" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_section', 'pmprogroupacct_manage_group' ) ); ?>">
			<?php echo wp_kses_post( $seats_message ); ?>
			<?php echo wp_kses_post( $group_code_message ); ?>
			<?php echo wp_kses_post( $action_message ); ?>
			<?php echo empty( $generate_code_message ) ? '' : wp_kses_post( $generate_code_message ); ?>
			<?php echo wp_kses_post( $invite_message ); ?>
			<?php echo wp_kses_post( $create_member_message ); ?>

			<?php
			// We want admins to have more settings, like the ability to change the number of seats.
			if ( $is_admin ) {
				?>
				<div id="pmprogroupacct_manage_group_settings" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card', 'pmprogroupacct_manage_group_settings' ) ); ?>">
					<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_title pmpro_font-large' ) ); ?>"><?php esc_html_e( 'Group Settings (Admin Only)', 'pmpro-group-accounts' ); ?></h2>
					<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
						<p class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_subtitle' ) ); ?>">
							<?php
							$group_parent = get_userdata( $group->group_parent_user_id );
							printf( esc_html__( 'Manage the settings for group ID %1$s managed by %2$s.', 'pmpro-group-accounts' ), esc_html( $group->id ), '<a href="' . esc_url( pmprogroupacct_member_edit_url_for_user( $group_parent ) ) . '">' . esc_html( $group_parent->display_name ) . '</a>' );
							?>
						</p>
						<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_spacer' ) ); ?>"></div>
						<form id="pmprogroupacct_manage_group_seats" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form', 'pmprogroupacct_manage_group_seats' ) ); ?>" action="<?php echo esc_url( add_query_arg( 'pmprogroupacct_group_id', $group->id, pmpro_url( 'pmprogroupacct_manage_group' ) ) ) ?>" method="post">
							<fieldset class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fieldset' ) ); ?>">
								<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fields' ) ); ?>">
									<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field' ) ); ?>">
										<label for="pmprogroupacct_group_total_seats" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e( 'Total Seats', 'pmpro-group-accounts' ); ?></label>
										<input type="number" max="4294967295" name="pmprogroupacct_group_total_seats" id="pmprogroupacct_group_total_seats" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-number', 'pmprogroupacct_group_total_seats' ) ); ?>" value="<?php echo esc_attr( $group->group_total_seats ); ?>">
									</div> <!-- end .pmpro_form_field -->
									<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field' ) ); ?>">
										<label for="pmprogroupacct_group_code" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e( 'Group Code', 'pmpro-group-accounts' ); ?></label>
										<input type="text" name="pmprogroupacct_group_code" id="pmprogroupacct_group_code" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input', 'pmprogroupacct_group_code' ) ); ?>" value="<?php echo esc_attr( $group->group_checkout_code ); ?>">
									</div> <!-- end .pmpro_form_field -->
								</div> <!-- end .pmpro_form_fields -->
								<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_submit' ) ); ?>">
									<input type="hidden" name="pmprogroupacct_update_group_settings_nonce" value="<?php echo esc_attr( wp_create_nonce( 'pmprogroupacct_update_group_settings' ) ); ?>">
									<input type="submit" name="pmprogroupacct_update_group_settings_submit" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn' ) ); ?>" value="<?php esc_attr_e( 'Update Settings', 'pmpro-group-accounts' ); ?>">
								</div> <!-- end .pmpro_form_submit -->
							</fieldset> <!-- end .pmpro_form_fieldset -->
						</form>
					</div> <!-- end .pmpro_card_content -->
				</div>
				<?php
			}
			?>
			<div id="pmprogroupacct_manage_group_members" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card', 'pmprogroupacct_manage_group_members' ) ); ?>">
				<?php
				// We're going to show a paginated list of group members.
				$member_type = ( ! empty( $_REQUEST['pmprogroupacct_manage_group_member_type'] ) && 'inactive' === $_REQUEST['pmprogroupacct_manage_group_member_type'] ) ? 'inactive' : 'active';
				$limit = apply_filters( 'pmpro_group_accounts_manage_group_members_per_page', 10 );
				$page  = empty( $_GET['pmprogroupacct_pn'] ) ? 1 : intval( $_GET['pmprogroupacct_pn'] );
				$offset = ( $page - 1 ) * $limit;

				// Build the args to pass to get_group_members.
				$get_members_to_show_args = array(
					'group_id' => $group->id,
					'group_child_status' => $member_type,
					'limit' => $limit,
					'offset' => $offset,
				);

				// If we were passed a username or email, get the user ID.
				$user_id = 0;
				if ( ! empty( $_REQUEST['pmprogroupacct_group_member_search'] ) ) {
					$search_param = sanitize_text_field( $_REQUEST['pmprogroupacct_group_member_search'] );
					$user_search_query = $wpdb->prepare(
						"SELECT ID FROM {$wpdb->users} WHERE user_login LIKE %s OR user_email LIKE %s OR user_nicename LIKE %s OR display_name LIKE %s",
						'%' . $search_param . '%',
						'%' . $search_param . '%',
						'%' . $search_param . '%',
						'%' . $search_param . '%'
					);
					$results = $wpdb->get_col( $user_search_query );
					$get_members_to_show_args['group_child_user_id'] = empty( $results ) ? -1 : $results;
				}

				// Get the array of group members to show.
				$members_to_show = PMProGroupAcct_Group_Member::get_group_members( $get_members_to_show_args );

				// Get the total number of group members for the given $member_type.
				unset( $get_members_to_show_args['limit'] );
				unset( $get_members_to_show_args['offset'] );
				$get_members_to_show_args['return_count'] = true;
				$member_type_count = PMProGroupAcct_Group_Member::get_group_members( $get_members_to_show_args );

				// Get the total number of active group members.
				$active_member_count = $group->get_active_members( true );
				?>
				<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_title pmpro_font-large' ) ); ?>"><?php esc_html_e( 'Group Members', 'pmpro-group-accounts' ); ?> (<?php echo esc_html( number_format_i18n( $active_member_count ) ) . '/' . esc_html( number_format_i18n( (int)$group->group_total_seats ) ); ?>)</h2>
				<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
					<form id="pmprogroupacct_filter_group_members" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form', 'pmprogroupacct_manage_group_members' ) ); ?>" action="<?php echo esc_url( pmpro_url( 'pmprogroupacct_manage_group' ) ) ?>" method="get">
						<input type="hidden" name="pmprogroupacct_group_id" value="<?php echo esc_attr( $group->id ); ?>" />
						<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_data_filters' ) ); ?>">
							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_data_filter-left' ) ); ?>">
								<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fields' ) ); ?>">
									<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-select' ) ); ?>">
										<label class="screen-reader-text" for="pmprogroupacct_manage_group_member_type"><?php esc_html_e( 'Show', 'pmpro-group-accounts' ); ?></label>
										<select id="pmprogroupacct_manage_group_member_type" name="pmprogroupacct_manage_group_member_type" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-select', 'pmprogroupacct_manage_group_member_type' ) ); ?>" onchange="this.form.submit();">
											<option value="active" <?php selected( empty( $_REQUEST['pmprogroupacct_manage_group_member_type'] ) || 'active' === $_REQUEST['pmprogroupacct_manage_group_member_type'] ); ?>><?php esc_html_e( 'Show Active Members', 'pmpro-group-accounts' ); ?></option>
											<option value="inactive" <?php selected( ! empty( $_REQUEST['pmprogroupacct_manage_group_member_type'] ) && 'inactive' === $_REQUEST['pmprogroupacct_manage_group_member_type'] ); ?>><?php esc_html_e( 'Show Old Members', 'pmpro-group-accounts' ); ?></option>
										</select>
									</div>
								</div>
							</div>
							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_data_filter-right' ) ); ?>">
								<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fields pmpro_form_fields-inline' ) ); ?>">
									<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-search' ) ); ?>">
										<label class="screen-reader-text" for="pmprogroupacct_group_member_search"><?php esc_html_e( 'Search for username or email', 'pmpro-group-accounts' ); ?></label>
										<input type="text" id="pmprogroupacct_group_member_search" name="pmprogroupacct_group_member_search" value="<?php echo esc_attr( sanitize_text_field( $_REQUEST['pmprogroupacct_group_member_search'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'Search...', 'pmpro-group-accounts' ); ?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-search', 'pmprogroupacct_group_member_search' ) ); ?>"/>
									</div>
									<input type="submit" value="<?php esc_attr_e( 'Search', 'pmpro-group-accounts' ); ?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn' ) ); ?>" />
								</div>
							</div>
						</div>
					</form>
					<?php
					if ( empty( $members_to_show ) ) {
						echo '<p>' . esc_html__( 'There are no members to show.', 'pmpro-group-accounts' ) . '</p>';
					} else {
						?>
						<form id="pmprogroupacct_manage_group_members" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form', 'pmprogroupacct_manage_group_members' ) ); ?>" action="<?php echo esc_url( add_query_arg( 'pmprogroupacct_group_id', $group->id, pmpro_url( 'pmprogroupacct_manage_group' ) ) ) ?>" method="post">
							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_data_filters' ) ); ?>">
								<?php
								// Build the bulk member actions.
								$bulk_member_actions = array();
								if ( 'active' === $member_type ) {
									// Remove members.
									$bulk_member_actions[] = array(
										'label'   => __( 'Remove', 'pmpro-group-accounts' ),
										'confirm' => __( 'Are you sure you want to remove these users from the group?', 'pmpro-group-accounts' ),
										'action'  => 'remove',
									);

									if ( $is_admin ) {
										// Only allow transferring members if there is more than one group on the site.
										$groups = PMProGroupAcct_Group::get_groups( array(
											'limit' => 2
										) );
										if ( count( $groups ) > 1 ) {
											// Transfer members.
											$bulk_member_actions[] = array(
												'label'            => __( 'Transfer', 'pmpro-group-accounts' ),
												'confirm'          => __( 'Are you sure you want to transfer these users to another group?', 'pmpro-group-accounts' ),
												'conditional_html' => '<input type="text" name="pmprogroupacct_transfer_group_code" class="' . esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-checkbox' ) ) . '" placeholder="' . esc_attr__( 'Enter Group Code', 'pmpro-group-accounts' ) . '" />',
												'action'           => 'transfer',
											);
										}
									}
								} elseif ( $is_admin ) {
									// Restore membership.
									$bulk_member_actions[] = array(
										'label'       => __( 'Restore', 'pmpro-group-accounts' ),
										'confirm'     => __( 'Are you sure you want to restore these users to the group?', 'pmpro-group-accounts' ),
										'action'      => 'restore',
									);
								}

								// Display the bulk member actions in a select dropdown if there are any.
								if ( ! empty( $bulk_member_actions ) ) {
									?>
									<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_data_filters-left' ) ); ?>">
										<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fields pmpro_form_fields-inline' ) ); ?>">
											<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-select' ) ); ?>">
												<label class="screen-reader-text" for="pmprogroupacct_bulk_member_action"><?php esc_html_e( 'Select bulk action', 'pmpro-group-accounts' ); ?></label>
												<select name="pmprogroupacct_bulk_member_action" id="pmprogroupacct_bulk_member_action" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-select' ) ); ?>">
													<option value=""><?php esc_html_e( 'Bulk actions', 'pmpro-group-accounts' ); ?></option>
													<?php
													foreach ( $bulk_member_actions as $action ) {
														?>
														<option value="<?php echo esc_attr( $action['action'] ); ?>" data-confirm="<?php echo esc_attr( $action['confirm'] ); ?>"><?php echo esc_html( $action['label'] ); ?></option>
														<?php
													}
													?>
												</select>
											</div>
											<?php
												foreach ( $bulk_member_actions as $action ) {
													if ( ! empty( $action['conditional_html'] ) ) {
														?>
														<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmprogroupacct_bulk_member_action_conditional 	pmprogroupacct_bulk_member_action_conditional_' . $action['action'] ) ); ?>" style="display:none;">
															<?php
															echo wp_kses(
																$action['conditional_html'], array(
																	'input' => array(
																		'type' => array(),
																		'name' => array(),
																		'class' => array(),
																		'placeholder' => array(),
																	)
																)
															);
														?>
														</div>
														<?php
													}
												}
											?>
											<input type="submit" name="pmprogroupacct_bulk_member_action_submit" value="<?php esc_attr_e( 'Apply', 'pmpro-group-accounts' ); ?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn' ) ); ?>" />
										</div>
									</div>
									<script>
										// Logic to show conditional fields and switch confirm text.
										document.addEventListener('DOMContentLoaded', function() {
											var bulkActionSelect = document.getElementById('pmprogroupacct_bulk_member_action');
											var conditionalDivs = document.querySelectorAll('.pmprogroupacct_bulk_member_action_conditional');

											bulkActionSelect.addEventListener('change', function() {
												var selectedAction = this.value;

												conditionalDivs.forEach(function(div) {
													div.style.display = 'none';
												});

												if (selectedAction) {
													var activeDiv = document.querySelector('.pmprogroupacct_bulk_member_action_conditional_' + selectedAction);
													if (activeDiv) {
														activeDiv.style.display = 'inline-block';
													}
												}
											});

											// Localize the confirmation messages into JS so that they can be attached to the submit button.
											var confirmMessages = <?php
												$confirm_messages = array();
												foreach ( $bulk_member_actions as $action ) {
													$confirm_messages[ $action['action'] ] = $action['confirm'];
												}
												echo wp_json_encode( $confirm_messages );
											?>;

											var submitButton = document.querySelector('input[name="pmprogroupacct_bulk_member_action_submit"]');
											if (submitButton) {
												// Remove any existing event lister
												submitButton.addEventListener('click', function(event) {
													var selectedAction = bulkActionSelect.value;
													if (selectedAction && confirmMessages[selectedAction]) {
														if (!confirm(confirmMessages[selectedAction])) {
															event.preventDefault();
														}
													}
												});
											}

											// Set up the select all checkbox
											var selectAllCheckbox = document.getElementById('pmprogroupacct_select_all_members');
											if (selectAllCheckbox) {
												selectAllCheckbox.addEventListener('change', function() {
													var memberCheckboxes = document.querySelectorAll('input[name="pmprogroupacct_action_user_ids[]"]');
													memberCheckboxes.forEach(function(checkbox) {
														checkbox.checked = selectAllCheckbox.checked;
													});
												});
											}
										});
									</script>
									<?php
									// Add the nonce.
									wp_nonce_field( 'pmprogroupacct_member_action', 'pmprogroupacct_member_action_nonce' );
								}
								?>
							</div>
							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_data_count' ) ); ?>">
								<?php echo esc_html( sprintf( __( 'Showing %d - %d of %d members', 'pmpro-group-accounts' ), $offset + 1, $offset + count( $members_to_show ), $member_type_count ) ); ?>
							</div>
							<table class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_table' ) ); ?>">
								<thead>
									<tr>
										<?php
										if ( ! empty( $bulk_member_actions ) ) {
											?>
											<th><label class="screen-reader-text" for="pmprogroupacct_select_all_members"><?php esc_html_e( 'Select All Members', 'pmpro-group-accounts' ); ?></label><input type="checkbox" id="pmprogroupacct_select_all_members" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-checkbox' ) ); ?>" /></th>
											<?php
										}
										?>
										<th><?php esc_html_e( 'Username', 'pmpro-group-accounts' ); ?></th>
										<th><?php esc_html_e( 'Level', 'pmpro-group-accounts' ); ?></th>
										<th><?php echo esc_html( 'active' === $member_type ? esc_html__( 'Joined', 'pmpro-group-accounts' ) : esc_html__( 'Removed', 'pmpro-group-accounts' ) ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php
									foreach ( $members_to_show as $member ) {
										$user  = get_userdata( $member->group_child_user_id );
										if ( ! empty( $user ) ) {
											$user_login = $user->user_login;
										} else {
											$user_login = __( '[deleted]', 'pmpro-group-accounts' );
										}

										//Note: When you delete a membership level, it removes/cancels the level from the user so this will never get here if the level is deleted for a child level.
										$level = pmpro_getLevel( $member->group_child_level_id );
										?>
										<tr>
											<?php
											if ( ! empty( $bulk_member_actions ) ) {
												?>
												<td data-title="<?php esc_attr_e( 'Action', 'pmpro-group-accounts' ); ?>">
													<label class="screen-reader-text" for="pmprogroupacct_action_user_<?php echo esc_attr( $member->id ); ?>"><?php printf( esc_html__( 'Select Member %s', 'pmpro-group-accounts' ), esc_html( $user_login ) ); ?></label>
													<input type="checkbox" id="pmprogroupacct_action_user_<?php echo esc_attr( $member->id ); ?>" name="pmprogroupacct_action_user_ids[]" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-checkbox' ) ); ?>" value="<?php echo esc_attr( $member->id ); ?>">
												</td>
												<?php
											}
											?>
											<th data-title="<?php esc_attr_e( 'Username', 'pmpro-group-accounts' ); ?>"><?php echo esc_html( $user_login ); ?></th>
											<td data-title="<?php esc_attr_e( 'Level', 'pmpro-group-accounts' ); ?>"><?php echo esc_html( $level->name ); ?></td>
											<td data-title="<?php esc_attr_e( 'Joined', 'pmpro-group-accounts' ); ?>"><?php echo ( '0000-00-00 00:00:00' == $member->status_updated ) ? '&#8212;' : esc_html( wp_date( get_option( 'date_format' ), strtotime( $member->status_updated ) ) ); ?></td>
										</tr>
										<?php
									}
									?>
								</tbody>
							</table>
							<?php
							$pagination_url_args = array(
								'pmprogroupacct_group_id' => $group->id,
							);
							if ( $member_type === 'inactive' ) {
								$pagination_url_args['pmprogroupacct_manage_group_member_type'] = 'inactive';
							}
							if ( ! empty( $search_param ) ) {
								$pagination_url_args['pmprogroupacct_group_member_search'] = $search_param;
							}
							echo wp_kses_post( pmpro_getPaginationString( $page, $member_type_count, $limit, 1, add_query_arg( $pagination_url_args, get_permalink() ), '&pmprogroupacct_pn=' ) );
							?>
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
									if ( empty( $child_level ) ) {
										continue;
									}

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
							<form id="pmprogroupacct_generate_new_group_code" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form', 'pmprogroupacct_generate_new_group_code' ) ); ?>" action="<?php echo esc_url( add_query_arg( 'pmprogroupacct_group_id', $group->id, pmpro_url( 'pmprogroupacct_manage_group' ) ) ) ?>" method="post">
								<h3 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_font-large' ) ); ?>"><?php esc_html_e( 'Generate a New Group Code', 'pmpro-group-accounts' ); ?></h3>
								<p><?php esc_html_e( 'Generate a new group code to prevent new members from joining your group with the current code. Your existing group members will remain in your group. This action is permanent and cannot be reversed.', 'pmpro-group-accounts' ); ?></p>

								<?php
								// Create nonce.
								wp_nonce_field( 'pmprogroupacct_generate_new_group_code', 'pmprogroupacct_generate_new_group_code_nonce' );

								// Show group code regenerate button.
								?>
								<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_submit' ) ); ?>">
									<input type="submit" name="pmprogroupacct_generate_new_group_code" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn' ) ); ?>" value="<?php esc_attr_e( 'Generate New Group Code', 'pmpro-group-accounts' ); ?>">
								</div> <!-- end .pmpro_form_submit -->
							</form>

							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_spacer' ) ); ?>"></div>

							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_divider' ) ); ?>"></div>

							<?php
							// Show a form to invite new members via email.
							?>
							<div id="pmprogroupacct_manage_group_invite_members">
								<h3 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_font-large' ) ); ?>"><?php esc_html_e( 'Invite New Members via Email', 'pmpro-group-accounts' ); ?></h3>
								<form id="pmprogroupacct_manage_group_invites" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form', 'pmprogroupacct_manage_group_invites' ) ); ?>" action="<?php echo esc_url( add_query_arg( 'pmprogroupacct_group_id', $group->id, pmpro_url( 'pmprogroupacct_manage_group' ) ) ) ?>" method="post">
									<fieldset class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fieldset' ) ); ?>">
										<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fields' ) ); ?>">
											<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-textarea' ) ); ?>">
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
												<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-select' ) ); ?>">
													<label for="pmprogroupacct_invite_new_members_level_id" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label', 'pmprogroupacct_invite_new_members_level_id' ) ); ?>"><?php esc_html_e( 'Level', 'pmpro-group-accounts' ); ?></label>
													<select name="pmprogroupacct_invite_new_members_level_id" id="pmprogroupacct_invite_new_members_level_id" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-select' ) ); ?>">
														<?php
														foreach ( $group_settings['child_level_ids'] as $child_level_id ) {
															$child_level = pmpro_getLevel( $child_level_id );
															if ( empty( $child_level ) ) {
																continue;
															}
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
									<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_submit' ) ); ?>">
										<input type="hidden" name="pmprogroupacct_invite_new_members_nonce" value="<?php echo esc_attr( wp_create_nonce( 'pmprogroupacct_invite_new_members' ) ); ?>">
										<input type="submit" name="pmprogroupacct_invite_new_members_submit" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn' ) ); ?>" value="<?php esc_attr_e( 'Invite New Members', 'pmpro-group-accounts' ); ?>">
									</div> <!-- end .pmpro_form_submit -->
								</form> <!-- end #pmprogroupacct_manage_group_invites -->
							</div> <!-- end #pmprogroupacct_manage_group_invite_members -->
							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_spacer' ) ); ?>"></div>
							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_divider' ) ); ?>"></div>
							<?php
							// Show a form manually create an account for a new group member.
							?>
							<div id="pmprogroupacct_manage_group_create_member">
								<h3 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_font-large' ) ); ?>"><?php esc_html_e( 'Create a New Group Member', 'pmpro-group-accounts' ); ?></h3>
								<form id="pmprogroupacct_create_member" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form', 'pmprogroupacct_manage_group_create_member' ) ); ?>" action="<?php echo esc_url( add_query_arg( 'pmprogroupacct_group_id', $group->id, pmpro_url( 'pmprogroupacct_manage_group' ) ) ) ?>" method="post">
									<fieldset class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fieldset' ) ); ?>">
										<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fields' ) ); ?>">
											<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-text pmpro_form_field-username pmpro_form_field-required' ) ); ?>">
												<label for="pmprogroupacct_create_member_username" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>">
													<?php esc_html_e( 'Username', 'pmpro-group-accounts' ); ?>
													<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_asterisk' ) ); ?>"> <abbr title="<?php esc_html_e( 'Required Field', 'pmpro-group-accounts' ); ?>">*</abbr></span>
												</label>
												<input type="text" name="pmprogroupacct_create_member_username" id="pmprogroupacct_create_member_username" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-text pmpro_form_input-required', 'pmprogroupacct_create_member_username' ) ); ?>">
											</div> <!-- end .pmpro_form_field -->
											<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-email pmpro_form_field-required' ) ); ?>">
												<label for="pmprogroupacct_create_member_email" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>">
													<?php esc_html_e( 'Email', 'pmpro-group-accounts' ); ?>
													<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_asterisk' ) ); ?>"> <abbr title="<?php esc_html_e( 'Required Field', 'pmpro-group-accounts' ); ?>">*</abbr></span>
												</label>
												<input type="email" name="pmprogroupacct_create_member_email" id="pmprogroupacct_create_member_email" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-email pmpro_form_input-required', 'pmprogroupacct_create_member_email' ) ); ?>">
											</div> <!-- end .pmpro_form_field -->
											<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-password pmpro_form_field-required' ) ); ?>">
												<label for="pmprogroupacct_create_member_password" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>">
													<?php esc_html_e( 'Password', 'pmpro-group-accounts' ); ?>
													<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_asterisk' ) ); ?>"> <abbr title="<?php esc_html_e( 'Required Field', 'pmpro-group-accounts' ); ?>">*</abbr></span>
												</label>
												<input type="password" name="pmprogroupacct_create_member_password" id="pmprogroupacct_create_member_password" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-password pmpro_form_input-required', 'pmprogroupacct_create_member_password' ) ); ?>">
											</div> <!-- end .pmpro_form_field -->
											<?php
											// Just one child level in the group? Show as a hidden field.
											if ( count( $group_settings['child_level_ids'] ) === 1 ) {
												?>
												<input type="hidden" name="pmprogroupacct_create_member_level_id" id="pmprogroupacct_create_member_level_id" value="<?php echo esc_attr( $group_settings['child_level_ids'][0] ); ?>">
												<?php
											} else {
												?>
												<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-select' ) ); ?>">
													<label for="pmprogroupacct_create_member_level_id" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label', 'pmprogroupacct_create_member_level_id' ) ); ?>"><?php esc_html_e( 'Level', 'pmpro-group-accounts' ); ?></label>
													<select name="pmprogroupacct_create_member_level_id" id="pmprogroupacct_create_member_level_id" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-select' ) ); ?>">
														<?php
														foreach ( $group_settings['child_level_ids'] as $child_level_id ) {
															$child_level = pmpro_getLevel( $child_level_id );
															if ( empty( $child_level ) ) {
																continue;
															}
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
									<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_submit' ) ); ?>">
										<input type="hidden" name="pmprogroupacct_create_member_nonce" value="<?php echo esc_attr( wp_create_nonce( 'pmprogroupacct_create_member' ) ); ?>">
										<input type="submit" name="pmprogroupacct_create_member_submit" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn' ) ); ?>" value="<?php esc_attr_e( 'Create New Member', 'pmpro-group-accounts' ); ?>">
									</div> <!-- end .pmpro_form_submit -->
								</form> <!-- end #pmprogroupacct_create_member -->
							</div> <!-- end #pmprogroupacct_manage_group_create_member -->
							<?php
							// If this is an admin, allow them to add existing users to the group.
							if ( $is_admin ) {
								?>
								<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_spacer' ) ); ?>"></div>
								<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_divider' ) ); ?>"></div>
								<div id="pmprogroupacct_manage_group_add_existing_member">
									<h3 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_font-large' ) ); ?>"><?php esc_html_e( 'Add Existing User to Group (Admin Only)', 'pmpro-group-accounts' ); ?></h3>
									<p><?php esc_html_e( 'This will assign a new membership level to the user which may cause their other membership levels to be removed and payment subscriptions to be terminated.', 'pmpro-group-accounts' ); ?></p>
									<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_spacer' ) ); ?>"></div>
									<form id="pmprogroupacct_add_existing_member" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form', 'pmprogroupacct_manage_group_add_existing_member' ) ); ?>" action="<?php echo esc_url( add_query_arg( 'pmprogroupacct_group_id', $group->id, pmpro_url( 'pmprogroupacct_manage_group' ) ) ) ?>" method="post">
										<fieldset class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fieldset' ) ); ?>">
											<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fields' ) ); ?>">
												<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-text pmpro_form_field-username pmpro_form_field-required' ) ); ?>">
													<label for="pmprogroupacct_add_existing_member_username" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>">
														<?php esc_html_e( 'Username', 'pmpro-group-accounts' ); ?>
														<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_asterisk' ) ); ?>"> <abbr title="<?php esc_html_e( 'Required Field', 'pmpro-group-accounts' ); ?>">*</abbr></span>
													</label>
													<input type="text" name="pmprogroupacct_add_existing_member_username" id="pmprogroupacct_add_existing_member_username" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-text pmpro_form_input-required', 'pmprogroupacct_add_existing_member_username' ) ); ?>">
													<br>
												</div> <!-- end .pmpro_form_field -->
												<?php
												// Just one child level in the group? Show as a hidden field.
												if ( count( $group_settings['child_level_ids'] ) === 1 ) {
													?>
													<input type="hidden" name="pmprogroupacct_add_existing_member_level_id" id="pmprogroupacct_add_existing_member_level_id" value="<?php echo esc_attr( $group_settings['child_level_ids'][0] ); ?>">
													<?php
												} else {
													?>
													<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-select' ) ); ?>">
														<label for="pmprogroupacct_add_existing_member_level_id" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label', 'pmprogroupacct_add_existing_member_level_id' ) ); ?>"><?php esc_html_e( 'Level', 'pmpro-group-accounts' ); ?></label>
														<select name="pmprogroupacct_add_existing_member_level_id" id="pmprogroupacct_add_existing_member_level_id" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-select' ) ); ?>">
															<?php
															foreach ( $group_settings['child_level_ids'] as $child_level_id ) {
																$child_level = pmpro_getLevel( $child_level_id );
																if ( empty( $child_level ) ) {
																	continue;
																}
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
										<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_submit' ) ); ?>">
											<input type="hidden" name="pmprogroupacct_add_existing_member_nonce" value="<?php echo esc_attr( wp_create_nonce( 'pmprogroupacct_add_existing_member' ) ); ?>">
											<input type="submit" name="pmprogroupacct_add_existing_member_submit" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn' ) ); ?>" value="<?php esc_attr_e( 'Add Existing Member', 'pmpro-group-accounts' ); ?>">
										</div> <!-- end .pmpro_form_submit -->
									</form> <!-- end #pmprogroupacct_add_existing_member -->
								</div> <!-- end #pmprogroupacct_manage_group_add_existing_member -->
							<?php
							}	
						}
						?>
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
