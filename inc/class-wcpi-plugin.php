<?php

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Basic class that contains common functions,
 * such as:
 * - installation / deinstallation
 * - meta & options management,
 * - adding pages to menu
 * etc
 */
class Wcpi_Plugin extends Wcpi_Core {

	const CHECK_RESULT_OK = 'ok';

	public function __construct( $plugin_root ) {

		Wcpi_Core::$plugin_root = $plugin_root;

		add_action( 'plugins_loaded', array($this, 'initialize'), 10 );

		if ( is_admin() ) {
			//add_action( 'admin_enqueue_scripts', array($this, 'register_admin_styles_and_scripts') );
		}

		add_action( 'admin_menu', array( 'Wcpi_Settings', 'add_page_to_menu' ) );

		add_action( 'admin_notices', array( $this, 'display_admin_messages' ) );

		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'add_product_fields' ) );

		add_action( 'woocommerce_process_product_meta', array( $this, 'save_product_fields' ) );
		
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'update_order_meta_on_checkout' ), 10, 2 );
		
		//add_action( 'woocommerce_order_status_completed', array( $this, 'process_plugivery_products_in_order' ), 2, 1 );
		
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'process_plugivery_products_in_order' ), 2, 3 );
		
		
		//add_filter( 'woocommerce_email_classes', array( $this, 'register_plugivery_order_email' ) );
	}

	/**
	 * Save plugivery status for order in checkout.
	 * 
	 * @param int $order_id
	 * @param bool $posted
	 */
	public function update_order_meta_on_checkout( $order_id, $posted ) {
		$order = wc_get_order( $order_id );

		if ( is_a( $order, 'WC_Order' ) ) {

			$items = $order->get_items();

			$has_plugivery_products = false;

			foreach ( $items as $key => $item ) {

				if ( get_post_meta( $item['product_id'], '_plugivery_enabled', true ) == 1 ) {
					$has_plugivery_products = true;
					break;
				}
			}

			if ( $has_plugivery_products ) {
				$order->update_meta_data( 'plugivery_order_type', 1 );
			} else {
				$order->update_meta_data( 'plugivery_order_type', 0 );
			}

			$order->save();
		}
	}
	
	/**
	 * Register our custom email-sending class.
	 * 
	 * @param array $classes
	 * @return array
	 */
	public function register_plugivery_order_email( $classes ) {
    include( __DIR__ . '/class-wcpi-email.php');

		if ( class_exists( 'Wcpi_Order_Completed_Email' ) ) {
			$classes['Wcpi_Order_Completed_Email'] = new Wcpi_Order_Completed_Email();
		}
		
    return $classes;
	}


	public static function process_plugivery_products_in_order( $order_id, $posted_data, $order ) {
		
		$updated_products_count = 0;
		
		$plugivery_enabled = self::is_plugivery_enabled();
		$token = self::get_plugivery_token();
		// $order = wc_get_order($order_id);
		
		if ( $plugivery_enabled && $token && is_a( $order, 'WC_Order' ) ) {
			$order_items = $order->get_items();

			foreach ( $order_items as $item ) {
				$updated_products_count += self::update_plugivery_product_in_order( $item, $token );
			}
		}
		
		return $updated_products_count;
	}
	
	
	public static function update_plugivery_product_in_order( $item, $token ) {
		
		$result = 0;
		
		$product_id = $item['product_id'];

		if ( get_post_meta( $product_id, '_plugivery_enabled', true ) && ! isset( $item['item_meta']['_plugivery'] ) ) {
			$plugivery_data = self::get_plugivery_product_data( $token, $product_id );

			if ( is_array( $plugivery_data ) ) {

				// $download_link = '<a href="' . $plugivery_data['redeem_url'] . '">Download Here</a>';
				$download_link = $plugivery_data['redeem_url'];
				
				$item->add_meta_data('_plugivery', $plugivery_data, true );
				$item->add_meta_data('Redeem code', $plugivery_data['redeem_code'], true );
				$item->add_meta_data('Download link', $download_link, true );
				$item->save();
				
				$result = 1;
			}
		}
		
		return $result;
	}
	
	/**
	 * 
	 * @param int $post_id
	 */
	public function save_product_fields( int $post_id ) {

		$is_plugivery = isset( $_POST['_plugivery_enabled'] );

		$plugivery_product_id = intval( $_POST['_plugivery_product_id'] );

		if ( $plugivery_product_id == 0 ) {
			update_post_meta( $post_id, '_plugivery_product_id', 0 );
		} else {
			update_post_meta( $post_id, '_plugivery_product_id', esc_attr( intval( $plugivery_product_id ) ) );
		}

		if ( $plugivery_product_id != 0 && $is_plugivery ) {
			update_post_meta( $post_id, '_plugivery_enabled', 1 );
		} else {
			update_post_meta( $post_id, '_plugivery_enabled', 0 );
		}

		$plugivery_coupon = $_POST['_plugivery_coupon'];
		update_post_meta( $post_id, '_plugivery_coupon', esc_attr( $plugivery_coupon ) );

	}

	public function add_product_fields() {
		global $post;
		echo '<div class="options_group">';

		$plugivery_enabled = get_post_meta( $post->ID, '_plugivery_enabled', true ) ? 1 : 0;

		woocommerce_wp_checkbox(
			array(
				'id' => '_plugivery_enabled',
				'label' => __( 'Plugivery Product', WCPI_TEXT_DOMAIN ),
				'desc_tip' => 'true',
				'value' => $plugivery_enabled,
				'description' => __( 'Check this field for the plugivery product', WCPI_TEXT_DOMAIN )
		) );

		$plugivery_product_id = get_post_meta( $post->ID, '_plugivery_product_id', true );

		woocommerce_wp_text_input(
			array(
				'id' => '_plugivery_product_id',
				'label' => __( 'Plugivery Product Id', WCPI_TEXT_DOMAIN ),
				'placeholder' => '',
				'description' => __( 'Enter the plugivery product id', WCPI_TEXT_DOMAIN ),
				'type' => 'number',
				'value' => $plugivery_product_id ?: 0,
				'custom_attributes' => array(
					'step' => 'any',
					'min' => '0'
				)
			)
		);

		$plugivery_coupon = get_post_meta( $post->ID, '_plugivery_coupon', true );

		woocommerce_wp_text_input(
			array(
				'id' => '_plugivery_coupon',
				'label' => __( 'Plugivery Coupon', WCPI_TEXT_DOMAIN ),
				'placeholder' => '',
				'description' => __( 'Enter the plugivery coupon code', WCPI_TEXT_DOMAIN ),
				'type' => 'text',
				'value' => $plugivery_coupon ?: '',
			)
		);

		echo '</div>';
	}

	/**
	 * Returns the current status of Plugivery integration
	 * @return bool
	 */
	public static function is_plugivery_enabled() {
		
		$stored_options = get_option( self::OPTIONS_NAME, array() );
		
		$enabled = $stored_options['plugivery_enabled'] ?? false;
		
		return $enabled;
	}
	
	/**
	 * Returns the current token for Plugivery integration
	 * @return string
	 */
	public static function get_plugivery_token() {
	
		$stored_options = get_option( self::OPTIONS_NAME, array() );
		
		$token = $stored_options['plugivery_api_key'] ?? false;
		
		return $token;
	}
	
	public static function get_plugivery_product_data( $token, $product_id ) {
		
		$plugivery_product_id              = intval( get_post_meta( $product_id, '_plugivery_product_id', true ) );
		$plugivery_enabled_for_product     = get_post_meta( $product_id, '_plugivery_enabled', true );
		$plugivery_coupon                  = get_post_meta( $product_id, '_plugivery_coupon', true );
		
		//self::wc_log( 'check for get_plugivery_product_data', [ $token, $product_id, $plugivery_enabled_for_product, $plugivery_coupon, $plugivery_product_id ] );
		
		if ( $token && $plugivery_product_id > 0 && $plugivery_enabled_for_product ) {
			
			$plugivery_data = self::request_api( $token, $plugivery_product_id, $plugivery_coupon );
			
			return $plugivery_data;
		}
		
		return false;
	}
	
	/**
	 * 
	 * @param string $token
	 * @param int $plugivery_product_id
	 * @param string $plugivery_coupon
	 * @return array or false
	 */
	public static function request_api( $token, $plugivery_product_id, $plugivery_coupon = '') {
		
		$plugivery_result = false;
		$all_ok = false;
		
		$parameters = array(
			'act'      => 'buy',
			'token'    => $token,
			'pid'      => $plugivery_product_id,
			'qty'      => 1,
			'coup'     => $plugivery_coupon,
			'mid'      => 0,
			'via'      => 'credit',
			'print'    => 2
		);
		
		$parameter_string = http_build_query( $parameters );

		$api_request_url = 'https://api.plugivery.com/orders/?' . $parameter_string;
		
		$ch = curl_init();
		
		
		//self::wc_log( 'check for request_api', [ $parameters, $api_request_url ] );
		
		
		curl_setopt( $ch, CURLOPT_URL, $api_request_url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		
		$data = curl_exec( $ch );
		
		$result = json_decode( $data, true );

		if ( is_array( $result ) ) {
			
			if ( isset( $result['error'] ) && $result['error'] == 0 ) { // All ok
	
				$response = (array) $result['data'][0];

				if ( isset($response['redeem_code']) && isset($response['redeem_url']) ) {

					$all_ok = true;
					$plugivery_result = [
						'redeem_code' => $response['redeem_code'],
						'redeem_url' => $response['redeem_url'],
					];
				}
			}
		}
		else {
			self::wc_log('Failed to get a response from Plugivery data: ' . print_r( $data, true ) );
		}
		
		if ( ! $all_ok ) {
			self::wc_log('Received error response from Plugivery' . print_r( $result, true ) );
		}
		
		return $plugivery_result;
	}
	
	public function initialize() {
		self::load_options();
	}

	public function display_admin_messages() {
		echo self::display_messages( Wcpi_Core::$error_messages, Wcpi_Core::$messages );
	}

	public static function install() {
		// nothing to do yet
	}
}
