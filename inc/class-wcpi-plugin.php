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
	}

	/**
	 * 
	 * @param int $post_id
	 */
	public function save_product_fields( int $post_id ) {

		$is_plugivery = isset( $_POST['_plugivery_enabled'] ) ? 'yes' : 'no';

		$plugivery_product_id = intval( $_POST['_plugivery_product_id'] );

		if ( $plugivery_product_id == 0 ) {
			update_post_meta( $post_id, '_plugivery_product_id', 0 );
		} else {
			update_post_meta( $post_id, '_plugivery_product_id', esc_attr( intval( $plugivery_product_id ) ) );
		}

		if ( $plugivery_product_id != 0 && $is_plugivery === 'yes' ) {
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

		$plugivery_enabled = get_post_meta( $post->ID, '_plugivery_enabled', true ) ? 'yes' : 'no';

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

	public function initialize() {
		self::load_options();
	}

	public function display_admin_messages() {
		echo self::display_messages( Wcpi_Core::$error_messages, Wcpi_Core::$messages );
	}

	public static function install() {
		// TODO
	}
}
