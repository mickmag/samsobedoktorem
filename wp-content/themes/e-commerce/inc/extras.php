<?php
/**
 * Custom functions that act independently of the theme templates
 *
 * Eventually, some of the functionality here could be replaced by core features
 *
 * @package E-Commerce
 */

/**
 * Adds custom classes to the array of body classes.
 *
 * @param array $classes Classes for the body element.
 * @return array
 */
function e_commerce_body_classes( $classes ) {
	// Adds a class of group-blog to blogs with more than 1 published author.
	if ( is_multi_author() ) {
		$classes[] = 'group-blog';
	}

	return $classes;
}
add_filter( 'body_class', 'e_commerce_body_classes' );

if ( ! function_exists( 'e_commerce_custom_css' ) ) :
	/**
	 * Enqueue Custom CSS
	 *
	 * @uses  get_theme_mod
	 *
	 * @action wp_head
	 *
	 * @since E-commerce 1.0.1
	 */
	function e_commerce_custom_css() {
		if( $e_commerce_custom_css = get_theme_mod( 'custom_css' ) ) {
				echo '<!-- refreshing cache -->' . "\n";

				echo '<!-- '.get_bloginfo('name').' inline CSS Styles -->' . "\n" . '<style type="text/css" media="screen">' . "\n" . $e_commerce_custom_css;

				echo '</style>' . "\n";
			}

	}
endif; //e_commerce_custom_css
add_action( 'wp_head', 'e_commerce_custom_css', 101 );

if ( ! function_exists( 'e_commerce_get_logo' ) ) :
	/**
	 * Get the logo
	 *
	 * @get logo from options
	 *
	 * @since E-commerce 1.0.5
	 */
	function e_commerce_get_logo() {
		$output = '';
		//Checking Logo
		if ( function_exists( 'has_custom_logo' ) ) {
			if ( has_custom_logo() ) {
				$output = '
				<div class="site-logo">'. get_custom_logo() . '</div><!-- #site-logo -->';
			}
		}
		return $output;
	}
endif; // e_commerce_get_logo


/**
 * Flush out all transients
 *
 * @uses delete_transient
 *
 * @action customize_save, e_commerce_customize_preview (see e_commerce_customizer function: e_commerce_customize_preview)
 *
 * @since since E-commerce 1.2
 */
function e_commerce_flush_transients(){
	delete_transient( 'e_commerce_featured_image' );
}
add_action( 'customize_save', 'e_commerce_flush_transients' );
add_action( 'customize_preview_init', 'e_commerce_flush_transients' );


/**
 * Migrate Custom CSS to WordPress core Custom CSS
 *
 * Runs if version number saved in theme_mod "custom_css_version" doesn't match current theme version.
 */
function e_commerce_custom_css_migrate() {
	$ver = get_theme_mod( 'custom_css_version', false );

	// Return if update has already been run
	if ( version_compare( $ver, '4.7' ) >= 0 ) {
		return;
	}
	
	if ( function_exists( 'wp_update_custom_css_post' ) ) {
	    // Migrate any existing theme CSS to the core option added in WordPress 4.7.
	    
	    /**
		 * Get Theme Options Values
		 */
	    $custom_css = get_theme_mod( 'custom_css' );

	    if ( '' != $custom_css ) {
			$core_css = wp_get_custom_css(); // Preserve any CSS already added to the core option.
			$return   = wp_update_custom_css_post( $core_css . $custom_css );
	        if ( ! is_wp_error( $return ) ) {
	            // Remove the old theme_mod, so that the CSS is stored in only one place moving forward.
	            remove_theme_mod( 'custom_css' );

	            // Update to match custom_css_version so that script is not executed continously
				set_theme_mod( 'custom_css_version', '4.7' );
	        }
	    }
	}
}
add_action( 'after_setup_theme', 'e_commerce_custom_css_migrate' );