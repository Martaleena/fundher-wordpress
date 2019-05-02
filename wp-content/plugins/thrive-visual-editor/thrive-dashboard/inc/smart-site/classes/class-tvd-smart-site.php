<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

if ( ! class_exists( 'TVD_Smart_Site' ) ) :

	final class TVD_Smart_Site {

		/**
		 * Global fields shortcode key.
		 */
		const GLOBAL_FIELDS_SHORTCODE = 'thrive_global_fields';

		/**
		 * Database instance for Smart Site
		 *
		 * @var TVD_Smart_DB
		 */
		private $db;

		/**
		 * @var string
		 */
		private $_dashboard_page = 'tve_dash_smart_site';

		/**
		 * @var TVD_Smart_Shortcodes
		 */
		public $shortcodes;

		/**
		 * TVD_Smart_Site constructor.
		 *
		 * @throws Exception
		 */
		public function __construct() {
			$this->db = new TVD_Smart_DB();
			$this->do_db_migrations();
			$this->action_filters();
			$this->shortcodes = new TVD_Smart_Shortcodes();
		}

		/**
		 * Prepare Migrations
		 *
		 * @throws Exception
		 */
		private function do_db_migrations() {
			TD_DB_Manager::add_manager(
				TVE_DASH_PATH . '/inc/smart-site/migrations',
				'tve_td_db_version',
				DB_VERSION,
				'Thrive Dashboard',
				'td_'
			);
		}

		/**
		 *  Add actiions and filters
		 */
		private function action_filters() {
			add_action( 'current_screen', array( $this, 'conditional_hooks' ) );
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
			add_action( 'tve_after_db_migration', array( $this, 'insert_default_data' ) );
			add_action( 'rest_api_init', array( $this, 'create_initial_rest_routes' ) );

			add_filter( 'tve_dash_filter_features', array( $this, 'smart_site_feature' ) );
			add_filter( 'tcb_inline_shortcodes', array( $this, 'smart_site_tcb_shortcodes' ), 10, 1 );
			add_action( 'wp_enqueue_scripts', array( $this, 'frontend_enqueue_scripts' ), 11 );
		}

		/**
		 * Hooks for the edit screen
		 *
		 * @param $screen
		 */
		public function conditional_hooks( $screen ) {
			if ( ! $screen = get_current_screen() ) {
				return;
			}

			/**
			 * Main Dashboard section
			 */
			if ( $screen->id === 'toplevel_page_tve_dash_section' ) {
				add_filter( 'tve_dash_filter_features', array( $this, 'smart_site_feature' ) );
				add_filter( 'tve_dash_features', array( $this, 'smart_site_enable_feature' ) );
			}

			/**
			 * Smart Site Dashboard
			 */
			if ( $screen->id === 'admin_page_' . $this->_dashboard_page ) {
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
				add_action( 'admin_print_footer_scripts', array( $this, 'backbone_templates' ) );
			}
		}

		/**
		 * Add Card to dashboard
		 *
		 * @param $features
		 *
		 * @return mixed
		 */
		public function smart_site_feature( $features ) {
			$features['smart_site'] = array(
				'icon'        => 'tvd-icon-notification',
				'title'       => 'Smart Site',
				'description' => __( 'Smart Site Description should be here', TVE_DASH_TRANSLATE_DOMAIN ),
				'btn_link'    => add_query_arg( 'page', $this->_dashboard_page, admin_url( 'admin.php' ) ),
				'btn_text'    => __( "Smart Settings", TVE_DASH_TRANSLATE_DOMAIN ),
			);

			return $features;
		}

		/**
		 * Enable the NM feature to be displayed on Thrive Features Section
		 *
		 * @param $features
		 *
		 * @return mixed
		 */
		public function smart_site_enable_feature( $features ) {

			$features['smart_site'] = true;

			return $features;
		}

		/**
		 * Add to admin menu
		 */
		public function admin_menu() {
			add_submenu_page( null, __( 'Smart Site', TVE_DASH_TRANSLATE_DOMAIN ), __( 'Smart Site', TVE_DASH_TRANSLATE_DOMAIN ), 'manage_options', $this->_dashboard_page, array(
				$this,
				'admin_dashboard',
			) );
		}

		/**
		 * Main Smart Site page content
		 */
		public function admin_dashboard() {
			ob_start(); ?>
			<?php include TVE_DASH_PATH . '/css/font/dashboard-icons.svg' ?>
			<div id="tvd-tss-header"></div>
			<div class="tvd-tss-breadcrumbs-wrapper" id="tvd-tss-breadcrumbs-wrapper"></div>
			<div id="tvd-tss-wrapper">
				<div class="tvd-tss-menu"></div>
				<div class="tvd-tss-content"></div>
			</div><?php
			echo ob_get_clean();
		}

		/**
		 * Insert the default data in the DB
		 */
		public function insert_default_data() {
			$db = new TVD_Smart_DB();
			$db->insert_default_data();
		}

		/**
		 * Enqueue admin styles
		 */
		public function enqueue_styles() {
			tve_dash_enqueue_style( 'tvd-ss-admin', TVD_Smart_Const::url( 'assets/admin/css/styles.css' ) );
		}

		/**
		 * Enqueue admin scripts
		 */
		public function enqueue_scripts() {
			$screen    = get_current_screen();
			$screen_id = $screen ? $screen->id : '';

			if ( $screen_id === 'admin_page_' . $this->_dashboard_page ) {

				tve_dash_enqueue();

				wp_enqueue_script( 'jquery' );
				wp_enqueue_script( 'backbone' );

				tve_dash_enqueue_script( 'tvd-ss-admin', TVD_Smart_Const::url( 'assets/admin/js/dist/admin.min.js' ), array(
					'jquery',
					'backbone',
				), false, true );
//				tve_dash_enqueue_script( 'tvd-ss-google-maps', 'https://maps.googleapis.com/maps/api/js?key=AIzaSyB1ZwA7aErgL6reIUqIW0oVmzgPHiySnTo', array(
//					'jquery',
//				), false, true );

				wp_localize_script( 'tvd-ss-admin', 'TVD_SS', $this->localize() );

			}
		}

		/**
		 * Enqueue scripts in the frontend
		 */
		public function frontend_enqueue_scripts() {
			tve_dash_enqueue_script( 'tvd-ss-tcb-hooks', TVD_Smart_Const::url( 'assets/js/tcb_hooks.js' ), array(
				'jquery',
			), false, true );

			wp_localize_script( 'tvd-ss-tcb-hooks', 'TVD_SS', $this->frontent_localize() );
		}

		/**
		 * Localize Frontend Data
		 *
		 * @return array
		 */
		private function frontent_localize() {
			return array(
				'is_editor_page' => function_exists( 'is_editor_page' ) ? is_editor_page() : false,
			);
		}

		/**
		 * Localize admin data
		 *
		 * @return array
		 */
		public function localize() {
			return array(
				't'        => include TVD_Smart_Const::path( 'i18n.php' ),
				'dash_url' => admin_url( 'admin.php?page=tve_dash_section' ),
				'url'      => TVD_Smart_Const::url(),
				'nonce'    => wp_create_nonce( 'wp_rest' ),

				'routes' => array(
					'groups' => $this->get_route_url( 'groups' ),
					'fields' => $this->get_route_url( 'fields' ),
				),
				'data'   => array(
					'groups' => $this->db->get_groups(),
				),
			);
		}

		/**
		 * Add backbone templates
		 */
		public function backbone_templates() {
			$templates = tve_dash_get_backbone_templates( TVD_Smart_Const::path( 'views/admin/templates' ), 'templates' );
			tve_dash_output_backbone_templates( $templates );
		}

		/**
		 * Create the rest routes
		 */
		public function create_initial_rest_routes() {
			$endpoints = array(
				'TVD_Groups_Controller',
				'TVD_Fields_Controller',
			);

			foreach ( $endpoints as $e ) {
				/** @var TVD_REST_Controller $controller */
				$controller = new $e();
				$controller->register_routes();
			}
		}

		/**
		 * Get the route url
		 *
		 * @param       $endpoint
		 * @param int   $id
		 * @param array $args
		 *
		 * @return string
		 */
		private function get_route_url( $endpoint, $id = 0, $args = array() ) {

			$url = get_rest_url() . TVD_Smart_Const::REST_NAMESPACE . '/' . $endpoint;

			if ( ! empty( $id ) && is_numeric( $id ) ) {
				$url .= '/' . $id;
			}

			if ( ! empty( $args ) ) {
				add_query_arg( $args, $url );
			}

			return $url;
		}

		/**
		 * Add the shortcodes to the froala editor
		 *
		 * @param $shortcodes
		 *
		 * @return array
		 */
		public function smart_site_tcb_shortcodes( $shortcodes ) {
			if ( ! empty( $_REQUEST[ TVE_FRAME_FLAG ] ) ) {
				$groups = $this->db->get_groups();

				$shortcode = array(
					__( 'Global Fields', TVE_DASH_TRANSLATE_DOMAIN ) => array(),
				);


				foreach ( $groups as $group ) {
					if ( ! empty( $group['fields'] ) ) {
						$shortcode[ __( 'Global Fields', TVE_DASH_TRANSLATE_DOMAIN ) ][ $group['id'] ] = array(
							'name'        => $group['name'],
							'option'      => $group['name'],
							'value'       => TVD_Smart_Site::GLOBAL_FIELDS_SHORTCODE,
							'extra_param' => $group['id'],
							'input'       => array(
								'id' => array(
									'type'  => 'select',
									'label' => __( 'Field', TVE_DASH_TRANSLATE_DOMAIN ),
									'value' => array(),
								),
							),
						);
						foreach ( $group['fields'] as $field ) {
							$shortcode[ __( 'Global Fields', TVE_DASH_TRANSLATE_DOMAIN ) ][ $group['id'] ]['input']['id']['value'][ $field['id'] ]     = $field['name'];
							$shortcode[ __( 'Global Fields', TVE_DASH_TRANSLATE_DOMAIN ) ][ $group['id'] ]['input']['id']['real_data'][ $field['id'] ] = $this->shortcodes->tvd_tss_smart_fields( [ 'id' => $field['id'] ] );
						}
					}
				}

				$shortcode[ __( 'Global Fields', TVE_DASH_TRANSLATE_DOMAIN ) ] = array_values( $shortcode[ __( 'Global Fields', TVE_DASH_TRANSLATE_DOMAIN ) ] );

				$shortcodes = array_merge( $shortcodes, $shortcode );
			}

			return $shortcodes;
		}

	}

endif;

/**
 * Start Smart Site
 */
new TVD_Smart_Site();