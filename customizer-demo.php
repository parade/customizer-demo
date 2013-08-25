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

//https://github.com/WordPress/WordPress/blob/master/wp-admin/css/customize-controls.css
//https://github.com/WordPress/WordPress/blob/master/wp-admin/js/customize-controls.js
//https://github.com/WordPress/WordPress/blob/master/wp-admin/js/customize-controls.min.js

/* Example types:
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

/* Setup Capabilities */

//You need to be able to "edit_theme_options" to see customizer
//tools. This is granted to admins by default, so we'll have to 
//extend this ability to enable to let folks see the tools.
//But we don't want to grant this carte blanche, so we only enable
//when viewing a page that can be customized (and not, for example,
//while on the theme options page on the backend)

//give a couple of ways to override this, via local-config.php defines
//or via filter
function customizer_demo_setup_capabilities() {
	if ( false === defined( 'CUSTOMIZER_DEMO_ADMINISTRATOR_CAN_SEE' ) ) {
		define( 'CUSTOMIZER_DEMO_ADMINISTRATOR_CAN_SEE', false );
	}
	if ( false === defined( 'CUSTOMIZER_DEMO_EDITOR_CAN_SEE' ) ) {
		define( 'CUSTOMIZER_DEMO_EDITOR_CAN_SEE', true );
	}
	if ( false === defined( 'CUSTOMIZER_DEMO_CONTRIB_CAN_SEE' ) ) {
		define( 'CUSTOMIZER_DEMO_CONTRIBUTOR_CAN_SEE', true );
	}
	if ( false === defined( 'CUSTOMIZER_DEMO_AUTHOR_CAN_SEE' ) ) {
		define( 'CUSTOMIZER_DEMO_AUTHOR_CAN_SEE', true );
	}
	if ( false === defined( 'CUSTOMIZER_DEMO_SUBSCRIBER_CAN_SEE' ) ) {
		define( 'CUSTOMIZER_DEMO_SUBSCRIBER_CAN_SEE', false );
	}
	global $wp_roles;
	$caps = apply_filters( 'customizer_wrapper_roles', array_keys( $wp_roles->get_names() ) );
	foreach ( $caps as $cap ) {
		$const = defined( 'CUSTOMIZER_DEMO_' . strtoupper( $cap ) . "_CAN_SEE" ) ? constant( 'CUSTOMIZER_DEMO_' . strtoupper( $cap ) . "_CAN_SEE" ) : false;
		if ( true === apply_filters( "customizer_wrapper_{$cap}_can_see", $const ) ) {
			$wp_roles->add_cap( $cap, 'edit_theme_options' );
		}
	}

}
add_filter( 'init', 'customizer_demo_setup_capabilities', 20, 0 );

//determine previewed page type via ?url= parameter
//filters:
//* customizer_demo_preview_url
//* customizer_demo_preview_type
function customizer_demo_preview_type( $previewed_url = null ) {
	//make this usable on front-end templating and backend-admin
	//by also handling the easy cases: regular templating
	if ( true === is_single() ) {
		return array( 'id' => get_query_var( 'p' ), 'type' => 'single' );
	} else if ( true === is_home() ) {
		return array( 'id' => null, 'type' => 'home' );
	} else if ( true === is_category() ) {
		return array( 'id' => get_query_var( 'cat' ), 'type' => 'category' );
	} else if ( true === is_author() ) {
		return array( 'id' => get_query_var( 'author' ), 'type' => 'author' );
	}
	if ( null === $previewed_url ) {
		//the harder stuff: ?url= parameter
		//first step, figure out the url
		$previewed_url = $_GET[ 'url' ];
		if ( empty( $previewed_url ) ) {
			$full = $_SERVER[ 'HTTP_REFERER' ];
			$parsed = parse_url( $full );
			$query = $parsed[ 'query' ];
			$args = array();
			parse_str( $query, $args );
			$previewed_url = $args[ 'url' ];
		}
	}
	$previewed_url = apply_filters( 'customizer_demo_preview_url', $previewed_url );
	if ( empty( $previewed_url ) ) {
		return;
	}
	//next step: figure out what kind of page the
	//$previewed_url represents
	$object_id = null;
	$post_id = null;
	//check if it's a post 
	$post_id = url_to_postid( $previewed_url );
	if ( 0 === $post_id ) {
		//last attempt to parse (recipes fail url_to_postid) 
		$parsed = parse_url( $previewed_url );
		$matches = array();
		//catch ?p=123 (guid-like) permalinks
		preg_match_all( '/^\/(\d{1,})\//', $parsed[ 'path' ], $matches );
		if ( isset( $matches[ 1 ] ) && isset( $matches[ 1 ][ 0 ] ) ) {
			$post_id = $matches[ 1 ][ 0 ];
		}
	}
	if ( null !== $post_id && 0 !== $post_id ) {
		$type = 'single';
		$object_id = $post_id;
	} else {
		//check if it's a category
		$home_url = get_home_url();
		$cat = get_category_by_path( str_replace( $home_url, "", $previewed_url ), $full_match = true, constant( 'OBJECT' ) );
		if ( null !== $cat ) {
			$type = 'category';
			$object_id = $cat->ID;
		}
		//check if it's a homepage
		if ( null === $type ) {
			if ( $previewed_url === $home_url ) {
				$type = 'homepage';
			}
		}
	}
	return apply_filters( 'customizer_demo_preview_type', array(
		'url' => $previewed_url,
		'id' => $object_id,
		'type' => $type
	) );
}

/* Register Sections */

function customizer_demo_sections() {

}

function customizer_wrapper_sections() {
	customizer_wrapper_clear_sections();
	error_log( "customizer_wrapper_sections()" );
	$type = customizer_demo_preview_type();
	error_log( json_encode( "TYPE: " . json_encode( $type ) ) );
}
add_action( 'customize_register', 'customizer_wrapper_sections', 100, 1 );

// WordPress comes with default sections (filled with settings and matching controls)
// and also display a non-optional theme description header
// For a clean start, it can be useful to disable these.
function customizer_wrapper_clear_sections() {
	customizer_wrapper_remove_default_sections();
	customizer_wrapper_hide_default_header();
}

//core example: https://github.com/WordPress/WordPress/blob/master/wp-includes/class-wp-customize-manager.php#L718
function customizer_wrapper_remove_default_sections() {
	global $wp_customize;
	$sections = array( 'static_front_page', 'colors', 'title_tagline', 'header_image', 'background_image' );
	foreach ( $sections as $section ) {
		$wp_customize->remove_section( $section );
	}
	$settings = array( 'blogname', 'blogdescription', 'header_textcolor', 'background_color', 'header_image', 'header_image_data', 'background_image', 'background_image_thumb', 'background_repeat', 'background_position_x', 'background_attachment', 'show_on_front', 'page_on_front', 'page_for_posts' );
	foreach ( $settings as $setting ) {
		$wp_customize->remove_setting( $setting );
	}
	$controls = array( 'blogname', 'blogdescription', 'display_header_text', 'header_textcolor', 'background_color', 'background_image_thumb', 'header_image_data', 'background_repeat', 'background_position_x', 'background_attachment', 'show_on_front', 'page_on_front', 'page_for_posts' );
	foreach ( $controls as $control ) {
		$wp_customize->remove_control( $control );
	}

}

function customizer_wrapper_hide_default_header() {
	add_action( 'customize_controls_print_styles', 'customizer_wrapper_print_styles', 20, 0 );
}


function customizer_wrapper_print_styles() {
	echo <<<CSS
<style type="text/css">
	#customize-info { display: none !important; }
</style>
CSS;
}



/* 
	customize_preview_init //enqueue
	customize_controls_print_footer_scripts
	customize_controls_print_styles
	customize_controls_print_scripts
*/

/* Add Settings */

//core example: https://github.com/WordPress/WordPress/blob/master/wp-includes/class-wp-customize-manager.php#L723

/* Add Controls */

//core example: https://github.com/WordPress/WordPress/blob/master/wp-includes/class-wp-customize-manager.php#L729

/* Handle Changes */

/* Handle Submissions */

/* Downside - how do you replicate filters such as autop client-side? */

//required w/WP versions less than 3.6
//http://core.trac.wordpress.org/attachment/ticket/23509/23509.diff
if ( ! function_exists( 'is_customizer' ) ) {
	function is_customizer(){
		global $wp_customize;

		if ( ! is_a( $wp_customize, 'WP_Customize_Manager' ) )
		return false;

		return $wp_customize->is_preview();
	 }
}

