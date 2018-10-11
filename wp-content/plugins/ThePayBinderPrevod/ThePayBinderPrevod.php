<?php
// Autor (c) Radomir Bednar, www.platiti.cz
// Pouzivani bez souhlasu autora neni povoleno
// #Ver:PRV079-15-g0f319ea:2018-08-28#

/*
  Plugin Name: WooCommerce ThePay Prevod
  Plugin URI: www.platiti.cz
  Description: uni Payment gateway for woocommerce
  Version: 1.00
  Author: platiti.cz
  Author URI: www.platiti.cz
 */


add_action( 'plugins_loaded', 'woocommerce_thepayprevod_init', 10 );

function woocommerce_thepayprevod_init(){
    if ( class_exists( 'WC_pay_uniadapter' ) ) {

	    class ThePayBinderPrevod extends WC_pay_uniadapter {

		    public
		    function __construct() {

			    //   $this->icon  =  get_bloginfo('url')."/UniModul/Uni".$uniModul->name."Logo.png";

			    parent::__construct( 'ThePay', 'Prevod' );
		    }

	    }

        function woocommerce_add_thepayprevod_gateway($methods){
	    $methods[] = 'ThePayBinderPrevod';
            return $methods;
        }

        add_filter( 'woocommerce_payment_gateways', 'woocommerce_add_thepayprevod_gateway' );
    }
}

if ( ! function_exists( "call_calc_action" ) ) {
	add_action( 'wp_head', 'call_calc_action', 10 );
	function call_calc_action() {
		$args = array(
			'UniModul'     => 'ThePayBinder',
			'hasSubMethod' => 'Prevod'
		);
		call_calc_product( $args );
	}
}

?>