<?php
/**
 * Integrating into our own Notification System. Serves as an example on how to utilize it.
 *
 * @since		1.0.0
 *
 * @package Toggl_Alert
 * @subpackage Toggl_Alert/core/notifications
 */

defined( 'ABSPATH' ) || die();

final class Toggl_Alert_Notification_Integration {

	/**
	 * Toggl_Alert_Notification_Integration constructor.
	 * 
	 * @since		1.0.0
	 */
	function __construct() {
		
		// Ensure we've got our own Notifications Args in the Global
		add_filter( 'toggl_alert_notifications', array( $this, 'init_global_notifications' ) );
		
		// Create Notitfication to Push to Email
		add_action( 'toggl_alert_do_notification_rbm', array( $this, 'create_notification' ), 10, 5 );
		
		// Inject some Checks before we do Replacements or send the Notification
		add_action( 'toggl_alert_before_replacements', array( $this, 'before_notification_replacements' ), 10, 5 );
		
		// Add our own Replacement Strings
		add_filter( 'toggl_alert_notifications_replacements', array( $this, 'custom_replacement_strings' ), 10, 5 );
		
	}
	
	/**
	 * Allows some flexibility in what Fields get passed
	 * 
	 * @param		array $notifications Global $toggl_alert_notifications
	 *										  
	 * @access		public
	 * @since		1.0.0
	 * @return		array Modified Global Array
	 */
	public function init_global_notifications( $notifications ) {
		
		$notifications['rbm'] = array(
			'name' => __( 'Toggl Alert Email', 'toggl-alert' ),
			'default_feed_title' => __( 'New Email Notification', 'toggl-alert' ),
			'fields' => TOGGLALERT()->get_settings_fields(),
		);
		
		return $notifications;
		
	}
	
	/**
	 * Formats the Notification Data to be passed to Email
	 * 
	 * @param		object  $post				WP_Post Object for our Saved Notification Data
	 * @param		array   $fields				Fields used to create the Post Meta
	 * @param		string  $trigger			Notification Trigger
	 * @param		string  $notification_id	ID Used for Notification Hooks
	 * @param		array   $args				$args Array passed from the original Trigger of the process
	 *			  
	 * @access		public
	 * @since		1.0.0
	 * @return		void
	 */
	public function create_notification( $post, $fields, $trigger, $notification_id, $args ) {

		$defaults = get_option( 'toggl_alert' );
		
		// This allows the chance to possibly alter $args if needed
		do_action_ref_array( 'toggl_alert_before_replacements', array( $post, $fields, $trigger, $notification_id, &$args ) );
		
		/**
		 * Allows Notification Sending to properly Bail
		 *
		 * @since 1.0.4
		 */
		if ( isset( $args['bail'] ) && $args['bail'] ) return false;
		
		$fields = wp_parse_args( array_filter( $fields ), array(
			'to' => get_option( 'admin_email', '' ),
			'subject' => '',
			'message' => '',
			'cc' => '',
			'bcc' => '',
		) );
		
		if ( empty( $fields['subject'] ) ) {
			
			// Default to the Notification Identifier
			$fields['subject'] = $post->post_title;
			
		}

		$replacements = TOGGLALERT()->notification_handler->notifications_replacements(
			array(
				'message'	=> $fields['message'],
				'subject'   => $fields['subject'],
			),
			$fields,
			$trigger,
			$notification_id,
			$args
		);
		
		$fields['message']	= $replacements['message'];
		$fields['subject']   = $replacements['subject'];
		
		do_action( 'toggl_alert_after_replacements', $post, $fields, $trigger, $notification_id, $args );

		$this->push_notification( $fields );
		
	}
	
	/**
	 * Inject some checks on whether or not to bail on the Notification
	 * 
	 * @param		object  $post				WP_Post Object for our Saved Notification Data
	 * @param		array   $fields				Fields used to create the Post Meta
	 * @param		string  $trigger			Notification Trigger
	 * @param		string  $notification_id	ID Used for Notification Hooks
	 * @param		array   $args				$args Array passed from the original Trigger of the process
	 *			  
	 * @access		public
	 * @since		1.0.0
	 * @return		void
	 */
	public function before_notification_replacements( $post, $fields, $trigger, $notification_id, &$args ) {
		
		$args = wp_parse_args( $args, array(
			
		) );
		
		$api_token = get_option( 'toggl_api_token' );
		
		if ( $api_token ) {
		
			$workspace_project = explode( '-', $fields['project'] );

			$workspace_id = $workspace_project[0];
			$project_id = $workspace_project[1];
			
			$trigger_day = current_time( 'Y-m-d' );
			
			// If today is not the day of our Trigger, find the last occurence of that day
			if ( (string) date( 'w', strtotime( $trigger_day ) ) !== (string) $args['weekday_index'] ) {
				
				// Ensure there are no issues with locale and convert 
				$day = date( 'D', strtotime( "Sunday +{$args['weekday_index']} days" ) );

				// Calculate Timezone offset, which will be subtracted from our calculated Timestamp
				// This means UTC-5 will be added to the Timestamp
				// This is necessary because WP Events are fired based on the Timezone but Timestamps are not, so we have to counteract it
				$time = new \DateTime( 'now', new DateTimeZone( get_option( 'timezone_string', 'America/Detroit' ) ) );
				$timezone_offset = $time->format( 'Z' );

				$trigger_day = strtotime( 'last ' . $day ) - $timezone_offset;
				
				$trigger_day = date( 'Y-m-d', $trigger_day );
				
			}
			
			$since_date = date( 'Y-m-d', strtotime( "$trigger_day -7 days" ) );

			$toggl_reports = \AJT\Toggl\ReportsClient::factory( array(
				'api_key' => $api_token,
				'apiVersion' => 'v2',
				'debug' => false,
			) );

			$week_report = $toggl_reports->weekly( array(
				'user_agent' => 'rbm-toggl-alert',
				'workspace_id' => (int) $workspace_id,
				'project_ids' => $project_id,
				'since' => $since_date, // Show data for the past week up until the Trigger Date
			) );

			// This is recorded in milliseconds
			$args['logged_hours'] = $week_report['total_grand'] / ( HOUR_IN_SECONDS * 1000 );
			
			// If more hours were logged then we were checking for, don't send a notification
			if ( $args['logged_hours'] >= $fields['hours'] ) {
				$args['bail'] = true;
				return false;
			}
			
			// Pass into the Notification Flow along with the Logged Hours so it can be used for Merge Tags
			$args['since'] = $since_date;
			$args['until'] = date( 'Y-m-d', strtotime( "$trigger_day -1 days" ) ); // Subtract one day from here because the range does not include the trigger day. The math above works out for this for the query, but we do not want the Merge Tag to be confusing for Users.
			
		}
		else {
			
			// No API Key, don't send notification
			$args['bail'] = true;
			return false;
			
		}
		
	}
	
	/**
	 * Based on our Notification ID and Trigger, use some extra Replacement Strings
	 * 
	 * @param		array  $replacements    Notification Fields to check for replacements in
	 * @param		array  $fields          Fields used to create the Post Meta
	 * @param		string $trigger         Notification Trigger
	 * @param		string $notification_id ID used for Notification Hooks
	 * @param		array  $args            $args Array passed from the original Trigger of the process
	 * 
	 * @access		public
	 * @since		1.0.0
	 * @return		array  Replaced Strings within each Field
	 */
	public function custom_replacement_strings( $replacements, $fields, $trigger, $notification_id, $args ) {

		if ( $notification_id == 'rbm' ) {
			
			$api_token = get_option( 'toggl_api_token' );
			
			$projects_array = toggl_alert_get_projects( $api_token );
			$project = TOGGLALERT()->array_key_search( $projects_array, $fields['project'] );
			
			$replacements['%project%'] = ( $project ) ? $project : __( 'Project not found', 'toggl-alert' );
			
			$replacements['%minimum_hours%'] = $fields['hours'];
			$replacements['%logged_hours%'] = number_format( $args['logged_hours'], 2 );
			
			$replacements['%since_date%'] = date_i18n( 'l, F j, Y', strtotime( $args['since'] ) );
			$replacements['%until_date%'] = date_i18n( 'l, F j, Y', strtotime( $args['until'] ) );

			switch ( $trigger ) {
					
				default:
					break;

			}
			
		}
		
		return $replacements;
		
	}
	
	/**
	 * Sends the Data to Email
	 * 
	 * @param		array $fields Fully Transformed Notification Fields
	 *													 
	 * @access		public
	 * @since		1.0.0
	 * @return		void
	 */
	public function push_notification( $fields ) {
		
		TOGGLALERT()->email_api->email( $fields );
		
	}
	
	/**
	 * Create/Update Notification Feed Posts via your Settings Interface
	 *																										  
	 * @access	  public
	 * @since	  1.1.0
	 * @return	  void
	 */
	public static function update_feed() {
		
		if ( is_admin() && 
			current_user_can( 'manage_options' ) && 
		   check_admin_referer( 'toggl_alert_settings', 'toggl_alert_settings_nonce' ) ) {
		
			global $toggl_alert_notifications;

			$toggl_alert_notifications = apply_filters( 'toggl_alert_notifications', array() );

			$notification_id = apply_filters( 'toggl_alert_notification_id', 'rbm' );
			$notification_args = $toggl_alert_notifications[ $notification_id ];

			$notification_args = wp_parse_args( $notification_args, array(
				'default_feed_title' => _x( 'New Email Notification', 'New Email Notification Header', 'toggl-alert' ),
				'fields'			 => array(),
			) );

			$post_args = array(
				'ID'		  => (int) $_POST['email_post_id'] > 0 ? (int) $_POST['email_post_id'] : 0,
				'post_type'   => "toggl-alert-{$notification_id}-feed",
				'post_title'  => '',
				'post_status' => 'publish',
			);

			$notification_meta = array();

			foreach ( $notification_args['fields'] as $field_name => $field ) {

				if ( isset( $_POST[ $field_name ] ) ) {

					if ( $field_name == 'email_post_id' || $field_name == 'admin_title' ) continue;

					$notification_meta["toggl_alert_{$notification_id}_feed_$field_name"] = $_POST[ $field_name ];

				}

			}

			if ( $_POST['admin_title'] ) {
				$post_args['post_title'] = $_POST['admin_title'];
			}
			else {
				$post_args['post_title'] = $notification_args['default_feed_title'];
			}

			$post_id = wp_insert_post( $post_args );

			if ( $post_id !== 0 && ! is_wp_error( $post_id ) ) {

				foreach ( $notification_meta as $field_name => $field_value ) {

					if ( $field_name == 'email_post_id' || $field_name == 'admin_title' ) continue;

					update_post_meta( $post_id, $field_name, $field_value );

				}

			}
			else {

				return wp_send_json_error( array(
					'error' => $post_id, // $post_id holds WP_Error object in this case
				) );

			}

			return wp_send_json_success( array(
				'post_id' => $post_id,
			) );
			
		}
		
		return wp_send_json_error( array(
			'error' => _x( 'Access Denied', 'Current User Cannot Create Notications Error', 'toggl-alert' ),
		) );
		
	}
	
	/**
	 * Delete Feed Posts via ID
	 * 
	 * @access	  public
	 * @since	  1.1.0
	 * @return	  void
	 */
	public static function delete_feed() {
		
		if ( is_admin() && 
			current_user_can( 'manage_options' ) && 
		   check_admin_referer( 'toggl_alert_settings', 'toggl_alert_settings_nonce' ) ) {

			$post_id = $_POST['post_id'];

			$success = wp_delete_post( $post_id, true );

			if ( $success ) {
				return wp_send_json_success();
			}
			else {
				return wp_send_json_error();
			}
			
		}
		
		return wp_send_json_error( array(
			'error' => _x( 'Access Denied', 'Current User Cannot Delete Notications Error', 'toggl-alert' ),
		) );

	}
	
}

// AJAX Hook for Inserting new/updating Notifications
add_action( 'wp_ajax_insert_toggl_alert_rbm_notification', array( 'Toggl_Alert_Notification_Integration', 'update_feed' ) );

// AJAX Hook for Deleting Notifications
add_action( 'wp_ajax_delete_toggl_alert_rbm_notification', array( 'Toggl_Alert_Notification_Integration', 'delete_feed' ) );