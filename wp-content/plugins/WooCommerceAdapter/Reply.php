<?php

// Autor (c) Radomir Bednar, www.platiti.cz
// Pouzivani bez souhlasu autora neni povoleno
// #Ver:PRV079-15-g0f319ea:2018-08-28#

$root = dirname( dirname( dirname( dirname( __FILE__ ) ) ) );
if ( file_exists( $root . '/wp-load.php' ) ) {
// WP 2.6
	require_once( $root . '/wp-load.php' );
} else {
// Before 2.6
	require_once( $root . '/wp-config.php' );
}

//sanitize
$uniModulName = filter_input( INPUT_GET, 'unimodul', FILTER_SANITIZE_STRING );
$adapter      = new WC_pay_uniadapter( $uniModulName );

BeginUniErr();
$languagepay      = $adapter->getlang();
$orderReplyStatus = $adapter->uniModul->gatewayReceiveReply( $languagepay );
$order_id         = $orderReplyStatus->shopOrderNumber;
$adapter->processReplyStatus( $orderReplyStatus, $order_id );

EndUniErr();
