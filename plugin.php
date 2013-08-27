<?php
/*
Plugin Name: Theme Customizer Demo
Plugin URI: http://vip.wordpress.com/2013/08/20/demo-wordpress-customizer/
Description: Example code to support the online demo.
Version: 1.0
Author: Taylor Buley
Author URI: http://www.parade.com/
License: MIT
*/

//https://github.com/WordPress/WordPress/blob/master/wp-includes/class-wp-customize-control.php
//https://github.com/WordPress/WordPress/blob/master/wp-includes/class-wp-customize-manager.php
//https://github.com/WordPress/WordPress/blob/master/wp-includes/class-wp-customize-section.php
//https://github.com/WordPress/WordPress/blob/master/wp-includes/class-wp-customize-setting.php

//https://github.com/WordPress/WordPress/blob/master/wp-admin/customize.php
//https://github.com/WordPress/WordPress/blob/master/wp-admin/css/customize-controls.css
//https://github.com/WordPress/WordPress/blob/master/wp-admin/js/customize-controls.js
//https://github.com/WordPress/WordPress/blob/master/wp-admin/js/customize-controls.min.js

/* 
	customize_register
	customize_preview_init //enqueue front-end
	customize_controls_enqueue_scripts
	customize_controls_init
	customize_controls_print_footer_scripts (_wp_footer_scripts)
	customize_controls_print_styles (print_admin_styles)
	customize_controls_print_scripts (print_head_scripts)	
*/

/* Control types:
 * - text
 * - checkbox
 * - radio
 * - image picker
 * - color picker
 * - custom!
 */

/* Process:
 * - Setup capabilities
 * - Register sections
 * - Register settings
 *   -> Trick: Determine customizer controls based on URL
 *   -> Key-data smuggling
 * - Create controls
 *   -> Field lockdown
 *   -> Pre-populate data
 *   -> Admin-size Scripts/CSS
 * - Handle live-changes
 *   -> Customizer-side (submit button)
 *   -> Viewport-side
 *      - Promises, etc.
 *      - Data bootstrapping
 * - Handle Submission
 *   -> Global hell, data prefixing
 *   -> Lists
 *   -> Sanitation
 *   -> Update lockdown
 */

include( dirname( __FILE__ ) . '/core.php' );
include( dirname( __FILE__ ) . '/example.php' );
