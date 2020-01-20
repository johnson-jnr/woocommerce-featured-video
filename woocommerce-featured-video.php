<?php

/**
 * The plugin bootstrap file
 *
 *
 * @link              https://figarts.co
 * @since             1.0.0
 * @package           Woofv
 *
 * @wordpress-plugin
 * Plugin Name:       WooCommerce Featured Video
 * Plugin URI:        https://figarts.co/product/woocommerce-featured-video
 * Description:       Replaces WooCommerce featured image with embedded video
 * Version:           1.0.0
 * Author:            David Towoju (Figarts)
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woofv
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Current plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'WOOFV_VERSION', '1.0.0' );

/**
 * Deactivates if WooCommerce is deactivated
*/
function woofv_auto_deactivate() {
	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

	if(is_plugin_inactive('woocommerce/woocommerce.php')) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
	}
}
woofv_auto_deactivate();	

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-woofv.php';

/**
 * Begins execution of the plugin.
 *
 * @since    1.0.0
 */
function run_woofv() {
  // if ( class_exists( 'WooCommerce' ) ) {
    // wp_dump('sdsd');

    new WooCommerce_Featured_Video();
  // }
}
run_woofv();
