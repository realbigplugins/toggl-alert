<?php
/**
 * The Admin Side Toggl Alert
 *
 * @since		1.0.0
 *
 * @package Toggl_Alert
 * @subpackage Toggl_Alert/core/admin
 */

defined( 'ABSPATH' ) || die();

final class Toggl_Alert_Admin {

	/**
	 * Toggl_Alert_Admin constructor.
	 * 
	 * @since		1.0.0
	 */
	function __construct() {
		
		// Creates a (temporary) Submenu Item for our Admin Page
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		
		// Register our Fields as WP Settings
		add_action( 'admin_init', array( $this, 'register_options' ) );
		
		// Creates the Repeater
		add_action( 'toggl_alert_notifications_field', array( $this, 'notification_repeater_field' ) );
		
		// Include Hidden Field for Post ID within the Repeater
		add_action( 'toggl_alert_email_post_id', array( $this, 'post_id_field' ) );
		
		// Include Replacement Hints
		add_action( 'toggl_alert_replacement_hints', array( $this, 'replacement_hints_field' ) );
		
		// Localize the admin.js
		add_filter( 'toggl_alert_localize_admin_script', array( $this, 'localize_script' ) );
		
		add_action( 'admin_enqueue_scripts', array( $this, 'enable_select2' ), 1 );
		
		// Enqueue our Styles/Scripts on our Settings Page
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		
		// Fix <title> tag for the Settings Page
		add_filter( 'admin_title', array( $this, 'admin_title' ), 10, 2 );
		
	}
	
	/**
	 * Creates a (temporary) Submenu Item for our Admin Page
	 * 
	 * @access		public
	 * @since		1.0.0
	 * @return		void
	 */
	public function admin_menu() {
		
		// We're hooking into options-general.php so that we have better access to WP's Settings API
		$page_hook = add_submenu_page(
			'options-general.php',
			_x( 'Toggl Alert Settings', 'Admin Page Title', 'toggl-alert' ),
			_x( 'Toggl Alerts', 'Admin Menu Item', 'toggl-alert' ),
			'manage_options',
			'toggl-alert',
			array( $this, 'admin_page' )
		);
		
	}
	
	/**
	 * Output our Admin Page (Finally!)
	 * 
	 * @access		public
	 * @since		1.0.0
	 * @return		HTML
	 */
	public function admin_page() {
			
		settings_errors(); ?>

		<div id="tab_container">

			<form method="post" action="options.php">
				
				<?php echo wp_nonce_field( 'toggl_alert_settings', 'toggl_alert_settings_nonce' ); ?>

				<?php settings_fields( 'toggl_alert' ); ?>

				<?php do_settings_sections( 'toggl-alert' ); ?>

				<?php submit_button(); ?>

			</form>

		</div>

		<?php
		
	}
	
	/**
	 * Regsiter Options for each Field
	 * 
	 * @access		public
	 * @since		1.0.0
	 * @return		void
	 */
	public function register_options() {
		
		if ( false === get_option( 'toggl_alert' ) ) {
			add_option( 'toggl_alert' );
		}
		
		add_settings_section(
			'toggl_alert',
			__( 'Toggl Alert Settings', 'toggl-alert' ),
			'__return_false',
			'toggl-alert'
		);
		
		// Holds all non-repeater values
		$global_values = get_option( 'toggl_alert' );
		
		// Initialize repeater
		$repeater_values = array();
		$fields = TOGGLALERT()->get_settings_fields( get_option( 'toggl_api_token' ) );
		$notification_id = apply_filters( 'toggl_alert_notification_id', 'rbm' );
		
		$feeds = get_posts( array(
			'post_type'   => "toggl-alert-{$notification_id}-feed",
			'numberposts' => -1,
			'order'	   => 'ASC',
		) );

		if ( ! empty( $feeds ) && ! is_wp_error( $feeds ) ) {

			foreach ( $feeds as $feed ) {

				$value = array(
					'admin_title' => get_the_title( $feed->ID ), // The first element in this Array is used for the Collapsable Title
					'email_post_id' => $feed->ID,
				);
				
				// Conditionally Hide certain fields
				$trigger = get_post_meta( $feed->ID, 'toggl_alert_rbm_feed_trigger', true );
				$trigger = ( $trigger ) ? $trigger : 0;

				foreach ( $fields as $field_id => $field ) {
					
					if ( $field_id == 'email_post_id' || $field_id == 'admin_title' ) continue; // We don't need to do anything special with these
					
					$value[ $field_id ] = get_post_meta( $feed->ID, "toggl_alert_rbm_feed_$field_id", true );
					
					if ( $field_id == 'replacement_hints' ) {
						
						$value[ $field_id ] = $trigger;
						
					}
					
					if ( $field['type'] == 'select' &&
						isset( $field['multiple'] ) && 
						$field['multiple'] === true ) {
						
						// Support for LD Slack v1.1.X
						$value[ $field_id ] = ( ! is_array( $value[ $field_id ] ) ) ? array( $value[ $field_id ] ) : $value[ $field_id ];
						
					}
					
				}

				$repeater_values[] = $value;

			}

		}
		
		$fields = array(
			array(
				'type' => 'text',
				'name' => 'toggl_api_token',
				'id' => 'toggl_api_token',
				'no_init' => true,
				'options_field' => true,
				'settings_label' => __( 'API Token', 'toggl-alert' ),
				'default' => get_option( 'toggl_api_token' ),
				'input_class' => 'regular-text toggl-alert-api-token',
			),
			array(
				'type' => 'hook',
				'id' => 'notifications_field',
				'input_name' => 'toggl_alert_rbm_feeds',
				'default' => $repeater_values,
				'settings_label' => __( 'Email Notifications', 'toggl-alert' ),
				'sortable' => false,
				'collapsable' => true,
				'layout' => 'row',
				'add_item_text' => __( 'Add Email Notification', 'toggl-alert' ),
				'edit_item_text' => __( 'Edit Email Notification', 'toggl-alert' ),
				'save_item_text' => __( 'Save Email Notification', 'toggl-alert' ),
				'saving_item_text' => __( 'Saving...', 'toggl-alert' ),
				'delete_item_text' => __( 'Delete Email Notification', 'toggl-alert' ),
				'default_title' => __( 'New Email Notification', 'toggl-alert' ),
				'fields' => $fields,
			),
		);
		
		foreach ( $fields as $field ) {
			
			$field = wp_parse_args( $field, array(
				'settings_label' => '',
			) );
			
			$callback = 'toggl_alert_do_field_' . $field['type'];
			
			add_settings_field(
				$field['id'],
				$field['settings_label'],
				( is_callable( $callback ) ) ? 'toggl_alert_do_field_' . $field['type'] : 'toggl_alert_missing_callback',
				'toggl-alert',
				'toggl_alert',
				$field
			);
			
			register_setting( 'toggl_alert', $field['id'] );
			
		}
		
	}
	
	/**
	 * Creates the Notification Repeater
	 * 
	 * @param		array $args Field Args
	 *                           
	 * @access		public
	 * @since		1.1.0
	 * @return		string HTML
	 */
	public function notification_repeater_field( $args ) {
		
		$args = wp_parse_args( $args, array(
			'id' => '',
			'default' => '',
			'classes' => array(),
			'fields' => array(),
			'add_item_text' => __( 'Add Row', 'toggl-alert' ),
			'edit_item_text' => __( 'Edit Row', 'toggl-alert' ),
			'save_item_text' => __( 'Save Row', 'toggl-alert' ),
			'saving_item_text' => __( 'Saving...', 'toggl-alert' ),
			'delete_item_text' => __( 'Delete Row', 'toggl-alert' ),
			'default_title' => __( 'New Row', 'toggl-alert' ),
			'input_name' => false,
		) );
		
		// Ensure Dummy Field is created
		$field_count = ( count( $args['default'] ) >= 1 ) ? count( $args['default'] ) : 1;
		
		$name = $args['input_name'] !== false ? $args['input_name'] : esc_attr( $args['name_attr_wrap'] ) . '[' . esc_attr( $args['id'] ) . ']';
		
		do_action( 'toggl_alert_before_repeater' );
		
		?>

		<div data-toggl-alert-rbm-repeater class="toggl-alert-rbm-repeater <?php echo ( isset( $args['classes'] ) ) ? ' ' . implode( ' ', $args['classes'] ) : ''; ?>">
			
			<div data-repeater-list="<?php echo $name; ?>" class="toggl-alert-rbm-repeater-list">

					<?php for ( $index = 0; $index < $field_count; $index++ ) : $value = ( isset( $args['default'][$index] ) ) ? $args['default'][$index] : array(); ?>
				
						<div data-repeater-item<?php echo ( ! isset( $args['default'][$index] ) ) ? ' data-repeater-dummy style="display: none;"' : ''; ?> class="toggl-alert-rbm-repeater-item">
							
							<table class="repeater-header wp-list-table widefat fixed posts">

								<thead>

									<tr>
										<th scope="col">
											<div class="title" data-repeater-default-title="<?php echo $args['default_title']; ?>">

												<?php if ( isset( $args['default'][$index] ) && reset( $args['default'][$index] ) !== '' ) : 

													// Surprisingly, this is the most efficient way to do this. http://stackoverflow.com/a/21219594
													foreach ( $value as $key => $setting ) : ?>
														<?php echo $setting; ?>
													<?php 
														break;
													endforeach; 

												else: ?>

													<?php echo $args['default_title']; ?>

												<?php endif; ?>

											</div>
											
											<div class="toggl-alert-rbm-repeater-controls">
											
												<input data-repeater-edit type="button" class="button" value="<?php echo $args['edit_item_text']; ?>" />
												<input data-repeater-delete type="button" class="button button-danger" value="<?php echo $args['delete_item_text']; ?>" />
												
											</div>

										</th>

									</tr>

								</thead>

							</table>
							
							<div class="toggl-alert-rbm-repeater-content reveal" data-reveal data-v-offset="64">
								
								<div class="toggl-alert-rbm-repeater-form">

									<table class="widefat" width="100%" cellpadding="0" cellspacing="0">

										<tbody>

											<?php foreach ( $args['fields'] as $field_id => $field ) : ?>

												<tr>

													<?php if ( is_callable( "toggl_alert_do_field_{$field['type']}" ) ) : 

														$field['name'] = $field_id;
														$field['id'] = $field_id;
														$field['no_init'] = true;
														$field['default'] = ( isset( $value[ $field_id ] ) ) ? $value[ $field_id ] : $field['default'];

														if ( $field['type'] == 'checkbox' ) : 

															if ( isset( $field['default'] ) && (int) $field['default'] !== 0 ) {
																$field['input_class'] .= ' default-checked';
															}

														endif;

														if ( $field['type'] !== 'hook' ) : ?>

															<td>

																<?php call_user_func( "toggl_alert_do_field_{$field['type']}", $field ); ?>

															</td>

														<?php else : 

															// Don't wrap calls for a Hook
															call_user_func( "toggl_alert_do_field_{$field['type']}", $field );

														endif;
		
													else :
		
														call_user_func( 'toggl_alert_missing_callback', $field );

													endif; ?>

												</tr>

											<?php endforeach; ?>

										</tbody>

									</table>
									
									<input type="submit" class="button button-primary alignright" value="<?php echo $args['save_item_text']; ?>" data-saving_text="<?php echo $args['saving_item_text']; ?>" />
								  
								</div>
								
								<a class="close-button" data-close aria-label="<?php echo __( 'Close Notification Editor', 'toggl-alert' ); ?>">
									<span aria-hidden="true">&times;</span>
								</a>
								
							</div>
							
						</div>

					<?php endfor; ?>	  

			</div>
			
			<input data-repeater-create type="button" class="button" style="margin-top: 6px;" value="<?php echo $args['add_item_text']; ?>" />

		</div>
		
		<?php
		
		do_action( 'toggl_alert_after_repeater' );
		
	}
	
	/**
	 * Creating a Hidden Field for a Post ID works out more simply using a Hook. 
	 * 
	 * @param		array  Field Args
	 * 
	 * @access		public
	 * @since		1.0.0
	 * @return		void
	 */
	public function post_id_field( $args ) {
		
		// Post ID of 0 on wp_insert_post() auto-generates an available Post ID
		if ( empty( $args['default'] ) ) $args['default'] = 0;
		?>

		<input type="hidden" class="toggl-alert-field toggl-alert-post-id" name="<?php echo $args['id']; ?>" value="<?php echo (string) $args['default']; ?>" />

	<?php
	}
	
	/**
	 * Create the Replacements Field based on the returned Array
	 * 
	 * @param		array  $args Field Args
	 *						  
	 * @access		public
	 * @since		1.0.0
	 * @return		void
	 */
	public function replacement_hints_field( $args ) {
		
		$args = wp_parse_args( $args, array(
			'default' => false,
		) );
		
		$hints = $this->get_replacement_hints();
		$selected = $args['default'];
		
		foreach ( $hints as $class => $hint ) : ?>

			<td class="toggl-alert-replacement-instruction <?php echo $class; ?><?php echo ( $class !== $selected ) ? ' hidden' : ''; ?>">
				<div class="header-text">
					<?php echo _x( 'Here are the available text replacements to use in the Message Pre-Text, Message Title, and Message Fields for the Slack Trigger selected:', 'Text Replacements Label', 'toggl-alert' ); ?>

				</div>

				<?php foreach ( $hint as $replacement => $label ) : ?>

					<div class="replacement-wrapper">
						<span class="replacement"><?php echo $replacement; ?></span> : <span class="label"><?php echo $label; ?></span>
					</div>

				<?php endforeach; ?>

				<?php if ( $class == 'complete_course' ) : ?>

					<div class="additional-help-text <?php echo $class; ?>">
						<?php printf( _x( '<em>Cumulative</em> is the average for all the %s in the %s.', 'toggl-alert' ), LearnDash_Custom_Label::get_label( 'quizzes' ), LearnDash_Custom_Label::get_label( 'course' ) ); ?>
						<br />
						<?php printf( _x( '<em>Aggregate</em> is the sum for all the %s in the %s.', 'toggl-alert' ), LearnDash_Custom_Label::get_label( 'quizzes' ), LearnDash_Custom_Label::get_label( 'course' ) ); ?>
					</div>

				<?php endif; ?>
			</td>

		<?php endforeach;
		
	}
	
	/**
	 * Filterable Array holding all the Text Replacement Hints for all the Triggers
	 * 
	 * @access		private
	 * @since		1.0.0
	 * @return		array   Array of Text Replacement Hints for each Trigger
	 */
	private function get_replacement_hints() {

		/**
		* Add extra Replacement Hints directly to the User Group
		*
		* @since 1.0.0
		*/
		$user_hints = apply_filters( 'toggl_alert_do_fielduser_replacement_hints', array(
			'%username%' => _x( 'Display the User\'s username.', '%username% Hint Text', 'toggl-alert' ),
			'%email%' => _x( 'Display the User\'s email.', '%email% Hint Text', 'toggl-alert' ),
			'%name%' => _x( 'Display the User\'s display name.', '%name% Hint Text', 'toggl-alert' ),
		) );
		
		$course_basic_hints = array(
			'%course_name%' => sprintf( _x( 'Display the %s name.', '%course_name% Hint Text', 'toggl-alert' ), LearnDash_Custom_Label::get_label( 'course' ) ),
			'%course_link%' => sprintf( _x( 'Show a link to the %s.', '%course_link% Hint Text', 'toggl-alert' ), LearnDash_Custom_Label::get_label( 'course' ) ),
		);
		
		$course_point_hints = array();
		
		// If we're on LD >= 2.4, Course Points exist
		if ( version_compare( LEARNDASH_VERSION, '2.4' ) >= 0 ) {
			
			$course_point_hints['%student_course_points%'] = sprintf( _x( 'The total %s Points the Student has.', '%student_course_points% Hint Text', 'toggl-alert' ), LearnDash_Custom_Label::get_label( 'course' ) );
			$course_point_hints['%access_course_points%'] = sprintf( _x( 'The number of %s Points required to access the %s.', '%access_course_points% Hint Text', 'toggl-alert' ), LearnDash_Custom_Label::get_label( 'course' ), LearnDash_Custom_Label::get_label( 'course' ) );
			
		}
		
		/**
		* Keeps Course Point stuff out of the Basic Hints so that useless Replacement Hints don't end up in Lessons/Topics/etc.
		* 
		* @since 1.2.4
		*/
		$course_point_hints = apply_filters( 'toggl_alert_do_fieldcourse_point_replacement_hints', $course_point_hints );
		
		/**
		* Add extra Replacement Hints directly to the Basic Course Hint Group
		* 
		* @since 1.0.0
		*/
		$course_basic_hints = apply_filters( 'toggl_alert_do_fieldcourse_basic_replacement_hints', $course_basic_hints );

		$course_advanced_hints = array(
			'%timestamp%' => sprintf( _x( 'Display the time when %s was completed.', '%timestamp% Hint Text for Courses', 'toggl-alert' ), LearnDash_Custom_Label::get_label( 'course' ) ),
			'%cumulative_correct%' => sprintf( _x( 'Display the average points scored across all %s in the %s.', '%cumulative_correct% Hint Text', 'toggl-alert' ), LearnDash_Custom_Label::get_label( 'quizzes' ), LearnDash_Custom_Label::get_label( 'course' ) ),
			'%cumulative_incorrect%' => sprintf( _x( 'Display the average points missed across all %s in the %s.', '%cumulative_incorrect% Hint Text', 'toggl-alert' ), LearnDash_Custom_Label::get_label( 'quizzes' ), LearnDash_Custom_Label::get_label( 'course' ) ),
			'%cumulative_percentage%' => sprintf( _x( 'Display the average percentage of correct answers across all %s in the %s.', '%cumulative_percentage% Hint Text', 'toggl-alert' ), LearnDash_Custom_Label::get_label( 'quizzes' ), LearnDash_Custom_Label::get_label( 'course' ) ),
			'%cumulative_timespent%' => sprintf( _x( 'Display the average time spent across all %s in the %s.', '%cumulative_timespent% Hint Text', 'toggl-alert' ), LearnDash_Custom_Label::get_label( 'quizzes' ), LearnDash_Custom_Label::get_label( 'course' ) ),
			'%cumulative_total%' => sprintf( _x( 'Display the average of the total number of questions in the %s', '%cumulative_total% Hint Text', 'toggl-alert' ), LearnDash_Custom_Label::get_label( 'course' ) ),
			'%aggregate_correct%' => sprintf( _x( 'Display the sum of the points scored across all %s in the %s.', '%aggregate_correct% Hint Text', 'toggl-alert' ), LearnDash_Custom_Label::get_label( 'quizzes' ), LearnDash_Custom_Label::get_label( 'course' ) ),
			'%aggregate_incorrect%' => sprintf( _x( 'Display the sum of the points missed across all %s in the %s.', '%aggregate_incorrect% Hint Text', 'toggl-alert' ), LearnDash_Custom_Label::get_label( 'quizzes' ), LearnDash_Custom_Label::get_label( 'course' ) ),
			'%aggregate_percentage%' => sprintf( _x( 'Display the sum of the percentage of correct answers across all %s in the %s.', '%aggregate_percentage% Hint Text', 'toggl-alert' ), LearnDash_Custom_Label::get_label( 'quizzes' ), LearnDash_Custom_Label::get_label( 'course' ) ),
			'%aggregate_timespent%' => sprintf( _x( 'Display the sum of the time spent across all %s in the %s.', '%aggregate_timespent% Hint Text', 'toggl-alert' ), LearnDash_Custom_Label::get_label( 'quizzes' ), LearnDash_Custom_Label::get_label( 'course' ) ),
			'%aggregate_total%' => sprintf( _x( 'Display the sum of the total number of questions on the %s', '%aggregate_total% Hint Text', 'toggl-alert' ), LearnDash_Custom_Label::get_label( 'course' ) ),
		);
		
		// If we're on LD >= 2.4, Course Points exist
		if ( version_compare( LEARNDASH_VERSION, '2.4' ) >= 0 ) {
			
			$course_advanced_hints = array( '%awarded_course_points%' => sprintf( _x( 'The %s Points awarded upon completion.', '%awarded_course_points% Hint Text', 'toggl-alert' ), LearnDash_Custom_Label::get_label( 'course' ) ) ) + $course_advanced_hints;
			
		}
		
		/**
		* Add extra Replacement Hints directly to the Advanced Course Hint Group
		* 
		* @since 1.0.0
		*/
		$course_advanced_hints = apply_filters( 'toggl_alert_do_fieldcourse_advanced_replacement_hints', $course_advanced_hints );

		/**
		* Add extra Replacement Hints directly to the Lesson Hint Group
		* 
		* @since 1.0.0
		*/
		$lesson_hints = apply_filters( 'toggl_alert_do_fieldlesson_replacement_hints', array(
			'%lesson_name%' => sprintf( _x( 'Display the %s name.', '%lesson_name% Hint Text', 'toggl-alert' ), LearnDash_Custom_Label::get_label( 'lesson' ) ),
			'%lesson_link%' => sprintf( _x( 'Show a link to the %s.', '%lesson_link% Hint Text', 'toggl-alert' ), LearnDash_Custom_Label::get_label( 'lesson' ) ),
		) );
		
		/**
		* Add extra Replacement Hints directly to the Advanced Lesson Hint Group
		* 
		* @since 1.2.4
		*/
		$lesson_advanced_hints = apply_filters( 'toggl_alert_do_fieldlesson_advanced_replacement_hints', array(
			'%timestamp%' => sprintf( _x( 'Display the time when the %s was completed.', '%timestamp% Hint Text for Lessons', 'toggl-alert' ), LearnDash_Custom_Label::get_label( 'lesson' ) ),
		) );

		/**
		* Add extra Replacement Hints directly to the Topic Hint Group
		* 
		* @since 1.0.0
		*/
		$topic_hints = apply_filters( 'toggl_alert_do_fieldtopic_replacement_hints', array(
			'%topic_name%' => sprintf( _x( 'Display the %s name.', '%topic_name% Hint Text', 'toggl-alert' ), LearnDash_Custom_Label::get_label( 'topic' ) ),
			'%topic_link%' => sprintf( _x( 'Show a link to the %s.', '%topic_link% Hint Text', 'toggl-alert' ), LearnDash_Custom_Label::get_label( 'topic' ) ),
			'%timestamp%' => sprintf( _x( 'Display the time when the %s was completed.', '%timestamp% Hint Text for Topics', 'toggl-alert' ), LearnDash_Custom_Label::get_label( 'topic' ) ),
		) );
	  
		/**
		* Add extra Replacement Hints directly to the Quiz Hint Group
		* 
		* @since 1.0.0
		*/
		$quiz_hints = apply_filters( 'toggl_alert_do_fieldquiz_replacement_hints', array(
			'%quiz_name%' => sprintf( _x( 'Display the %s name.', '%quiz_name% Hint Text', 'toggl-alert' ), LearnDash_Custom_Label::get_label( 'quiz' ) ),
			'%quiz_link%' => sprintf( _x( 'Show a link to the %s.', '%quiz_link% Hint Text', 'toggl-alert' ), LearnDash_Custom_Label::get_label( 'quiz' ) ),
			'%correct%' => sprintf( _x( 'Display the number of correct answers given on the %s', '%correct% Hint Text', 'toggl-alert' ), LearnDash_Custom_Label::get_label( 'quiz' ) ),
			'%incorrect%' => sprintf( _x( 'Display the number of incorrect answers given on the %s', '%incorrect% Hint Text', 'toggl-alert' ), LearnDash_Custom_Label::get_label( 'quiz' ) ),
			'%total%' => sprintf( _x( 'Display the total number of questions on the %s', '%total% Hint Text for Quizzes', 'toggl-alert' ), LearnDash_Custom_Label::get_label( 'quiz' ) ),
			'%percentage%' => sprintf( _x( 'Display the percentage of correct answers on the %s.', '%percentage% Hint Text', 'toggl-alert' ), LearnDash_Custom_Label::get_label( 'quiz' ) ),
			'%timestamp%' => sprintf( _x( 'Display the time when the %s was completed.', '%timestamp% Hint Text for Quizzes', 'toggl-alert' ), LearnDash_Custom_Label::get_label( 'quiz' ) ),
			'%time_spent%' => sprintf( _x( 'Display the how long it took to complete the %s.', '%time_spent% Hint Text', 'toggl-alert' ), LearnDash_Custom_Label::get_label( 'quiz' ) ),
			'%questions_pending_grading%' => sprintf( _x( 'Shows a list of links to Questions pending manual grading for the %s submission.', '%questions_pending_grading% Hint Text', 'toggl-alert' ), LearnDash_Custom_Label::get_label( 'quiz' ) ),
		) );
		
		/**
		* Add extra Replacement Hints directly to the Essay Hint Group
		* 
		* @since 1.0.0
		*/
		$essay_hints = apply_filters( 'toggl_alert_do_fieldessay_replacement_hints', array(
			'%points_earned%' => _x( 'Display the total points earned.', '%points_earned% Hint Text', 'toggl-alert' ),
			'%points_possible%' => _x( 'Display the total points possible.', '%points_possible% Hint Text', 'toggl-alert' ),
		) );

		/**
		 * Add extra Replacement Hints directly to the Upload Assignment Hint Group
		 * 
		 * @since 1.2.0
		 */
		$upload_assignment_hints = apply_filters( 'toggl_alert_do_fieldupload_assignment_replacement_hints', array(
			'%assignment_name%' => _x( 'Display the Assignment name.', '%assignment_name% Hint Text', 'toggl-alert' ),
			'%file_name%' => _x( 'Display the Assignment file name.', '%file_name% Hint Text', 'toggl-alert' ),
			'%file_link%' => _x( 'Display the Assignment file link.', '%file_link% Hint Text', 'toggl-alert' ),
			'%lesson_type%' => sprintf( _x( 'Display the %s type.', '%lesson_type% Hint Text', 'toggl-alert' ), LearnDash_Custom_Label::get_label( 'lesson' ) ),
			'%course_name%' => sprintf( _x( 'Display the %s name.', '%course_name% Hint Text', 'toggl-alert' ), LearnDash_Custom_Label::get_label( 'course' ) ),
			'%course_link%' => sprintf( _x( 'Show a link to the %s.', '%course_link% Hint Text', 'toggl-alert' ), LearnDash_Custom_Label::get_label( 'course' ) ),
			'%points_possible%' => _x( 'Display the total points possible.', '%points_possible% Hint Text', 'toggl-alert' ),
		) );
		
		/**
		 * Add extra Replacement Hints directly to the Approve Assignment Hint Group
		 * 
		 * @since 1.2.0
		 */
		$approve_assignment_hints = apply_filters( 'toggl_alert_do_fieldapprove_assignment_replacement_hints', $upload_assignment_hints + array(
			'%points_earned%' => _x( 'Display the total points earned.', '%points_earned% Hint Text', 'toggl-alert' ),
		) );
		
		$x_days_hints = apply_filters( 'toggl_alert_do_fieldx_days_replacement_hints', array(
			'%x_days%' => _x( 'The number of days before/after the Event.', '%x_days% Hint Text', 'toggl-alert' ),
		) );

		$replacement_hints = array(
			'enroll_course' => array_merge( $user_hints, $course_basic_hints, $course_point_hints ),
			'complete_course' => array_merge( $user_hints, $course_basic_hints, $course_point_hints, $course_advanced_hints ),
			'complete_lesson' => array_merge( $user_hints, $course_basic_hints, $lesson_hints, $lesson_advanced_hints ),
			'lesson_available' => array_merge( $user_hints, $course_basic_hints, $lesson_hints ),
			'complete_topic' => array_merge( $user_hints, $course_basic_hints, $lesson_hints, $topic_hints ),
			'pass_quiz' => array_merge( $user_hints, $quiz_hints, $course_basic_hints ),
			'fail_quiz' => array_merge( $user_hints, $quiz_hints, $course_basic_hints ),
			'complete_quiz' => array_merge( $user_hints, $quiz_hints, $course_basic_hints ),
			'upload_assignment' => array_merge( $user_hints, $upload_assignment_hints, $lesson_hints ),
			'approve_assignment' => array_merge( $user_hints, $approve_assignment_hints, $lesson_hints ),
			'not_logged_in' => array_merge( $user_hints, $x_days_hints ),
			'course_expires' => array_merge( $course_basic_hints, $course_point_hints, $x_days_hints ),
			'course_enrollment' => array_merge( $user_hints, $course_basic_hints, $course_point_hints ),
			'essay_graded' => array_merge( $user_hints, $course_basic_hints, $lesson_hints, $essay_hints ),
		);
		
		/**
		* Allows additional Triggers to be added to the Replacement Hints
		*
		* @since 1.0.0
		*/
		return apply_filters( 'toggl_alert_do_fieldtext_replacement_hints', $replacement_hints, $user_hints, $course_basic_hints );
		
	}
	
	/**
	 * Localize the Admin.js with some values from PHP-land
	 * 
	 * @param	  array $localization Array holding all our Localizations
	 *														
	 * @access	  public
	 * @since	  1.0.0
	 * @return	  array Modified Array
	 */
	public function localize_script( $localization ) {
		
		$localization['i18n'] = array(
			'activeText' => __( 'Active Notification', 'toggl-alert' ),
			'inactiveText' => __( 'Inactive Notification', 'toggl-alert' ),
			'confirmNotificationDeletion' => __( 'Are you sure you want to delete this Email Notification?', 'toggl-alert' ),
			'validationError' => _x( 'This field is required', 'Required Field not filled out (Ancient/Bad Browsers Only)', 'toggl-alert' ),
			'onbeforeunload' => __( 'Any unsaved changes will be lost. Are you sure you want to leave this page?', 'toggl-alert' ),
		);
		
		$localization['ajax'] = admin_url( 'admin-ajax.php' );
		
		return $localization;
		
	}
	
	public function enable_select2() {
		
		global $current_screen;

		if ( $current_screen->base == 'settings_page_toggl-alert' ) {
			add_filter( 'rbm_fieldhelpers_load_select2', '__return_true' );
		}
		
	}
	
	/**
	 * Enqueue our CSS/JS on our Settings Page
	 * 
	 * @access		public
	 * @since		1.0.0
	 * @return		void
	 */
	public function admin_enqueue_scripts() {
		
		global $current_screen;

		if ( $current_screen->base == 'settings_page_toggl-alert' ) {

			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_style( 'toggl-alert-admin' );
			
			// Dependencies
			wp_enqueue_script( 'jquery-effects-core' );
			wp_enqueue_script( 'jquery-effects-highlight' );
			wp_enqueue_script( 'jquery-ui-sortable' );
			
			wp_enqueue_script( 'toggl-alert-admin' );
			
		}
		
	}
	
	/**
	 * Fix the Admin Title since our pages "don't exist"
	 * 
	 * @param		string $admin_title The page title, with extra context added
	 * @param		string $title       The original page title
	 *                                               
	 * @access		public
	 * @since		1.2.0
	 * @return		string Admin Title
	 */
	public function admin_title( $admin_title, $title ) {
		
		global $current_screen;
		
		if ( $current_screen->base == 'settings_page_toggl_alert' ) {
			return __( 'Toggl Alert Settings', 'toggl-alert' ) . $admin_title;
		}
		
		return $admin_title;
		
	}

}