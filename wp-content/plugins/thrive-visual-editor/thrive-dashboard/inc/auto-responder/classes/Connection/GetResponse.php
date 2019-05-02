<?php

/**
 * Created by PhpStorm.
 * User: radu
 * Date: 03.04.2015
 * Time: 19:44
 */
class Thrive_Dash_List_Connection_GetResponse extends Thrive_Dash_List_Connection_Abstract {
	/**
	 * Return the connection type
	 *
	 * @return String
	 */
	public static function getType() {
		return 'autoresponder';
	}

	/**
	 * @return string the API connection title
	 */
	public function getTitle() {
		return 'GetResponse';
	}

	/**
	 * output the setup form html
	 *
	 * @return void
	 */
	public function outputSetupForm() {
		$this->_directFormHtml( 'get-response' );
	}

	/**
	 * should handle: read data from post / get, test connection and save the details
	 *
	 * on error, it should register an error message (and redirect?)
	 *
	 * @return mixed
	 */
	public function readCredentials() {
		$key     = ! empty( $_POST['connection']['key'] ) ? $_POST['connection']['key'] : '';
		$url     = ! empty( $_POST['connection']['url'] ) ? $_POST['connection']['url'] : '';
		$version = ! empty( $_POST['connection']['version'] ) ? $_POST['connection']['version'] : '';

		if ( empty( $key ) ) {
			return $this->error( __( 'You must provide a valid GetResponse key', TVE_DASH_TRANSLATE_DOMAIN ) );
		}

		if ( $version && $version == 3 && empty( $url ) ) {
			return $this->error( __( 'You must provide a valid GetResponse V3 API URL', TVE_DASH_TRANSLATE_DOMAIN ) );
		}

		$this->setCredentials( $_POST['connection'] );

		$result = $this->testConnection();

		if ( $result !== true ) {
			return $this->error( sprintf( __( 'Could not connect to GetResponse using the provided key (<strong>%s</strong>)', TVE_DASH_TRANSLATE_DOMAIN ), $result ) );
		}

		/**
		 * finally, save the connection details
		 */
		$this->save();

		return $this->success( __( 'GetResponse connected successfully', TVE_DASH_TRANSLATE_DOMAIN ) );
	}

	/**
	 * test if a connection can be made to the service using the stored credentials
	 *
	 * @return bool|string true for success or error message for failure
	 */
	public function testConnection() {
		$gr = $this->getApi();
		/**
		 * just try getting a list as a connection test
		 */
		$credentials = $this->getCredentials();

		try {
			if ( ! $credentials['version'] || $credentials['version'] == 2 ) {
				/** @var Thrive_Dash_Api_GetResponse $gr */
				$gr->getCampaigns();
			} else {
				/** @var Thrive_Dash_Api_GetResponseV3 $gr */
				$gr->ping();
			}
		} catch ( Thrive_Dash_Api_GetResponse_Exception $e ) {
			return $e->getMessage();
		}

		return true;
	}

	/**
	 * Instantiate the API code required for this connection
	 *
	 * @return Thrive_Dash_Api_GetResponse|Thrive_Dash_Api_GetResponseV3
	 */
	protected function _apiInstance() {
		if ( ! $this->param( 'version' ) || $this->param( 'version' ) == 2 ) {
			return new Thrive_Dash_Api_GetResponse( $this->param( 'key' ) );
		} else {
			$getresponse = new Thrive_Dash_Api_GetResponseV3( $this->param( 'key' ), $this->param( 'url' ) );

			$enterprise_param = $this->param( 'enterprise' );
			if ( ! empty( $enterprise_param ) ) {
				$getresponse->enterprise_domain = $this->param( 'enterprise' );

			}

			return $getresponse;
		}
	}

	/**
	 * get all Subscriber Lists from this API service
	 *
	 * @return array
	 */
	protected function _getLists() {
		/** @var Thrive_Dash_Api_GetResponse $gr */
		$gr = $this->getApi();

		try {
			$lists       = array();
			$items       = $gr->getCampaigns();
			$credentials = $this->getCredentials();

			if ( ! $credentials['version'] || $credentials['version'] == 2 ) {
				foreach ( $items as $key => $item ) {
					$lists [] = array(
						'id'   => $key,
						'name' => $item->name,
					);
				}
			} else {
				foreach ( $items as $item ) {
					$lists [] = array(
						'id'   => $item->campaignId,
						'name' => $item->name,
					);
				}
			}

			return $lists;
		} catch ( Exception $e ) {
			$this->_error = $e->getMessage();

			return false;
		}
	}

	/**
	 * add a contact to a list
	 *
	 * @param string $list_identifier
	 * @param array  $arguments
	 *
	 * @return mixed
	 */
	public function addSubscriber( $list_identifier, $arguments ) {

		$api         = $this->getApi();
		$credentials = $this->getCredentials();

		try {
			if ( ! $credentials['version'] || $credentials['version'] == 2 ) {
				if ( empty( $arguments['name'] ) ) {
					$arguments['name'] = ' ';
				}
				/** @var Thrive_Dash_Api_GetResponse $api */
				$api->addContact( $list_identifier, $arguments['name'], $arguments['email'], 'standard', (int) $arguments['get-response_cycleday'] );
			} else {
				if ( empty( $arguments['phone'] ) ) {
					$arguments['phone'] = '';
				}

				if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
					$ip_addr = $_SERVER['REMOTE_ADDR'];
				} else {
					$ip_addr = empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ? '' : $_SERVER['HTTP_X_FORWARDED_FOR'];
				}

				$params = array(
					'email'      => $arguments['email'],
					'dayOfCycle' => $arguments['get-response_cycleday'],
					'campaign'   => array(
						'campaignId' => $list_identifier,
					),
					'ipAddress'  => $ip_addr,
				);

				if ( ! empty( $arguments['name'] ) ) {
					$params['name'] = $arguments['name'];
				}

				if ( ! empty( $arguments['phone'] ) ) {
					$custom_fields = $api->getCustomFields();

					if ( is_array( $custom_fields ) ) {
						$phone_field = wp_list_filter( $custom_fields, array( 'name' => 'thrvphone' ) );
						$phone_field = array_values( $phone_field );

						/**
						 * We use a custom field to add phone filed for getResponse
						 * This because getResponse has a strict validation for built in phone number and very often added contacts
						 * with this custom field will fail
						 */

						if ( empty( $phone_field ) ) {

							$field_args = array(
								'name'   => 'thrvphone',
								'type'   => 'number',
								'hidden' => false,
								'values' => array(),
							);

							$phone_field = $api->setCustomField( $field_args );
						}

						if ( isset( $phone_field[0]->customFieldId ) ) {
							$phone_value = str_replace( array( '-', '+', ' ' ), '', trim( $arguments['phone'] ) );

							$params['customFieldValues']
								= array(
								array(
									'customFieldId' => $phone_field[0]->customFieldId,
									'value'         => array( $phone_value ),
								),
							);
						}
					}
				}

				$api->addContact( $params );
			}
		} catch ( Exception $e ) {
			return $e->getMessage();
		}

		return true;
	}

	/**
	 * Render extra html API setup form
	 *
	 * @param array $params
	 *
	 * @see api-list.php
	 *
	 */
	public
	function get_extra_settings(
		$params = array()
	) {
		return $params;
	}

	/**
	 * Render extra html API setup form
	 *
	 * @param array $params
	 *
	 * @see api-list.php
	 *
	 */
	public
	function renderExtraEditorSettings(
		$params = array()
	) {
		$this->_directFormHtml( 'getresponse/cycleday', $params );
	}

	/**
	 * Return the connection email merge tag
	 *
	 * @return String
	 */
	public
	static function getEmailMergeTag() {
		return '[[email]]';
	}
}
