<?php
/**
 * The template for displaying the footer.
 *
 * Contains the closing of the #content div and all content after
 *
 * @package E-Commerce
 */

/** 
 * e_commerce_after_content hook
 *
 * @hooked e_commerce_content_end - 10
 *
 */
do_action( 'e_commerce_after_content' ); 


/** 
 * e_commerce_footer hook
 * 
 * @hooked e_commerce_page_end - 200
 *
 */
do_action( 'e_commerce_footer' );

/** 
 * e_commerce_after hook
 *
 */
do_action( 'e_commerce_after' );?>
<?php wp_footer(); ?>

</body>
</html>