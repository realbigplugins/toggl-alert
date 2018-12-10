<?php
/**
 * Provides helper functions.
 *
 * @since	  {{VERSION}}
 *
 * @package	Toggl_Alert
 * @subpackage Toggl_Alert/core
 */
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Returns the main plugin object
 *
 * @since		{{VERSION}}
 *
 * @return		Toggl_Alert
 */
function TOGGLALERT() {
	return Toggl_Alert::instance();
}