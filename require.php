<?php

class CashierRequire {
	private static $_instance = null;


	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct() {

		// require vendor autoload file
		require_once CASHIER_DIR_PATH . 'vendor/autoload.php';

		new \Cashier\Admin\Menu();
		new \Cashier\Admin\Role();
		new \Cashier\Admin\Plan();
		new \Cashier\Shortcode\Shortcode();
	}
}

add_action( 'plugins_loaded', array( 'CashierRequire', 'instance' ) );