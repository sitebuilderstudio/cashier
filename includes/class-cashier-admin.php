<?php // cashier/includes/class-cashier-admin.php

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

class Cashier_Admin
{
    public function __construct()
    {
        add_action( 'admin_menu', [ $this, 'cashier_register_admin_menus' ] );
    }

    public function cashier_register_admin_menus() {
        add_menu_page(
            __( 'Cashier', 'cashier' ),
            'Cashier',
            'manage_options',
            'cashier-payments',
            array( $this, 'cashier_settings_html' ),
            'dashicons-chart-bar',
            40
        );

        // call register settings function
        add_action( 'admin_init', [ $this, 'cashier_register_settings' ] );
    }

    public function cashier_register_settings() {
        register_setting( 'cashier_settings_group', 'cashier_settings', [ $this, 'cashier_sanitize_settings' ] );
    }

    public function cashier_sanitize_settings( $input ) {
        $input['cashier_select_plan_page']         = absint( $input['cashier_select_plan_page']);
        $input['cashier_register_subscribe_page']         = absint( $input['cashier_register_subscribe_page']);
        $input['cashier_thank_you_page']         = absint( $input['cashier_thank_you_page']);
        $input['cashier_secret_key']              = sanitize_text_field( $input['cashier_secret_key'] );
        $input['cashier_publishable_key']         = sanitize_text_field( $input['cashier_publishable_key'] );
        $input['cashier_active_campaign_api_url'] = sanitize_text_field( $input['cashier_active_campaign_api_url'] );
        $input['cashier_active_campaign_api_key'] = sanitize_text_field( $input['cashier_active_campaign_api_key'] );
        return $input;
    }

    public function cashier_settings_html() {
        include_once CASHIER_DIR_PATH . "templates/admin/partials/settings.php";
    }

    public function cashier_plan_settings_html() {
        include_once CASHIER_DIR_PATH . "templates/admin/partials/settings-plan.php";
    }
}