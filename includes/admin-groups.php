<?php
/**
 * Group Accounts admin page: list of all groups and form to create new groups.
 *
 * @since 1.6
 */

/**
 * Render the Group Accounts admin page.
 *
 * Dispatches on $_REQUEST['action']:
 *   - '' (default): list table view
 *   - 'add':        Add Group form
 *
 * @since 1.6
 */
function pmprogroupacct_admin_groups_page() {
	if ( ! function_exists( 'pmpro_get_edit_member_capability' ) || ! current_user_can( pmpro_get_edit_member_capability() ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'pmpro-group-accounts' ) );
	}

	$action = isset( $_REQUEST['action'] ) ? sanitize_key( $_REQUEST['action'] ) : '';
	?>
	<div class="wrap">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Group Accounts', 'pmpro-group-accounts' ); ?></h1>
		<?php if ( 'add' !== $action ) { ?>
			<a href="<?php echo esc_url( pmprogroupacct_admin_groups_url( array( 'action' => 'add' ) ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New Group', 'pmpro-group-accounts' ); ?></a>
		<?php } ?>
		<hr class="wp-header-end" />

		<?php pmprogroupacct_admin_groups_render_notice(); ?>

		<?php if ( 'add' === $action ) {
			pmprogroupacct_admin_groups_render_add_form();
		} else {
			pmprogroupacct_admin_groups_render_list();
		} ?>
	</div>
	<?php
}

/**
 * Render the list view.
 *
 * @since 1.6
 */
function pmprogroupacct_admin_groups_render_list() {
	$list_table = new PMProGroupAcct_Groups_List_Table();
	$list_table->prepare_items();
	?>
	<form method="get">
		<input type="hidden" name="page" value="pmpro-groupacct-groups" />
		<?php
		// Preserve sort state across filter submissions.
		if ( ! empty( $_REQUEST['orderby'] ) ) {
			echo '<input type="hidden" name="orderby" value="' . esc_attr( sanitize_key( wp_unslash( $_REQUEST['orderby'] ) ) ) . '" />';
		}
		if ( ! empty( $_REQUEST['order'] ) ) {
			echo '<input type="hidden" name="order" value="' . esc_attr( sanitize_key( wp_unslash( $_REQUEST['order'] ) ) ) . '" />';
		}
		$list_table->display();
		?>
	</form>
	<?php
}

/**
 * Render the Add Group form.
 *
 * Pre-fills parent user and parent level from query args when provided
 * (e.g. when arriving from the Edit Member panel's "Create Group" link).
 *
 * @since 1.6
 */
function pmprogroupacct_admin_groups_render_add_form() {
	$state           = pmprogroupacct_admin_groups_consume_form_state();
	$errors          = isset( $state['errors'] ) ? (array) $state['errors'] : array();
	$parent_user_in  = isset( $state['parent_user'] ) ? (string) $state['parent_user'] : '';
	$parent_level_id = isset( $state['parent_level_id'] ) ? (int) $state['parent_level_id'] : ( isset( $_REQUEST['parent_level_id'] ) ? (int) $_REQUEST['parent_level_id'] : 0 );
	$seats           = isset( $state['seats'] ) ? (int) $state['seats'] : 0;

	// If we arrived via the Edit Member CTA with a parent_user_id, prefill the user field with their login.
	if ( '' === $parent_user_in && ! empty( $_REQUEST['parent_user_id'] ) ) {
		$prefill_user = get_userdata( (int) $_REQUEST['parent_user_id'] );
		if ( ! empty( $prefill_user ) ) {
			$parent_user_in = $prefill_user->user_login;
		}
	}

	$parent_levels = pmprogroupacct_get_parent_eligible_levels();

	foreach ( $errors as $error_message ) {
		echo '<div class="notice notice-error"><p>' . esc_html( $error_message ) . '</p></div>';
	}

	if ( empty( $parent_levels ) ) {
		?>
		<div class="notice notice-warning">
			<p><?php esc_html_e( 'No membership levels are configured for group accounts. Add a child level to a parent level under Memberships → Settings → Levels before creating a group.', 'pmpro-group-accounts' ); ?></p>
		</div>
		<?php
		return;
	}
	?>
	<form method="post" action="<?php echo esc_url( pmprogroupacct_admin_groups_url() ); ?>">
		<?php wp_nonce_field( 'pmprogroupacct_admin_groups_add', 'pmprogroupacct_admin_groups_nonce' ); ?>
		<input type="hidden" name="pmprogroupacct_admin_action" value="add" />

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="pmprogroupacct_parent_user"><?php esc_html_e( 'Parent User', 'pmpro-group-accounts' ); ?></label>
				</th>
				<td>
					<input type="text" id="pmprogroupacct_parent_user" name="pmprogroupacct_parent_user" class="regular-text" value="<?php echo esc_attr( $parent_user_in ); ?>" required />
					<p class="description"><?php esc_html_e( 'Enter the parent user\'s username or email. They must already hold the chosen parent level.', 'pmpro-group-accounts' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="pmprogroupacct_parent_level_id"><?php esc_html_e( 'Parent Level', 'pmpro-group-accounts' ); ?></label>
				</th>
				<td>
					<select name="pmprogroupacct_parent_level_id" id="pmprogroupacct_parent_level_id" required>
						<option value=""><?php esc_html_e( '— Select a parent level —', 'pmpro-group-accounts' ); ?></option>
						<?php foreach ( $parent_levels as $level ) { ?>
							<option value="<?php echo esc_attr( $level->id ); ?>" <?php selected( $parent_level_id, (int) $level->id ); ?>><?php echo esc_html( $level->name ); ?></option>
						<?php } ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="pmprogroupacct_seats"><?php esc_html_e( 'Number of Seats', 'pmpro-group-accounts' ); ?></label>
				</th>
				<td>
					<input type="number" id="pmprogroupacct_seats" name="pmprogroupacct_seats" min="0" max="4294967295" value="<?php echo esc_attr( $seats ); ?>" required />
				</td>
			</tr>
		</table>

		<p class="submit">
			<?php submit_button( __( 'Create Group', 'pmpro-group-accounts' ), 'primary', 'submit', false ); ?>
			<a href="<?php echo esc_url( pmprogroupacct_admin_groups_url() ); ?>" class="button"><?php esc_html_e( 'Cancel', 'pmpro-group-accounts' ); ?></a>
		</p>
	</form>
	<?php
}

/**
 * Handle the Add Group form submission. Runs on admin_init so we can redirect.
 *
 * @since 1.6
 */
function pmprogroupacct_admin_groups_handle_post() {
	if ( empty( $_POST['pmprogroupacct_admin_action'] ) ) {
		return;
	}
	if ( 'add' !== sanitize_key( $_POST['pmprogroupacct_admin_action'] ) ) {
		return;
	}
	if ( ! function_exists( 'pmpro_get_edit_member_capability' ) || ! current_user_can( pmpro_get_edit_member_capability() ) ) {
		return;
	}
	if ( empty( $_POST['pmprogroupacct_admin_groups_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['pmprogroupacct_admin_groups_nonce'] ), 'pmprogroupacct_admin_groups_add' ) ) {
		return;
	}

	$parent_user_in  = isset( $_POST['pmprogroupacct_parent_user'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['pmprogroupacct_parent_user'] ) ) ) : '';
	$parent_level_id = isset( $_POST['pmprogroupacct_parent_level_id'] ) ? (int) $_POST['pmprogroupacct_parent_level_id'] : 0;
	$seats           = isset( $_POST['pmprogroupacct_seats'] ) ? max( 0, (int) $_POST['pmprogroupacct_seats'] ) : 0;

	$errors = array();

	// Resolve the parent user. Email-shaped input tries email first then login, since
	// users can have logins that look like emails (e.g. when login = email at signup
	// but the stored email differs).
	$parent_user = null;
	if ( '' !== $parent_user_in ) {
		if ( is_email( $parent_user_in ) ) {
			$parent_user = get_user_by( 'email', $parent_user_in );
		}
		if ( empty( $parent_user ) ) {
			$parent_user = get_user_by( 'login', $parent_user_in );
		}
	}
	if ( empty( $parent_user ) ) {
		$errors[] = __( 'Could not find a user with that username or email.', 'pmpro-group-accounts' );
	}

	$settings = $parent_level_id ? pmprogroupacct_get_settings_for_level( $parent_level_id ) : null;
	if ( empty( $settings ) || empty( $settings['child_level_ids'] ) ) {
		$errors[] = __( 'Please choose a parent level that is configured for group accounts.', 'pmpro-group-accounts' );
	}

	if ( empty( $errors ) && ! pmpro_hasMembershipLevel( $parent_level_id, $parent_user->ID ) ) {
		$errors[] = __( 'The chosen user does not currently have that membership level. Give them the level first, then create their group.', 'pmpro-group-accounts' );
	}

	if ( ! empty( $errors ) ) {
		pmprogroupacct_admin_groups_set_form_state(
			array(
				'parent_user'     => $parent_user_in,
				'parent_level_id' => $parent_level_id,
				'seats'           => $seats,
				'errors'          => $errors,
			)
		);
		wp_safe_redirect( pmprogroupacct_admin_groups_url( array( 'action' => 'add' ) ) );
		exit;
	}

	// Existing-group short-circuit: redirect to the existing manage-group page.
	$existing = PMProGroupAcct_Group::get_group_by_parent_user_id_and_parent_level_id( $parent_user->ID, $parent_level_id );
	if ( ! empty( $existing ) ) {
		pmprogroupacct_admin_groups_redirect_to_existing_group( $existing );
	}

	$group = PMProGroupAcct_Group::create( $parent_user->ID, $parent_level_id, $seats );
	if ( empty( $group ) ) {
		// Race or duplicate-constraint failure: re-check and route accordingly.
		$existing = PMProGroupAcct_Group::get_group_by_parent_user_id_and_parent_level_id( $parent_user->ID, $parent_level_id );
		if ( ! empty( $existing ) ) {
			pmprogroupacct_admin_groups_redirect_to_existing_group( $existing );
		}

		pmprogroupacct_admin_groups_set_form_state(
			array(
				'parent_user'     => $parent_user_in,
				'parent_level_id' => $parent_level_id,
				'seats'           => $seats,
				'errors'          => array( __( 'Unable to create the group. Please try again.', 'pmpro-group-accounts' ) ),
			)
		);
		wp_safe_redirect( pmprogroupacct_admin_groups_url( array( 'action' => 'add' ) ) );
		exit;
	}

	$parent_level = pmpro_getLevel( $parent_level_id );
	$level_label  = ! empty( $parent_level ) ? $parent_level->name : sprintf( /* translators: %d: parent level ID */ __( 'level #%d', 'pmpro-group-accounts' ), $parent_level_id );

	pmprogroupacct_admin_groups_set_form_state( array( 'notice' => array(
		'type'    => 'success',
		'message' => sprintf(
			/* translators: 1: parent user login, 2: parent level name or fallback */
			__( 'Group created for %1$s on the %2$s level.', 'pmpro-group-accounts' ),
			$parent_user->user_login,
			$level_label
		),
	) ) );
	wp_safe_redirect( pmprogroupacct_admin_groups_url() );
	exit;
}
add_action( 'admin_init', 'pmprogroupacct_admin_groups_handle_post' );

/**
 * Render (and clear) a pending admin notice for the current user.
 *
 * The handler only ever stashes EITHER a notice (success/duplicate paths) OR form
 * state (validation/create errors), never both — so clearing the whole transient
 * here is safe and avoids leaking a refreshed TTL across page reloads.
 *
 * @since 1.6
 */
function pmprogroupacct_admin_groups_render_notice() {
	$key   = 'pmprogroupacct_admin_form_' . get_current_user_id();
	$state = get_transient( $key );
	if ( empty( $state['notice']['message'] ) ) {
		return;
	}
	delete_transient( $key );

	$notice = $state['notice'];
	$type   = isset( $notice['type'] ) ? $notice['type'] : 'success';
	$class  = 'success' === $type ? 'notice-success' : ( 'warning' === $type ? 'notice-warning' : 'notice-error' );
	printf( '<div class="notice %1$s is-dismissible"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $notice['message'] ) );
}

/**
 * Stash form state (inputs + errors + optional notice) so the redirect target can re-render.
 * Single-use, scoped to the current user, 60-second TTL.
 *
 * @since 1.6
 */
function pmprogroupacct_admin_groups_set_form_state( $state ) {
	set_transient( 'pmprogroupacct_admin_form_' . get_current_user_id(), $state, 60 );
}

/**
 * Stash a "group already exists" notice and redirect to the frontend manage-group
 * page for the given group. Falls back to the list view if the frontend page isn't set.
 *
 * Exits.
 *
 * @since 1.6
 */
function pmprogroupacct_admin_groups_redirect_to_existing_group( $existing_group ) {
	pmprogroupacct_admin_groups_set_form_state( array(
		'notice' => array(
			'type'    => 'warning',
			'message' => __( 'A group already exists for that user and level. Opening the existing group.', 'pmpro-group-accounts' ),
		),
	) );
	$manage_group_url = pmpro_url( 'pmprogroupacct_manage_group' );
	$redirect_url     = ! empty( $manage_group_url ) ? add_query_arg( 'pmprogroupacct_group_id', $existing_group->id, $manage_group_url ) : pmprogroupacct_admin_groups_url();
	wp_safe_redirect( $redirect_url );
	exit;
}

/**
 * Read and clear the stashed form state.
 *
 * @since 1.6
 */
function pmprogroupacct_admin_groups_consume_form_state() {
	$key   = 'pmprogroupacct_admin_form_' . get_current_user_id();
	$state = get_transient( $key );
	if ( empty( $state ) ) {
		return array();
	}
	delete_transient( $key );
	return is_array( $state ) ? $state : array();
}
