<?php

// Autor (c) Radomir Bednar, www.platiti.cz
// Pouzivani bez souhlasu autora neni povoleno
// #Ver:PRV079-15-g0f319ea:2018-08-28#

/*
  Plugin Name: WooCommerce platiti.cz adapter
  Plugin URI: https://www.platiti.cz/
  Description: Platební brána Adapter pro WooCommerce
  Version: 1.00
  Author: platiti.cz
  Author URI: https://www.platiti.cz/
  Developer: Radomir Bednar
  Developer URI: https://www.platiti.cz/
  Tested up to: 4.9
  WC tested up to: 3.4.4
  WC requires at least: 1.6
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_action( 'plugins_loaded', 'woocommerce_pay_uni_init', 0 );

function init_uni_translation() {
	load_plugin_textdomain( 'index', false, dirname( plugin_basename( __FILE__ ) ) );
}

add_action( 'plugins_loaded', 'init_uni_translation' );

register_activation_hook( __FILE__, 'install_unimodul_transactions_db_table' );

function install_unimodul_transactions_db_table() {

	global $wpdb;
	$sql = file_get_contents( ABSPATH . "/UniModul/UniModul.sql" );
	$wpdb->query( $sql );

}

function call_calc_product( $args ) {
	if ( empty( $args['hasSubMethod'] ) ) {
		if ( is_product() ) {
			$className = $args['UniModul'];
			new $className();
		}
	}
}

function woocommerce_pay_uni_init() {

	require_once( ABSPATH . "/UniModul/UniModul.php" );

	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}

	class WC_pay_uniadapter extends WC_Payment_Gateway {

		public $uniModulName;
		protected $configInfo;
		protected $hasSubMethod;

		public function __construct( $uniModulName = null, $subMethod = null ) {

			BeginUniErr();

			if ( is_null( $uniModulName ) ) {
				return;
			}

			$this->uniModulName = $uniModulName;
			$this->hasSubMethod = $subMethod;


			//   $this->id = $this->uniModulName;

			$this->id           = empty( $subMethod ) ? mb_strtolower( $this->uniModulName . 'binder' ) : mb_strtolower( $this->uniModulName . 'binder' . $subMethod );
			$this->method_title = empty( $subMethod ) ? $this->uniModulName : $this->uniModulName . ' ' . $subMethod;
			$this->method_description = empty( $subMethod ) ? $this->uniModulName : $this->uniModulName . ' ' . $subMethod;

			$this->supports = array(
				'products',
				'subscriptions',
				'subscription_cancellation',
				'subscription_suspension',
				'subscription_reactivation',
				'subscription_amount_changes',
				'subscription_date_changes',
				'subscription_payment_method_change'
			);

			//$this->has_fields = true;

			$this->init_settings();

			//BeginUniErr();
			$uniFact = new UniModulFactory();

			$languagepay = $this->getlang();

			$this->configInfo = $uniFact->getConfigInfo( $this->uniModulName, $languagepay, $subMethod );
			$configSetting    = $this->getConfigData( $this->configInfo, $this->uniModulName );

			$this->uniModul = $uniFact->createUniModul( $this->uniModulName, $configSetting, $subMethod );
			//EndUniErr();

			$this->init_form_fields();
			$this->title = isset( $this->settings['title'] ) ? $this->settings['title'] : $this->uniModulName . $subMethod;

			//ikona pro multisite
			if ( is_multisite() ) {
				$shopBaseUrl = network_site_url() . '/';
				$this->icon  = $this->uniModul->getModulSubMethodLogoImage( $shopBaseUrl, $subMethod );
			} else {
				$shopBaseUrl = get_site_url() . '/';
				$this->icon  = $this->uniModul->getModulSubMethodLogoImage( $shopBaseUrl, $subMethod );
			}

			$this->description      = isset( $this->settings['description'] ) ? $this->settings['description'] : '';
			$this->redirect_page_id = isset( $this->settings['redirect_page_id'] ) ? $this->settings['redirect_page_id'] : '';

			//asi k nicemu
			$this->liveurl = '';

			$this->msg['message'] = "";
			$this->msg['class']   = "";

			if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
					&$this,
					'process_admin_options'
				) );
			} else {
				add_action( 'woocommerce_update_options_payment_gateways', array(
					&$this,
					'process_admin_options'
				) );
			}

			add_action( 'woocommerce_receipt_' . $this->id, array(
				&$this,
				'receipt_page'
			) );

			add_action( 'wp_head', array( $this, 'woocommerce_calc_init' ), 20 );

			add_action('woocommerce_thankyou_' . $this->id . '', array($this, 'uni_thankyou_page'));
			add_action('woocommerce_email_before_order_table', array($this, 'uni_email_instructions'), 10, 3);

			EndUniErr();
		}


		public function uni_thankyou_page($order_id)
		{
			// Get order and store in $order
			$order = wc_get_order($order_id);
			if ($this->id === $order->get_payment_method() && $order->has_status('on-hold')) {
				if (WC()->session->get('UniBankwireFioMessage')) {
					echo WC()->session->get('UniBankwireFioMessage');
				}
			}
		}

		public function uni_email_instructions($order, $sent_to_admin, $plain_text = false)
		{

			if ($this->id === $order->get_payment_method() && $order->has_status('on-hold')) {
				if (WC()->session->get('UniBankwireFioMessage')) {
					echo WC()->session->get('UniBankwireFioMessage');
				}
			}
		}




		public function woocommerce_calc_init() {

			if ( is_product() ) {
				$this->shopBaseUrl      = get_bloginfo( 'url' );
				$this->shopBaseUrl      = $this->shopBaseUrl . '/';
				$this->current_currency = get_woocommerce_currency();
				$product                = new WC_Product( get_the_ID() );
				$this->product_price    = ( WC()->version < '2.7.0' ) ? $product->price : $product->get_price();

				global $post, $product;
				$calcul = $this->embedHtml( $this->shopBaseUrl, $this->current_currency, $this->product_price );
				if ( null != $calcul ) {
					add_action( 'woocommerce_single_product_summary', array( $this, 'unimodul_show_calc' ), 11 );
				}
			}

			if ( is_checkout() ) {
				$this->product_price    = WC()->cart->total;
				$this->shopBaseUrl      = get_bloginfo( 'url' );
				$this->shopBaseUrl      = $this->shopBaseUrl . '/';
				$this->current_currency = get_woocommerce_currency();
				$calcul                 = $this->embedHtml( $this->shopBaseUrl, $this->current_currency,
					$this->product_price );
				if ( null != $calcul ) {
					add_action( 'woocommerce_review_order_before_payment', array( $this, 'unimodul_show_calc' ), 11 );
				}
			}
		}

		public function unimodul_show_calc() {

			echo $this->embedHtml( $this->shopBaseUrl, $this->current_currency, $this->product_price );

		}

		public function embedHtml( $shopBaseUrl, $current_currency, $product_price ) {

			$embedHtml = $this->uniModul->ProductGetInstallmentEmbedHtml( $shopBaseUrl, $current_currency,
				$product_price );

			return $embedHtml;
		}

		public function getlang() {

			$languagepay = explode( "-", get_bloginfo( 'language' ) );
			$languagepay = $languagepay[0];

			if ( function_exists( 'icl_object_id' ) ) {
				global $sitepress;
				if ( isset( $sitepress ) ) { // avoids a fatal error with Polylang
					$languagepay = strtoupper( $sitepress->get_current_language() );
					if ( strpos( $languagepay, 'CS' ) ) {
						$languagepay = 'CZ';
					}
				}
			}

			return strtolower( $languagepay );
		}


		//pomocna funkce
		public function getConfigData( $configInfo, $uniModulName ) {

			global $wpdb;

			$binder_settings = get_option( $this->plugin_id . $this->uniModulName . 'binder_settings', null );

			$uniModulNameConfig                     = new UniModulConfig();
			$uniModulNameConfig->mysql_server       = $wpdb->dbhost;
			$uniModulNameConfig->mysql_dbname       = $wpdb->dbname;
			$uniModulNameConfig->mysql_login        = $wpdb->dbuser;
			$uniModulNameConfig->mysql_password     = $wpdb->dbpassword;
			$uniModulNameConfig->uniModulDirUrl     = get_site_url() . '/UniModul/';
			$uniModulNameConfig->funcGetCallbackUrl = array( $this, 'getCallbackUrl' );
			$uniModulNameConfig->adapterName        = 'WooCommerce';
			$uniModulNameConfig->funcProcessReplyStatus = array( $this, 'funcProcessReplyStatus');

			$configArray = array();

			foreach ( $configInfo->configFields as $configField ) {
				$configArray[ $configField->name ] = isset( $binder_settings[ $configField->name ] ) ? $binder_settings[ $configField->name ] : '';
			}

			if ( isset( $configArray['subMethodsSelection'] ) && is_array( $configArray['subMethodsSelection'] ) ) {
				$configArray['subMethodsSelection'] = implode( ' ', $configArray['subMethodsSelection'] );
			}

			$cfgs = create_initialize_object( 'ConfigSetting', array(
				'configData'     => $configArray,
				'uniModulConfig' => $uniModulNameConfig
			) );

			return $cfgs;
		}

		public function funcProcessReplyStatus($orderReplyStatus) {

			$order_id = $orderReplyStatus->shopOrderNumber;
			$this->processReplyStatus($orderReplyStatus, $order_id, false);

		}

		public function getCallbackUrl( $callbackName, $arguments ) {

			return plugins_url( 'Callback.php?unimodul=' . $this->uniModulName . "&_callbackName=" . urlencode( $callbackName ) . "&" . http_build_query( $arguments ),
				__FILE__ );

		}


		public function init_form_fields() {

			BeginUniErr();

			$arrayfields = array();

			if ( $this->uniModul->subMethod == null ) {
				foreach ( $this->configInfo->configFields as $configFields ) {
					$typ = null;

					switch ( $configFields->type ) {
						case ConfigFieldType::$text:
							$typ = 'text';
							break;
						case ConfigFieldType::$choice:
							$typ = 'select';
							break;
					}

					if ( ! is_null( $typ ) ) {

						$arrayfields[ $configFields->name ] = array(
							'title' => $configFields->label,
							'type'  => $typ
						);

						if ( ! empty( $configFields->choiceItems ) ) {
							$arrayfields[ $configFields->name ]['options'] = $configFields->choiceItems;
						}
					}
				}
			}

			$arrayfields['enabled'] = array(
				'title'   => __( 'Enable/Disable', 'index' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable:', 'index' ),
				'default' => 'no'
			);

			$arrayfields['title'] = array(
				'title'       => __( 'Title:', 'index' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'index' ),
				'default'     => $this->uniModul->getModuleSubMethodName( $this->getlang() )
			);

			$arrayfields['description'] = array(
				'title'       => __( 'Description:', 'index' ),
				'type'        => 'textarea',
				'description' => __( '', 'index' ),
				'default'     => __( '', 'index' )
			);

			if ( version_compare( WOOCOMMERCE_VERSION, '2.1.0', '<' ) ) {

				$arrayfields['redirect_page_id'] = array(
					'title'       => __( 'Return Page', 'index' ),
					'type'        => 'select',
					'options'     => $this->get_pages( 'Select Page' ),
					'description' => __( "URL of success page", 'index' )
				);
			}
			$this->form_fields = $arrayfields;

			EndUniErr();
		}

		public function admin_options() {

			BeginUniErr();

			$html  = '<div class="woocommerce-message"><h3>&copy; platiti.cz WooCommerce ' . $this->uniModul->getModuleSubMethodName( $this->getlang() ) . '</h3>';
			$html .= '<p><a href="platiti.cz">' . __( '&copy; platiti.cz', 'index' ) . '</a></p>';
			$html .= '<p class=""><a href="http://www.platiti.cz/cart.php?action=add&amp;id=WooCommerce_'.$this->uniModulName.'_Modul_jeninst" class="button-primary">' . __( 'Doobjednat instalaci a nastavení modulu od platiti.cz', 'index' ) . '</a></p></div>';
			$html .= '<table class="form-table">';

			//  formular
			ob_start();
			$this->generate_settings_html();
			$html .= ob_get_contents();
			ob_end_clean();

			$html .= '</table>';

			echo EndUniErr( $html );
		}

		/**
		 *  popis.
		 * */
		public function payment_fields() {
			if ( $this->description ) {
				echo wpautop( wptexturize( $this->description ) );
			}

			// submetody
			/*
			  if (isset($this->settings['subMethodsSelection']) && is_array($this->settings['subMethodsSelection'])) {

			  $submethods_html = '';

			  foreach ($this->settings['subMethodsSelection'] as $key => $submethod) {
			  $submethods_html .= '<label><input type="radio" name="' . $this->uniModulName . '" value="' . $this->uniModulName . "#" . $submethod . '"' . (0 == $key ? ' checked="checked"' : '') . '>' . $this->uniModulName . ' ' . $submethod . '</label>';
			  }

			  echo $submethods_html;
			  }
			 */
		}

		/**
		 * Receipt Page
		 * */
		public function receipt_page( $order ) {

			$order     = new WC_Order( $order );
			$subMethod = $this->uniModulName;
			$this->getOrderToPayInfo( $subMethod, $order );
			$this->process_save_order();
		}

		/**
		 * Generate uni button link - historicky neni potreba
		 * */
		public function generate_uni_form( $order_id ) {

			BeginUniErr();

			$order = new WC_Order( $order_id );

			$subMethod = isset( $_GET[ $this->uniModulName ] ) && ! empty( $_GET[ $this->uniModulName ] ) ? $_GET[ $this->uniModulName ] : '';

			$this->getOrderToPayInfo( $subMethod, $order );

			if ( isset( $_POST["PayConfirm"] ) ) {
				$this->process_save_order();
			}

			if ( version_compare( WOOCOMMERCE_VERSION, '2.1.0', '>' ) ) {
				wc_enqueue_js( '
              $.blockUI({
              message: "' . esc_js( __( 'Thank you for your order. We are now redirecting you to make payment.',
						'woocommerce' ) ) . '",
              baseZ: 99999,
              overlayCSS:
              {
              background: "#fff",
              opacity: 0.6
              },
              css: {
              padding:        "20px",
              zindex:         "9999999",
              textAlign:      "center",
              color:          "#555",
              border:         "3px solid #aaa",
              backgroundColor:"#fff",
              cursor:         "wait",
              lineHeight:		"24px",
              }
              });
              jQuery("#submit_uni_payment_form").click();
              ' );
			}

			$return = '<span style="background:url(\'https://www.platiti.cz/muzo/minilogo.png\')"></span>
						<form action="" method="post" id="uni_payment_form">
                            <input type="hidden" name="PayConfirm" value="1">
                            <input type="submit" name="sub" class="button alt" id="submit_uni_payment_form" value="' . __( 'Place order',
					'index' ) . '" />
                            <a class="button cancel" href="' . $order->get_cancel_order_url() . '">' . __( 'Cancel order &amp; restore cart',
					'index' ) . '</a>
                       </form>';


			return EndUniErr( $return );
		}

		public function getOrderToPayInfo( $subMethod = null, $order ) {

			global $woocommerce;

			if ( ! isset( $order ) ) {
				$order = new stdClass();
			}

			$order_id = ( WC()->version < '2.7.0' ) ? $order->id : $order->get_id();

			$txnid = $order_id . '_' . date( "ymds" );

			$productinfo = "Order $order_id";

			$customerData              = new CustomerData();
			$customerData->email       = ( WC()->version < '2.7.0' ) ? $order->billing_email : $order->get_billing_email();
			$customerData->first_name  = ( WC()->version < '2.7.0' ) ? $order->billing_first_name : $order->get_billing_first_name();
			$customerData->last_name   = ( WC()->version < '2.7.0' ) ? $order->billing_last_name : $order->get_billing_last_name();
			$customerData->phone       = ( WC()->version < '2.7.0' ) ? $order->billing_phone : $order->get_billing_phone();
			$customerData->street      = ( WC()->version < '2.7.0' ) ? $order->billing_address_1 : $order->get_billing_address_1();
			$customerData->houseNumber = ( WC()->version < '2.7.0' ) ? $order->billing_address_2 : $order->get_billing_address_2();
			$customerData->city        = ( WC()->version < '2.7.0' ) ? $order->billing_city : $order->get_billing_city();
			$customerData->post_code   = ( WC()->version < '2.7.0' ) ? $order->billing_postcode : $order->get_billing_postcode();
			$customerData->state       = ( WC()->version < '2.7.0' ) ? $order->billing_state : $order->get_billing_state();
			$customerData->country     = ( WC()->version < '2.7.0' ) ? $order->billing_country : $order->get_billing_country();

			if ( is_user_logged_in() ) {
				$customerData->identifier = ( WC()->version < '2.7.0' ) ? $order->user_id : $order->get_user_id();
			} else {
				$customerData->identifier = null;
			}

			global $orderToPayInfo; // pro renderSubMethods

			$orderToPayInfo                  = new OrderToPayInfo();
			$orderToPayInfo->subMethod       = null;
			$orderToPayInfo->shopOrderNumber = $order_id;
			$orderToPayInfo->shopPairingInfo = 'pair-' . $order_id;
			$orderToPayInfo->amount          = $order->get_total();
			$orderToPayInfo->currency        = get_woocommerce_currency();
			$orderToPayInfo->language        = $this->getlang();
			//$orderToPayInfo->description = 'description';
			$orderToPayInfo->customerData = $customerData;
			$plugin_dir_path              = dirname( __FILE__ );

			// description naplnim seznamem kupovanych produktů
			$product_in_cart = array();


			if ( sizeof( $order->get_items() ) > 0 ) :
				$cartItems = array();
				foreach ( $order->get_items() as $item ) :
					$_product = new WC_Product( $item['product_id'] );
					if ( $_product->exists() && $item['qty'] > 0 ) :

						//cena s dani
						$tax_item   = $item['line_tax'] / $item['qty'];
						$price_tax  = $item['line_total'] / $item['qty'];
						$price_item = $tax_item + $price_tax;

						$cartItem            = new UniCartItem();
						$cartItem->name      = $item['name'];
						$cartItem->quantity  = $item['qty'];
						$cartItem->unitPrice = $price_item;

						$rate              = WC_Tax::get_rates( $item['tax_class'] );
						$rate              = reset( $rate );
						$cartItem->taxRate = round( $rate['rate'] );
						$cartItems[]       = $cartItem;
						$product_title     = $item['name'];
						//$product_subtotal = apply_filters( 'woocommerce_checkout_item_subtotal', $woocommerce->cart->get_product_subtotal( $_product, $values['quantity'] ), $values, $cart_item_key );
						$product_in_cart[] = (int) $item['qty'] . 'x ' . strip_tags( $product_title );
					endif;
				endforeach;


				if ( $order->get_total_shipping() > 0 ) {
					$cartItem             = new UniCartItem();
					$total_shipping_price = ( $order->get_total_shipping() + $order->get_shipping_tax() );
					$cartItem->name       = $order->get_shipping_method();
					$cartItem->quantity   = 1;
					$cartItem->unitPrice  = $total_shipping_price;
					$rate                 = WC_Tax::get_shipping_tax_rates();
					$rate                 = reset( $rate );
					$cartItem->taxRate    = round( $rate['rate'] );
					$cartItems[]          = $cartItem;
				}
				$orderToPayInfo->cartItems = $cartItems;
			endif;


			$orderToPayInfo->description    = get_bloginfo( 'name' );
			$orderToPayInfo->replyUrl       = plugins_url( 'Reply.php?unimodul=' . $this->uniModulName, __FILE__ );
			$orderToPayInfo->notifyUrl      = plugins_url( 'Notify.php?unimodul=' . $this->uniModulName, __FILE__ );
			$orderToPayInfo->uniModulDirUrl = site_url() . '/UniModul/';

			$orderToPayInfo->currencyRates = array();

			return $orderToPayInfo;
		}

		/**
		 * Process the payment and return the result
		 * */
		public function process_payment( $order_id ) {

			$order = new WC_Order( $order_id );

			if ( version_compare( WOOCOMMERCE_VERSION, '2.1.0', '>' ) ) {

				if ( method_exists( $order, 'get_order_key' ) ) {
					$vars = array(
						//  'order' => $order->id,
						'key' => $order->get_order_key()
					);
				}else{
					$vars = array(
						'key' => $order->order_key
					);
				}

			} else {
				$vars = array(
					'order' => $order->id,
					'key'   => $order->order_key
				);
			}

			$payment_method = $_POST['payment_method'];
			$needle         = strpos( $payment_method, '#' );

			if ( false !== $needle ) {
				$vars[ $this->uniModulName ] = substr( $payment_method, $needle + 1,
					strlen( $payment_method ) - $needle );
			} else {
				$vars[ $this->uniModulName ] = $payment_method;
			}

			if ( version_compare( WOOCOMMERCE_VERSION, '2.1.0', '<=' ) ) {
				$return = array(
					'result'   => 'success',
					'redirect' => add_query_arg( $vars, get_permalink( get_option( 'woocommerce_pay_page_id' ) ) )
				);
			} else {
				$return = array(
					'result'   => 'success',
					'redirect' => add_query_arg( $vars, $order->get_checkout_payment_url( true ) )
				);
			}

			return $return;
		}

		/**
		 * Process save to database UniModul
		 * */
		public function process_save_order() {

			global $orderToPayInfo;

			BeginUniErr();
			$prePayGWInfo   = $this->uniModul->queryPrePayGWInfo( $orderToPayInfo );
			$redirectAction = $this->uniModul->gatewayOrderRedirectAction( $orderToPayInfo );

			if ( $redirectAction->orderReplyStatus != null ) {   // okamzita odpoved muze byt i zaroven s redirektem - napr. Cetelem
				$frontend_redir = $redirectAction->redirectUrl == null && $redirectAction->redirectForm == null;
				$this->processReplyStatus( $redirectAction->orderReplyStatus, $orderToPayInfo->shopOrderNumber,
					$frontend_redir );
			}
			if ( $redirectAction->inlineForm != null ) {
				echo( $redirectAction->inlineForm );
			} else if ( $redirectAction->redirectForm != null ) {
				$this->uniModul->formRedirect( $redirectAction->redirectForm );
			} else if ( $redirectAction->redirectUrl != null ) {
				header( 'Location: ' . $redirectAction->redirectUrl );
				ResetUniErr();
				exit();
			}
			EndUniErr();
		}

		public function showMessage() {
			return '<div class="box ' . $this->msg['class'] . '-box">' . $this->msg['message'] . '</div>' . $content;
		}

		/**
		 * zpracovani platby
		 *
		 * @global type $woocommerce
		 *
		 * @param type $orderReplyStatus
		 * @param type $order_id
		 * @param boolean $redirect
		 */
		public function processReplyStatus( $orderReplyStatus, $order_id, $redirect = true ) {
			global $woocommerce;

			$order = new WC_Order( $order_id );

			BeginSynchronized();

			$order_total = $order->get_total();


			$order_id = ( WC()->version < '2.7.0' ) ? $order->id : $order->get_id();

			if ( $orderReplyStatus->orderStatus == OrderStatus::$successful ) {

				$this->uniModul->updateShopOrderNumber( $orderReplyStatus, $order_id );
				$transauthorised    = true;
				$this->msg['class'] = 'woocommerce_message';
				$order_note         = sprintf( __( 'uni payment successful<br/>Unnique Id from uni: %s', 'index' ),
					$orderReplyStatus->gwOrderNumber );
				$woocommerce->cart->empty_cart();
				$this->uniModul->logger->writeLog( "ADAPTER: Creating shop order number " . $order_id . ", new order state processing" );
				$order->payment_complete();

			} else if ( $orderReplyStatus->orderStatus == OrderStatus::$pending ) {

				$this->uniModul->updateShopOrderNumber( $orderReplyStatus, $order_id );
				BeginUniErr( E_UNIERR_DEFAULT & ~E_NOTICE );
				$order->update_status( 'awaiting-payment' );
				EndUniErr();
				$this->msg['message'] = __( "Thank you for shopping with us. Right now your payment staus is pending, We will keep you posted regarding the status of your order through e-mail",
					'index' );
				$this->msg['class']   = 'woocommerce_message woocommerce_message_info';
				BeginUniErr( E_UNIERR_DEFAULT & ~E_NOTICE );
				$this->uniModul->logger->writeLog( "ADAPTER: Creating shop order number " . $order_id . ", new order state on hold" );
				$order_note         =  "Creating shop order number " . $order_id . ", new order state on hold";

				if ($orderReplyStatus->successHtml) {
					WC()->session->set('UniBankwireFioMessage', $orderReplyStatus->successHtml);
				}

				$order->update_status( 'on-hold' );
				EndUniErr();

			} else if ( $orderReplyStatus->orderStatus == OrderStatus::$failedFinal ) {

				$this->uniModul->updateShopOrderNumber( $orderReplyStatus, $order_id );
				$this->msg['class']   = 'woocommerce_error';
				$this->msg['message'] = __( "Thank you for shopping with us. However, the transaction has been declined.",
					'index' );
				$order_note           = sprintf( __( 'Failed - Transaction Declined: <br/>Unnique Id from uni: %s',
					'index' ), $orderReplyStatus->gwOrderNumber );
				BeginUniErr( E_UNIERR_DEFAULT & ~E_NOTICE );
				$order->update_status( 'failed' );
				$this->uniModul->logger->writeLog( "ADAPTER: Creating shop order number " . $order_id . ", new order state on failed" );
				EndUniErr();

			} else if ( $orderReplyStatus->orderStatus == OrderStatus::$invalidReply ) {
				$this->uniModul->updateShopOrderNumber( $orderReplyStatus, $order_id );
				$this->uniModul->logger->writeLog( "Reply" );
			} else {
				$this->uniModul->updateShopOrderNumber( $orderReplyStatus, $order_id );
				$this->msg['class']   = 'error';
				$this->msg['message'] = __( "Security Error. Illegal access detected", 'index' );
			}

			if ( $orderReplyStatus->orderStatus == OrderStatus::$failedRetriable ) {
				BeginUniErr( E_UNIERR_DEFAULT & ~E_NOTICE );
				$order_note = sprintf( __( 'Failed' ) );
				$order->update_status( 'failed' );
				EndUniErr();
			}

			EndSynchronized();


			if ( true === $redirect ) {

				$order->add_order_note( $order_note );
				$location = $this->get_return_url( $order );
				//WPML
				if ( function_exists( 'icl_object_id' ) ) {
					global $sitepress;
					if ( isset( $sitepress ) ) { // avoids a fatal error with Polylang
						$meta      = get_post_meta( $order->post->ID );
						$wpml_lang = $meta['wpml_language'][0];
						$sitepress->switch_lang( $wpml_lang );
						$location = $this->get_return_url( $order );
					}
				}
				//order canceled
				/*if ($orderReplyStatus->orderStatus == OrderStatus::$failedRetriable) {
					$location = $order->get_cancel_order_url_raw();
				}*/

				ResetUniErr();
				wp_safe_redirect( $location );
				exit();
			}
		}

		// vsechny stranky z woocomerce - vyber navratove stranky
		public function get_pages( $title = false, $indent = true ) {
			$wp_pages  = get_pages( 'sort_column=menu_order' );
			$page_list = array();
			if ( $title ) {
				$page_list[] = $title;
			}
			foreach ( $wp_pages as $page ) {
				$prefix = '';

				if ( $indent ) {
					$has_parent = $page->post_parent;
					while ( $has_parent ) {
						$prefix     .= ' - ';
						$next_page  = get_page( $has_parent );
						$has_parent = $next_page->post_parent;
					}
				}

				$page_list[ $page->ID ] = $prefix . $page->post_title;
			}

			return $page_list;
		}
	}
}