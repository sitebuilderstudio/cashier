<?php

namespace Cashier\Admin;

class Menu {

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

		add_action( 'admin_menu', [ $this, 'register_admin_menus' ] );

		/*
		 * handle form submit for tiers select
		 * Need to filter some of these, we might not need all of them
		 *
		 */
		add_action( 'admin_post_tiers_select_form_submit', [ $this, 'tiers_select_form_handler' ] );
		add_action( 'admin_post_tiers_form_submit', [ $this, 'tiers_form_handler' ] );
		add_action( 'admin_post_custom_tiers_form_submit', [ $this, 'custom_tiers_form_handler' ] );
		add_action( 'admin_post_client_select_form_handler', [ $this, 'client_select_form_handler' ] );
		add_action( 'admin_post_client_select_partner_form_handler', [ $this, 'client_select_partner_form_handler' ] );
		add_action( 'admin_post_client_select_provider_form_handler', [
			$this,
			'client_select_provider_form_handler'
		] );

		// enqueue admin styles and scripts if needed

	}

	public function register_admin_menus() {
		add_menu_page(
			__( 'Cashier', 'cashier' ),
			'Cashier',
			'manage_options',
			'cashier-payments',
			array( $this, 'cashier_api_settings_html' ),
			'dashicons-chart-bar',
			40
		);

		add_submenu_page(
			'cashier-payments',
			'Roles',
			'Roles',
			'manage_options',
			'cashier-roles',
			array( 'Cashier\Admin\Role', 'cashier_role_settings_html' )
		);

		add_submenu_page(
			'cashier-payments',
			'Plans',
			'Plans',
			'manage_options',
			'cashier-plans',
			array( 'Cashier\Admin\Plan', 'cashier_plan_settings_html' )
		);

//		add_submenu_page(
//			'cashier-payments',
//			'Tiers',
//			'Tiers',
//			'manage_options',
//			'cashier-tiers',
//			array( $this, 'cashier_tiers_view' ),
//			2
//		);

//		add_submenu_page(
//			'cashier-payments',
//			'Custom Tiers',
//			'Custom Tiers',
//			'manage_options',
//			'custom-tiers',
//			[ $this, 'cashier_connect_sub_accounts_view' ],
//			3
//		);

		add_submenu_page(
			'cashier-payments',
			'Clients',
			'Clients',
			'manage_options',
			'clients',
			[ $this, 'cashier_connect_clients_view' ]
		);

		add_submenu_page(
			'cashier-payments',
			'Payment History',
			'Payment History',
			'manage_options',
			'payments-history',
			[ $this, 'cashier_payments_history_view' ]
		);

		// call register settings function
		add_action( 'admin_init', [ $this, 'cashier_register_settings' ] );
	}

	public function cashier_register_settings() {
		register_setting( 'cashier_settings_group', 'cashier_settings', [ $this, 'cashier_sanitize_settings' ] );
	}


	public function cashier_sanitize_settings( $input ) {
		$input['options_secret_key']              = sanitize_text_field( $input['options_secret_key'] );
		$input['options_publishable_key']         = sanitize_text_field( $input['options_publishable_key'] );
		$input['options_active_campaign_api_url'] = sanitize_text_field( $input['options_active_campaign_api_url'] );
		$input['options_active_campaign_api_key'] = sanitize_text_field( $input['options_active_campaign_api_key'] );
		$input['option_notification_email']       = sanitize_text_field( $input['options_notification_email'] );

		return $input;
	}

	public function cashier_api_settings_html() {
		include_once CASHIER_DIR_PATH . "templates/admin/partials/settings-api.php";
	}

	public function cashier_plan_settings_html() {
		include_once CASHIER_DIR_PATH . "templates/admin/partials/settings-plan.php";
	}

	public function cashier_tiers_view() {
		include_once CASHIER_DIR_PATH . "templates/admin/partials/tiers-view.php";
	}

	function cashier_connect_sub_accounts_view() {
		include_once CASHIER_DIR_PATH . "templates/admin/partials/custom-tiers.php";
	}

	function cashier_connect_clients_view() {
		include_once CASHIER_DIR_PATH . "templates/admin/partials/clients.php";
	}

	function cashier_payments_history_view() {
		include_once CASHIER_DIR_PATH . "templates/admin/partials/view-payments-history.php";
	}

	/* handle from submit for tiers select */
	public function tiers_select_form_handler() {
		$tier_group = $_GET['tier_group'];
		$redirect   = "admin.php?page=cashier-tiers&tier_group=" . $tier_group;
		wp_redirect( admin_url( $redirect ) );
	}

	/* handle form for tiers view */
	public function tiers_form_handler() {

		if ( isset( $_POST['earnings'] ) ) {
			global $wpdb;
			$earnings = $_POST['earnings'];


			// iterate earnings array and get percent for each with key, do update
			foreach ( $earnings as $key => $item ) {

				// key is id of tier, item is earnings, set percent to percent (corresponding key)
				$percent = $_POST['percent'][ $key ];

				//echo $key . ":" . $item . " and percent is ".$percent."<br>";

				// update the tier
				$data['earnings'] = (int) sanitize_text_field( $item );
				$data['percent']  = (int) sanitize_text_field( $percent );
				$table            = $wpdb->prefix . "cash_tiers";
				$format           = array( '%d', '%d' );
				$where            = [ 'id' => $key ];
				$update           = $wpdb->update( $table, $data, $where, $format );
			}
		}

		$redirect = "admin.php?page=cashier-tiers&tier_group=" . $_POST['tier_group'];
		wp_redirect( admin_url( $redirect ) );
	}

	/* handle form for custom tiers view */
	public function custom_tiers_form_handler() {

		global $wpdb;

		// if adding new rows
		if ( isset( $_POST['level']['new'] ) ) {

			$level_new_array    = array_values( $_POST['level']['new'] );
			$earnings_new_array = array_values( $_POST['earnings']['new'] );
			$percent_new_array  = array_values( $_POST['percent']['new'] );

			// iterate earnings array and get percent for each with key, do INSERT
			$i = 0;
			foreach ( $earnings_new_array as $earnings ) {

				// key is id of tier, item is earnings, set percent to percent (corresponding key)
				$percent = $percent_new_array[ $i ];
				$level   = $level_new_array[ $i ];

				// insert the tier
				$data['user_id']  = (int) $_POST['user'];
				$data['level']    = (int) sanitize_text_field( $level );
				$data['earnings'] = $earnings;
				$data['percent']  = $percent;
				$table            = $wpdb->prefix . "cash_tiers";
				$format           = array( '%d', '%d', '%d', '%d' );
				$wpdb->insert( $table, $data, $format );

				$i ++;
			}
			unset( $i );

		} else { // do the update

			$earnings = $_POST['earnings'];

			// iterate earnings array and get percent for each with key, do UPDATE
			foreach ( $earnings as $key => $item ) {

				// key is id of tier, item is earnings, set percent to percent (corresponding key)
				$percent = $_POST['percent'][ $key ];

				if ( $item == 0 ) {
					$item = "";
				}

				// update the tier
				$data['earnings'] = $item;
				$data['percent']  = $percent;
				$table            = $wpdb->prefix . "cash_tiers";
				$format           = array( '%s', '%s' );
				$where            = [ 'id' => $key ];
				$wpdb->update( $table, $data, $where, $format );
			}
		}

		$provider = (int) $_POST['user'];
		$redirect = "admin.php?page=custom-tiers&user=" . $provider;
		wp_redirect( admin_url( $redirect ) );
	}

	/* handle from submit for clients select */
	public function client_select_form_handler() {
		$client   = (int) $_GET['client'];
		$redirect = "admin.php?page=clients&client=" . $client;
		wp_redirect( admin_url( $redirect ) );
	}

	/* handle from submit for clients view > select partner */
	public function client_select_partner_form_handler() {

		//$client = (int) $_POST['client'];
		//$partner = (int) $_POST['partner'];

		//echo "here"; exit;

		$user_id    = (int) $_POST['client'];
		$meta_key   = "sb_partner";
		$meta_value = (int) $_POST['partner'];

		update_user_meta( $user_id, $meta_key, $meta_value );
		$redirect = "admin.php?page=clients&client=" . $user_id;
		wp_redirect( admin_url( $redirect ) );

	}

	/* handle from submit for clients view > select provider */
	public function client_select_provider_form_handler() {

		//$client = (int) $_POST['client'];
		//$provider = (int) $_POST['provider'];

		//echo "here"; exit;

		$user_id    = (int) $_POST['client'];
		$meta_key   = "sb_provider";
		$meta_value = (int) $_POST['provider'];

		update_user_meta( $user_id, $meta_key, $meta_value );
		$redirect = "admin.php?page=clients&client=" . $user_id;
		wp_redirect( admin_url( $redirect ) );
	}
}