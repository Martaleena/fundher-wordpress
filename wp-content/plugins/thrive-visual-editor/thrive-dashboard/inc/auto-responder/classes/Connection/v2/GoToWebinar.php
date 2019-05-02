<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 */

class Thrive_Dash_List_Connection_GoToWebinar extends Thrive_Dash_List_Connection_Abstract {

	/**
	 * @var string
	 */
	private $_consumer_key = 'Mtm8i2IdR2mOkAY3uVoW5f4TdGaBxpkY';

	/**
	 * @var string
	 */
	private $_consumer_secret = 'qjr8KW9Ga6G2AJjE';

	/**
	 * Return the connection type
	 *
	 * @return string
	 */
	public static function getType() {
		return 'webinar';
	}

	/**
	 * Check if the expires_in field is in the past
	 * GoToWebinar auth access tokens expire after about one year
	 *
	 * @return bool
	 */
	public function isExpired() {
		if ( ! $this->isConnected() ) {
			return false;
		}

		$expires_in = $this->param( 'expires_in' );

		return time() > $expires_in;
	}

	/**
	 * get the expiry date and time user-friendly formatted
	 */
	public function getExpiryDate() {
		return date( 'l, F j, Y H:i:s', $this->param( 'expires_in' ) );
	}

	/**
	 * API connection title
	 *
	 * @return string
	 */
	public function getTitle() {
		return 'GoToWebinar';
	}

	/**
	 * @return string
	 */
	public function getListSubtitle() {
		return __( 'Choose from the following upcoming webinars', TVE_DASH_TRANSLATE_DOMAIN );
	}

	/**
	 * output the setup form html
	 *
	 * @return void
	 */
	public function outputSetupForm() {
		$this->_directFormHtml( 'gotowebinar' );
	}

	/**
	 * should handle: read data from post / get, test connection and save the details
	 * on error, it should register an error message (and redirect?)
	 *
	 * @return mixed|Thrive_Dash_List_Connection_Abstract
	 */
	public function readCredentials() {

		$email    = $_POST['gtw_email'];
		$password = $_POST['gtw_password'];

		$v = array(
			'version'    => ! empty( $_POST['connection']['version'] ) ? $_POST['connection']['version'] : '',
			'versioning' => ! empty( $_POST['connection']['versioning'] ) ? $_POST['connection']['versioning'] : '',
		);

		if ( empty( $email ) || empty( $password ) ) {
			return $this->error( __( 'Email and password are required', TVE_DASH_TRANSLATE_DOMAIN ) );
		}

		/** @var Thrive_Dash_Api_GoToWebinar $api */
		$api = $this->getApi();

		try {

			// Login and setters
			$api->directLogin( $email, $password, $v );

			// Set credentials
			$this->setCredentials( $api->getCredentials() );

			// Save the connection details
			$this->save();

			return $this->success( __( 'GoToWebinar connected successfully', TVE_DASH_TRANSLATE_DOMAIN ) );

		} catch ( Thrive_Dash_Api_GoToWebinar_Exception $e ) {
			return $this->error( sprintf( __( 'Could not connect to GoToWebinar using the provided data (%s)', TVE_DASH_TRANSLATE_DOMAIN ), $e->getMessage() ) );
		}
	}

	/**
	 * For using in template
	 *
	 * @return bool|mixed
	 */
	public function getVersion() {
		$credentials = (array) $this->getCredentials();
		if ( ! empty( $credentials['version'] ) ) {
			return $credentials['version'];
		}

		return false;
	}

	/**
	 * @return mixed|string
	 */
	public function getUsername() {
		$credentials = (array) $this->getCredentials();
		if ( ! empty( $credentials['username'] ) ) {
			return $credentials['username'];
		}

		return '';
	}

	/**
	 * @return mixed|string
	 */
	public function getPassword() {
		$credentials = (array) $this->getCredentials();
		if ( ! empty( $credentials['password'] ) ) {
			return $credentials['password'];
		}

		return '';
	}

	/**
	 * test if a connection can be made to the service using the stored credentials
	 *
	 * @return bool|string
	 */
	public function testConnection() {

		try {
			/** @var Thrive_Dash_Api_GoToWebinar * */
			$api      = $this->getApi();
			$webinars = $api->getUpcomingWebinars();

			if ( ! empty( $webinars ) ) {
				return true;
			}

			return false;
		} catch ( Thrive_Dash_Api_GoToWebinar_Exception $e ) {
			return $e->getMessage();
		}
	}

	/**
	 * Instantiate the API required for this connection
	 *
	 * @return mixed|Thrive_Dash_Api_GoToWebinar
	 */
	protected function _apiInstance() {

		$access_token = $organizer_key = null;
		$settings     = array();

		if ( $this->isConnected() && ! $this->isExpired() ) {
			$access_token  = $this->param( 'access_token' );
			$organizer_key = $this->param( 'organizer_key' );
			$settings      = array(
				'version'       => $this->param( 'version' ),
				'versioning'    => $this->param( 'versioning' ), // used on class instances from [/v1/, /v2/ etc] namespace folder
				'expires_in'    => $this->param( 'expires_in' ),
				'auth_type'     => $this->param( 'auth_type' ),
				'refresh_token' => $this->param( 'refresh_token' ),
				'username'      => $this->param( 'username' ),
				'password'      => $this->param( 'password' ),
			);
		}

		return new Thrive_Dash_Api_GoToWebinar( base64_encode( $this->_consumer_key . ':' . $this->_consumer_secret ), $access_token, $organizer_key, $settings );
	}

	/**
	 * Get all webinars from this API service
	 *
	 * @return array|bool
	 */
	protected function _getLists() {

		/** @var Thrive_Dash_Api_GoToWebinar $api */
		$api   = $this->getApi();
		$lists = array();

		try {
			$all = $api->getUpcomingWebinars();

			foreach ( $all as $item ) {

				preg_match( '#register/(\d+)$#', $item['registrationUrl'], $m );

				$lists [] = array(
					'id'   => isset( $m[1] ) ? $m[1] : number_format( (float) $item['webinarKey'], 0, "", "" ),
					'name' => $item['subject'],
				);
			}

			return $lists;
		} catch ( Thrive_Dash_Api_GoToWebinar_Exception $e ) {
			$this->_error = $e->getMessage();

			return false;
		}
	}

	/**
	 * Add a contact to a list
	 *
	 * @param mixed $list_identifier
	 * @param array $arguments
	 *
	 * @return bool|mixed|string
	 */
	public function addSubscriber( $list_identifier, $arguments ) {

		/** @var Thrive_Dash_Api_GoToWebinar $api */
		$api   = $this->getApi();
		$phone = isset( $arguments['phone'] ) ? $arguments['phone'] : null;

		list( $first_name, $last_name ) = $this->_getNameParts( $arguments['name'] );

		if ( empty( $first_name ) ) {
			$first_name = ' ';
		}

		if ( empty( $last_name ) ) {
			$last_name = ' ';
		}

		try {
			$api->registerToWebinar( $list_identifier, $first_name, $last_name, $arguments['email'], $phone );

			return true;
		} catch ( Thrive_Dash_Api_GoToWebinar_Exception $e ) {
			return $e->getMessage();
		} catch ( Exception $e ) {
			return $e->getMessage();
		}
	}

	/**
	 *
	 * @return int the number of days in which this token will expire
	 */
	public function expiresIn() {
		$expires_in = $this->param( 'expires_in' );
		$diff       = (int) ( ( $expires_in - time() ) / ( 3600 * 24 ) );

		return $diff;
	}

	/**
	 * check if the connection is about to expire in less than 30 days or it's already expired
	 */
	public function getWarnings() {
		if ( ! $this->isConnected() ) {
			return array();
		}

		$fix = '<a href="' . admin_url( 'admin.php?page=tve_dash_api_connect' ) . '#edit/' . $this->getKey() . '">' . __( 'Click here to renew the token', TVE_DASH_TRANSLATE_DOMAIN ) . '</a>';

		if ( $this->isExpired() ) {

			return array(
				sprintf( __( 'Thrive API Connections: The access token for %s has expired on %s.', TVE_DASH_TRANSLATE_DOMAIN ), '<strong>' . $this->getTitle() . '</strong>', '<strong>' . $this->getExpiryDate() . '</strong>' ) . ' ' . $fix . '.',
			);
		}

		$diff = $this->expiresIn();

		if ( $diff > 30 ) {
			return array();
		}

		$message = $diff == 0
			?
			__( 'Thrive API Connections: The access token for %s will expire today.', TVE_DASH_TRANSLATE_DOMAIN )
			:
			( $diff == 1
				?
				__( 'Thrive API Connections: The access token for %s will expire tomorrow.', TVE_DASH_TRANSLATE_DOMAIN )
				:
				__( 'Thrive API Connections: The access token for %s will expire in %s days.', TVE_DASH_TRANSLATE_DOMAIN ) );

		return array(
			sprintf( $message, '<strong>' . $this->getTitle() . '</strong>', '<strong>' . $diff . '</strong>' ) . ' ' . $fix . '.',
		);
	}
}
