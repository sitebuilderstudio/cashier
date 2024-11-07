<?php
// includes/admin/class-cashier-admin-plans.php
namespace Cashier\Admin;

class Plans {
    private static $instance = null;

    public static function init() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('admin_menu', array($this, 'add_submenu_pages'));
        add_action('admin_post_add_plan', array($this, 'handle_add_plan'));
        add_action('admin_post_edit_plan', array($this, 'handle_edit_plan'));
        add_action('admin_post_delete_plan', array($this, 'handle_delete_plan'));
    }

    public function add_submenu_pages() {
        // Main plans page
        add_submenu_page(
            'cashier-payments', // Parent slug
            __('Plans', 'cashier'),
            __('Plans', 'cashier'),
            'manage_options',
            'cashier-plans',
            array($this, 'render_list_page')
        );

        // Hidden add/edit pages
        add_submenu_page(
            null, // No parent - makes it hidden
            __('Add Plan', 'cashier'),
            __('Add Plan', 'cashier'),
            'manage_options',
            'cashier-plan-add',
            array($this, 'render_add_page')
        );

        add_submenu_page(
            null, // No parent - makes it hidden
            __('Edit Plan', 'cashier'),
            __('Edit Plan', 'cashier'),
            'manage_options',
            'cashier-plan-edit',
            array($this, 'render_edit_page')
        );
    }

    public function render_list_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cash_products';

        // Get plans
        $plans = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY id DESC");

        // Include the list view
        require_once CASHIER_DIR_PATH . 'admin/partials/plans/list.php';
    }

    public function render_add_page() {
        require_once CASHIER_DIR_PATH . 'admin/partials/plans/form.php';
    }

    public function render_edit_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cash_products';

        $plan_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
        $plan = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $plan_id));

        if (!$plan) {
            wp_die(__('Plan not found.', 'cashier'));
        }

        require_once CASHIER_DIR_PATH . 'admin/partials/plans/form.php';
    }

    public function handle_add_plan() {
        // Verify nonce
        check_admin_referer('cashier_add_plan');

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'cash_products';

        $data = array(
            'title' => sanitize_text_field($_POST['plan_title']),
            'description' => sanitize_textarea_field($_POST['plan_description']),
            'price' => floatval($_POST['plan_price']),
            'subscription' => isset($_POST['plan_subscription']) ? 1 : 0,
            'billing_interval' => isset($_POST['plan_billing_interval']) ? absint($_POST['plan_billing_interval']) : null,
            'stripe_product_id' => sanitize_text_field($_POST['plan_stripe_product_id']),
            'stripe_price_id' => sanitize_text_field($_POST['plan_stripe_price_id'])
        );

        $wpdb->insert($table_name, $data);

        wp_redirect(add_query_arg(
            array(
                'page' => 'cashier-plans',
                'message' => 'added'
            ),
            admin_url('admin.php')
        ));
        exit;
    }

    public function handle_edit_plan() {
        check_admin_referer('cashier_edit_plan');

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'cash_products';

        $plan_id = absint($_POST['plan_id']);

        $data = array(
            'title' => sanitize_text_field($_POST['plan_title']),
            'description' => sanitize_textarea_field($_POST['plan_description']),
            'price' => floatval($_POST['plan_price']),
            'subscription' => isset($_POST['plan_subscription']) ? 1 : 0,
            'billing_interval' => isset($_POST['plan_billing_interval']) ? absint($_POST['plan_billing_interval']) : null,
            'stripe_product_id' => sanitize_text_field($_POST['plan_stripe_product_id']),
            'stripe_price_id' => sanitize_text_field($_POST['plan_stripe_price_id'])
        );

        $wpdb->update(
            $table_name,
            $data,
            array('id' => $plan_id)
        );

        wp_redirect(add_query_arg(
            array(
                'page' => 'cashier-plans',
                'message' => 'updated'
            ),
            admin_url('admin.php')
        ));
        exit;
    }

    public function handle_delete_plan() {
        $plan_id = absint($_REQUEST['id']);
        check_admin_referer('delete-plan_' . $plan_id);

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'cash_products';

        $wpdb->delete($table_name, array('id' => $plan_id));

        wp_redirect(add_query_arg(
            array(
                'page' => 'cashier-plans',
                'message' => 'deleted'
            ),
            admin_url('admin.php')
        ));
        exit;
    }
}