<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

/**
 * Class TVD_Smart_DB
 */
class TVD_Smart_DB {

	public $field_types
		= array(
			'Text',
			'Address',
			'Phone number',
			'Email',
			'Link',
			//		'Location',
		);
	/**
	 * Groups table name
	 *
	 * @var string
	 */
	private $groups_table_name;

	/**
	 * Fields table name
	 *
	 * @var string
	 */
	private $fields_table_name;

	/**
	 * Wordpress Database
	 *
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * Icons specific to type
	 */
	public static $icons
		= array(
			0 => 'text',
			1 => 'home-lg-alt-regular',
			2 => 'phone-solid',
			3 => 'envelope-light',
			4 => 'link',
			5 => 'map-marker-solid',
		);

	/**
	 * Default fields and data
	 *
	 * @var array
	 */
	private $groups
		= array(
			'Company' => array(
				array(
					'name' => 'Company name',
					'type' => 0,
				),
				array(
					'name' => 'Address',
					'type' => 1,
				),
				array(
					'name' => 'Phone number',
					'type' => 2,
				),
				array(
					'name' => 'Alternative phone number',
					'type' => 2,
				),
				array(
					'name' => 'Email address',
					'type' => 3,
				),
				array(
					'name' => 'Map Location',
					'type' => 5,
				),
			),
			'Legal'   => array(
				array(
					'name' => 'Privacy policy',
					'type' => 4,
				),
				array(
					'name' => 'Terms and Conditions',
					'type' => 4,
				),
			),
			'Social'  => array(
				array(
					'name' => 'Facebook Page',
					'icon' => 'facebook-brands',
					'type' => 4,
				),
				array(
					'name' => 'YouTube Page',
					'icon' => 'youtube-brands',
					'type' => 4,
				),
				array(
					'name' => 'LinkedIn',
					'icon' => 'linkedin-brands',
					'type' => 4,
				),
				array(
					'name' => 'Pinterest',
					'icon' => 'pinterest-brands',
					'type' => 4,
				),
				array(
					'name' => 'Google +',
					'icon' => 'google-plus-brands',
					'type' => 4,
				),
			),
		);

	/**
	 * TVD_Smart_DB constructor.
	 */
	public function __construct() {
		global $wpdb;

		$this->wpdb              = $wpdb;
		$this->groups_table_name = $this->wpdb->prefix . 'td_groups';
		$this->fields_table_name = $this->wpdb->prefix . 'td_fields';
	}

	/**
	 * Insert the default data in the db
	 */
	public function insert_default_data() {
		/**
		 * We can't use the migration queries in the migration file because we have relationships, so we insert the data here
		 */
		$result = $this->wpdb->get_row( "SELECT `id` FROM $this->groups_table_name  LIMIT 0,1", ARRAY_A );

		if ( empty( $result ) ) {
			foreach ( $this->groups as $group => $fields ) {

				/**
				 * Insert the group
				 */
				$result = $this->wpdb->insert(
					$this->groups_table_name,
					array(
						'name'       => $group,
						'is_default' => 1,
					),
					array(
						'%s',
					)
				);
				$id     = $this->wpdb->insert_id;

				if ( $result ) {
					/**
					 * Insert the fields
					 */
					foreach ( $fields as $field ) {
						$this->wpdb->insert(
							$this->fields_table_name,
							array(
								'name'       => $field['name'],
								'type'       => $field['type'],
								'is_default' => 1,
								'group_id'   => $id,
							),
							array(
								'%s',
								'%d',
							)
						);
					}
				}
			}
		}
	}

	/**
	 * Get groups with fields
	 *
	 * @param int $id
	 *
	 * @return array|object|null
	 */
	public function get_groups( $id = 0, $with_fields = true ) {
		$args  = array();
		$query = 'SELECT * FROM ' . $this->groups_table_name;
		if ( $id ) {
			$where  = ' WHERE id = %d';
			$args[] = $id;
		} else {
			/**
			 * We need this so WPDB won't complain about not preparing the data correctly
			 */
			$where  = ' WHERE 1 = %d';
			$args[] = 1;
		}

		$query .= $where;

		$results = $this->wpdb->get_results( $this->wpdb->prepare( $query, $args ), ARRAY_A );

		if ( $results && $with_fields ) {

			foreach ( $results as $key => $group ) {
				$results[ $key ]['fields'] = $this->get_fields( $group );

				if ( ! empty( $results[ $key ]['fields'] ) ) {
					foreach ( $results[ $key ]['fields'] as $_key => $field ) {
						$results[ $key ]['fields'][ $_key ]['formated_data'] = empty( $field['data'] ) ? '' : TVD_Smart_DB::format_field_data( maybe_unserialize( $field['data'] ), $field['type'] );
						$results[ $key ]['fields'][ $_key ]['data']          = empty( $field['data'] ) ? '' : maybe_unserialize( $field['data'] );
						$results[ $key ]['fields'][ $_key ]['icon']          = empty( $this->groups[ $results[ $key ]['name'] ][ $_key ]['icon'] ) ? TVD_Smart_DB::field_icon( $field['type'] ): dashboard_icon( $this->groups[ $results[ $key ]['name'] ][ $_key ]['icon'], true );
						$results[ $key ]['fields'][ $_key ]['default_field'] = empty( $this->groups[ $results[ $key ]['name'] ][ $_key ] ) ? 0 : 1;
					}
				}
			}
		}

		return $results;
	}

	public static function field_icon( $field_type ) {
		return dashboard_icon( TVD_Smart_DB::$icons[ $field_type ], true );
	}

	public static function format_field_data( $field_data, $type ) {

		if ( $field_data ) {
			switch ( (int) $type ) {
				// text field
				case 0:
					$data = $field_data['text'];
					break;
				//address field
				case 1:
					$data = implode( ', ', $field_data );
					break;
				// phone field
				case 2:
					$data = $field_data['phone'];
					break;
				// email field
				case 3:
					$data = $field_data['email'];
					break;
				//link field
				case 4:

					$data = '<a href="' . $field_data['url'] . '" target="_blank">' . $field_data['text'] . '</a>';
					break;
				// location field
				case 5:
					$data =  $field_data['location'];
					break;
				default:
					$data = $field_data;
			}
		}

		return $data;
	}

	/**
	 * Get fields for group or by ID
	 *
	 * @param array $group
	 * @param int   $id
	 *
	 * @return array|object|null
	 */
	public function get_fields( $group = array(), $id = 0 ) {
		if ( $group ) {
			$where  = ' WHERE group_id = %d';
			$args[] = $group['id'];
		} else {
			/**
			 * We need this so WPDB won't complain about not preparing the data correctly
			 */
			$where  = ' WHERE 1 = %d';
			$args[] = 1;
		}

		if ( $id ) {
			$where  .= ' AND id = %d';
			$args[] = $id;
		}

		$query = $this->wpdb->prepare( 'SELECT * FROM ' . $this->fields_table_name . $where, $args );

		if ( ! $id ) {
			$results = $this->wpdb->get_results( $query, ARRAY_A );
		} else {
			$results = $this->wpdb->get_row( $query, ARRAY_A );
		}


		return $results;
	}
}