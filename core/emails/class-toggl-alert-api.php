<?php
/**
 * Provides Email integration functionality.
 *
 * @since		1.0.0
 *
 * @package Toggl_Alert
 * @subpackage Toggl_Alert/core
 */

defined( 'ABSPATH' ) || die();

final class Toggl_Alert_API {

	/**
	 * Toggl_Alert_API constructor.
	 *
	 * @since		1.0.0
	 */
	function __construct() {
	}

	/**
	 * Sends an Email
	 *
	 * @since		1.0.0
	 */
	public function email( $args = array() ) {

		$args = wp_parse_args( $args, array(
			'to' => get_option( 'admin_email', '' ),
			'subject' => '',
			'message' => '',
			'cc' => '',
			'bcc' => '',
		) );

		$result = wp_mail( $args['to'], $args['subject'], $args['message'], array(
			'Cc' => $args['cc'],
			'Bcc' => $args['bcc'],
		) );

		return $result;
		
	}
	
}