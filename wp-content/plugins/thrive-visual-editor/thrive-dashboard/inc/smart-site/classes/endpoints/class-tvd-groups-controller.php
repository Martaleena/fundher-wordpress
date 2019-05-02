<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

class TVD_Groups_Controller extends TVD_REST_Controller {

	/**
	 * @var string Base name
	 */
	public $base = 'groups';

	/**
	 * Register Routes
	 */
	public function register_routes() {
		register_rest_route( self::$namespace . self::$version, '/' . $this->base, array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'add_group' ),
				'permission_callback' => array( $this, 'groups_permissions_check' ),
				'args'                => array(),
			),
		) );

		register_rest_route( self::$namespace . self::$version, '/' . $this->base . '/(?P<id>[\d]+)', array(
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_group' ),
				'permission_callback' => array( $this, 'groups_permissions_check' ),
				'args'                => array(),
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'edit_group' ),
				'permission_callback' => array( $this, 'groups_permissions_check' ),
				'args'                => array(),
			),
		) );
	}

	/**
	 * Add a group
	 *
	 * @param $request WP_REST_Request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function add_group( $request ) {
		$model               = $request->get_params();
		$model['created_at'] = date( 'Y-m-d h:i:s' );

		global $wpdb;

		$result = $wpdb->insert(
			$wpdb->prefix . 'td_groups',
			array(
				'name'       => $model['name'],
				'created_at' => $model['created_at'],
			),
			array(
				'%s',
				'%s'
			)
		);

		$model['id'] = $wpdb->insert_id;

		/**
		 * Insert the fields if we have any
		 */
		if ( ! empty( $model['fields'] ) ) {
			foreach ( $model['fields'] as $k => $field ) {
				$model['fields'][ $k ]['group_id'] = $model['id'];
				$result                            = $wpdb->insert(
					$wpdb->prefix . 'td_fields',
					array(
						'group_id'   => $model['id'],
						'name'       => $field['name'],
						'type'       => $field['type'],
						'data'       => maybe_serialize( $field['data'] ),
						'created_at' => $field['created_at'],
					),
					array(
						'%d',
						'%s',
						'%s',
						'%s',
						'%s'
					)
				);

				$model['fields'][ $k ]['id'] = $wpdb->insert_id;
				$model['fields'][ $k ]['formated_data'] = TVD_Smart_DB::format_field_data( $field['data'], $field['type'] );
				$model['fields'][ $k ]['icon']          = TVD_Smart_DB::field_icon( $field['type'] );
			}
		}

		if ( $result ) {
			return new WP_REST_Response( $model, 200 );
		}

		return new WP_Error( 'no-results', __( 'The group was not added, please try again !', TVE_DASH_TRANSLATE_DOMAIN ) );
	}

	/**
	 * Delete a group and all it's fields
	 *
	 * @param $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function delete_group( $request ) {
		global $wpdb;

		$id = $request->get_param( 'id' );

		$result = $wpdb->delete( $wpdb->prefix . 'td_groups', array( 'id' => $id ) );

		if ( $result ) {
			$wpdb->delete( $wpdb->prefix . 'td_fields', array( 'group_id' => $id ), array( '%d' ) );
		}


		if ( $result ) {
			return new WP_REST_Response( true, 200 );
		}

		return new WP_Error( 'no-results', __( 'No group was deleted!', TVE_DASH_TRANSLATE_DOMAIN ) );
	}

	/**
	 * Edit a group
	 *
	 * @param $request WP_REST_Request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function edit_group( $request ) {
		global $wpdb;

		$model               = $request->get_params();
		$model['updated_at'] = date( 'Y-m-d h:i:s' );


		$result = $wpdb->update(
			$wpdb->prefix . 'td_groups',
			array(
				'name'       => $model['name'],
				'updated_at' => $model['updated_at'],
			),
			array( 'id' => $model['id'] ),
			array(
				'%s',
				'%s',
			),
			array( '%d' )
		);

		if ( $result ) {
			return new WP_REST_Response( $model, 200 );
		}

		return new WP_Error( 'no-results', __( 'No group was updated!', TVE_DASH_TRANSLATE_DOMAIN ) );
	}

	/**
	 * Permissions check
	 *
	 * @param $request
	 *
	 * @return bool
	 */
	public function groups_permissions_check( $request ) {
		return current_user_can( 'manage_options' );
	}
}
