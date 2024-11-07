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
//        if (wp_verify_nonce($_POST['_wpnonce'], 'nonce-add-plan')) {
//            global $wpdb;
//            $table_name = $wpdb->prefix . 'cash_products';
//
//            $data = [
//                'title' => sanitize_text_field($_POST['plan_title']),
//                'price' => floatval($_POST['plan_price']),
//                'subscription' => isset($_POST['plan_subscription']) ? 1 : 0,
//                'billing_interval' => intval($_POST['plan_billing_interval']),
//                'stripe_product_id' => sanitize_text_field($_POST['plan_stripe_product_id']),
//                'stripe_price_id' => sanitize_text_field($_POST['plan_stripe_price_id']),
//            ];
//
//            $wpdb->insert($table_name, $data);
//
//            wp_redirect(esc_url($_POST['_wp_http_referer']));
//            exit;
//        }

    }

    public static function cashier_plan_settings_html() {
        include_once CASHIER_DIR_PATH . "templates/admin/partials/settings-plan.php";
    }



}