<?php
/**
 * Sample implementation of the Custom Header feature
 * http://codex.wordpress.org/Custom_Headers
 *
 * @package Catch Themes
 * @subpackage E-Commerce Pro
 * @since E-Commerce 1.0
 */

/**
 * Set up the WordPress core custom header feature.
 *
 * @uses e_commerce_header_style()
 * @uses e_commerce_admin_header_style()
 * @uses e_commerce_admin_header_image()
 */
function e_commerce_custom_header_setup() {
	add_theme_support( 'custom-header', apply_filters( 'e_commerce_custom_header_args', array(
	    'default-image'          => '%s/images/default-image.jpg',
		'default-text-color'     => '#242424',
		'width'                  => 1440,
		'height'                 => 618,
		'flex-height'            => true,
		'wp-head-callback'       => 'e_commerce_header_style',
		'admin-head-callback'    => 'e_commerce_admin_header_style',
		'admin-preview-callback' => 'e_commerce_admin_header_image',
	) ) );
}
add_action( 'after_setup_theme', 'e_commerce_custom_header_setup' );

if ( ! function_exists( 'e_commerce_header_style' ) ) :
/**
 * Styles the header image and text displayed on the blog
 *
 * @see e_commerce_custom_header_setup().
 */
	function e_commerce_header_style() {
		$header_text_color = get_header_textcolor();

		// If no custom options for text are set, let's bail
		// get_header_textcolor() options: get_theme_support( 'custom-header', 'default-text-color' ) is default, hide text (returns 'blank') or any hex value
		if ( get_theme_support( 'custom-header', 'default-text-color' ) === $header_text_color ) {
			return;
		}

		// If we get this far, we have custom styles. Let's do this.
		?>
		<style type="text/css">
		<?php
			// Has the text been hidden?
			if ( 'blank' == $header_text_color ) :
		?>
			.site-header .site-title,
			.site-header .site-description {
				position: absolute;
				clip: rect(1px, 1px, 1px, 1px);
			}
		<?php
			// If the user has set a custom color for the text use that
			else :
		?>
			.site-branding .site-header {
				color: #<?php echo esc_attr( $header_text_color ); ?>;
			}
		<?php endif; ?>
		</style>
		<?php
	}
endif; // e_commerce_header_style

if ( ! function_exists( 'e_commerce_admin_header_style' ) ) :
/**
 * Styles the header image displayed on the Appearance > Header admin panel.
 *
 * @see e_commerce_custom_header_setup().
 */
function e_commerce_admin_header_style() {
?>
	<style type="text/css">
		.appearance_page_custom-header #headimg {
			border: none;
		}
		#headimg h1,
		#desc {
		}
		#headimg h1 {
		}
		#headimg h1 a {
		}
		#desc {
		}
		#headimg img {
		}
	</style>
<?php
}
endif; // e_commerce_admin_header_style

if ( ! function_exists( 'e_commerce_admin_header_image' ) ) :
/**
 * Custom header image markup displayed on the Appearance > Header admin panel.
 *
 * @see e_commerce_custom_header_setup().
 */
function e_commerce_admin_header_image() {
	$style = sprintf( ' style="color:#%s;"', get_header_textcolor() );
?>
	<div id="headimg">
		<h1 class="displaying-header-text"><a id="name"<?php echo $style; ?> onclick="return false;" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a></h1>
		<div class="displaying-header-text" id="desc"<?php echo $style; ?>><?php bloginfo( 'description' ); ?></div>
		<?php if ( get_header_image() ) : ?>
		<img src="<?php header_image(); ?>" alt="">
		<?php endif; ?>
	</div>
<?php
}
endif; // e_commerce_admin_header_image


if ( ! function_exists( 'e_commerce_featured_image' ) ) :
	/**
	 * Template for Featured Header Image from theme options
	 *
	 * To override this in a child theme
	 * simply create your own e_commerce_featured_image(), and that function will be used instead.
	 *
	 * @since E-Commerce 1.0
	 */
	function e_commerce_featured_image() {
		//Support Random Header Image
		if ( is_random_header_image() ) {
			delete_transient( 'e_commerce_featured_image' );
		}

		if ( ! $e_commerce_featured_image = get_transient( 'e_commerce_featured_image' ) ) {
			echo '<!-- refreshing cache -->';

			$header_image = get_header_image();
			$title        =	get_bloginfo( 'name', 'display' );

			if ( '' != $header_image ) {

				// Header Image
				$feat_image = '<img class="wp-post-image" alt="' . esc_attr( $title ) . '" src="' . esc_url(  $header_image ) . '" />';

				$e_commerce_featured_image = '<div id="header-featured-image" class="site-header-image">
					<div class="wrapper">
						<a href="' . esc_url( home_url( '/' ) ) . '" rel="home">' . $feat_image . '</a>
					</div><!-- .wrapper -->
				</div><!-- #header-featured-image -->';
			}

			set_transient( 'e_commerce_featured_image', $e_commerce_featured_image, 86940 );
		}

		echo $e_commerce_featured_image;
	} // e_commerce_featured_image
endif;


if ( ! function_exists( 'e_commerce_featured_image_display' ) ) :
	/**
	 * Display Featured Header Image
	 *
	 * @since E-Commerce 1.0
	 */
	function e_commerce_featured_image_display() {
		e_commerce_featured_image();
	} // e_commerce_featured_image_display
endif;
add_action( 'e_commerce_after_header', 'e_commerce_featured_image_display', 30 );
