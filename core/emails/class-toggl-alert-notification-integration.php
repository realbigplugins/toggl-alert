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
		if ( $args['bail'] ) return false;
		
		// Use Term ID to grab the Webhook URL from Term Meta
		// Doing it here allows it to easily work whether it is the Default or defined in the Notification
		if ( isset( $fields['webhook_url'] ) && 
			$fields['webhook_url'] !== '' ) {
			
			// If Webhook used for the Notification has been deleted, default to the Default Webhook so that it still sends
			if ( term_exists( (int) $fields['webhook_url'], "toggl-alert-{$notification_id}-webhook" ) === NULL ||
				term_exists( (int) $fields['webhook_url'], "toggl-alert-{$notification_id}-webhook" ) == 0 ) {
				unset( $fields['webhook_url'] );
			}
			else {
				$fields['webhook_url'] = get_term_meta( $fields['webhook_url'], 'webhook_url', true );
			}
			
		}
		
		$fields = wp_parse_args( array_filter( $fields ), array(
			'webhook_url'	 => ( $defaults['webhook_default'] ) ? $defaults['webhook_default'] : '',
			'channel'		 => '',
			'message_text'	=> '',
			'message_title'   => $post->post_title,
			'message_pretext' => '',
			'color'		   => '',
			'username'		=> get_bloginfo( 'name' ),
			'icon'			=> function_exists( 'has_site_icon' ) && has_site_icon() ? get_site_icon_url( 270 ) : '',
		) );

		$replacements = TOGGLALERT()->notification_handler->notifications_replacements(
			array(
				'message_text'	=> $fields['message_text'],
				'message_title'   => $fields['message_title'],
				'message_pretext' => $fields['message_pretext'],
			),
			$fields,
			$trigger,
			$notification_id,
			$args
		);
		
		$fields['message_text']	= $replacements['message_text'];
		$fields['message_title']   = $replacements['message_title'];
		$fields['message_pretext'] = $replacements['message_pretext'];
		
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
			'quiz_id' => null,
			'lesson_id' => null,
			'course_id' => null,
			'topic_id' => null,
			'expire_time' => 0,
			'last_login' => 0,
			'user_id' => null,
			'question_id' => null,
			'bail' => false,
		) );
		
		if ( $trigger == 'fail_quiz'
		   || $trigger == 'pass_quiz'
		   || $trigger == 'complete_quiz' ) {
			
			// Support for LD Email v1.1.X
			if ( ! is_array( $fields['quiz'] ) ) $fields['quiz'] = array( $fields['quiz'] );
			
			// Bail if we aren't set to notify for this particular quiz
			if ( ! in_array( 'all', $fields['quiz'] ) ) {
					
				if ( ! in_array( $args['quiz_id'], $fields['quiz'] ) ) {
					$args['bail'] = true;
					return false;
				}

			}
			else {

				// Support for LD Email v1.1.X
				if ( ! isset( $fields['exclude_quiz'] ) ) $fields['exclude_quiz'] = array();
				
				if ( in_array( $args['quiz_id'], $fields['exclude_quiz'] ) ) {
					$args['bail'] = true;
					return false;
				}

			}
			
		}
		
		if ( $trigger == 'complete_lesson'
		   || $trigger == 'lesson_available' ) {
			
			// Support for LD Email v1.1.X
			if ( ! is_array( $fields['lesson'] ) ) $fields['lesson'] = array( $fields['lesson'] );
			
			// Bail if we aren't set to notify for this particular lesson
			if ( ! in_array( 'all', $fields['lesson'] ) ) {
					
				if ( ! in_array( $args['lesson_id'], $fields['lesson'] ) ) {
					$args['bail'] = true;
					return false;
				}

			}
			else {

				// Support for LD Email v1.1.X
				if ( ! isset( $fields['exclude_lesson'] ) ) $fields['exclude_lesson'] = array();

				if ( in_array( $args['lesson_id'], $fields['exclude_lesson'] ) ) {
					$args['bail'] = true;
					return false;
				}

			}
			
		}
		
		if ( $trigger == 'complete_topic' ) {
			
			// Support for LD Email v1.1.X
			if ( ! is_array( $fields['topic'] ) ) $fields['topic'] = array( $fields['topic'] );
			
			// Bail if we aren't set to notify for this particular topic
			if ( ! in_array( 'all', $fields['topic'] ) ) {
					
				if ( ! in_array( $args['topic_id'], $fields['topic'] ) ) {
					$args['bail'] = true;
					return false;
				}

			}
			else {

				// Support for LD Email v1.1.X
				if ( ! isset( $fields['exclude_topic'] ) ) $fields['exclude_topic'] = array();

				if ( in_array( $args['topic_id'], $fields['exclude_topic'] ) ) {
					$args['bail'] = true;
					return false;
				}

			}
			
		}
		
		if ( $trigger == 'course_expires' ) {
			
			// Support for LD Email v1.1.X
			if ( ! is_array( $fields['course'] ) ) $fields['course'] = array( $fields['course'] );
			
			// Bail if we aren't set to notify for this particular course
			if ( ! in_array( 'all', $fields['course'] ) ) {
					
				if ( ! in_array( $args['course_id'], $fields['course'] ) ) {
					$args['bail'] = true;
					return false;
				}

			}
			else {

				// Support for LD Email v1.1.X
				if ( ! isset( $fields['exclude_course'] ) ) $fields['exclude_course'] = array();

				if ( in_array( $args['course_id'], $fields['exclude_course'] ) ) {
					$args['bail'] = true;
					return false;
				}

			}
			
			// Check to see if Current Date is X Days away from Course Expiry
			// Bail on any other possibility
			if ( date( 'Y-m-d' ) !== date( 'Y-m-d', strtotime( '-' . $fields['before_days'] . ' days', $args['expire_time'] ) ) ) {
				$args['bail'] = true;
				return false;
			}
			
			// So that we have access to it for Text Replacement
			$args['x_days'] = $fields['before_days'];
			
		}
		
		if ( $trigger == 'not_logged_in' ) {
			
			// Check to see if Current Date is X Days after their last login
			// Bail on any other possibility
			if ( date( 'Y-m-d' ) !== date( 'Y-m-d', strtotime( '+' . $fields['after_days'] . ' days', $args['last_login'] ) ) ) {
				$args['bail'] = true;
				return false;
			}
			
			// So that we have access to it for Text Replacement
			$args['x_days'] = $fields['after_days'];
			
		}
		
		if ( $trigger == 'essay_graded' ) {
		
			// Ensure we've got the data we absolutely need
			if ( ! isset( $args['user_id'] ) || ! isset( $args['question_id'] ) ) {
				$args['bail'] = true;
				return false;
			}
			
		}
		
		if ( $trigger == 'enroll_course' 
			|| $trigger == 'complete_course' ) {
			
			// Support for LD Email v1.1.X
			if ( ! is_array( $fields['course'] ) ) $fields['course'] = array( $fields['course'] );
			
			// Bail if we aren't set to notify for this particular course
			if ( ! in_array( 'all', $fields['course'] ) ) {
					
				if ( ! in_array( $args['course_id'], $fields['course'] ) ) {
					$args['bail'] = true;
					return false;
				}

			}
			else {

				// Support for LD Email v1.1.X
				if ( ! isset( $fields['exclude_course'] ) ) $fields['exclude_course'] = array();

				if ( in_array( $args['course_id'], $fields['exclude_course'] ) ) {
					$args['bail'] = true;
					return false;
				}

			}
			
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
		
		// Allow Users to possibly be targeted
		if ( $fields['channel'] !== '' && strpos( $fields['channel'], '#' ) !== 0 && strpos( $fields['channel'], '@' ) !== 0 ) {
			$fields['channel'] = '#' . $fields['channel'];
		}
		
		$fields['icon'] = $this->format_icon_emoji( $fields['icon'] );
		
		$args = array(
			'channel' => $fields['channel'] ? $fields['channel'] : '',
			'username' => $fields['username'],
			'icon_emoji' => strpos( $fields['icon'], 'http' ) === false ? $fields['icon'] : '',
			'icon_url' => strpos( $fields['icon'], 'http' ) !== false ? $fields['icon'] : '',
			'attachments' => array(
				array(
					'text' => html_entity_decode( $fields['message_text'] ),
					'title' => html_entity_decode( $fields['message_title'] ),
					'pretext' => html_entity_decode( $fields['message_pretext'] ),
					'color' => $fields['color'],
					'mrkdwn_in' => array(
						'text',
						'pretext', // The Title unfortunately cannot use Markdown. The Title is always bolded though.
					),
				),
			),
		);
		
		TOGGLALERT()->email_api->push_incoming_webhook( $fields['webhook_url'], $args );
		
	}
	
	/**
	 * Ensures an Emoji passed to Email is formatted correctly
	 * 
	 * @param	  string $icon_emoji Image/Emoji String
	 *											 
	 * @access	  public
	 * @since	  1.1.0
	 * @return	  string Image/Emoji String
	 */
	public function format_icon_emoji( $icon_emoji ) {
		
		// If an image was passed through
		if ( strpos( $icon_emoji, 'http' ) !== false ) return $icon_emoji;
		
		// Sanitize the emoji string somewhat
		$icon_emoji = preg_replace( '/\W/i', '', $icon_emoji );
		
		// If it is empty, pass it as empty so the Webhook can handle it
		if ( empty( $icon_emoji ) ) return $icon_emoji;
		
		// Otherwise ensure it is wrapped by colons
		return ':' . $icon_emoji . ':';
		
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
	
	/**
	 * Create/Update Webhook URL Terms via your Settings Interface
	 *																										  
	 * @access	  public
	 * @since	  1.2.0
	 * @return	  void
	 */
	public static function update_webhook() {
		
		if ( is_admin() && 
			current_user_can( 'manage_options' ) && 
		   check_admin_referer( 'toggl_alert_settings', 'toggl_alert_settings_nonce' ) ) {

			$notification_id = apply_filters( 'toggl_alert_notification_id', 'rbm' );
			
			$term_id = isset( $_POST['term_id'] ) && (int) $_POST['term_id'] > 0 ? (int) $_POST['term_id'] : 0;
			
			$term = false;

			if ( $term_id > 0 ) { // If we're updating an existing Term
				
				if ( preg_match( '/https:\/\/hooks.email.com\/services\/.*/', $_POST['url'] ) ) {
				
					// Annoyingly, wp_insert_term() and wp_update_term() are entirely separate
					// update_term_meta() can be used in both cases though
					$term = wp_update_term( $term_id, "toggl-alert-{$notification_id}-webhook", array(
						'name' => $_POST['name'],
					) );

					$term_meta_id = update_term_meta( $term_id, 'webhook_url', $_POST['url'] );
					
				}
				
			}
			else { // Creating a new Term
				
				if ( preg_match( '/https:\/\/hooks.email.com\/services\/.*/', $_POST['url'] ) ) {
				
					$term = wp_insert_term( $_POST['name'], "toggl-alert-{$notification_id}-webhook" );

					// $term is an Array holding term_id and term_taxonomy_id
					$term_id = $term['term_id'];

					$term_meta_id = update_term_meta( $term_id, 'webhook_url', $_POST['url'] );
					
				}
				
			}
			
			if ( ! $term || 
				is_wp_error( $term ) ) {

				return wp_send_json_error( array(
					'error' => $term, // $term holds WP_Error object in this case
				) );

			}
			
			return wp_send_json_success( array(
				'term_id' => $term_id,
			) );
			
		}
		
		return wp_send_json_error( array(
			'error' => _x( 'Access Denied', 'Current User Cannot Create Webhooks Error', 'toggl-alert' ),
		) );
		
	}
	
	/**
	 * Delete Feed Posts via ID
	 * 
	 * @access	  public
	 * @since	  1.2.0
	 * @return	  void
	 */
	public static function delete_webhook() {
		
		if ( is_admin() && 
			current_user_can( 'manage_options' ) && 
		   check_admin_referer( 'toggl_alert_settings', 'toggl_alert_settings_nonce' ) ) {

			$notification_id = apply_filters( 'toggl_alert_notification_id', 'rbm' );
			
			$term_id = isset( $_POST['term_id'] ) && (int) $_POST['term_id'] > 0 ? (int) $_POST['term_id'] : 0;

			$success = wp_delete_term( $term_id, "toggl-alert-{$notification_id}-webhook" );

			if ( $success ) {
				return wp_send_json_success();
			}
			else {
				return wp_send_json_error();
			}
			
		}
		
		return wp_send_json_error( array(
			'error' => _x( 'Access Denied', 'Current User Cannot Delete Webhooks Error', 'toggl-alert' ),
		) );

	}
	
	/**
	 * Create/Update the Default Webhook URL via your Settings Interface
	 *																										  
	 * @access	  public
	 * @since	  1.2.0
	 * @return	  void
	 */
	public static function update_default_webhook() {
		
		if ( is_admin() && 
			current_user_can( 'manage_options' ) && 
		   check_admin_referer( 'toggl_alert_settings', 'toggl_alert_settings_nonce' ) ) {
			
			$global_options = get_option( 'toggl_alert' );
			
			$updated = false;
			
			if ( preg_match( '/https:\/\/hooks.email.com\/services\/.*/', $_POST['url'] ) ) {
				
				$global_options['webhook_default'] = $_POST['url'];
				$updated = update_option( 'toggl_alert', $global_options );
				
			}
			
			if ( ! $updated ) {

				return wp_send_json_error( array(
					'error' => __( 'Invalid Webhook URL', 'toggl-alert' ),
				) );

			}
			
			return wp_send_json_success();
			
		}
		
		return wp_send_json_error( array(
			'error' => _x( 'Access Denied', 'Current User Cannot Save Default Webhook Error', 'toggl-alert' ),
		) );
		
	}
	
	/**
	 * Create/Update the Timestamp Format via your Settings Interface
	 *																										  
	 * @access	  public
	 * @since	  1.2.0
	 * @return	  void
	 */
	public static function update_timestamp_format() {
		
		if ( is_admin() && 
			current_user_can( 'manage_options' ) && 
		   check_admin_referer( 'toggl_alert_settings', 'toggl_alert_settings_nonce' ) ) {
			
			$global_options = get_option( 'toggl_alert' );
				
			$global_options['timestamp_format'] = $_POST['format'];
			$updated = update_option( 'toggl_alert', $global_options );
			
			if ( ! $updated ) {

				return wp_send_json_error( array(
					'error' => __( 'Something went wrong updating the Timestamp Format', 'toggl-alert' ),
				) );

			}
			
			return wp_send_json_success();
			
		}
		
		return wp_send_json_error( array(
			'error' => _x( 'Access Denied', 'Current User Cannot Save Timestamp Format Error', 'toggl-alert' ),
		) );
		
	}
	
}

// AJAX Hook for Inserting new/updating Notifications
add_action( 'wp_ajax_insert_toggl_alert_rbm_notification', array( 'Toggl_Alert_Notification_Integration', 'update_feed' ) );

// AJAX Hook for Deleting Notifications
add_action( 'wp_ajax_delete_toggl_alert_rbm_notification', array( 'Toggl_Alert_Notification_Integration', 'delete_feed' ) );

// AJAX Hook for Inserting new/updating Webhooks
add_action( 'wp_ajax_insert_toggl_alert_rbm_webhook', array( 'Toggl_Alert_Notification_Integration', 'update_webhook' ) );

// AJAX Hook for Deleting Webhooks
add_action( 'wp_ajax_delete_toggl_alert_rbm_webhook', array( 'Toggl_Alert_Notification_Integration', 'delete_webhook' ) );

// AJAX Hook for Updating the Default Webhook
add_action( 'wp_ajax_insert_toggl_alert_rbm_default_webhook', array( 'Toggl_Alert_Notification_Integration', 'update_default_webhook' ) );

// AJAX Hook for Updating the Timestamp Format
add_action( 'wp_ajax_insert_toggl_alert_rbm_timestamp_format', array( 'Toggl_Alert_Notification_Integration', 'update_timestamp_format' ) );