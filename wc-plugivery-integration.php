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
 * Version: 0.0.1
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

define( 'WCPI_VERSION', '0.0.1' );
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

/* * * Initialise Plugin *** */

$wclu_plugin = new Wcpi_Plugin( $plugin_root );

function apd_add_product_type_field()
{
    global $woocommerce, $post;
    echo '<div class="options_group">';

    woocommerce_wp_checkbox(
        array(
            'id' => '_plugivery_prd',
            'label' => __('Plugivery Product', 'woocommerce'),
            'desc_tip' => 'true',
            'description' => __('Check this field for the plugivery product', 'woocommerce')
        ));

    woocommerce_wp_text_input(
        array(
            'id' => '_plugivery_product_id',
            'label' => __('Plugivery Product Id', 'woocommerce'),
            'placeholder' => '',
            'description' => __('Enter the plugivery product id', 'woocommerce'),
            'type' => 'number',
            'value' => (get_post_meta($post->ID, '_plugivery_product_id', true)) ? get_post_meta($post->ID, '_plugivery_product_id', true) : 0,
            'custom_attributes' => array(
                'step' => 'any',
                'min' => '0'
            )
        )
    );

    woocommerce_wp_text_input(
        array(
            'id' => '_plugivery_coupon',
            'label' => __('Plugivery Coupon', 'woocommerce'),
            'placeholder' => '',
            'description' => __('Enter the plugivery coupon code', 'woocommerce'),
            'type' => 'text',
            'value' => (get_post_meta($post->ID, '_plugivery_coupon', true)) ? get_post_meta($post->ID, '_plugivery_coupon', true) : '',
        )
    );


    echo '</div>';
}

add_action('woocommerce_product_options_general_product_data', 'apd_add_product_type_field');