<?php

/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://sitebuilderstudio.com
 * @since             1.0.0
 * @package           Stripe_Builder
 *
 * @wordpress-plugin
 * Plugin Name:       Cashier
 * Plugin URI:        https://sitebuilderstudio.com
 * Description:       Stripe Builder Plugin by sitebuilderstudio.com
 * Version:           1.0.0
 * Author:            Joe Kneeland
 * Author URI:        https://sitebuilderstudio.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       cashier
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Define Constants
 */

define( 'CASHIER_FILE_PATH', __FILE__ );
define( 'CASHIER_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'CASHIER_DIR_URL', plugin_dir_url( __FILE__ ) );
define( 'CASHIER_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-stripe-builder-activator.php
 */
function activate_cashier() {
	require_once CASHIER_DIR_PATH . 'includes/class-cashier-activator.php';
	Cashier_Activator::database_setup();

}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-stripe-builder-deactivator.php
 */
function deactivate_cashier() {
	require_once CASHIER_DIR_PATH . 'includes/class-cashier-deactivator.php';
	Cashier_Deactivator::deactivate();
}


register_activation_hook( __FILE__, 'activate_cashier' );
register_deactivation_hook( __FILE__, 'deactivate_cashier' );


function preint( $value ) {
	echo '<pre>';
	print_r( $value );
	echo '</pre>';
}

function preintq( $q ) {
	echo "<div style='margin:20px 16px 0px 0; padding: 16px; border-left:3px solid teal; background: hsl(50deg 50% 100%);' class='preintq'>$q</div>";
}


/**
 * Requiring Files
 */
require_once( CASHIER_DIR_PATH . 'require.php' );