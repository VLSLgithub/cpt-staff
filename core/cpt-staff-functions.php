<?php
/**
 * Provides helper functions.
 *
 * @since	  1.0.0
 *
 * @package	CPT_Staff
 * @subpackage CPT_Staff/core
 */
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Returns the main plugin object
 *
 * @since		1.0.0
 *
 * @return		CPT_Staff
 */
function CPTSTAFF() {
	return CPT_Staff::instance();
}

add_action( 'after_setup_theme', function() {

	if ( ! function_exists( 'vibrant_life_get_wysiwyg_options' ) ) {

		// Fallback in case the Theme is not active
		function vibrant_life_get_wysiwyg_options() {

			return array(
				'mediaButtons' => true,
			);

		}

	}
	
} );