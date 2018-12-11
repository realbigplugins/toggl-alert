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

/**
 * Quick access to plugin field helpers.
 *
 * @since {{VERSION}}
 *
 * @return RBM_FieldHelpers
 */
function toggl_alert_fieldhelpers() {
	return TOGGLALERT()->field_helpers;
}

/**
 * Initializes a field group for automatic saving.
 *
 * @since {{VERSION}}
 *
 * @param $group
 */
function toggl_alert_init_field_group( $group ) {
	toggl_alert_fieldhelpers()->fields->save->initialize_fields( $group );
}

/**
 * Gets a meta field helpers field.
 *
 * @since {{VERSION}}
 *
 * @param string $name Field name.
 * @param string|int $post_ID Optional post ID.
 * @param mixed $default Default value if none is retrieved.
 * @param array $args
 *
 * @return mixed Field value
 */
function toggl_alert_get_field( $name, $post_ID = false, $default = '', $args = array() ) {
    $value = toggl_alert_fieldhelpers()->fields->get_meta_field( $name, $post_ID, $args );
    return $value !== false ? $value : $default;
}

/**
 * Gets a option field helpers field.
 *
 * @since {{VERSION}}
 *
 * @param string $name Field name.
 * @param mixed $default Default value if none is retrieved.
 * @param array $args
 *
 * @return mixed Field value
 */
function toggl_alert_get_option_field( $name, $default = '', $args = array() ) {
	$value = toggl_alert_fieldhelpers()->fields->get_option_field( $name, $args );
	return $value !== false ? $value : $default;
}

/**
 * Outputs a text field.
 *
 * @since {{VERSION}}
 *
 * @param array $args
 */
function toggl_alert_do_field_text( $args = array() ) {
	toggl_alert_fieldhelpers()->fields->do_field_text( $args['name'], $args );
}

/**
 * Outputs a password field.
 *
 * @since {{VERSION}}
 *
 * @param array $args
 */
function toggl_alert_do_field_password( $args = array() ) {
	toggl_alert_fieldhelpers()->fields->do_field_password( $args['name'], $args );
}

/**
 * Outputs a textarea field.
 *
 * @since {{VERSION}}
 *
 * @param array $args
 */
function toggl_alert_do_field_textarea( $args = array() ) {
	toggl_alert_fieldhelpers()->fields->do_field_textarea( $args['name'], $args );
}

/**
 * Outputs a checkbox field.
 *
 * @since {{VERSION}}
 *
 * @param array $args
 */
function toggl_alert_do_field_checkbox( $args = array() ) {
	toggl_alert_fieldhelpers()->fields->do_field_checkbox( $args['name'], $args );
}

/**
 * Outputs a toggle field.
 *
 * @since {{VERSION}}
 *
 * @param array $args
 */
function toggl_alert_do_field_toggle( $args = array() ) {
	toggl_alert_fieldhelpers()->fields->do_field_toggle( $args['name'], $args );
}

/**
 * Outputs a radio field.
 *
 * @since {{VERSION}}
 *
 * @param array $args
 */
function toggl_alert_do_field_radio( $args = array() ) {
	toggl_alert_fieldhelpers()->fields->do_field_radio( $args['name'], $args );
}

/**
 * Outputs a select field.
 *
 * @since {{VERSION}}
 *
 * @param array $args
 */
function toggl_alert_do_field_select( $args = array() ) {
	toggl_alert_fieldhelpers()->fields->do_field_select( $args['name'], $args );
}

/**
 * Outputs a number field.
 *
 * @since {{VERSION}}
 *
 * @param array $args
 */
function toggl_alert_do_field_number( $args = array() ) {
	toggl_alert_fieldhelpers()->fields->do_field_number( $args['name'], $args );
}

/**
 * Outputs an image field.
 *
 * @since {{VERSION}}
 *
 * @param array $args
 */
function toggl_alert_do_field_media( $args = array() ) {
	toggl_alert_fieldhelpers()->fields->do_field_media( $args['name'], $args );
}

/**
 * Outputs a datepicker field.
 *
 * @since {{VERSION}}
 *
 * @param array $args
 */
function toggl_alert_do_field_datepicker( $args = array() ) {
	toggl_alert_fieldhelpers()->fields->do_field_datepicker( $args['name'], $args );
}

/**
 * Outputs a timepicker field.
 *
 * @since {{VERSION}}
 *
 * @param array $args
 */
function toggl_alert_do_field_timepicker( $args = array() ) {
	toggl_alert_fieldhelpers()->fields->do_field_timepicker( $args['name'], $args );
}

/**
 * Outputs a datetimepicker field.
 *
 * @since {{VERSION}}
 *
 * @param array $args
 */
function toggl_alert_do_field_datetimepicker( $args = array() ) {
	toggl_alert_fieldhelpers()->fields->do_field_datetimepicker( $args['name'], $args );
}

/**
 * Outputs a colorpicker field.
 *
 * @since {{VERSION}}
 *
 * @param array $args
 */
function toggl_alert_do_field_colorpicker( $args = array() ) {
	toggl_alert_fieldhelpers()->fields->do_field_colorpicker( $args['name'], $args );
}

/**
 * Outputs a list field.
 *
 * @since {{VERSION}}
 *
 * @param array $args
 */
function toggl_alert_do_field_list( $args = array() ) {
	toggl_alert_fieldhelpers()->fields->do_field_list( $args['name'], $args );
}

/**
 * Outputs a hidden field.
 *
 * @since {{VERSION}}
 *
 * @param array $args
 */
function toggl_alert_do_field_hidden( $args = array() ) {
	toggl_alert_fieldhelpers()->fields->do_field_hidden( $args['name'], $args );
}

/**
 * Outputs a table field.
 *
 * @since {{VERSION}}
 *
 * @param array $args
 */
function toggl_alert_do_field_table( $args = array() ) {
	toggl_alert_fieldhelpers()->fields->do_field_table( $args['name'], $args );
}

/**
 * Outputs a HTML field.
 *
 * @since {{VERSION}}
 *
 * @param array $args
 */
function toggl_alert_do_field_html( $args = array() ) {
	toggl_alert_fieldhelpers()->fields->do_field_html( $args['name'], $args );
}

/**
 * Outputs a repeater field.
 *
 * @since {{VERSION}}
 *
 * @param mixed $values
 */
function toggl_alert_do_field_repeater( $args = array() ) {
	toggl_alert_fieldhelpers()->fields->do_field_repeater( $args['name'], $args );
}

/**
 * Outputs a hook
 *
 * @since {{VERSION}}
 *
 * @param mixed $values
 */
function toggl_alert_do_field_hook( $args = array() ) {
	do_action( 'toggl_alert_' . $args['id'], $args );
}

/**
 * Outputs a String if a Callback Function does not exist for an Options Page Field
 *
 * @since {{VERSION}}
 *
 * @param array $args
 */
function toggl_alert_missing_callback( $args ) {
	
	printf( 
		_x( 'A callback function called "toggl_alert_do_field_%s" does not exist.', '%s is the Field Type', 'toggl-alert' ),
		$args['type']
	);
		
}