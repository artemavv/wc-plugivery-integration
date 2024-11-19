<?php

/**
 * Plugin Name: Plugivery for WooCommerce
 * Description: Provides Plugivery API integration for WooCommerce
 * Requires Plugins: woocommerce
 * Author: Artem Avvakumov
 * License: GPL3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Requires at least: 5.4
 * Requires PHP: 7.4
 * Version: 0.0.7
 */

/*
  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'includes.php';

define( 'WCPI_VERSION', '0.0.7' );
define( 'WCPI_SCHEMA_VERSION', '1' );
define( 'WCPI_TEXT_DOMAIN', 'wc-plugivery-integration' );

if ( ! defined( 'WCPI_URL' ) ) {
	define( 'WCPI_URL', plugin_dir_url( __FILE__ ) );
}

if ( !defined( 'WCPI_PATH' ) ) {
	define( 'WCPI_PATH', plugin_dir_path( __FILE__ ) );
}

$plugin_root = __FILE__;

Wcpi_Core::$plugin_root = $plugin_root;

register_activation_hook( $plugin_root, array('Wcpi_Plugin', 'install') );
register_deactivation_hook( $plugin_root, array('Wcpi_Plugin', 'uninstall') );

/*** Initialise Plugin ***/

$wcpi_plugin = new Wcpi_Plugin( $plugin_root );
