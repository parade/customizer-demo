<?php

/* Example Implementation:
 * - Setup
 * - Sections
 * - Settings
 * - Controls
 * - Saving
 * - Templating
 */

/* Setup */

function customizer_demo_prefix() {
	return 'customizer-demo-';
}

function customizer_demo_static() {
	wp_enqueue_script(
		'customizer_demo_static',
		plugins_url( 'frontend.js', __FILE__ ),
		array( 'jquery','customize-preview' ),
		'1.0',
		true
	);
}
add_action( 'customize_preview_init', 'customizer_demo_static', 20, 2 );

function customizer_demo_maybe_clear( $wp_customize ) {
	$preview = customizer_wrapper_preview_type();
	if ( 'single' === $preview[ 'type' ] ) {
		add_filter( 'customizer_wrapper_clear_header', '__return_true', 10, 1 );
		add_filter( 'customizer_wrapper_clear_elements', '__return_true', 10, 1 );
	}
}
//purposefully late on customize_register
add_action( 'customize_register', 'customizer_demo_maybe_clear', 0, 1 );

/* Sections */

function customizer_demo_sections( $sections = array() ) {

	$preview_type = customizer_wrapper_preview_type();
	$prefix = customizer_demo_prefix();

	if ( 'single' === $preview_type[ 'type' ] ) { 

		$sections[] = array(
			'id' => $prefix . 'single',
			'title' => 'Customize Post',
			'priority' => 20
		);

	}
	return $sections;
}
add_filter( 'customizer_wrapper_sections', 'customizer_demo_sections', 20, 1 );

/* Settings */

function customizer_demo_settings( $settings = array() ) {

	$preview_type = customizer_wrapper_preview_type();
	$prefix = customizer_demo_prefix();

	if ( 'single' === $preview_type[ 'type' ] ) {

		//we are setting up article-level tooling
		//so declare a current $post_id far for reuse
		$post_id = $preview_type[ 'id' ];

		//checkbox
		//using the same key for postmeta and customizer setting makes life easier
		$key = $prefix . 'single-checkbox';
		//fetch existing value to populate as "default"
		$value = get_post_meta( $post_id, $key, true );
		//if the existing value doesn't exist, use an actual default
		$value = empty( $value ) ? true : $value;
		//add the setting to the stack
		$settings[] = array(
			'id' => $key,
			'default' => $value,
			'transport' => 'postMessage',
			'capability' => 'edit_others_posts'
		);

		//other control types typically follow the same pattern,
		//except for images, which may require some fancy footwork
		//if you'd like to store attachment ids

		//radio
		$key = $prefix . 'single-radio';
		$value = get_post_meta( $post_id, $key, true );
		$value = empty( $value ) ? 'showing' : $value;
		$settings[] = array(
			'id' => $key,
			'default' => $value,
			'transport' => 'postMessage',
			'capability' => 'edit_others_posts'
		);

		//text
		$key = $prefix . 'single-text';
		$value = get_post_meta( $post_id, $key, true );
		$value = empty( $value ) ? get_bloginfo( 'description' ) : $value;
		$settings[] = array(
			'id' => $key,
			'default' => $value,
			'transport' => 'postMessage',
			'capability' => 'edit_others_posts'
		);
	
		//select
		$key = $prefix . 'single-select';
		$value = get_post_meta( $post_id, $key, true );
		$value = empty( $value ) ? 'showing' : $value; 
		$settings[] = array(
			'id' => $key,
			'default' => $value,
			'transport' => 'postMessage',
			'capability' => 'edit_others_posts'
		);

		//color
		$key = $prefix . 'single-color';
		$value = get_post_meta( $post_id, $key, true );
		$value = empty( $value ) ? '#336699' : $value; 
		$settings[] = array(
			'id' => $key,
			'default' => $value,
			'transport' => 'postMessage',
			'capability' => 'edit_others_posts'
		);

		//image
		//images are somewhat special when setting up sections
		$key = $prefix . 'single-image';
		$value = get_post_meta( $post_id, $key, true );
		//it can be much more useful to store a post ID rather than a raw image
		if ( empty( $value ) ) {
			//when it's an image ID, translate that into a img url
			//as required by the image control
			$value = wp_get_attachment_image_src( $value, 'full' );
			$value = $value[ 0 ];
		}

		$settings[] = array(
			'id' => $key,
			'default' => $value,
			'capability' => 'edit_others_posts',
			'transport' => 'postMessage'
		);
		error_log("SETTINGS" . json_encode( $settings ) );
	}
	return $settings;
}
add_filter( 'customizer_wrapper_settings', 'customizer_demo_settings', 20, 1 );


/* Controls */

function customizer_demo_controls( $controls = array() ) {
	
	$type = customizer_wrapper_preview_type();
	$prefix = customizer_demo_prefix();

	if ( 'single' === $type[ 'type' ] ) {
		
		//text
		$controls[] = array(
			'id' => $prefix . 'single-text',
			'settings' => $prefix . 'single-text',
			'label' => 'Sitename',
			'type' => 'text',
			'section' => $prefix . 'single'
		);

		//checkbox
		$controls[] = array(
			'id' => $prefix . 'single-checkbox',
			'settings' => $prefix . 'single-checkbox',
			'label' => 'Header Description',
			'type' => 'checkbox',
			'section' => $prefix . 'single'
		);


		//image
		$controls[] = array(
			'id' => $prefix . 'single-image',
			'settings' => $prefix . 'single-image',
			'label' => 'Header Image',
			'type' => 'image',
			'section' => $prefix . 'single'
		);

		//color
		$controls[] = array(
			'id' => $prefix . 'single-color',
			'settings' => $prefix . 'single-color',
			'label' => 'Header Color',
			'type' => 'color',
			'section' => $prefix . 'single'
		);

		//radio
		$controls[] = array(
			'id' => $prefix . 'single-radio',
			'settings' => $prefix . 'single-radio',
			'label' => 'Font size',
			'type' => 'radio',
			'choices' => array(
				'normal' => 'Normal',
				'large' => 'Large',
				'xlarge' => 'XL'
			),
			'section' => $prefix . 'single'
		);

		//select
		$controls[] = array(
			'id' => $prefix . 'single-select',
			'settings' => $prefix . 'single-select',
			'label' => 'Example Select',
			'type' => 'select',
			'choices' => array(
				'showing' => 'Show Search Bar',
				'hiding' => 'Hide Search Bar'
			),
			'section' => $prefix . 'single'
		);

	}
	return $controls;
}
add_filter( 'customizer_wrapper_controls', 'customizer_demo_controls', 20, 1 );


/* Saving */

function customizer_demo_save( $data, $wp_customize, $type, $controls ) {
	$type = customizer_wrapper_preview_type();
	$prefix = customizer_demo_prefix();
	if ( 'single' === $type[ 'type' ] ) {
		foreach ( $data as $id => $value ) {
			$item = $controls[ $id ];
			//say you wanted to pull data from the original control
			//$control = $item[ 'control' ];
			//$default = $control->settings[ 'default' ]->default;
			$delete = false;
			if ( $prefix . 'single-image' === $id ) {
				$attachment = customizer_wrapper_attachment_by_url( $value );
				if ( null !== $attachment ) {
					$value = $attachment->ID;
				}
			}
			if ( true === $value ) {
				$value = 'true';
			} else if ( false === $value ) {
				$value = 'false';
			}
		
			if ( true === empty( $value ) ) {
				delete_post_meta( $type[ 'id' ], $id );				
			} else {
				update_post_meta( $type[ 'id' ], $id, $value );
			}
		}
	}

}
add_filter( 'customizer_wrapper_save', 'customizer_demo_save', 20, 4 );


/* Templating */

function customizer_demo_maybe_filter_blogname( $title = '' ) {
	if ( true === is_single() ) {
		global $post;
		$meta_title = get_post_meta( $post->ID, 'customizer-demo-single-text', true );
		if ( false === empty( $meta_title ) ) {
			return $meta_title;
		}
	}
	return $title;
}
add_filter( 'pre_option_blogname', 'customizer_demo_maybe_filter_blogname', 10, 1 );

function customizer_demo_maybe_hide_description() {
	if ( true === is_single() ) {
		$prefix = customizer_demo_prefix();
		global $post;
		$desc = get_post_meta( $post->ID, $prefix . 'single-checkbox', true );
		if ( 'false' === $desc ) {
			echo <<<CSS
<style type="text/css">
	.site-description { display: none !important; }
</style>
CSS;
		}
	}
}
add_action( 'wp_print_footer_scripts' , 'customizer_demo_maybe_hide_description', 10 );


function customizer_demo_maybe_filter_header_image( $description = '' ) {
	if ( true === is_single() ) {
		$prefix = customizer_demo_prefix();
		global $post;
		$meta_image = get_post_meta( $post->ID, $prefix . 'single-image', true );
		if ( intval( $meta_image ) > 0 ) {
			$image = wp_get_attachment_image_src( $meta_image, 'full' );
			return $image[ 0 ];
		}
	}
	return $description;
}
add_filter( 'theme_mod_header_image', 'customizer_demo_maybe_filter_header_image', 10, 1 );

function customizer_demo_maybe_change_color() {
	if ( true === is_single() ) {
		$prefix = customizer_demo_prefix();
		global $post;
		$color = get_post_meta( $post->ID, $prefix . 'single-color', true );
		if ( false === empty( $color ) ) {
			echo <<<CSS
<style type="text/css">
	.site-title { color: {$color}; }
	.site-description { color: {$color}; }
</style>
CSS;
		}
	}
}
add_action( 'wp_print_footer_scripts' , 'customizer_demo_maybe_change_color', 10 );


function customizer_demo_maybe_change_font_size() {
	if ( true === is_single() ) {
		$prefix = customizer_demo_prefix();
		global $post;
		$size = get_post_meta( $post->ID, $prefix . 'single-radio', true );
		if ( 'normal' !== $size ) {
			if ( 'large' === $size ) {
				$px = 24;
				$hpx = 54;
			}
			if ( 'xlarge' === $size ) {
				$px = 32;
				$hpx = 64;
			}
			echo <<<CSS
<style type="text/css">
	 .entry-content { font-size: {$px}px !important; }
     h1 { font-size:{$hpx}px !important; }
</style>
CSS;
		}
	}
}
add_action( 'wp_print_footer_scripts' , 'customizer_demo_maybe_change_font_size', 10 );



function customizer_demo_maybe_hide_bar() {
	if ( true === is_single() ) {
		$prefix = customizer_demo_prefix();
		global $post;
		$bar = get_post_meta( $post->ID, $prefix . 'single-select', true );
		if ( 'hiding' === $bar ) {
			echo <<<CSS
<style type="text/css">
	#navbar { display: none; }
</style>
CSS;
		}
	}
}
add_action( 'wp_print_footer_scripts' , 'customizer_demo_maybe_hide_bar', 10 );

