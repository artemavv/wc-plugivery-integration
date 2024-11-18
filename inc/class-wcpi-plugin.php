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

		add_action( 'admin_menu', array('Wcpi_Settings', 'add_page_to_menu') );

		add_action( 'admin_notices', array($this, 'display_admin_messages') );

		
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
