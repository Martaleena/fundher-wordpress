<?php
/**
 * Plugin Name: Thrive Product Manager
 * Plugin URI: http://thrivethemes.com
 * Description: Connect this site with Thrive Themes account to install and activate Thrive product.
 * Version: 1.2.1
 * Author: Thrive Themes
 * Author URI: http://thrivethemes.com
 */

class Thrive_Product_Manager {

	CONST V = '1.2.1';
	CONST T = 'thrive_product_manager';

	protected static $_instance;

	CONST CACHE_ENABLED = true;

	/**
	 * @var array of admin pages added by the plugin
	 */
	protected $_admin_menu_pages = array();

	private function __construct() {

		$this->_includes();
		$this->_init();
	}

	protected function _init() {

		//admin
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'current_screen', array( $this, 'try_process_connection' ) );
		add_action( 'current_screen', array( $this, 'try_activate_manually' ) );
		add_action( 'current_screen', array( $this, 'try_clear_cache' ) );
		add_action( 'current_screen', array( $this, 'try_logout' ) );
		add_action( 'current_screen', array( $this, 'try_set_url' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), PHP_INT_MAX );
		add_action( 'admin_print_footer_scripts', array( $this, 'print_admin_templates' ) );

		//all
		add_action( 'init', array( $this, 'update_checker' ) );

		//ajax
		add_action( 'wp_ajax_tpm_install_and_activate_product', array( $this, 'try_install_and_activate_product' ) );

		//rest api
		add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );

		//tve dash
		add_filter( 'tve_dash_localize', array( $this, 'dash_filter_localize' ) );
	}

	protected function _includes() {

		if ( is_admin() ) {
			require_once dirname( __FILE__ ) . '/inc/classes/class-tpm-request.php';
			require_once dirname( __FILE__ ) . '/inc/classes/class-tpm-proxy-request.php';
		}

		//todo: do not include this files in frontend
		require_once dirname( __FILE__ ) . '/inc/classes/class-tpm-log-manager.php';
		require_once dirname( __FILE__ ) . '/inc/classes/class-tpm-license.php';
		require_once dirname( __FILE__ ) . '/inc/classes/class-tpm-connection.php';
		require_once dirname( __FILE__ ) . '/inc/classes/class-tpm-page-manager.php';
		require_once dirname( __FILE__ ) . '/inc/classes/class-tpm-product-list.php';
		require_once dirname( __FILE__ ) . '/inc/classes/class-tpm-product.php';
		require_once dirname( __FILE__ ) . '/inc/classes/class-tpm-product-plugin.php';
		require_once dirname( __FILE__ ) . '/inc/classes/class-tpm-product-theme.php';
		require_once dirname( __FILE__ ) . '/inc/classes/class-tpm-license-manager.php';
		require_once dirname( __FILE__ ) . '/plugin-updates/plugin-update-checker.php';
	}

	/**
	 * Returns url relative to plugin url
	 *
	 * @param string $file
	 *
	 * @return string
	 */
	public function url( $file = '' ) {

		return plugin_dir_url( __FILE__ ) . ltrim( $file, '\\/' );
	}

	/**
	 * Returns path relative to plugin path
	 *
	 * @param string $file
	 *
	 * @return string
	 */
	public function path( $file = '' ) {

		return plugin_dir_path( __FILE__ ) . ltrim( $file, '\\/' );
	}

	/**
	 * @return string
	 */
	public static function get_ttw_url() {

		if ( defined( 'TTW_URL' ) ) {

			return trim( TTW_URL, '/' );
		}

		if ( self::is_debug_mode() ) {

			return get_option( 'tpm_ttw_url', 'https://staging.thrivethemes.com' );
		}

		return 'https://thrivethemes.com';
	}

	/**
	 * If environment is on a staging server
	 *
	 * @return bool
	 */
	public static function is_debug_mode() {

		return defined( 'TPM_DEBUG' ) && TPM_DEBUG === true || defined( 'TVE_DEBUG' ) && TVE_DEBUG === true;
	}

	public function admin_menu() {

		$this->_admin_menu_pages[] = add_menu_page(
			'Thrive Product Manager',
			'Product Manager',
			'manage_options',
			'thrive_product_manager',
			array( TPM_Page_Manager::get_instance(), 'render' ),
			untrailingslashit( plugin_dir_url( __FILE__ ) ) . '/css/images/logo-icon.png'
		);
	}

	public static function get_instance() {

		if ( ! self::$_instance ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function get_admin_url() {

		$url = admin_url( 'admin.php?page=thrive_product_manager' );

		return $url;
	}

	public function get_clear_cache_url() {

		$url = $this->get_admin_url();
		$url = add_query_arg( array( 'tpm_refresh' => 1 ), $url );

		return $url;
	}

	public function known_page( $page ) {

		return in_array( $page, $this->_admin_menu_pages );
	}

	public function is_known_page() {

		$current_screen = get_current_screen();

		return $this->known_page( $current_screen->id );
	}

	/**
	 * clear cache and redirect to plugin main page
	 */
	public function try_clear_cache() {

		if ( empty( $_REQUEST['tpm_refresh'] ) ) {
			return;
		}

		if ( $this->is_known_page() === false ) {
			return;
		}

		TPM_Product_List::get_instance()->clear_cache();
		TPM_License_Manager::get_instance()->clear_cache();

		wp_redirect( $this->get_admin_url() );
		die;
	}

	public function try_process_connection() {

		if ( $this->is_known_page() === false ) {
			return;
		}

		$connection = TPM_Connection::get_instance();

		if ( ! empty( $_REQUEST['tpm_token'] ) ) {

			$processed = $connection->process_data();

			if ( $processed === true ) {

				$connection->push_message( 'Your account has been successfully connected.', 'success' );

				TPM_License_Manager::get_instance(); //get licenses
				TPM_Product_List::get_instance();//get product lists


				wp_redirect( $this->get_admin_url() );
				die;
			}
		}
	}

	public function enqueue_scripts() {

		if ( ! $this->is_known_page() ) {
			return false;
		}

		wp_enqueue_script( 'updates' );

		wp_enqueue_style( 'tpm-style', $this->url( 'css/tpm-admin.css' ) );

		$js_prefix = defined( 'TVE_DEBUG' ) === true && TVE_DEBUG === true ? '.js' : '.min.js';
		wp_enqueue_script( 'thrive-product-manager', $this->url( 'js/dist/tpm-admin' . $js_prefix ), array(
			'jquery',
			'backbone',
		), self::V, true );

		wp_localize_script( 'thrive-product-manager', 'TPM', $this->get_localization_data() );
	}

	public function get_localization_data() {

		$data = array(
			'products'     => TPM_Product_List::get_instance()->get_products_array(),
			'tpm_url'      => $this->get_admin_url(),
			'ttw_url'      => self::get_ttw_url(),
			'tve_dash_url' => admin_url( 'admin.php?page=tve_dash_section' ),
			'messages'     => apply_filters( 'tpm_messages', array() ),
		);

		return $data;
	}

	public function get_backbone_templates( $dir = null, $root = 'backbone' ) {

		if ( null === $dir ) {
			$dir = plugin_dir_path( dirname( __FILE__ ) ) . 'templates/backbone';
		}

		$folders   = scandir( $dir );
		$templates = array();

		foreach ( $folders as $item ) {
			if ( in_array( $item, array( ".", ".." ) ) ) {
				continue;
			}

			if ( is_dir( $dir . '/' . $item ) ) {
				$templates = array_merge( $templates, $this->get_backbone_templates( $dir . '/' . $item, $root ) );
			}

			if ( is_file( $dir . '/' . $item ) ) {
				$_parts     = explode( $root, $dir );
				$_truncated = end( $_parts );
				$tpl_id     = ( ! empty( $_truncated ) ? trim( $_truncated, '/\\' ) . '/' : '' ) . str_replace( array(
						'.php',
						'.phtml',
					), '', $item );

				$tpl_id = str_replace( array( '/', '\\' ), '-', $tpl_id );

				$templates[ $tpl_id ] = $dir . '/' . $item;
			}
		}

		return $templates;
	}

	public function print_admin_templates() {

		if ( $this->is_known_page() === false ) {
			return false;
		}

		$templates = $this->get_backbone_templates( $this->path( 'inc/templates/backbone' ) );

		foreach ( $templates as $tpl_id => $path ) {
			echo "\n" . '<script type="text/template" id="' . $tpl_id . '">' . "\n";
			include $path;
			echo '</script>';
		}

		wp_print_request_filesystem_credentials_modal();
	}

	public function try_install_and_activate_product() {

		if ( empty( $_REQUEST['tag'] ) ) {
			return false;
		}

		$tag         = $_REQUEST['tag'];
		$productList = TPM_Product_List::get_instance();
		$product     = $productList->get_product_instance( $tag );

		$data = array(
			'status'  => null,
			'tag'     => $product->get_tag(),
			'message' => null,
		);

		//INSTALL PRODUCT

		$credentials = array(
			'hostname' => '',
			'username' => '',
			'password' => '',
		);

		$submitted_form = wp_unslash( $_POST );
		$credentials    = wp_parse_args( ! empty( $submitted_form['credentials'] ) ? $submitted_form['credentials'] : array(), $credentials );

		$credentials['hostname'] = defined( 'FTP_HOST' ) ? FTP_HOST : $credentials['hostname'];
		$credentials['username'] = defined( 'FTP_USER' ) ? FTP_USER : $credentials['username'];
		$credentials['password'] = defined( 'FTP_PASS' ) ? FTP_PASS : $credentials['password'];

		$installed = $product->install( $credentials );

		if ( is_wp_error( $installed ) ) {

			TPM_Log_Manager::get_instance()->set_message( $installed )->log();

			$message = $installed->get_error_message();
			$code    = $installed->get_error_code();

			$data['message'] = empty( $message ) ? __( 'Product could not be installed', Thrive_Product_Manager::T ) : $message;
			$data['status']  = $code;

			wp_send_json_error( $data );
			die;
		}

		//ACTIVATE PRODUCT

		$activated = $product->activate();

		if ( is_wp_error( $activated ) ) {
			TPM_Log_Manager::get_instance()->set_message( $activated )->log();
			$data['message'] = $activated->get_error_message();
			$data['status']  = 'not_activated';
			wp_send_json_error( $data );
			die;
		}

		//LICENSE PRODUCT

		if ( $product->is_licensed() ) {
			$data['status']  = 'ready';
			$data['message'] = sprintf( "%s is now ready to use", $product->get_name() );
			wp_send_json_success( $data );
			die;
		}

		$product->search_license();

		$licensed = TPM_License_Manager::get_instance()->activate_licenses( array( $product ) );

		TPM_Product_List::get_instance()->clear_cache();
		TPM_License_Manager::get_instance()->clear_cache();

		if ( $licensed === false ) {
			$data['message'] = sprintf( "%s could not be licensed", $product->get_name() );
			$data['status']  = 'not_licensed';
			wp_send_json_error( $data );
			die;
		}

		$data['status']  = 'ready';
		$data['message'] = sprintf( "%s is now ready to use", $product->get_name() );
		wp_send_json_success( $data );

		die;
	}

	public function try_activate_manually() {

		if ( empty( $_REQUEST['tpm_action'] ) || $_REQUEST['tpm_action'] !== 'manually' ) {
			return;
		}

		if ( $this->is_known_page() === false ) {
			return;
		}

		$connection = TPM_Connection::get_instance();

		$ttw_salt     = ! empty( $_REQUEST['ttw_salt'] ) ? $_REQUEST['ttw_salt'] : null;
		$license_id   = ! empty( $_REQUEST['license_id'] ) ? (int) $_REQUEST['license_id'] : null;
		$tags         = ! empty( $_REQUEST['tags'] ) ? $_REQUEST['tags'] : array();
		$callback_url = ! empty( $_REQUEST['callback_url'] ) ? urldecode( base64_decode( $_REQUEST['callback_url'] ) ) : null;

		if ( empty( $callback_url ) ) {
			wp_redirect( $this->get_admin_url() );
			die;
		}

		$fail_url = add_query_arg( array(
			'success' => 0,
		), $callback_url );

		$success_url = add_query_arg( array(
			'success' => 1,
		), $callback_url );

		if ( empty( $license_id ) || empty( $tags ) ) {
			wp_redirect( $fail_url );
			die;
		}

		if ( $ttw_salt !== $connection->ttw_salt ) {
			wp_redirect( $fail_url );
			die;
		}

		$license = new TPM_License( $license_id, $tags );
		$license->save();

		TPM_Product_List::get_instance()->clear_cache();
		TPM_License_Manager::get_instance()->clear_cache();

		wp_redirect( $success_url );
		die;
	}

	public function rest_api_init() {

		register_rest_route( 'thrive-product-manager/v1', '/deactivate/(?P<id>\d+)', array(
			'methods'  => 'POST',
			'callback' => array( TPM_License_Manager::get_instance(), 'license_deactivate' ),
		) );
	}

	public function try_set_url() {

		if ( $this->is_known_page() === false ) {
			return;
		}

		if ( ! empty( $_REQUEST['url'] ) && ! empty( $_REQUEST['tpm_action'] ) && $_REQUEST['tpm_action'] === 'set_url' ) {

			update_option( 'tpm_ttw_url', $_REQUEST['url'] );

			wp_redirect( $this->get_admin_url() );
			die;
		}
	}

	public function try_logout() {

		if ( $this->is_known_page() === false ) {
			return;
		}

		if ( ! empty( $_REQUEST['tpm_disconnect'] ) ) {

			$connection = TPM_Connection::get_instance();

			$params  = array(
				'website' => get_site_url(),
			);
			$request = new TPM_Request( '/api/v1/public/disconnect/' . $connection->ttw_id, $params );
			$request->set_header( 'Authorization', $connection->ttw_salt );

			$proxy_request = new TPM_Proxy_Request( $request );
			$proxy_request->execute( '/tpm/proxy' );

			$connection->disconnect();

			wp_redirect( $this->get_admin_url() );
			die;
		}
	}

	public function itemActivated( $tag ) {

		$product = new TPM_Product( 'name', 'description', 'logo_url', $tag, 'api_slug', 'file' );

		$licensed = $product->is_licensed();

		return $licensed;
	}

	public function dash_filter_localize( $data ) {

		$data['tpm'] = array(
			'admin_url' => $this->get_admin_url(),
		);

		return $data;
	}

	public function update_checker() {
		/** plugin updates script **/

		if ( ! class_exists( 'TVE_PluginUpdateChecker', false ) ) {
			return;
		}

		new TVE_PluginUpdateChecker(
			'http://service-api.thrivethemes.com/plugin/update',
			$this->path( 'thrive-product-manager.php' ),
			'thrive-product-manager',
			12,
			'',
			'thrive_product_manager'
		);
	}
}

function thrive_product_manager() {

	return Thrive_Product_Manager::get_instance();
}

thrive_product_manager();
