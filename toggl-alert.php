<?php
/**
 * Plugin Name: Toggl Alert
 * Plugin URI: https://github.com/realbigplugins/toggl-alert
 * Description: Configurable Email Alerts to send out if a certain number of hours in a Project within a time period have not been met
 * Version: 0.1.0
 * Text Domain: toggl-alert
 * Author: Real Big Marketing
 * Author URI: https://realbigplugins.com/
 * Contributors: d4mation
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Toggl_Alert' ) ) {

	/**
	 * Main Toggl_Alert class
	 *
	 * @since	  {{VERSION}}
	 */
	final class Toggl_Alert {
		
		/**
		 * @var			array $plugin_data Holds Plugin Header Info
		 * @since		{{VERSION}}
		 */
		public $plugin_data;
		
		/**
		 * @var			array $admin_errors Stores all our Admin Errors to fire at once
		 * @since		{{VERSION}}
		 */
		private $admin_errors;

		/**
		 * Get active instance
		 *
		 * @access	  public
		 * @since	  {{VERSION}}
		 * @return	  object self::$instance The one true Toggl_Alert
		 */
		public static function instance() {
			
			static $instance = null;
			
			if ( null === $instance ) {
				$instance = new static();
			}
			
			return $instance;

		}
		
		protected function __construct() {
			
			$this->setup_constants();
			$this->load_textdomain();
			
			if ( version_compare( get_bloginfo( 'version' ), '4.4' ) < 0 ) {
				
				$this->admin_errors[] = sprintf( _x( '%s requires v%s of %sWordPress%s or higher to be installed!', 'First string is the plugin name, followed by the required WordPress version and then the anchor tag for a link to the Update screen.', 'toggl-alert' ), '<strong>' . $this->plugin_data['Name'] . '</strong>', '4.4', '<a href="' . admin_url( 'update-core.php' ) . '"><strong>', '</strong></a>' );
				
				if ( ! has_action( 'admin_notices', array( $this, 'admin_errors' ) ) ) {
					add_action( 'admin_notices', array( $this, 'admin_errors' ) );
				}
				
				return false;
				
			}
			
			$this->require_necessities();
			
			// Register our CSS/JS for the whole plugin
			add_action( 'init', array( $this, 'register_scripts' ) );
			
		}

		/**
		 * Setup plugin constants
		 *
		 * @access	  private
		 * @since	  {{VERSION}}
		 * @return	  void
		 */
		private function setup_constants() {
			
			// WP Loads things so weird. I really want this function.
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . '/wp-admin/includes/plugin.php';
			}
			
			// Only call this once, accessible always
			$this->plugin_data = get_plugin_data( __FILE__ );

			if ( ! defined( 'Toggl_Alert_VER' ) ) {
				// Plugin version
				define( 'Toggl_Alert_VER', $this->plugin_data['Version'] );
			}

			if ( ! defined( 'Toggl_Alert_DIR' ) ) {
				// Plugin path
				define( 'Toggl_Alert_DIR', plugin_dir_path( __FILE__ ) );
			}

			if ( ! defined( 'Toggl_Alert_URL' ) ) {
				// Plugin URL
				define( 'Toggl_Alert_URL', plugin_dir_url( __FILE__ ) );
			}
			
			if ( ! defined( 'Toggl_Alert_FILE' ) ) {
				// Plugin File
				define( 'Toggl_Alert_FILE', __FILE__ );
			}

		}

		/**
		 * Internationalization
		 *
		 * @access	  private 
		 * @since	  {{VERSION}}
		 * @return	  void
		 */
		private function load_textdomain() {

			// Set filter for language directory
			$lang_dir = Toggl_Alert_DIR . '/languages/';
			$lang_dir = apply_filters( 'toggl_alert_languages_directory', $lang_dir );

			// Traditional WordPress plugin locale filter
			$locale = apply_filters( 'plugin_locale', get_locale(), 'toggl-alert' );
			$mofile = sprintf( '%1$s-%2$s.mo', 'toggl-alert', $locale );

			// Setup paths to current locale file
			$mofile_local   = $lang_dir . $mofile;
			$mofile_global  = WP_LANG_DIR . '/toggl-alert/' . $mofile;

			if ( file_exists( $mofile_global ) ) {
				// Look in global /wp-content/languages/toggl-alert/ folder
				// This way translations can be overridden via the Theme/Child Theme
				load_textdomain( 'toggl-alert', $mofile_global );
			}
			else if ( file_exists( $mofile_local ) ) {
				// Look in local /wp-content/plugins/toggl-alert/languages/ folder
				load_textdomain( 'toggl-alert', $mofile_local );
			}
			else {
				// Load the default language files
				load_plugin_textdomain( 'toggl-alert', false, $lang_dir );
			}

		}
		
		/**
		 * Include different aspects of the Plugin
		 * 
		 * @access	  private
		 * @since	  {{VERSION}}
		 * @return	  void
		 */
		private function require_necessities() {
			
			require_once __DIR__ . '/core/library/rbm-field-helpers/rbm-field-helpers.php';
		
			$this->field_helpers = new RBM_FieldHelpers( array(
				'ID'   => 'toggl_alert', // Your Theme/Plugin uses this to differentiate its instance of RBM FH from others when saving/grabbing data
				'l10n' => array(
					'field_table'    => array(
						'delete_row'    => __( 'Delete Row', 'toggl-alert' ),
						'delete_column' => __( 'Delete Column', 'toggl-alert' ),
					),
					'field_select'   => array(
						'no_options'       => __( 'No select options.', 'toggl-alert' ),
						'error_loading'    => __( 'The results could not be loaded', 'toggl-alert' ),
						/* translators: %d is number of characters over input limit */
						'input_too_long'   => __( 'Please delete %d character(s)', 'toggl-alert' ),
						/* translators: %d is number of characters under input limit */
						'input_too_short'  => __( 'Please enter %d or more characters', 'toggl-alert' ),
						'loading_more'     => __( 'Loading more results...', 'toggl-alert' ),
						/* translators: %d is maximum number items selectable */
						'maximum_selected' => __( 'You can only select %d item(s)', 'toggl-alert' ),
						'no_results'       => __( 'No results found', 'toggl-alert' ),
						'searching'        => __( 'Searching...', 'toggl-alert' ),
					),
					'field_repeater' => array(
						'collapsable_title' => __( 'New Row', 'toggl-alert' ),
						'confirm_delete'    => __( 'Are you sure you want to delete this element?', 'toggl-alert' ),
						'delete_item'       => __( 'Delete', 'toggl-alert' ),
						'add_item'          => __( 'Add', 'toggl-alert' ),
					),
					'field_media'    => array(
						'button_text'        => __( 'Upload / Choose Media', 'toggl-alert' ),
						'button_remove_text' => __( 'Remove Media', 'toggl-alert' ),
						'window_title'       => __( 'Choose Media', 'toggl-alert' ),
					),
					'field_checkbox' => array(
						'no_options_text' => __( 'No options available.', 'toggl-alert' ),
					),
				),
			) );
			
			require_once __DIR__ . '/core/library/composer/autoload.php';
			
			require_once __DIR__ . '/core/admin/class-toggl-alert-admin.php';
			$this->admin = new Toggl_Alert_Admin();
			
			require_once __DIR__ . '/core/emails/class-toggl-alert-api.php';
			$this->email_api = new Toggl_Alert_API();
			
			require_once __DIR__ . '/core/notifications/class-toggl-alert-notification-handler.php';
			$this->notification_handler = new Toggl_Alert_Notification_Handler();
			
			require_once __DIR__ . '/core/emails/class-toggl-alert-notification-integration.php';
			$this->integration = new Toggl_Alert_Notification_Integration();
			
			require_once __DIR__ . '/core/emails/class-toggl-alert-notification-triggers.php';
			$this->triggers = new Toggl_Alert_Notification_Triggers();
			
		}
		
		/**
		 * Show admin errors.
		 * 
		 * @access	  public
		 * @since	  {{VERSION}}
		 * @return	  HTML
		 */
		public function admin_errors() {
			?>
			<div class="error">
				<?php foreach ( $this->admin_errors as $notice ) : ?>
					<p>
						<?php echo $notice; ?>
					</p>
				<?php endforeach; ?>
			</div>
			<?php
		}
		
		/**
		 * Register our CSS/JS to use later
		 * 
		 * @access	  public
		 * @since	  {{VERSION}}
		 * @return	  void
		 */
		public function register_scripts() {
			
			wp_register_style(
				'toggl-alert',
				Toggl_Alert_URL . 'dist/assets/css/app.css',
				null,
				defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : Toggl_Alert_VER
			);
			
			wp_register_script(
				'toggl-alert',
				Toggl_Alert_URL . 'dist/assets/js/app.js',
				array( 'jquery' ),
				defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : Toggl_Alert_VER,
				true
			);
			
			wp_localize_script( 
				'toggl-alert',
				'togglAlert',
				apply_filters( 'toggl_alert_localize_script', array() )
			);
			
			wp_register_style(
				'toggl-alert-admin',
				Toggl_Alert_URL . 'dist/assets/css/admin.css',
				null,
				defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : Toggl_Alert_VER
			);
			
			wp_register_script(
				'toggl-alert-admin',
				Toggl_Alert_URL . 'dist/assets/js/admin.js',
				array( 'jquery' ),
				defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : Toggl_Alert_VER,
				true
			);
			
			wp_localize_script( 
				'toggl-alert-admin',
				'togglAlert',
				apply_filters( 'toggl_alert_localize_admin_script', array() )
			);
			
		}
		
		/**
		 * Grab LearnDash Slack Fields
		 * 
		 * $param		$api_token Whether to run all the Queries for Choices or not
		 * 
		 * @access		public
		 * @since		1.0.0
		 * @return		array  LearnDash_Slack Settings API Fields
		 */
		public function get_settings_fields( $api_token = false ) {
			
			$workspaces_array = array();
			$clients_array = array();
			$projects_array = array();

			// Only run through all these queries when we need them
			if ( $api_token ) {
				
				$toggl_client = \AJT\Toggl\TogglClient::factory( array(
					'api_key' => $api_token,
					'apiVersion' => 'v8',
					'debug' => false,
				) );
				
				$toggl_reports = \AJT\Toggl\ReportsClient::factory( array(
					'api_key' => $api_token,
					'apiVersion' => 'v2',
					'debug' => false,
				) );
				
				if ( ! $workspaces_array = get_transient( 'toggl_alert_workspaces' ) ) {
					
					$workspaces_response = $toggl_client->getWorkspaces( array() );
				
					foreach ( $workspaces_response as $workspace ) {

						$workspaces_array[ $workspace['id'] ] = $workspace['name'];

					}
					
					set_transient( 'toggl_alert_workspaces', $workspaces_array, DAY_IN_SECONDS );
					
				}
				
				if ( ! $clients_array = get_transient( 'toggl_alert_clients' ) ) {
					
					$clients_response = $toggl_client->getClients( array() );
				
					foreach ( $clients_response as $client ) {

						$clients_array[ $client['id'] ] = $client['name'];

					}
					
					set_transient( 'toggl_alert_clients', $clients_array, DAY_IN_SECONDS );
					
				}
				
				if ( ! $projects_array = get_transient( 'toggl_alert_projects' ) ) {
					
					foreach ( $workspaces_array as $workspace_id => $workspace_name ) {
						
						$projects_in_workspace = $toggl_client->getProjects( array(
							'id' => $workspace_id,
							'active' => 'true',
						) );
						
						foreach ( $projects_in_workspace as $project ) {
							
							$workspace_name = $workspaces_array[ $project['wid'] ];
							$client_name = $clients_array[ $project['cid'] ];
							
							if ( ! isset( $projects_array[ $workspace_name ] ) ) {
								$projects_array[ $workspace_name ] = array();
							}
							
							if ( empty( $client_name ) ) {
								$client_name = __( 'No Client', 'toggl-alert' );
							}
							
							$projects_array[ $workspace_name ][ $project['id'] ] = $client_name . ': ' . $project['name'];
							
						}
						
						foreach ( $projects_array as $workspace_name => $projects ) {
							
							sort( $projects_array[ $workspace_name ] );
							
						}
						
						set_transient( 'toggl_alert_projects', $projects_array, DAY_IN_SECONDS );
						
					}
					
				}

			}
			
			$triggers = array();

			$weekdays = Toggl_Alert::get_weekdays();
			
			foreach ( $weekdays as $index => $day ) {
				
				$key = date( 'l', strtotime( "Sunday +{$index} days" ) );
				
				$triggers[ 'every_' . strtolower( $key ) ] = sprintf( __( 'Every %s', 'toggl-alert' ), $day );
				
			}
			
			$fields = array( 
				'email_post_id' => array(
					'type'  => 'hook',
				),
				'admin_title' => array(
					'label' => _x( 'Identifier for this Notification', 'Admin Title Field Label', 'toggl-alert' ),
					'type'  => 'text',
					'input_class' => 'regular-text email-post-title',
					'input_atts' => array(
						'placeholder' => __( 'New Email Notification', 'toggl-alert' ),
					),
					'description'  => __( 'Helps distinguish Notifications from one another on the Settings Screen. If left blank, your Notification will be labeled &ldquo;New Email Notification&rdquo;.', 'toggl-alert' ),
				),
				'project' => array(
					'type' => 'select',
					'label' => __( 'Project', 'toggl-alert' ),
					'opt_groups' => true,
					'opt_group_selection_prefix' => false,
					'placeholder' => __( '-- Select Project --', 'toggl-alert' ),
					'input_class' => 'required toggl-alert-project select2',
					'options' => $projects_array,
					'default' => '',
				),
				'trigger' => array(
					'type' => 'select',
					'label' => __( 'Trigger', 'toggl-alert' ),
					'placeholder' => __( '-- Select Trigger --', 'toggl-alert' ),
					'input_class' => 'required toggl-alert-trigger select2',
					'options' => $triggers,
					'default' => '',
				),
				'hours' => array(
					'type' => 'number',
					'label' => __( 'Alert when the Project is under X Hours for the selected Time Period', 'toggl-alert' ),
					'input_class' => 'required toggl-alert-hours',
					'default' => '',
				),
				'subject' => array(
					'label' => __( 'Subject Line', 'toggl-alert' ),
					'type'  => 'text',
					'input_class' => 'regular-text email-subject required',
					'description'  => __( 'If not set, this will default to your &ldquo;Identifier for this Notification&rdquo; value.', 'toggl-alert' ),
				),
				'message' => array(
					'label' => __( 'Message', 'toggl-alert' ),
					'type'  => 'textarea',
					'input_class' => 'regular-text email-message required',
				),
				'to' => array(
					'label' => __( 'To: (Optional)', 'toggl-alert' ),
					'type'  => 'text',
					'input_class' => 'regular-text email-to',
					'input_atts' => array(
						'placeholder' => get_option( 'admin_email' ),
					),
					'description'  => sprintf( __( 'Separate multiple emails by commas. If not set, this will default to <code>%s</code>', 'toggl-alert' ), get_option( 'admin_email' ) ),
				),
				'cc' => array(
					'label' => __( 'CC: (Optional)', 'toggl-alert' ),
					'type'  => 'text',
					'input_class' => 'regular-text email-cc',
					'description'  => __( 'Separate multiple emails by commas.', 'toggl-alert' ),
				),
				'bcc' => array(
					'label' => __( 'BCC: (Optional)', 'toggl-alert' ),
					'type'  => 'text',
					'input_class' => 'regular-text email-bcc',
					'description'  => __( 'Separate multiple emails by commas.', 'toggl-alert' ),
				),
			);

			return apply_filters( 'toggl_alert_settings_fields', $fields );

		}
		
		/**
		 * Utility Function to insert one Array into another at a specified Index. Useful for the Notification Repeater Field's Filter
		 * @param		array   &$array       Array being modified. This passes by reference.
		 * @param		integer $index        Insertion Index. Even if it is an associative array, give a numeric index. Determine it by doing a foreach() until you hit your desired placement and then break out of the loop.
		 * @param		array   $insert_array Array being Inserted at the Index
		 *                                                           
		 * @access		public
		 * @since		1.2.0
		 * @return		void
		 */
		public function array_insert( &$array, $index, $insert_array ) { 
			
			// First half before the cut-off for the splice
			$first_array = array_splice( $array, 0, $index ); 
			
			// Merge this with the inserted array and the last half of the splice
			$array = array_merge( $first_array, $insert_array, $array );
			
		}
		
		/**
		 * Gets an array of Localized Weekdays
		 * 
		 * @access		public
		 * @since		{{VERSION}}
		 * @return		array Array of Localized Weekdays
		 */
		public static function get_weekdays() {
			
			global $wp_locale;

			$weekdays = array();

			foreach ( $wp_locale->weekday as $index => $weekday ) {
				$weekdays[ $index ] = $weekday;
			}
			
			return $weekdays;
			
		}
		
		/**
		 * Runs on Plugin Activation to set up a WP Cron
		 * 
		 * @access		public
		 * @since		1.0.0
		 * @return		void
		 */
		public static function activate() {
			
			$weekdays = Toggl_Alert::get_weekdays();
			
			foreach ( $weekdays as $index => $day ) {
				
				if ( ! wp_next_scheduled( 'toggl_alert_weekly_' . $index . '_cron' ) ) {
					
					// Ensure there are no issues with locale and convert 
					$day = date( 'D', strtotime( "Sunday +{$index} days" ) );
					
					// Calculate Timezone offset, which will be subtracted from our calculated Timestamp
					// This means UTC-5 will be added to the Timestamp
					// This is necessary because WP Events are fired based on the Timezone but Timestamps are not, so we have to counteract it
					$time = new \DateTime( 'now', new DateTimeZone( get_option( 'timezone_string', 'America/Detroit' ) ) );
					$timezone_offset = $time->format( 'Z' );
					
					wp_schedule_event( strtotime( 'next ' . $day ) - $timezone_offset, 'weekly', 'toggl_alert_weekly_' . $index . '_cron' );
					
				}
					
			}
			
		}
		
		/**
		 * Runs on Plugin Deactivation to remove the WP Cron from Activation
		 * 
		 * @access		public
		 * @since		1.0.0
		 * @return		void
		 */
		public static function deactivate() {
			
			foreach ( $weekdays as $index => $day ) {
			
				wp_clear_scheduled_hook( 'toggl_alert_weekly_' . $index . '_cron' );
				
			}
			
		}
		
	}
	
} // End Class Exists Check

/**
 * The main function responsible for returning the one true Toggl_Alert
 * instance to functions everywhere
 *
 * @since	  {{VERSION}}
 * @return	  \Toggl_Alert The one true Toggl_Alert
 */
add_action( 'plugins_loaded', 'toggl_alert_load' );
function toggl_alert_load() {

	require_once __DIR__ . '/core/toggl-alert-functions.php';
	TOGGLALERT();

}

register_activation_hook( __FILE__, array( 'Toggl_Alert', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Toggl_Alert', 'deactivate' ) );