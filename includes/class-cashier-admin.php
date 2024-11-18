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
        $input['options_thank_you_page']         = absint( $input['options_thank_you_page']);
        $input['options_secret_key']              = sanitize_text_field( $input['options_secret_key'] );
        $input['options_publishable_key']         = sanitize_text_field( $input['options_publishable_key'] );
        $input['options_active_campaign_api_url'] = sanitize_text_field( $input['options_active_campaign_api_url'] );
        $input['options_active_campaign_api_key'] = sanitize_text_field( $input['options_active_campaign_api_key'] );
        return $input;
    }

    public function cashier_settings_html() {
        include_once CASHIER_DIR_PATH . "templates/admin/partials/settings.php";
    }

    public function cashier_plan_settings_html() {
        include_once CASHIER_DIR_PATH . "templates/admin/partials/settings-plan.php";
    }
}