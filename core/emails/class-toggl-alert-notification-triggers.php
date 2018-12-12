<?php
/**
 * Notification Triggers for Toggl Alert
 *
 * @since		1.0.0
 *
 * @package Toggl_Alert
 * @subpackage Toggl_Alert/core/emails
 */

defined( 'ABSPATH' ) || die();

final class Toggl_Alert_Notification_Triggers {

	/**
	 * Toggl_Alert_Notification_Triggers constructor.
	 * 
	 * @since		1.0.0
	 */
	function __construct() {

		// Sundays
		add_action( 'toggl_alert_weekly_0_cron', array( $this, 'every_sunday' ) );
		
		// Monday
		add_action( 'toggl_alert_weekly_1_cron', array( $this, 'every_monday' ) );
		
		// Tuesday
		add_action( 'toggl_alert_weekly_2_cron', array( $this, 'every_tuesday' ) );
		
		// Wednesday
		add_action( 'toggl_alert_weekly_3_cron', array( $this, 'every_wednesday' ) );
		
		// Thursday
		add_action( 'toggl_alert_weekly_4_cron', array( $this, 'every_thursday' ) );
		
		// Friday
		add_action( 'toggl_alert_weekly_5_cron', array( $this, 'every_friday' ) );
		
		// Saturday
		add_action( 'toggl_alert_weekly_6_cron', array( $this, 'every_saturday' ) );

	}
	
	/**
	 * Checks for Alerts every Sunday
	 * 
	 * @access		public
	 * @since		{{VERSION}}
	 * @return		void
	 */
	public function every_sunday() {
		
		do_action( 'toggl_alert_notify', 'every_sunday' );
		
	}
	
	/**
	 * Checks for Alerts every Monday
	 * 
	 * @access		public
	 * @since		{{VERSION}}
	 * @return		void
	 */
	public function every_monday() {
		
		do_action( 'toggl_alert_notify', 'every_monday' );
		
	}
	
	/**
	 * Checks for Alerts every Tuesday
	 * 
	 * @access		public
	 * @since		{{VERSION}}
	 * @return		void
	 */
	public function every_tuesday() {
		
		do_action( 'toggl_alert_notify', 'every_tuesday' );
		
	}
	
	/**
	 * Checks for Alerts every Wednesday
	 * 
	 * @access		public
	 * @since		{{VERSION}}
	 * @return		void
	 */
	public function every_wednesday() {
		
		do_action( 'toggl_alert_notify', 'every_wednesday' );
		
	}
	
	/**
	 * Checks for Alerts every Thursday
	 * 
	 * @access		public
	 * @since		{{VERSION}}
	 * @return		void
	 */
	public function every_thursday() {
		
		do_action( 'toggl_alert_notify', 'every_thursday' );
		
	}
	
	/**
	 * Checks for Alerts every Friday
	 * 
	 * @access		public
	 * @since		{{VERSION}}
	 * @return		void
	 */
	public function every_friday() {
		
		do_action( 'toggl_alert_notify', 'every_friday' );
		
	}
	
	/**
	 * Checks for Alerts every Saturday
	 * 
	 * @access		public
	 * @since		{{VERSION}}
	 * @return		void
	 */
	public function every_saturday() {
		
		do_action( 'toggl_alert_notify', 'every_saturday' );
		
	}

}