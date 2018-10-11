<?php
/**
 * The template for Managing Theme Structure
 *
 * @package Catch Themes
 * @subpackage E-Commerce Pro
 * @since E-Commerce 1.0
 */

if ( ! function_exists( 'e_commerce_doctype' ) ) :
	/**
	 * Doctype Declaration
	 *
	 * @since E-Commerce 1.0
	 *
	 */
	function e_commerce_doctype() {
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<?php
	}
endif;
add_action( 'e_commerce_doctype', 'e_commerce_doctype', 10 );


if ( ! function_exists( 'e_commerce_head' ) ) :
	/**
	 * Header Codes
	 *
	 * @since E-Commerce 1.0
	 *
	 */
	function e_commerce_head() {
		?>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="profile" href="http://gmpg.org/xfn/11">
		<?php
		if ( is_singular() && pings_open() ) {
			echo '<link rel="pingback" href="', esc_url( get_bloginfo( 'pingback_url' ) ), '">';
		}
	}
endif;
add_action( 'e_commerce_before_wp_head', 'e_commerce_head', 10 );


if ( ! function_exists( 'e_commerce_page_start' ) ) :
	/**
	 * Start div id #page
	 *
	 * @since E-Commerce 1.0
	 *
	 */
	function e_commerce_page_start() {
		?>
		<div id="page" class="hfeed site">
		<a class="skip-link screen-reader-text" href="#content"><?php _e( 'Skip to content', 'e-commerce' ); ?></a>
		<?php
	}
endif;
add_action( 'e_commerce_before_header', 'e_commerce_page_start', 10 );


if ( ! function_exists( 'e_commerce_header_start' ) ) :
	/**
	 * Start Header id #masthead
	 *
	 * @since E-Commerce 1.0
	 *
	 */
	function e_commerce_header_start() {
		echo "\n";
		?>
		<header id="masthead" class="site-header" role="banner">
		<?php
	}
endif;
add_action( 'e_commerce_header', 'e_commerce_header_start', 10 );


if ( ! function_exists( 'e_commerce_site_banner_start' ) ) :
	/**
	 * Start in header class .site-banner and class .wrapper
	 *
	 * @since E-Commerce 1.0
	 *
	 */
	function e_commerce_site_banner_start() {
		?>
		<div class="site-banner">
	    	<div class="wrapper">
		<?php
	}
endif;
add_action( 'e_commerce_header', 'e_commerce_site_banner_start', 20 );


if ( ! function_exists( 'e_commerce_site_branding_start' ) ) :
	/**
	 * Start in header class .site-branding
	 *
	 * @since E-Commerce 1.0
	 *
	 */
	function e_commerce_site_branding_start() {
		?>
		<div class="site-branding">
		<?php
	}
endif;
add_action( 'e_commerce_header', 'e_commerce_site_branding_start', 30 );


if ( ! function_exists( 'e_commerce_logo_site_title' ) ) :
	/**
	 * Get logo output and display
	 *
	 * @get logo output
	 * @since E-Commerce 1.0
	 *
	 */
	function e_commerce_logo() {
		echo e_commerce_get_logo();
		?>
		<h1 class="site-title">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
				<?php bloginfo( 'name' ); ?>
			</a>
		</h1>
		<?php
	}
endif;
add_action( 'e_commerce_header', 'e_commerce_logo', 40 );

if ( ! function_exists( 'e_commerce_site_branding_end' ) ) :
	/**
	 * End in header class .site-branding
	 *
	 * @since E-Commerce 1.0
	 *
	 */
	function e_commerce_site_branding_end() {
		?>
		</div><!-- .site-branding -->
		<?php
	}
endif;
add_action( 'e_commerce_header', 'e_commerce_site_branding_end', 50 );

if ( ! function_exists( 'e_commerce_header_left' ) ) :
	/**
	 * Display Site Title
	 *
	 * @get logo output
	 * @since E-Commerce 1.0
	 *
	 */
	function e_commerce_header_left() {
		?>
		<div id="header-left">
			<h2 class="site-description"><?php bloginfo( 'description' ); ?></h2>

	        <?php
	        if ( has_nav_menu( 'social' ) ) { ?>
	            <div class="social-menu">
			        <?php wp_nav_menu( array(
					    'theme_location' => 'social',
					    'depth'          => '1',
					    'link_before'    => '<span class="screen-reader-text">',
					    'link_after'     => '</span>' )
					    );
	                ?>
	            </div><!-- .social-menu -->
	        <?php
    		}
    		?>
		</div> <!-- #header-left -->
    	<?php
	}
endif;
add_action( 'e_commerce_header', 'e_commerce_header_left', 60 );


if ( ! function_exists( 'e_commerce_header_right' ) ) :
	/**
	 * Display Header Right Sidebar
	 *
	 * @since E-Commerce 1.0
	 *
	 */
	function e_commerce_header_right() {
		if ( is_active_sidebar( 'header-right' ) ) {
			get_sidebar( 'header-right' );
		}
	}
endif;
add_action( 'e_commerce_header', 'e_commerce_header_right', 70 );

if ( ! function_exists( 'e_commerce_site_banner_end' ) ) :
	/**
	 * Start in header class .site-banner and class .wrapper
	 *
	 * @since E-Commerce 1.0
	 *
	 */
	function e_commerce_site_banner_end() {
		?>
			</div><!-- .wrapper -->
		</div><!-- .site-banner -->
		<?php
	}
endif;
add_action( 'e_commerce_header', 'e_commerce_site_banner_end', 80 );


if ( ! function_exists( 'e_commerce_header_menu' ) ) :
	/**
	 * Start after class .site-banner before primary menu
	 *
	 * @since E-Commerce 1.0
	 *
	 */
	function e_commerce_header_menu() {
		?>
			<a href="#sidr-main" class="menu-toggle menu-icon"></a>
		<?php
	}
endif;
add_action( 'e_commerce_header', 'e_commerce_header_menu', 90 );


if ( ! function_exists( 'e_commerce_primary_menu' ) ) :
	/**
	 * Start in header primary menu
	 *
	 * @since E-Commerce 1.0
	 *
	 */
	function e_commerce_primary_menu() {
	?>
		<div id="header-navigation">
			<div class="wrapper">
	    		<nav id="site-navigation" class="main-navigation nav-primary" role="navigation">
		           	<?php
	                if ( has_nav_menu( 'primary' ) ) {
	                    $e_commerce_primary_menu_args = array(
	                        'theme_location'    => 'primary',
	                        'menu_id' 			=> 'primary-menu',
	                    );
	                    wp_nav_menu( $e_commerce_primary_menu_args );
	                }
	                else {
	                    wp_page_menu( array( 'menu_class'  => 'menu page-menu-wrap' ) );
	                }
		           	?>
	        	</nav><!-- #site-navigation -->
	        	<?php
	        	if ( function_exists( 'e_commerce_cart_link' ) ) {
	        		e_commerce_cart_link();
	        	}
	        	?>
	        </div><!-- .wrapper -->
	    </div><!-- #header-navigation -->
	    <?php
	}
endif;
add_action( 'e_commerce_header', 'e_commerce_primary_menu', 100 );


if ( ! function_exists( 'e_commerce_header_end' ) ) :
	/**
	 * End header after class .site-banner and class .wrapper
	 *
	 * @since E-Commerce 1.0
	 *
	 */
	function e_commerce_header_end() {
		?>
		</header><!-- #masthead -->
		<?php
	}
endif;
add_action( 'e_commerce_header', 'e_commerce_header_end', 200 );


if ( ! function_exists( 'e_commerce_content_start' ) ) :
	/**
	 * Start div id #content and class .wrapper
	 *
	 * @since E-Commerce 1.0
	 *
	 */
	function e_commerce_content_start() {
		?>
		<div id="content" class="site-content">
	<?php
	}
endif;
add_action('e_commerce_content', 'e_commerce_content_start', 10 );


if ( ! function_exists( 'e_commerce_content_end' ) ) :
	/**
	 * End div id #content and class .wrapper
	 *
	 * @since E-Commerce 1.0
	 */
	function e_commerce_content_end() {
		?>
	    </div><!-- #content -->
		<?php
	}
endif;
add_action( 'e_commerce_after_content', 'e_commerce_content_end', 10 );


if ( ! function_exists( 'e_commerce_content_end' ) ) :
	/**
	 * End div id #content and class .wrapper
	 *
	 * @since E-Commerce 1.0
	 */
	function e_commerce_content_end() {
		?>
	    </div><!-- #content -->
		<?php
	}
endif;
add_action( 'e_commerce_after_content', 'e_commerce_content_end', 10 );


if ( ! function_exists( 'e_commerce_page_end' ) ) :
	/**
	 * End div id #page
	 *
	 * @since E-Commerce 1.0
	 *
	 */
	function e_commerce_page_end() {
		?>
		</div><!-- #page -->
		<?php
	}
endif;
add_action( 'e_commerce_footer', 'e_commerce_page_end', 200 );
