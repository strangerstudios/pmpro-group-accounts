<?php

/**
 * The PMPro Group Account Group object.
 *
 * @since TBD
 */
class PMProGroupAcct_Group {
	/**
	 * The ID of the group entry.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected $id;

	/**
	 * The user ID of the group parent.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected $group_parent_user_id;

	/**
	 * The parent user's level ID that this group is associated with.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected $group_parent_level_id;

	/**
	 * The total number of seats in this group.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected $group_total_seats;

	/**
	 * The checkout code to join this group.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $group_checkout_code;

	/**
	 * Get a group object by ID.
	 *
	 * @since TBD
	 *
	 * @param int $group The group ID to populate.
	 */
	public function __construct( $group_id ) {
		global $wpdb;

		if ( is_int( $group_id ) ) {
			$data = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->pmprogroupacct_groups} WHERE id = %d",
					$group_id
				)
			);

			if ( ! empty( $data ) ) {
				$this->id                   = (int)$data->id;
				$this->group_parent_user_id  = (int)$data->group_parent_user_id;
				$this->group_parent_level_id = (int)$data->group_parent_level_id;
				$this->group_checkout_code   = $data->group_checkout_code;
				$this->group_total_seats     = (int)$data->group_total_seats;
			}
		}
	}

	/**
	 * Get the list of groups based on query arguments.
	 *
	 * @since TBD
	 *
	 * @param array $args The query arguments to use to retrieve groups.
	 *
	 * @return PMProGroupAcct_Group[] The list of groups.
	 */
	public static function get_groups( $args = array() ) {
		global $wpdb;

		$sql_query = "SELECT id FROM {$wpdb->pmprogroupacct_groups}";

		$prepared = array();
		$where    = array();
		$orderby  = isset( $args['orderby'] ) ? $args['orderby'] : '`id` DESC';
		$limit    = isset( $args['limit'] ) ? (int) $args['limit'] : 100;

		// Detect unsupported orderby usage.
		if ( $orderby !== preg_replace( '/[^a-zA-Z0-9\s,`]/', ' ', $orderby ) ) {
			return [];
		}

		// Filter by ID.
		if ( isset( $args['id'] ) ) {
			$where[]    = 'id = %d';
			$prepared[] = $args['id'];
		}

		// Filter by parent user ID.
		if ( isset( $args['group_parent_user_id'] ) ) {
			$where[]    = 'group_parent_user_id = %d';
			$prepared[] = $args['group_parent_user_id'];
		}

		// Filter by parent level ID.
		if ( isset( $args['group_parent_level_id'] ) ) {
			$where[]    = 'group_parent_level_id = %d';
			$prepared[] = $args['group_parent_level_id'];
		}

		// Filter by checkout group_checkout_code.
		if ( isset( $args['group_checkout_code'] ) ) {
			$where[]    = 'group_checkout_code = %s';
			$prepared[] = $args['group_checkout_code'];
		}

		// Maybe filter the data.
		if ( ! empty( $where ) ) {
			$sql_query .= ' WHERE ' . implode( ' AND ', $where );
		}

		// Add the order and limit.
		$sql_query .= " ORDER BY {$orderby} LIMIT {$limit}";

		// Prepare the query.
		if ( ! empty( $prepared ) ) {
			$sql_query = $wpdb->prepare( $sql_query, $prepared );
		}

		// Get the data.
		$group_ids = $wpdb->get_col( $sql_query );
		if ( empty( $group_ids ) ) {
			return array();
		}

		// Return the list of groups.
		$groups = array();
		foreach ( $group_ids as $group_id ) {
			$group = new self( (int)$group_id );
			if ( ! empty( $group->id ) ) {
				$groups[] = $group;
			}
		}
		return $groups;
	}

	/**
	 * Get a group object by parent user ID and parent level ID.
	 *
	 * @since TBD
	 *
	 * @param int $group_parent_user_id The user ID of the parent user.
	 * @param int $group_parent_level_id The level ID of the parent user.
	 * @return PMProGroupAcct_Group|null The group object or null if the group could not be found.
	 */
	public static function get_group_by_parent_user_id_and_parent_level_id( $group_parent_user_id, $group_parent_level_id ) {
		$groups = self::get_groups(
			array(
				'group_parent_user_id'  => $group_parent_user_id,
				'group_parent_level_id' => $group_parent_level_id,
				'limit'                 => 1,
			)
		);
		if ( empty( $groups ) ) {
			return null;
		}
		return $groups[0];
	}

	/**
	 * Get a group object by checkout code.
	 *
	 * @since TBD
	 *
	 * @param string $group_checkout_code The checkout code to search for.
	 * @return PMProGroupAcct_Group|null The group object or null if the group could not be found.
	 */
	public static function get_group_by_checkout_code( $group_checkout_code ) {
		$groups = self::get_groups(
			array(
				'group_checkout_code' => $group_checkout_code,
				'limit'               => 1,
			)
		);
		if ( empty( $groups ) ) {
			return null;
		}
		return $groups[0];
	}

	/**
	 * Create a new group.
	 *
	 * @since TBD
	 *
	 * @param int $group_parent_user_id The user ID of the parent user.
	 * @param int $group_parent_level_id The level ID of the parent user.
	 * @param int $group_total_seats The number of seats in the group.
	 *
	 * @return bool|PMProGroupAcct_Group The new group object or false if the group could not be created.
	 */
	public static function create( $group_parent_user_id, $group_parent_level_id, $group_total_seats ) {
		global $wpdb;

		// Validate the passed data.
		if (
			! is_numeric( $group_parent_user_id ) || (int) $group_parent_user_id <= 0 ||
			! is_numeric( $group_parent_level_id ) || (int) $group_parent_level_id <= 0 ||
			! is_numeric( $group_total_seats ) || (int) $group_total_seats < 0
		) {
			return false;
		}

		// Get a checkout code for the group.
		$group_checkout_code = self::generate_group_checkout_code();

		// Create the group in the database.
		$wpdb->insert(
			$wpdb->pmprogroupacct_groups,
			array(
				'group_parent_user_id'  => (int)$group_parent_user_id,
				'group_parent_level_id' => (int)$group_parent_level_id,
				'group_checkout_code'   => $group_checkout_code,
				'group_total_seats'     => (int)$group_total_seats,
			)
		);

		// Check if the insert failed. This could be the case if the entry already existed.
		if ( empty( $wpdb->insert_id ) ) {
			return false;
		}

		// Return the new group object.
		return new self( $wpdb->insert_id );
	}

	/**
	 * Magic getter to retrieve protected properties.
	 *
	 * @since TBD
	 *
	 * @param string $name The name of the property to retrieve.
	 * @return mixed The value of the property.
	 */
	public function __get( $name ) {
		if ( property_exists( $this, $name ) ) {
			return $this->$name;
		}
	}

	/**
	 * Magic isset to check protected properties.
	 *
	 * @since TBD
	 *
	 * @param string $name The name of the property to check.
	 * @return bool Whether the property is set.
	 */
	public function __isset( $name ) {
		if ( property_exists( $this, $name ) ) {
			return isset( $this->$name );
		}
		return false;
	}

	/**
	 * Regenerate the checkout code for this group.
	 *
	 * @since TBD
	 */
	public function regenerate_group_checkout_code() {
		global $wpdb;
		$this->group_checkout_code = self::generate_group_checkout_code();
		$wpdb->update(
			$wpdb->pmprogroupacct_groups,
			array(
				'group_checkout_code' => $this->group_checkout_code,
			),
			array(
				'id' => $this->id,
			),
			array(
				'%s',
			),
			array(
				'%d',
			)
		);
	}

	/**
	 * Update the number of seats in this group.
	 *
	 * @since TBD
	 *
	 * @param int $group_total_seats The new number of seats in the group.
	 */
	public function update_group_total_seats( $group_total_seats ) {
		global $wpdb;

		// Validate the passed data.
		if ( ! is_int( $group_total_seats ) || $group_total_seats < 0 ) {
			return;
		}

		$this->group_total_seats = $group_total_seats;
		$wpdb->update(
			$wpdb->pmprogroupacct_groups,
			array(
				'group_total_seats' => $group_total_seats,
			),
			array(
				'id' => $this->id,
			),
			array(
				'%d',
			),
			array(
				'%d',
			)
		);
	}

	/**
	 * Whether the group is accepting signups.
	 *
	 * Specifically checks whether the parent user still has the level that the group is associated with
	 * and whether the group has any seats available.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the group is accepting signups.
	 */
	public function is_accepting_signups() {
		// Check whether the parent user still has the level that the group is associated with.
		if ( ! pmpro_hasMembershipLevel( $this->group_parent_level_id, $this->group_parent_user_id ) ) {
			return false;
		}

		// Check whether the group has any seats available.
		if ( $this->get_active_members( true ) >= $this->group_total_seats ) {
			return false;
		}

		return true;
	}

	/**
	 * Get the active members in this group.
	 *
	 * @since TBD
	 *
	 * @param bool return_count Whether to return the count of members instead of the list of members.
	 * @return PMProGroupAcct_Group_Member[]|int The list of members or the count of members.
	 */
	public function get_active_members( $return_count = false ) {
		$member_query_args = array(
			'group_id' => $this->id,
			'group_child_status' => 'active',
			'return_count' => $return_count,
		);
		return PMProGroupAcct_Group_Member::get_group_members( $member_query_args );
	}

	/**
	 * Generate a checkout code.
	 *
	 * @since TBD
	 *
	 * @return string The checkout code.
	 */
	protected static function generate_group_checkout_code() {
		global $wpdb;

		// While $new_group_checkout_code is not unique, generate a new code.
		$new_group_checkout_code = pmpro_getDiscountCode();
		while ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->pmprogroupacct_groups} WHERE group_checkout_code = %s", $new_group_checkout_code ) ) ) {
			$new_group_checkout_code = pmpro_getDiscountCode();
		}

		return $new_group_checkout_code;
	}
}
