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

        // for ordering plans
        add_action('wp_ajax_update_plan_order', array($this, 'handle_update_order'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    public function enqueue_admin_scripts($hook) {
        // Only load on our plans page
        if ('cashier_page_cashier-plans' !== $hook) {
            return;
        }

        wp_enqueue_script('jquery-ui-sortable');

        // Add custom JavaScript for handling the order updates
        wp_add_inline_script('jquery-ui-sortable', "
        jQuery(document).ready(function($) {
            $('#the-list').sortable({
                items: 'tr',
                cursor: 'move',
                axis: 'y',
                handle: '.column-order',
                placeholder: 'ui-sortable-placeholder',
                update: function(event, ui) {
                    var order = [];
                    $('.plan-order').each(function() {
                        order.push($(this).val());
                    });

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'update_plan_order',
                            security: $('#plan-order-nonce').val(),
                            order: order
                        },
                        success: function(response) {
                            if (response.success) {
                                // Show success message
                                $('<div class=\"notice notice-success is-dismissible\"><p>Order updated successfully.</p></div>')
                                    .insertAfter('.wp-header-end')
                                    .delay(2000)
                                    .fadeOut();
                            } else {
                                // Show error message
                                console.error('Error updating order:', response.data);
                                $('<div class=\"notice notice-error is-dismissible\"><p>Error updating order.</p></div>')
                                    .insertAfter('.wp-header-end');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Ajax error:', error);
                            $('<div class=\"notice notice-error is-dismissible\"><p>Error updating order.</p></div>')
                                .insertAfter('.wp-header-end');
                        }
                    });
                }
            });
            
            // Make notices dismissible
            $(document).on('click', '.notice-dismiss', function() {
                $(this).parent().remove();
            });
        });
    ");

        // Add custom CSS for ordering
        wp_add_inline_style('wp-admin', "
        .column-order { width: 30px; cursor: move; }
        .column-order .dashicons { color: #bbb; }
        .ui-sortable-helper { background: #fff !important; border: 1px solid #ddd; }
        .ui-sortable-placeholder { visibility: visible !important; background: #f9f9f9; }
    ");
    }

    public function add_submenu_pages(): void
    {
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
        $plans = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY display_order DESC");

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
            'stripe_price_id' => sanitize_text_field($_POST['plan_stripe_price_id']),
            'display_order' => 0
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

    public function handle_update_order() {
        check_admin_referer('update-plan-order', 'security');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $order = isset($_POST['order']) ? array_reverse($_POST['order']) : array();

        if (!empty($order)) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'cash_products';

            // Use MySQL's FIELD() function to order by the provided array
            $ids_ordered = implode(',', array_map('absint', $order));
            $sql = "UPDATE {$table_name} 
                SET display_order = FIND_IN_SET(id, '{$ids_ordered}')
                WHERE id IN ({$ids_ordered})";

            $result = $wpdb->query($sql);

            if ($result === false) {
                wp_send_json_error($wpdb->last_error);
            }

            wp_send_json_success();
        }

        wp_send_json_error('No order data received');
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