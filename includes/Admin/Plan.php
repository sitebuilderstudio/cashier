<?php

namespace Cashier\Admin;

class Plan {

	/**
	 * Create only one instance so that it may not Repeat
	 *
	 * @since 1.0.0
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct() {

		// handle form submits of plans

		// enqueue admin styles and scripts if needed

	}

	public static function cashier_plan_settings_html() {
		include_once CASHIER_DIR_PATH . "templates/admin/partials/settings-plan.php";
	}



}