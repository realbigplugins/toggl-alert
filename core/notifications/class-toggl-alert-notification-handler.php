<?php
/**
 * Notification Triggers get bounced here to be handled and emailed out
 *
 * @since		1.0.0
 *
 * @package Toggl_Alert
 * @subpackage Toggl_Alert/core/notifications
 */

defined( 'ABSPATH' ) || die();

final class Toggl_Alert_Notification_Handler {

	/**
	 * Toggl_Alert_Notification_Handler constructor.
	 * 
	 * @since		1.0.0
	 */
	function __construct() {
		
		// Create Necessary Post Type(s) and set up Create/Update/Delete functionality
		add_action( 'init', array( $this, 'setup_notifications' ) );
		
		// Generic Notification Router
		add_action( 'toggl_alert_notify', array( $this, 'notify' ), 10, 2 );

	}
	
	/**
	 * Creates Post Type(s) and necessary Create/Update/Delete functionality for Post Meta
	 * Controlled by the toggl_alert_notifications Filter
	 * 
	 * @access		public
	 * @since		1.0.0
	 * @return		void
	 */
	public function setup_notifications() {
		
		global $toggl_alert_notifications;
		
		$toggl_alert_notifications = apply_filters( 'toggl_alert_notifications', array() );
		
		if ( ! $toggl_alert_notifications ) {
			return;
		}
		
		foreach ( $toggl_alert_notifications as $notification_id => $notification_args ) {
		
			// Init the Post Types
			$this->create_notification_post_types( $notification_id, $notification_args );
			
		}
		
	}
	
	/**
	 * Handles Post Type(s) Creation
	 * 
	 * @param		string $notification_id   ID Used for Notification Hooks
	 * @param		array  $notification_args Array holding some basic Strings, but more importantly the Fields Array
	 *																										  
	 * @access		private
	 * @since		1.0.0
	 * @return		void
	 */
	private function create_notification_post_types( $notification_id, $notification_args ) {
		
		$labels = array(
			'name'			   => "$notification_args[name] Feeds",
			'singular_name'	  => "$notification_args[name] Feed",
			'menu_name'		  => "$notification_args[name] Feeds",
			'name_admin_bar'	 => "$notification_args[name] Feed",
			'add_new'			=> "Add New",
			'add_new_item'	   => "Add New $notification_args[name] Feed",
			'new_item'		   => "New $notification_args[name] Feed",
			'edit_item'		  => "Edit $notification_args[name] Feed",
			'view_item'		  => "View $notification_args[name] Feed",
			'all_items'		  => "All $notification_args[name] Feeds",
			'search_items'	   => "Search $notification_args[name] Feeds",
			'parent_item_colon'  => "Parent $notification_args[name] Feeds:",
			'not_found'		  => "No $notification_args[name] Feeds found.",
			'not_found_in_trash' => "No $notification_args[name] Feeds found in Trash.",
		);
		
		$args = array(
			'labels'			 => $labels,
			'public'			 => false,
			'publicly_queryable' => true,
			'show_ui'			=> false,
		);
		
		register_post_type( "toggl-alert-{$notification_id}-feed", $args );
		
	}
	
	/**
	 * Route our Notification Triggers to something more specific
	 * 
	 * @param		string $trigger Trigger
	 * @param		array  $args	Arguments Array
	 *										
	 * @access		public
	 * @since		1.0.0
	 * @return		void
	 */
	public function notify( $trigger, $args = array() ) {
		
		global $toggl_alert_notifications;
		
		$toggl_alert_notifications = apply_filters( 'toggl_alert_notifications', array() );
		
		do_action( "toggl_alert_notify_$trigger", $args );
		
		if ( $toggl_alert_notifications ) {
			
			foreach ( $toggl_alert_notifications as $notification_id => $notificaiton_args ) {
				
				$this->trigger_notification( $notification_id, $notificaiton_args, $trigger, $args );
				
			}
			
		}
		
	}
	
	/**
	 * Determines which specific route to send the Notitfication while grabbing related data
	 * 
	 * @param		string $notification_id		ID Used for Notification Hooks
	 * @param		array  $notification_args   $args defined by the toggl_alert_notifications Filter
	 * @param		string $trigger				Notification Trigger
	 * @param		array  $args				$args Array passed from the original Trigger of the process
	 *																							  
	 * @access		private
	 * @since		1.0.0
	 * @return		void
	 */
	private function trigger_notification( $notification_id, $notification_args, $trigger, $args ) {
		
		$notifications = get_posts( array(
			'post_type'   => "toggl-alert-{$notification_id}-feed",
			'numberposts' => -1,
			'meta_key'	=> "toggl_alert_{$notification_id}_feed_trigger",
			'meta_value'  => $trigger,
		) );
		
		if ( ! $notifications ) {
			return;
		}
		
		foreach ( $notifications as $notification_post ) {
			
			// Get Field Keys
			$notification_post_values = array();
			foreach ( $notification_args['fields'] as $field_id => $field ) {
				
				if ( $field['type'] == 'hook' ) continue;
				
				$notification_post_values[ $field_id ] = get_post_meta(
					$notification_post->ID,
					"toggl_alert_{$notification_id}_feed_$field_id",
					true
				);
				
			}
			
			// Fires the final Action that we use for whatever we feel like
			do_action( 
				"toggl_alert_do_notification_$notification_id",
				$notification_post,
				$notification_post_values,
				$trigger,
				$notification_id,
				$args
			);
			
		}
		
	}
	
	/**
	 * Does String Replacements for common things like the name of the User who triggered the Notification
	 * 
	 * @param		array  $strings			Notification Fields to check for replacements in
	 * @param		array  $fields			Fields used to create the Post Meta
	 * @param		string $trigger			Notification Trigger
	 * @param		string $notification_id ID used for Notification Hooks
	 * @param		array  $args			$args Array passed from the original Trigger of the process
	 *	 
	 * @access		public
	 * @since		1.0.0
	 * @return		array  Replaced Strings within each Field
	 */
	public function notifications_replacements( $strings, $fields, $trigger, $notification_id, $args, $replacements = array() ) {
		
		/**
		* Allows additional replacements to be made.
		*
		* @since 1.0.0
		*/
		$replacements = apply_filters( 'toggl_alert_notifications_replacements', $replacements, $fields, $trigger, $notification_id, $args );
		
		foreach ( $strings as $i => $string ) {
			$strings[ $i ] = str_replace( array_keys( $replacements ), array_values( $replacements ), $string );
		}
		
		return $strings;
		
	}

}