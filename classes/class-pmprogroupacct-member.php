<?php

/**
 * The PMPro Group Account Member object.
 *
 * @since TBD
 */
class PMProGroupAcct_Member {
	/**
	 * The ID of the group member entry.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected $id;

	/**
	 * The user ID of the group member.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected $user_id;

	/**
	 * The level ID that the group member claimed using this group.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected $level_id;

	/**
	 * The group ID that the group member is associated with.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected $group_id;

	/**
	 * The status of the group member.
	 * 'active' if they are still using the claimed level, 'inactive' if they are not.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $status;

	/**
	 * Get the list of members based on passed query arguments.
	 *
	 * @since TBD
	 *
	 * @param array $args The query arguments to use to retrieve the members.
	 * @return PMProGroupAcct_Member[] The list of members.
	 */
	public static function get_members( $args = array() ) {
		global $wpdb;

		$sql_query = "SELECT id FROM {$wpdb->pmprogroupacct_members}";

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
	
		// Filter by user ID.
		if ( isset( $args['user_id'] ) ) {
			$where[]    = 'user_id = %d';
			$prepared[] = $args['user_id'];
		}
	
		// Filter by level ID.
		if ( isset( $args['level_id'] ) ) {
			$where[]    = 'level_id = %d';
			$prepared[] = $args['level_id'];
		}
	
		// Filter by group ID.
		if ( isset( $args['group_id'] ) ) {
			$where[]    = 'group_id = %d';
			$prepared[] = $args['group_id'];
		}
	
		// Filter by status.
		if ( isset( $args['status'] ) ) {
			$where[]    = 'status = %s';
			$prepared[] = $args['status'];
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
		$member_ids = $wpdb->get_col( $sql_query );
		if ( empty( $member_ids ) ) {
			return array();
		}

		// Return the list of members.
		$members = array();
		foreach ( $member_ids as $member_id ) {
			$member = new self( $member_id );
			if ( ! empty( $member->id ) ) {
				$members[] = $member;
			}
		}
		return $members;
	}

	/**
	 * Create a new group member.
	 *
	 * @since TBD
	 *
	 * @param int $user_id The user ID of the group member.
	 * @param int $level_id The level ID that the group member claimed using this group.
	 * @param int $group_id The group ID that the group member is associated with.
	 */
	public static function create( $user_id, $level_id, $group_id ) {
		global $wpdb;

		// Validate the passed data.
		if (
			! is_int( $user_id ) || $user_id <= 0 ||
			! is_int( $level_id ) || $level_id <= 0 ||
			! is_int( $group_id ) || $group_id <= 0
		) {
			return false;
		}

		// Create the group member in the database with an "active" status.
		$wpdb->insert(
			$wpdb->pmprogroupacct_members,
			array(
				'user_id'  => $user_id,
				'level_id' => $level_id,
				'group_id' => $group_id,
				'status'   => 'active',
			),
			array(
				'%d',
				'%d',
				'%d',
				'%s',
			)
		);

		// Check if the insert failed. This could be the case if the entry already existed.
		if ( empty( $wpdb->insert_id ) ) {
			return false;
		}

		// Return the new group member object.
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
	 * Update the status of the group member.
	 *
	 * @since TBD
	 *
	 * @param string $status The new status of the group member. 'active' or 'inactive'.
	 */
	public function update_status( $status ) {
		global $wpdb;

		// Validate the passed data.
		if ( ! in_array( $status, array( 'active', 'inactive' ) ) ) {
			return;
		}

		$this->status = $status;
		$wpdb->update(
			$wpdb->pmprogroupacct_members,
			array(
				'status' => $status,
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
	 * Protected constructor to force the use of a factory method.
	 *
	 * @since TBD
	 *
	 * @param int $member_id The group member ID to populate.
	 */
	protected function __construct( $member_id ) {
		global $wpdb;
		if ( is_int( $member_id ) ) {
			$data = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->pmprogroupacct_members} WHERE id = %d",
					$member_id
				)
			);

			if ( ! empty( $data ) ) {
				$this->id       = $data->id;
				$this->user_id  = $data->user_id;
				$this->level_id = $data->level_id;
				$this->group_id = $data->group_id;
				$this->status   = $data->status;
			}
		}
	}
}
