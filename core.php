<?php

/* Customizer Wrapper */

/* - Sections
 * - Settings
 * - Controls 
 * - Save
 * - Capabilities
 * - Utilities
 */

/* Sections */

function customizer_wrapper_sections( $wp_customize ) {
	$sections  = apply_filters( 'customizer_wrapper_sections', array() );
	foreach ( $sections as $section ) {
		$wp_customize->add_section( $section[ 'id' ], array(
			'title' => empty( $section[ 'title' ] ) ? null : $section[ 'title' ],
			'theme_supports' => empty( $section[ 'theme_supports' ] ) ? null : $section[ 'theme_supports' ],
			'priority' => empty( $section[ 'priority' ] ) ? null : $section[ 'priority' ]
		) );
	}
}
add_action( 'customize_register', 'customizer_wrapper_sections', 100, 1 );


//core example: https://github.com/WordPress/WordPress/blob/master/wp-includes/class-wp-customize-manager.php#L718
function customizer_wrapper_remove_default_sections() {
	$initial = array( 'static_front_page', 'colors', 'title_tagline', 'header_image', 'background_image' );
	$finalized = apply_filters( 'customizer_wrapper_default_sections', $initial );
	global $wp_customize;
	foreach ( array_diff( $initial, $finalized ) as $section ) {
		$wp_customize->remove_section( $section );
	}
}


/* Settings */

//core example: https://github.com/WordPress/WordPress/blob/master/wp-includes/class-wp-customize-manager.php#L723

function customizer_wrapper_settings( $wp_customize ) {
	customizer_wrapper_remove_default_settings();
	$settings = apply_filters( 'customizer_wrapper_settings', array() );
	foreach ( $settings as $setting ) {
		$wp_customize->add_setting( $setting[ 'id' ], array(
			'default' => empty( $setting[ 'default' ] ) ? null : $setting[ 'default' ],
			'transport' => empty( $setting[ 'transport' ] ) ? null : $setting[ 'transport' ],
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
	//note: some controls, such as header_image, require both the setting and the control to be removed
	$initial = array( 'blogname', 'blogdescription', 'header_textcolor', 'background_color', 'header_image', 'header_image_data', 'background_image', 'background_image_thumb', 'background_repeat', 'background_position_x', 'background_attachment', 'show_on_front', 'page_on_front', 'page_for_posts' );
	$finalized = apply_filters( 'customizer_wrapper_default_settings', $initial );
	foreach ( array_diff( $initial, $finalized ) as $setting ) {
		$wp_customize->remove_setting( $setting );
	}
}


/* Controls */

//core example: https://github.com/WordPress/WordPress/blob/master/wp-includes/class-wp-customize-manager.php#L729

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
			$wp_customize->add_control( $control[ 'id' ], array(
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
	$initial = array( 'blogname', 'blogdescription', 'display_header_text', 'header_textcolor', 'background_color', 'background_image_thumb', 'header_image_data', 'background_repeat', 'background_position_x', 'background_attachment', 'show_on_front', 'page_on_front', 'page_for_posts', 'header_image' );
	$finalized = apply_filters( 'customizer_wrapper_default_controls', $initial );
	global $wp_customize;
	foreach ( array_diff( $initial, $finalized ) as $control ) {
		$wp_customize->remove_control( $control );
	}
}


/* Save */

//what happens when the customizer control is 
//saved depends on its type. can be of type option or theme_mod to be handled accordingly,
//with anything else firing only events on save: https://github.com/WordPress/WordPress/blob/master/wp-includes/class-wp-customize-setting.php#L159
function customizer_wrapper_save( $wp_customize ) {
	$data = get_object_vars( json_decode( stripslashes( $_POST[ 'customized' ] ) ) );
	$type = customizer_wrapper_preview_type();
	$controls = array();
	foreach ( $data as $id => $value ) {
		$type_id = null;
		if ( is_a( $control, 'WP_Customize_Image_Control' ) ) {
			$type_id = 'image';
		} else if ( is_a( $control, 'WP_Customize_Color_Control' ) ) {
			$type_id = 'color';
		} else {
			if ( isset( $control->type ) ) {
				$type_id = $control->type;
			}
		}
		$control = $wp_customize->get_control( $id );
		$controls[ $id ] = array( 'control' => $control, 'type' => $type_id );
		do_action( 'customizer_wrapper_saved_' . $id, $value, $wp_customize, $type, $controls[ $id ] );
	}
	do_action( 'customizer_wrapper_save', $data, $wp_customize, $type, $controls );
}
add_action( 'customize_save', 'customizer_wrapper_save', 20, 1 );

/* Capabilities */

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


/* Utilities */

//determine previewed page type via ?url= parameter
//filters:
//* customizer_demo_preview_url
//* customizer_wrapper_preview_type
function customizer_wrapper_preview_type( $previewed_url = null ) {
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
		return apply_filters( 'customizer_wrapper_preview_type', array( 'url' => null, 'id' => null, 'type' => null ) );
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
	return apply_filters( 'customizer_wrapper_preview_type', array(
		'url' => $previewed_url,
		'id' => $object_id,
		'type' => $type
	) );
}


// There is a header that forces display of your current theme name
// If you don't like it, you can hide it with a little CSS hackery

//dirty hack for hiding the theme header
function customizer_wrapper_print_styles() {
	echo <<<CSS
<style type="text/css">
	#customize-info { display: none !important; }
</style>
CSS;
}

// WordPress comes with default sections (filled with settings and matching controls)
// and also display a non-optional theme description header
// For a clean start, it can be useful to disable these.

// This is a blunt way of dealing with sections - wiping away everything I could
// find registred in core (not just sections, but also controls to keep the $_POST clean, etc.
function customizer_wrapper_clear_defaults() {
	if ( true === apply_filters( 'customizer_wrapper_clear_header', false ) ) {
		add_action( 'customize_controls_print_styles', 'customizer_wrapper_print_styles', 20, 0 );
	}
	if ( true === apply_filters( 'customizer_wrapper_clear_elements', false ) ) {
		add_filter( 'customizer_wrapper_default_sections', 'customizer_wrapper_empty', 20, 1 );
		add_filter( 'customizer_wrapper_default_settings', 'customizer_wrapper_empty', 20, 1 );
		add_filter( 'customizer_wrapper_default_controls', 'customizer_wrapper_empty', 20, 1 );
	}
}
//purposefully runs early on customize_register
add_action( 'customize_register', 'customizer_wrapper_clear_defaults', 5, 0 );

//Helper method usable as a /dev/null for filters
function customizer_wrapper_empty( $val ) {
	return is_array( $val ) ? array() : null;
}


// Shims/helpers
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

function customizer_wrapper_attachment_by_url( $src ) {
	if ( empty( $src ) ) {
		return null;
	}
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( "SELECT {$wpdb->prefix}posts.* FROM {$wpdb->prefix}posts WHERE guid = %s AND post_status = 'inherit' ORDER BY post_date_gmt DESC LIMIT 1", $src ), "OBJECT" );
}


