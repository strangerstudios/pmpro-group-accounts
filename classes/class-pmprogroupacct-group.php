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
	protected $parent_user_id;

	/**
	 * The parent user's level ID that this group is associated with.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected $parent_level_id;

	/**
	 * The total number of seats in this group.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected $seats;

	/**
	 * The checkout code to join this group.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $code;

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

		$sql_query = "SELECT * FROM {$wpdb->pmprogroupacct_groups}";

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
		if ( isset( $args['parent_user_id'] ) ) {
			$where[]    = 'parent_user_id = %d';
			$prepared[] = $args['parent_user_id'];
		}

		// Filter by parent level ID.
		if ( isset( $args['parent_level_id'] ) ) {
			$where[]    = 'parent_level_id = %d';
			$prepared[] = $args['parent_level_id'];
		}

		// Filter by checkout code.
		if ( isset( $args['code'] ) ) {
			$where[]    = 'code = %s';
			$prepared[] = $args['code'];
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
		$group_ids = $wpdb->get_results( $sql_query );
		if ( empty( $group_ids ) ) {
			return array();
		}

		// Return the list of groups.
		$groups = array();
		foreach ( $group_ids as $group_id ) {
			$group = new self( $group_id );
			if ( ! empty( $group->id ) ) {
				$groups[] = $group;
			}
		}
		return $groups;
	}

	/**
	 * Create a new group.
	 *
	 * @since TBD
	 *
	 * @param int $parent_user_id The user ID of the parent user.
	 * @param int $parent_level_id The level ID of the parent user.
	 * @param int $seats The number of seats in the group.
	 *
	 * @return bool|PMProGroupAcct_Group The new group object or false if the group could not be created.
	 */
	public static function create( $parent_user_id, $parent_level_id, $seats ) {
		global $wpdb;

		// Validate the passed data.
		if (
			! is_int( $parent_user_id ) || $parent_user_id <= 0 ||
			! is_int( $parent_level_id ) || $parent_level_id <= 0 ||
			! is_int( $seats ) || $seats < 0
		) {
			return false;
		}

		// Get a checkout code for the group.
		$code = self::generate_code();

		// Create the group in the database.
		$wpdb->insert(
			$wpdb->pmprogroupacct_groups,
			array(
				'parent_user_id'  => $parent_user_id,
				'parent_level_id' => $parent_level_id,
				'code'            => $code,
				'seats'           => $seats,
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
	 * Regenerate the checkout code for this group.
	 *
	 * @since TBD
	 */
	public function regenerate_code() {
		global $wpdb;
		$this->code = self::generate_code();
		$wpdb->update(
			$wpdb->pmprogroupacct_groups,
			array(
				'code' => $this->code,
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
	 * @param int $seats The new number of seats in the group.
	 */
	public function update_seats( $seats ) {
		global $wpdb;

		// Validate the passed data.
		if ( ! is_int( $seats ) || $seats <= 0 ) {
			return;
		}

		$this->seats = $seats;
		$wpdb->update(
			$wpdb->pmprogroupacct_groups,
			array(
				'seats' => $seats,
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
		if ( ! pmpro_hasMembershipLevel( $this->parent_level_id, $this->parent_user_id ) ) {
			return false;
		}

		// Check whether the group has any seats available.
		$members = PMProGroupAcct_Member::get_members( array( 'group_id' => $this->id, 'status' => 'active' ) );
		if ( count( $members ) >= $this->seats ) {
			return false;
		}

		return true;
	}

	/**
	 * Protected constructor to force the use of a factory method.
	 *
	 * @since TBD
	 *
	 * @param int $group The group ID to populate.
	 */
	protected function __construct( $group_id ) {
		if ( is_int( $group_id ) ) {
			$data = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->pmprogroupacct_groups} WHERE id = %d",
					$group_id
				)
			);

			if ( ! empty( $data ) ) {
				$this->id              = $data->id;
				$this->parent_user_id  = $data->parent_user_id;
				$this->parent_level_id = $data->parent_level_id;
				$this->code            = $data->code;
				$this->seats           = $data->seats;
			}
		}
	}

	/**
	 * Generate a checkout code.
	 *
	 * @since TBD
	 *
	 * @return string The checkout code.
	 */
	protected static function generate_code() {
		global $wpdb;

		// While $new_code is not unique, generate a new code.
		$new_code = pmpro_getDiscountCode();
		while ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->pmprogroupacct_groups} WHERE code = %s", $new_code ) ) ) {
			$new_code = pmpro_getDiscountCode();
		}

		return $new_code;
	}
}
