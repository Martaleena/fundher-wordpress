<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-product-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

class TPM_Connection {

	CONST CONNECTED = 'connected';

	CONST NAME = 'tpm_connection';

	CONST MAX_MIN_REPLY = 5;

	const SIGNATURE = 's6!xv(Q7Zp234L_snodt]CvG2meROk0Gurc49KiyJzz6kSjqAyqpUL&9+P4s';

	protected $_data = array();

	protected $_messages = array();

	protected $_errors = array();

	protected $_expected_data = array(
		'ttw_id',
		'ttw_email',
		'ttw_salt',
	);

	protected static $_instance;

	private function __construct() {

		$this->_data = get_option( self::NAME, array() );

		$this->_messages = get_option( 'tpm_connection_messages', array() );

		add_filter( 'tpm_messages', array( $this, 'apply_messages' ) );
	}

	public static function get_instance() {

		if ( ! self::$_instance ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __set( $key, $value ) {


		return $this->_data[ $key ] = $value;
	}

	public function __get( $param ) {

		$value = null;

		if ( isset( $this->_data[ $param ] ) ) {
			$value = $this->_data[ $param ];
		}

		return $value;
	}

	public function is_connected() {

		return $this->status === self::CONNECTED;
	}

	public function get_login_url() {

		return add_query_arg( array(
			'callback_url' => urlencode( base64_encode( $this->get_callback_url() ) ),
			'tpm_site'     => base64_encode( get_site_url() ),
		), Thrive_Product_Manager::get_ttw_url() . '/connect-account' );
	}

	/**
	 * URL where user is redirected back after he logs in TTW
	 *
	 * @return string
	 */
	protected function get_callback_url() {

		$url = Thrive_Product_Manager::get_instance()->get_admin_url();
		$url = add_query_arg( array(
			'tpm_token' => base64_encode( $this->get_token() ),
		), $url );

		return $url;
	}

	public function get_token() {

		$token = get_option( 'tpm_token', null );

		if ( ! empty( $token ) ) {
			return $this->decrypt( $token );
		}

		$rand_nr     = rand( 1, 11 );
		$rand_chars  = '^!#)_@%*^@(yR&dsYh';
		$rand_string = substr( str_shuffle( $rand_chars ), 0, $rand_nr );

		$token     = $rand_string . strrev( base_convert( bin2hex( hash( 'sha512', uniqid( mt_rand() . microtime( true ) * 10000, true ), true ) ), 16, 36 ) );
		$to_length = ceil( strlen( $token ) / 2 );

		$token = $rand_nr . substr( $token, rand( 1, 9 ), $to_length );

		add_option( 'tpm_token', $this->encrypt( $token ) );

		return $token;
	}

	public function encrypt( $str ) {

		$str .= '-' . self::SIGNATURE;
		$str = base64_encode( $str );

		return $str;
	}

	public function decrypt( $str ) {

		$str = base64_decode( $str );
		$str = explode( '-', $str );

		return $str[0];
	}

	protected function _is_valid_token( $token ) {

		$tpm_token = get_option( 'tpm_token', null );

		return $this->decrypt( $tpm_token ) === $token;
	}

	/**
	 * Process the request
	 * Validate it and sve the connection into DB
	 *
	 * @return bool
	 */
	public function process_data() {

		if ( ! $this->_is_valid_token( base64_decode( $_REQUEST['tpm_token'] ) ) ) {

			$this->_errors[] = __( 'Invalid token', Thrive_Product_Manager::T );

			return false;
		}

		$data = $this->_read_data();

		if ( ! $this->_is_valid( $data ) ) {

			$this->_errors[] = __( 'Invalid data', Thrive_Product_Manager::T );

			return false;
		}

		return $this->_save_connection( $data );
	}

	/**
	 * Reads expected data from request
	 *
	 * @return array
	 */
	protected function _read_data() {

		$data = array();

		$no_decode = array(
			'ttw_salt',
		);

		foreach ( $this->_expected_data as $key ) {

			//this has to be in clear; not encoded
			if ( in_array( $key, $no_decode ) ) {
				$data[ $key ] = $_REQUEST[ $key ];
				continue;
			}

			if ( ! empty( $_REQUEST[ $key ] ) ) {
				$data[ $key ] = base64_decode( urldecode( $_REQUEST[ $key ] ) );
			}
		}

		return $data;
	}

	public function render( $return = false ) {

		ob_start();
		include thrive_product_manager()->path( 'inc/templates/header.phtml' );

		if ( count( $this->_errors ) ) {
			include thrive_product_manager()->path( 'inc/templates/connection/error.phtml' );
		} else {
			include thrive_product_manager()->path( 'inc/templates/connection/form.phtml' );
		}

		$html = ob_get_clean();

		if ( $return === true ) {
			return $html;
		}

		echo $html;
	}

	public function get_data() {

		return $this->_data;
	}

	/**
	 * @param $data
	 *
	 * @return bool
	 */
	protected function _save_connection( $data ) {

		$data['status'] = self::CONNECTED;

		$this->_data = $data;

		update_option( self::NAME, $data );

		return true;
	}

	/**
	 * Check if data is as expected
	 *
	 * @param $data array
	 *
	 * @return bool
	 */
	protected function _is_valid( $data ) {

		if ( ! is_array( $data ) ) {
			return false;
		}

		$keys = array_intersect( array_keys( $data ), $this->_expected_data );

		return $keys === $this->_expected_data;
	}

	public function apply_messages( $messages = array() ) {

		$messages = array_merge( $messages, $this->_messages );

		$this->_messages = array();
		update_option( 'tpm_connection_messages', array() );

		return $messages;
	}

	public function push_message( $str, $status ) {

		$str = __( $str, Thrive_Product_Manager::T );

		$this->_messages[] = array(
			'message' => $str,
			'status'  => $status,
		);

		update_option( 'tpm_connection_messages', $this->_messages );
	}

	public function disconnect() {

		TPM_Product_List::get_instance()->clear_cache();
		TPM_License_Manager::get_instance()->clear_cache();

		return delete_option( self::NAME );
	}

	public function is_agency() {

		return $this->ttw_agency === 'yes';
	}

	public function get_email() {

		return $this->ttw_email;
	}

	public function get_disconnect_url() {

		$url = Thrive_Product_Manager::get_instance()->get_admin_url();
		$url = add_query_arg( array( 'tpm_disconnect' => 1 ), $url );

		return $url;
	}
}
