<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-product-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

class TPM_Theme_Installer_Skin extends Theme_Installer_Skin {

	public $done_header = true;
	public $done_footer = true;
	public $messages = array();

	public function request_filesystem_credentials( $error = false, $context = false, $allow_relaxed_file_ownership = false ) {

		return $this->options;
	}

	public function feedback( $string ) {

		if ( empty( $string ) ) {
			return;
		}

		array_push( $this->messages, $string );
	}
}
