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
 * Class TVD_Smart_Shortcodes
 */
final class TVD_Smart_Shortcodes {

	/**
	 * Database instance for Smart Site
	 *
	 * @var TVD_Smart_DB
	 */
	private $db;

	/**
	 * TVD_Smart_Shortcodes constructor.
	 */
	public function __construct() {
		$this->db = new TVD_Smart_DB();
		add_shortcode( TVD_Smart_Site::GLOBAL_FIELDS_SHORTCODE, array( $this, 'tvd_tss_smart_fields' ) );
	}

	/**
	 * Execute smart fields shortcode
	 *
	 * @param $args
	 *
	 * @return string
	 */
	public function tvd_tss_smart_fields( $args ) {
		$data = '';
		if ( $args['id'] ) {
			$field = $this->db->get_fields( array(), $args['id'] );
			if ( ! empty( $field ) ) {
				$field_data = maybe_unserialize( $field['data'] );

				if ( $field_data ) {
					switch ( (int) $field['type'] ) {
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
							$url = 'https://maps.google.com/maps?q=' . urlencode( empty( $field_data['location'] ) ? 'New York' : $field_data['location'] ) . '&t=m&z=10&output=embed&iwloc=near';

							$data = '<iframe frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="' . $url . '"></iframe>';
							break;
					}
				}

			}

		}

		return $data;
	}

}
