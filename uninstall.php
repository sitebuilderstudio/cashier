<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

global $wpdb;

// Delete custom tables
$tableArray = [
	$wpdb->prefix . "cash_donations",
	$wpdb->prefix . "cash_invoices",
	$wpdb->prefix . "cash_payments",
	$wpdb->prefix . "cash_tiers",
];
foreach ( $tableArray as $table ) {
	$wpdb->query( "DROP TABLE IF EXISTS $table" );
}

// Delete options
delete_option( "cashier_settings" );
delete_option( "cashier_db_version" );