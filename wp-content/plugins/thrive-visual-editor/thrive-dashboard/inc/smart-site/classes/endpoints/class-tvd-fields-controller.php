<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

class TVD_Fields_Controller extends TVD_REST_Controller {

	/**
	 * @var string Base name
	 */
	public $base = 'fields';

	/**
	 * Register Routes
	 */
	public function register_routes() {
		register_rest_route( self::$namespace . self::$version, '/' . $this->base, array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'add_field' ),
				'permission_callback' => array( $this, 'fields_permissions_check' ),
				'args'                => array(),
			),
		) );

		register_rest_route( self::$namespace . self::$version, '/' . $this->base . '/(?P<id>[\d]+)', array(
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_field' ),
				'permission_callback' => array( $this, 'fields_permissions_check' ),
				'args'                => array(),
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'edit_field' ),
				'permission_callback' => array( $this, 'fields_permissions_check' ),
				'args'                => array(),
			),
		) );

		register_rest_route( self::$namespace . self::$version, '/' . $this->base . '/save_fields/', array(
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'save_fields' ),
				'permission_callback' => array( $this, 'fields_permissions_check' ),
				'args'                => array(),
			),
		) );
	}

	/**
	 * Add multiple fields at once
	 *
	 * @param $request WP_REST_Request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function save_fields( $request ) {
		$models   = $request->get_param( 'models' );
		$response = array();

		foreach ( $models as $model ) {

			$route = '/' . self::$namespace . self::$version . '/' . $this->base;
			$field = new WP_REST_Request( 'POST', $route );
			$field->set_query_params( $model );


			$field_response = rest_do_request( $field );
			$server         = rest_get_server();
			$data           = $server->response_to_data( $field_response, false );


			if ( $data ) {
				$response[] = $data;
			} else {
				return new WP_Error( 'error', __( 'Something went wrong while saving the fields, please refresh and try again. If the problem persists please contact our support team.', TVE_DASH_TRANSLATE_DOMAIN ) );
			}
		}

		return new WP_REST_Response( $response, 200 );

	}

	/**
	 * @param $request WP_REST_Request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function add_field( $request ) {
		$model               = $request->get_params();
		$model['created_at'] = date( 'Y-m-d h:i:s' );

		global $wpdb;

		$result = $wpdb->insert(
			$wpdb->prefix . 'td_fields',
			array(
				'group_id'   => $model['group_id'],
				'name'       => $model['name'],
				'type'       => $model['type'],
				'data'       => maybe_serialize( $model['data'] ),
				'created_at' => $model['created_at'],
			),
			array(
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
			)
		);

		$model['id']            = $wpdb->insert_id;
		$model['formated_data'] = TVD_Smart_DB::format_field_data( $model['data'], $model['type'] );
		$model['icon']          = TVD_Smart_DB::field_icon( $model['type'] );
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
	public function delete_field( $request ) {
		global $wpdb;

		$id = $request->get_param( 'id' );

		$result = $wpdb->delete( $wpdb->prefix . 'td_fields', array( 'id' => $id ) );

		if ( $result ) {
			return new WP_REST_Response( true, 200 );
		}

		return new WP_Error( 'no-results', __( 'No field was deleted!', TVE_DASH_TRANSLATE_DOMAIN ) );
	}

	/**
	 * Edit a group
	 *
	 * @param $request WP_REST_Request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function edit_field( $request ) {
		global $wpdb;

		$model               = $request->get_params();
		$model['updated_at'] = date( 'Y-m-d h:i:s' );

		$result                 = $wpdb->update(
			$wpdb->prefix . 'td_fields',
			array(
				'group_id'   => (int) $model['group_id'],
				'name'       => $model['name'],
				'type'       => $model['type'],
				'data'       => maybe_serialize( $model['data'] ),
				'updated_at' => $model['updated_at'],
			),
			array( 'id' => $model['id'] ),
			array(
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
			),
			array( '%d' )
		);
		$model['formated_data'] = TVD_Smart_DB::format_field_data( $model['data'], $model['type'] );
		$model['icon']          = TVD_Smart_DB::field_icon( $model['type'] );
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
	public function fields_permissions_check( $request ) {
		return current_user_can( 'manage_options' );
	}
}
