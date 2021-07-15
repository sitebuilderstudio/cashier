<?php

/**
 * Fired during plugin activation
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @link       https://sitebuilderstudio.com
 * @since      1.0.0
 *
 * @package    Cashier
 * @subpackage Cashier/includes
 * @author     Joe Kneeland <omni.kneeland@gmail.com>
 */

class Cashier_Activator {

	/**
	 *  Cashier_Activator constructor.
	 */
	public function __construct() {

		$this->database_setup();
	}

	/**
	 * Short Description. (use period)
	 *
	 * https://wpmudev.com/blog/creating-database-tables-for-plugins/
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

	}

	public static function database_setup() {


		global $wpdb;
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$charset_collate = $wpdb->get_charset_collate();

		// donations
		$table_name = $wpdb->prefix . 'cash_donations';
		$sql        = "CREATE TABLE $table_name (
          id int(11) NOT NULL AUTO_INCREMENT,
          name varchar(150) NOT NULL,
          phone varchar(99) NOT NULL,
          email varchar(150) NOT NULL,
          type varchar(99) NOT NULL,
          note varchar(999) NOT NULL,
          amount int(99) NOT NULL,
          timestamp timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          PRIMARY KEY  (id)
	) $charset_collate;";
		dbDelta( $sql );

		// invoices
		$table_name = $wpdb->prefix . 'cash_invoices';
		$sql        = "CREATE TABLE $table_name (
          id int(11) NOT NULL AUTO_INCREMENT,
          uid int(11) NOT NULL,
          first_name varchar(250) NOT NULL,
          last_name varchar(250) NOT NULL,
          email varchar(250) NOT NULL,
          date_sent int(15) NOT NULL,
          date_paid int(15) NOT NULL,
          one_time_fee float NOT NULL,
          one_time_fee_pmt_type int(1) NOT NULL,
          monthly_sub float NOT NULL,
          monthly_sub_pmt_type int(1) NOT NULL,
          quarterly_sub float NOT NULL,
          quarterly_pmt_type int(1) NOT NULL,
          PRIMARY KEY  (id)
	) $charset_collate;";
		dbDelta( $sql );

		// payments
		$table_name = $wpdb->prefix . 'cash_payments';
		$sql        = "CREATE TABLE $table_name (
		  id int(11) NOT NULL AUTO_INCREMENT,
          stripe_charge_id varchar(35) NOT NULL,
          stripe_refund_id varchar(50) NOT NULL,
          refund_payment_id int(11) DEFAULT NULL,
          refund_date int(11) DEFAULT NULL,
          amt float NOT NULL,
          commission_amt float NOT NULL,
          payer_uid int(11) NOT NULL,
          payer_stripe_id varchar(35) NOT NULL,
          payee_uid int(11) NOT NULL,
          payee_stripe_id varchar(35) NOT NULL,
          date int(11) NOT NULL,
          PRIMARY KEY  (id)
	) $charset_collate;";
		dbDelta( $sql );


		// tiers
		$table_name = $wpdb->prefix . 'cash_tiers';
		$sql        = "CREATE TABLE $table_name (
          id int(11) NOT NULL AUTO_INCREMENT,
          tier_group int(9) DEFAULT NULL,
          user_id int(9) DEFAULT NULL,
          level int(1) DEFAULT NULL,
          earnings int(5) NOT NULL,
          percent int(2) NOT NULL,
          PRIMARY KEY  (id)
	) $charset_collate;";

		dbDelta( $sql );
		add_option( "cashier_db_version", CASHIER_VERSION );
	}
}