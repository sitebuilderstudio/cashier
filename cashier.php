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
 * @link              https://wpcashier.com
 * @since             1.0.0
 * @package           Stripe_Builder
 *
 * @wordpress-plugin
 * Plugin Name:       Cashier
 * Plugin URI:        https://wpcashier.com
 * Description:       Stripe plugin for WordPress
 * Version:           1.0.0
 * Author:            Joe Kneeland
 * Author URI:        https://sitebuilderstudio.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       cashier
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Define Constants
 */
define('CASHIER_FILE_PATH', __FILE__);
define('CASHIER_DIR_PATH', plugin_dir_path(__FILE__));
define('CASHIER_DIR_URL', plugin_dir_url(__FILE__));
define('CASHIER_VERSION', '1.0.0');

/**
 * Load required files
 */
require_once CASHIER_DIR_PATH . 'includes/class-cashier-admin.php';
require_once CASHIER_DIR_PATH . 'includes/admin/class-cashier-admin-plans.php';
require_once CASHIER_DIR_PATH . 'includes/public/class-cashier-public-shortcodes.php';
require_once CASHIER_DIR_PATH . 'includes/class-cashier-template-loader.php';
require_once CASHIER_DIR_PATH . 'includes/class-cashier-init.php';
require_once CASHIER_DIR_PATH . 'includes/class-cashier-login-handler.php';
require_once CASHIER_DIR_PATH . 'includes/class-cashier-portal-handler.php';
require_once CASHIER_DIR_PATH . 'includes/class-cashier-wp-api.php';


class Cashier {

    public function __construct() {
        // require vendor autoload file
        require_once CASHIER_DIR_PATH . 'vendor/autoload.php';

        // Initialize components
        new Cashier_Init();
        new Cashier_Admin();
        new Cashier_Plans();
        new Cashier_Shortcodes();
        new Cashier_Login_Handler();
        new Cashier_WP_API();

        // Add activation/deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Template loader
    }

    /**
     * The code that runs during plugin activation.
     */
    public function activate(): void
    {
        require_once CASHIER_DIR_PATH . 'includes/class-cashier-activator.php';
        Cashier_Activator::database_setup();
    }

    /**
     * The code that runs during plugin deactivation.
     */
    public function deactivate(): void
    {  // Changed from 'activate' to 'deactivate'
        require_once CASHIER_DIR_PATH . 'includes/class-cashier-deactivator.php';
        Cashier_Deactivator::deactivate();
    }
}

// Initialize the plugin
new Cashier();
