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
		return apply_filters( 'customizer_demo_preview_type', array( 'url' => null, 'id' => null, 'type' => null ) );
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

function customizer_demo_sections( $sections = array() ) {
	$type = customizer_demo_preview_type();
	if ( 'single' === $type[ 'type' ] ) { 
		$sections[] = array(
			'slug' => 'customizer-demo-single',
			'title' => 'Post tools',
			'priority' => 20
		);
	}
	return $sections;
}
add_filter( 'customizer_wrapper_sections', 'customizer_demo_sections', 20, 1 );

function customizer_wrapper_sections( $wp_customize ) {
	customizer_wrapper_clear_sections();
	$sections  = apply_filters( 'customizer_wrapper_sections', array() );
	foreach ( $sections as $section ) {
		$wp_customize->add_section( $section[ 'slug' ], array(
			'title' => empty( $section[ 'title' ] ) ? null : $section[ 'title' ],
			'theme_supports' => empty( $section[ 'theme_supports' ] ) ? null : $section[ 'theme_supports' ],
			'priority' => empty( $section[ 'priority' ] ) ? null : $section[ 'priority' ]
		) );
	}
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

//can be of type option, theme_mod or arbitrary: https://github.com/WordPress/WordPress/blob/master/wp-includes/class-wp-customize-setting.php#L159

function customizer_demo_settings( $settings = array() ) {
	$type = customizer_demo_preview_type();
	if ( 'single' === $type[ 'type' ] ) {

		$settings[] = array(
			'slug' => 'customizer-demo-single-checkbox',
			'default' => true,
			'capability' => 'edit_others_posts'
		);

		$settings[] = array(
			'slug' => 'customizer-demo-single-radio',
			'default' => 'two',
			'capability' => 'edit_others_posts'
		);

		$settings[] = array(
			'slug' => 'customizer-demo-single-text',
			'default' => 'default text',
			'capability' => 'edit_others_posts'
		);

		$settings[] = array(
			'slug' => 'customizer-demo-single-select',
			'default' => 'two',
			'capability' => 'edit_others_posts'
		);

		//$val = wp_get_attachment_image_src( get_user_meta( $user_id, $skin_top_setting, true ), 'full' )[ 0 ];
		$settings[] = array(
			'slug' => 'customizer-demo-single-image',
			'default' => 'http://www.google.com/images/errors/logo_sm.gif',
			'capability' => 'edit_others_posts',
			'type' => 'crazy_type'
		);

		$settings[] = array(
			'slug' => 'customizer-demo-single-color',
			'default' => '#336699',
			'capability' => 'edit_others_posts',
			'type' => 'crazy_type'
		);

	}
	return $settings;
}
add_filter( 'customizer_wrapper_settings', 'customizer_demo_settings', 20, 1 );

function customizer_wrapper_settings( $wp_customize ) {
	customizer_wrapper_remove_default_settings();
	$settings = apply_filters( 'customizer_wrapper_settings', array() );
	foreach ( $settings as $setting ) {
		$wp_customize->add_setting( $setting[ 'slug' ], array(
			'default' => empty( $setting[ 'default' ] ) ? null : $setting[ 'default' ],
			'capability' => empty( $setting[ 'capability' ] ) ? null : $setting[ 'capability' ],
			'theme_supports' => empty( $setting[ 'theme_supports' ] ) ? null : $setting[ 'theme_supports' ],
			'sanitize_callback' => empty( $setting[ 'sanitize_callback' ] ) ? null : $setting[ 'sanitize_callback' ],
			'sanitize_js_callback' => empty( $setting[ 'sanitize_js_callback' ] ) ? null : $setting[ 'sanitize_js_callback' ]
		) );
	}
}
add_action( 'customize_register', 'customizer_wrapper_settings', 100, 1 );

function customizer_wrapper_remove_default_settings() {
	global $wp_customize;
	$settings = array( 'blogname', 'blogdescription', 'header_textcolor', 'background_color', 'header_image', 'header_image_data', 'background_image', 'background_image_thumb', 'background_repeat', 'background_position_x', 'background_attachment', 'show_on_front', 'page_on_front', 'page_for_posts' );
	foreach ( $settings as $setting ) {
		$wp_customize->remove_setting( $setting );
	}
}

/* Add Controls */
//core example: https://github.com/WordPress/WordPress/blob/master/wp-includes/class-wp-customize-manager.php#L729

function customizer_demo_controls( $controls = array() ) {
	$type = customizer_demo_preview_type();
	if ( 'single' === $type[ 'type' ] ) {

		//checkbox
		$controls[] = array(
			'slug' => 'customizer-demo-single-checkbox',
			'settings' => 'customizer-demo-single-checkbox',
			'label' => 'Example Checkbox',
			'type' => 'checkbox',
			'section' => 'customizer-demo-single'
		);

		//text
		$controls[] = array(
			'slug' => 'customizer-demo-single-text',
			'settings' => 'customizer-demo-single-text',
			'label' => 'Example Textfield',
			'type' => 'text',
			'section' => 'customizer-demo-single'
		);

		//radio
		$controls[] = array(
			'slug' => 'customizer-demo-single-radio',
			'settings' => 'customizer-demo-single-radio',
			'label' => 'Example Radio',
			'type' => 'radio',
			'choices' => array(
				'one' => 'One',
				'two' => 'Two',
				'three' => 'Three'
			),
			'section' => 'customizer-demo-single'
		);

		//select
		$controls[] = array(
			'slug' => 'customizer-demo-single-select',
			'settings' => 'customizer-demo-single-select',
			'label' => 'Example Select',
			'type' => 'select',
			'choices' => array(
				'one' => 'One',
				'two' => 'Two',
				'three' => 'Three'
			),
			'section' => 'customizer-demo-single'
		);

		//image
		$controls[] = array(
			'slug' => 'customizer-demo-single-image',
			'settings' => 'customizer-demo-single-image',
			'label' => 'Example Image',
			'type' => 'image',
			'section' => 'customizer-demo-single'
		);

		//color
		$controls[] = array(
			'slug' => 'customizer-demo-single-color',
			'settings' => 'customizer-demo-single-color',
			'label' => 'Example Color',
			'type' => 'color',
			'section' => 'customizer-demo-single'
		);

	}
	return $controls;
}
add_filter( 'customizer_wrapper_controls', 'customizer_demo_controls', 20, 1 );

function customizer_wrapper_controls( $wp_customize ) {
	customizer_wrapper_remove_default_controls();
	$controls = apply_filters( 'customizer_wrapper_controls', array() );
	foreach ( $controls as $control ) {
		if ( 'image' === $control[ 'type' ] ) {
			$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, empty( $control[ 'settings' ] ) ? null : $control[ 'settings' ],
				array(
					'label' => empty( $control[ 'label' ] ) ? null : $control[ 'label' ],
					'section' => empty( $control[ 'section' ] ) ? null : $control[ 'section' ],
					'settings' => empty( $control[ 'settings' ] ) ? null : $control[ 'settings' ],
					'priority' => empty( $control[ 'priority' ] ) ? null : $control[ 'priority' ]
				)
			) );
		} else if ( 'color' === $control[ 'type' ] ) {
			$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, empty( $control[ 'settings' ] ) ? null : $control[ 'settings' ],
				array(
					'label' => empty( $control[ 'label' ] ) ? null : $control[ 'label' ],
					'section' => empty( $control[ 'section' ] ) ? null : $control[ 'section' ],
					'settings' => empty( $control[ 'settings' ] ) ? null : $control[ 'settings' ],
					'priority' => empty( $control[ 'priority' ] ) ? null : $control[ 'priority' ]
				)
			) );
		} else {
			$wp_customize->add_control( $control[ 'slug' ], array(
				'settings' => empty( $control[ 'settings' ] ) ? null : $control[ 'settings' ],
				'label' => empty( $control[ 'label' ] ) ? null : $control[ 'label' ],
				'section' => empty( $control[ 'section' ] ) ? null : $control[ 'section' ],
				'type' => empty( $control[ 'type' ] ) ? null : $control[ 'type' ],
				'choices' => empty( $control[ 'choices' ] ) ? null : $control[ 'choices' ]
			) );
		}
	}
}
add_action( 'customize_register', 'customizer_wrapper_controls', 100, 1 );

function customizer_wrapper_remove_default_controls() {
	global $wp_customize;
	$controls = array( 'blogname', 'blogdescription', 'display_header_text', 'header_textcolor', 'background_color', 'background_image_thumb', 'header_image_data', 'background_repeat', 'background_position_x', 'background_attachment', 'show_on_front', 'page_on_front', 'page_for_posts' );
	foreach ( $controls as $control ) {
		$wp_customize->remove_control( $control );
	}
}


/* Handle Changes */

/* Handle Submissions */

/* 
{
    "customizer-demo-single-checkbox": true,
    "customizer-demo-single-radio": "two",
    "customizer-demo-single-text": "default text",
    "customizer-demo-single-select": "two",
    "customizer-demo-single-image": "http:\/\/example.com\/wp-content\/uploads\/2013\/08\/942538_732791487998_1267339575_n1.jpg",
    "customizer-demo-single-color": "#336699"
}
 */

function customizer_demo_save( $wp_customize, $data, $type, $controls ) {
	foreach ( $data as $slug => $value ) {
		if ( 'single' === $type[ 'type' ] ) {
			$item = $controls[ $slug ];
			$control = $item[ 'control' ];
			$default = $control->settings[ 'default' ]->default;
			if ( 'customizer-demo-single-image' === $slug ) {
				$attachment = customizer_wrapper_attachment_by_url( $value );
				if ( null !== $attachment ) {
					$value = $attachment->ID;
				}
			}
			if ( empty( $value ) || $value === $default ) {
				delete_post_meta( $type[ 'id' ], $slug );				
			} else {
				if ( false === $value ) {
					$value = 0;
				} else if ( true === $value ) {
					$value = 1;
				}
				update_post_meta( $type[ 'id' ], $slug, $value );
			}
		}
	}

}
add_filter( 'customizer_wrapper_save', 'customizer_demo_save', 20, 4 );

function customizer_wrapper_save( $wp_customize ) {
	$data = get_object_vars( json_decode( stripslashes( $_POST[ 'customized' ] ) ) );
	$type = customizer_demo_preview_type();
	$controls = array();
	foreach ( $data as $slug => $value ) {
		$type_slug = null;
		if ( is_a( $control, 'WP_Customize_Image_Control' ) ) {
			$type_slug = 'image';
		} else if ( is_a( $control, 'WP_Customize_Color_Control' ) ) {
			$type_slug = 'color';
		} else {
			if ( isset( $control->type ) ) {
				$type_slug = $control->type;
			}
		}
		$control = $wp_customize->get_control( $slug );
		$controls[ $slug ] = array( 'control' => $control, 'type' => $type_slug );
		do_action( 'customizer_wrapper_saved_' . $slug, $value, $wp_customize, $type, $controls[ $slug ] );
	}
	do_action( 'customizer_wrapper_save', $data, $data, $type, $controls );
}
add_action( 'customize_save', 'customizer_wrapper_save', 20, 1 );

function customizer_wrapper_attachment_by_url( $src ) {
	if ( empty( $src ) ) {
		return null;
	}
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( "SELECT {$wpdb->prefix}posts.* FROM {$wpdb->prefix}posts WHERE guid = %s AND post_status = 'inherit' ORDER BY post_date_gmt DESC LIMIT 1", $src ), "OBJECT" );
}


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

