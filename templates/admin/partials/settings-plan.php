<?php

global $wpdb;
$table_name = $wpdb->prefix . 'cash_products';

// Handle deletions
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $wpdb->delete($table_name, ['id' => $id], ['%d']);
    echo '<div class="notice notice-success"><p>Plan deleted successfully.</p></div>';
}

// Fetch all plans
$plans = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC");
?>
<div class="wrap">
    <h1 class="wp-heading-inline">Plans</h1>
    <a href="<?php echo admin_url('admin.php?page=cash-plans-add'); ?>" class="page-title-action">Add New</a>
    <hr class="wp-header-end">

    <?php if (empty($plans)) : ?>
        <div class="no-items">
            <p>No plans found. <a href="<?php echo admin_url('admin.php?page=cash-plans-add'); ?>">Create your first plan</a>.</p>
        </div>
    <?php else : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
            <tr>
                <th scope="col" class="manage-column column-title column-primary">Title</th>
                <th scope="col" class="manage-column">Price</th>
                <th scope="col" class="manage-column">Subscription</th>
                <th scope="col" class="manage-column">Billing Interval</th>
                <th scope="col" class="manage-column">Stripe Product ID</th>
                <th scope="col" class="manage-column">Stripe Price ID</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($plans as $plan) : ?>
                <tr>
                    <td class="title column-title has-row-actions column-primary">
                        <strong>
                            <a href="<?php echo admin_url('admin.php?page=cash-plans-edit&id=' . $plan->id); ?>">
                                <?php echo esc_html($plan->title); ?>
                            </a>
                        </strong>
                        <div class="row-actions">
                                    <span class="edit">
                                        <a href="<?php echo admin_url('admin.php?page=cash-plans-edit&id=' . $plan->id); ?>">Edit</a> |
                                    </span>
                            <span class="delete">
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=cash-plans&action=delete&id=' . $plan->id), 'delete_plan_' . $plan->id); ?>"
                                           onclick="return confirm('Are you sure you want to delete this plan?');">
                                            Delete
                                        </a>
                                    </span>
                        </div>
                    </td>
                    <td><?php echo number_format($plan->price, 2); ?></td>
                    <td><?php echo $plan->subscription ? 'Yes' : 'No'; ?></td>
                    <td><?php echo $plan->billing_interval ? $plan->billing_interval . ' months' : '-'; ?></td>
                    <td><?php echo esc_html($plan->stripe_product_id); ?></td>
                    <td><?php echo esc_html($plan->stripe_price_id); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
    <table class="form-table">
        <tr valign="top">
            <th scope="row">Title</th>
            <td><input type="text" name="plan_title" required></td>
        </tr>
        <tr valign="top">
            <th scope="row">Price</th>
            <td><input type="number" step="0.01" name="plan_price" required></td>
        </tr>
        <tr valign="top">
            <th scope="row">Subscription</th>
            <td><input type="checkbox" name="plan_subscription"></td>
        </tr>
        <tr valign="top">
            <th scope="row">Billing Interval (months)</th>
            <td><input type="number" name="plan_billing_interval"></td>
        </tr>
        <tr valign="top">
            <th scope="row">Stripe Product ID</th>
            <td><input type="text" name="plan_stripe_product_id" required></td>
        </tr>
        <tr valign="top">
            <th scope="row">Stripe Price ID</th>
            <td><input type="text" name="plan_stripe_price_id" required></td>
        </tr>
    </table>
    <input type="hidden" name="action" value="add_plan">
    <?php wp_nonce_field('nonce-add-plan'); ?>
    <p class="submit">
        <input type="submit" class="button-primary" value="Save Plan">
    </p>
</form>