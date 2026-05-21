<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * List table for all groups in the Group Accounts admin page.
 *
 * @since 1.6
 */
class PMProGroupAcct_Groups_List_Table extends WP_List_Table {

	/**
	 * @since 1.6
	 */
	public function __construct() {
		parent::__construct(
			array(
				'plural'   => 'pmprogroupacct_groups',
				'singular' => 'pmprogroupacct_group',
				'ajax'     => false,
			)
		);
	}

	/**
	 * @since 1.6
	 */
	public function get_columns() {
		$columns = array(
			'id'                  => __( 'Group ID', 'pmpro-group-accounts' ),
			'parent_user'         => __( 'Parent Account', 'pmpro-group-accounts' ),
			'parent_level'        => __( 'Parent Level', 'pmpro-group-accounts' ),
			'group_checkout_code' => __( 'Group Code', 'pmpro-group-accounts' ),
			'seats'               => __( 'Seats', 'pmpro-group-accounts' ),
			'status'              => __( 'Status', 'pmpro-group-accounts' ),
		);

		/**
		 * Filter the columns shown on the Group Accounts admin list table.
		 *
		 * @since 1.6
		 *
		 * @param array $columns Column slug => label.
		 */
		return apply_filters( 'pmprogroupacct_manage_groupslist_columns', $columns );
	}

	/**
	 * Dispatch column rendering for any column without a dedicated column_<slug> method.
	 *
	 * @since 1.6
	 *
	 * @param PMProGroupAcct_Group $item The group object for this row.
	 * @param string               $column_name The column slug being rendered.
	 */
	public function column_default( $item, $column_name ) {
		/**
		 * Render a custom column added via the pmprogroupacct_manage_groupslist_columns filter.
		 * Callbacks should echo their own escaped HTML for the matching column slug; the return
		 * value of this method is discarded by WP_List_Table.
		 *
		 * @since 1.6
		 *
		 * @param string               $column_name The column slug being rendered.
		 * @param PMProGroupAcct_Group $item        The group object for this row.
		 */
		do_action( 'pmprogroupacct_manage_grouplist_custom_column', $column_name, $item );
	}

	/**
	 * @since 1.6
	 */
	protected function get_sortable_columns() {
		return array(
			'id'           => array( 'id', true ),
			'parent_user'  => array( 'group_parent_user_id', false ),
			'parent_level' => array( 'group_parent_level_id', false ),
			'seats'        => array( 'group_total_seats', false ),
		);
	}

	/**
	 * @since 1.6
	 */
	public function no_items() {
		esc_html_e( 'No groups found.', 'pmpro-group-accounts' );
	}

	/**
	 * Fetch the groups for the current page and set up pagination.
	 *
	 * @since 1.6
	 */
	public function prepare_items() {
		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

		$per_page     = 20;
		$current_page = max( 1, $this->get_pagenum() );

		// Build query args common to count and fetch. Validate orderby against the
		// sortable-columns map so a typo'd URL falls back to the default rather than
		// blanking the list.
		$args             = array();
		$orderby_input    = isset( $_REQUEST['orderby'] ) ? sanitize_key( $_REQUEST['orderby'] ) : 'id';
		$valid_orderbys   = array();
		foreach ( $this->get_sortable_columns() as $entry ) {
			if ( is_array( $entry ) && ! empty( $entry[0] ) ) {
				$valid_orderbys[] = $entry[0];
			}
		}
		if ( ! in_array( $orderby_input, $valid_orderbys, true ) ) {
			$orderby_input = 'id';
		}
		$order           = ( isset( $_REQUEST['order'] ) && 'asc' === strtolower( $_REQUEST['order'] ) ) ? 'ASC' : 'DESC';
		$args['orderby'] = $orderby_input . ' ' . $order;

		if ( ! empty( $_REQUEST['l'] ) ) {
			$args['group_parent_level_id'] = (int) $_REQUEST['l'];
		}

		if ( ! empty( $_REQUEST['status'] ) ) {
			$status = sanitize_key( $_REQUEST['status'] );
			if ( in_array( $status, array( 'active', 'inactive' ), true ) ) {
				$args['status'] = $status;
			}
		}

		$total_items = PMProGroupAcct_Group::get_groups( array_merge( $args, array( 'return_count' => true ) ) );
		$total_pages = $per_page > 0 ? (int) ceil( $total_items / $per_page ) : 0;

		// Clamp a stale ?paged=N that's past the end of the result set so admins don't
		// land on an empty page when groups actually exist.
		if ( $total_items > 0 && $current_page > $total_pages ) {
			$current_page = $total_pages;
		}

		$args['limit']  = $per_page;
		$args['offset'] = ( $current_page - 1 ) * $per_page;
		$this->items    = PMProGroupAcct_Group::get_groups( $args );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => $total_pages,
			)
		);
	}

	/**
	 * Render the Parent Level + Status filter dropdowns above the table.
	 *
	 * @since 1.6
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}

		$selected_level  = isset( $_REQUEST['l'] ) ? (int) $_REQUEST['l'] : 0;
		$selected_status = ! empty( $_REQUEST['status'] ) ? sanitize_key( $_REQUEST['status'] ) : '';
		$parent_levels   = pmprogroupacct_get_parent_eligible_levels();
		?>
		<div class="alignleft actions">
			<?php if ( ! empty( $parent_levels ) ) { ?>
				<label class="screen-reader-text" for="filter-by-parent-level"><?php esc_html_e( 'Filter by parent level', 'pmpro-group-accounts' ); ?></label>
				<select name="l" id="filter-by-parent-level">
					<option value="0"><?php esc_html_e( 'All Parent Levels', 'pmpro-group-accounts' ); ?></option>
					<?php foreach ( $parent_levels as $level ) { ?>
						<option value="<?php echo esc_attr( $level->id ); ?>" <?php selected( $selected_level, (int) $level->id ); ?>><?php echo esc_html( $level->name ); ?></option>
					<?php } ?>
				</select>
			<?php } ?>

			<label class="screen-reader-text" for="filter-by-status"><?php esc_html_e( 'Filter by status', 'pmpro-group-accounts' ); ?></label>
			<select name="status" id="filter-by-status">
				<option value=""><?php esc_html_e( 'All Statuses', 'pmpro-group-accounts' ); ?></option>
				<option value="active" <?php selected( $selected_status, 'active' ); ?>><?php esc_html_e( 'Active', 'pmpro-group-accounts' ); ?></option>
				<option value="inactive" <?php selected( $selected_status, 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'pmpro-group-accounts' ); ?></option>
			</select>

			<?php submit_button( __( 'Filter', 'pmpro-group-accounts' ), '', 'filter_action', false ); ?>
		</div>
		<?php
	}

	/**
	 * Group ID column — linked to the frontend Manage Group page when available.
	 *
	 * @since 1.6
	 */
	public function column_id( $item ) {
		$manage_group_url = pmpro_url( 'pmprogroupacct_manage_group' );
		if ( ! empty( $manage_group_url ) ) {
			return sprintf(
				'<strong><a href="%1$s">%2$s</a></strong>',
				esc_url( add_query_arg( 'pmprogroupacct_group_id', $item->id, $manage_group_url ) ),
				esc_html( $item->id )
			);
		}
		return '<strong>' . esc_html( $item->id ) . '</strong>';
	}

	/**
	 * @since 1.6
	 */
	public function column_parent_user( $item ) {
		$parent_user = get_userdata( $item->group_parent_user_id );
		if ( empty( $parent_user ) ) {
			return esc_html( $item->group_parent_user_id );
		}
		return sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( pmprogroupacct_member_edit_url_for_user( $parent_user ) ),
			esc_html( $parent_user->user_login )
		);
	}

	/**
	 * @since 1.6
	 */
	public function column_parent_level( $item ) {
		$level = pmpro_getLevel( $item->group_parent_level_id );
		if ( empty( $level ) ) {
			/* translators: %d: deleted parent level ID */
			return esc_html( sprintf( __( '(level %d not found)', 'pmpro-group-accounts' ), $item->group_parent_level_id ) );
		}
		return esc_html( $level->name );
	}

	/**
	 * @since 1.6
	 */
	public function column_group_checkout_code( $item ) {
		return '<code>' . esc_html( $item->group_checkout_code ) . '</code>';
	}

	/**
	 * Seats column: "active/total".
	 *
	 * @since 1.6
	 */
	public function column_seats( $item ) {
		$active = (int) $item->get_active_members( true );
		$total  = (int) $item->group_total_seats;
		return esc_html( sprintf( '%s/%s', number_format_i18n( $active ), number_format_i18n( $total ) ) );
	}

	/**
	 * Status column: Active when the parent still holds the parent level, else Inactive.
	 *
	 * Reuses PMPro core's pmpro_subscription-status badge classes for visual parity
	 * with the Orders and Subscriptions list tables.
	 *
	 * @since 1.6
	 */
	public function column_status( $item ) {
		$is_active = pmpro_hasMembershipLevel( (int) $item->group_parent_level_id, (int) $item->group_parent_user_id );

		$label    = $is_active ? __( 'Active', 'pmpro-group-accounts' ) : __( 'Inactive', 'pmpro-group-accounts' );
		$modifier = $is_active ? 'pmpro_subscription-status-active' : 'pmpro_subscription-status-cancelled';
		return '<span class="pmpro_subscription-status ' . esc_attr( $modifier ) . '">' . esc_html( $label ) . '</span>';
	}
}
