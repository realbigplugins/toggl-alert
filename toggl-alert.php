<?php
/**
 * Plugin Name: Toggl Alert
 * Plugin URI: https://github.com/realbigplugins/toggle-alert
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