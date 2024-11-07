<?php

namespace Cashier\Admin;

class Admin
{
    private static $instance = null;

    public static function init()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

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
}