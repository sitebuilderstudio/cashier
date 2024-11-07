<?php

namespace Cashier\Admin;

class Role {

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

        // handle form submits of roles
        add_action( 'admin_post_add_role', [ $this, 'add_role_submit_handler' ] );

        // enqueue admin styles and scripts if needed

    }

    public static function cashier_role_settings_html() {
        include_once CASHIER_DIR_PATH . "templates/admin/partials/settings-role.php";
    }

    public function add_role_submit_handler() {

        if ( wp_verify_nonce( $_POST['_wpnonce'], 'nonce-add-role' ) ) {

            $role = sanitize_text_field( $_POST['role'] );

            $role_slug = implode( '-', explode( ' ', $role ) );

            // add role
            add_role( $role_slug, $role, [ 'read' ] );

            // also just in case keep record who created what role
            $cashier_roles_arr = get_option( 'cashier_roles' );

            if ( $cashier_roles_arr == '' ) { // only if the $cashier_roles_arr is an empty string
                $cashier_roles_arr = [];
            }

            $cashier_roles_arr[] = [ get_current_user_id(), $role_slug ];
            update_option( 'cashier_roles', $cashier_roles_arr );

            // return to the referrer page
            wp_redirect( esc_url( $_POST['_wp_http_referer'] ) );

        }
    }

}