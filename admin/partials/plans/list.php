<?php
// admin/partials/plans/list.php

// Check for messages
$messages = array(
    'added' => __('Plan added successfully.', 'cashier'),
    'updated' => __('Plan updated successfully.', 'cashier'),
    'deleted' => __('Plan deleted successfully.', 'cashier')
);

if (isset($_GET['message']) && isset($messages[$_GET['message']])) {
    ?>
    <div class="notice notice-success is-dismissible">
        <p><?php echo esc_html($messages[$_GET['message']]); ?></p>
    </div>
    <?php
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Plans', 'cashier'); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=cashier-plan-add')); ?>" class="page-title-action"><?php _e('Add New', 'cashier'); ?></a>
    <hr class="wp-header-end">

    <?php if (empty($plans)) : ?>
        <div class="no-items">
            <p><?php _e('No plans found.', 'cashier'); ?></p>
        </div>
    <?php else : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
            <tr>
                <th scope="col" class="manage-column column-title column-primary"><?php _e('Title', 'cashier'); ?></th>
                <th scope="col" class="manage-column"><?php _e('Price', 'cashier'); ?></th>
                <th scope="col" class="manage-column"><?php _e('Subscription', 'cashier'); ?></th>
                <th scope="col" class="manage-column"><?php _e('Billing Interval', 'cashier'); ?></th>
                <th scope="col" class="manage-column"><?php _e('Stripe Product ID', 'cashier'); ?></th>
                <th scope="col" class="manage-column"><?php _e('Stripe Price ID', 'cashier'); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($plans as $plan) : ?>
                <tr>
                    <td class="title column-title has-row-actions column-primary">
                        <strong>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=cashier-plan-edit&id=' . $plan->id)); ?>" class="row-title">
                                <?php echo esc_html($plan->title); ?>
                            </a>
                        </strong>
                        <div class="row-actions">
                                <span class="edit">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=cashier-plan-edit&id=' . $plan->id)); ?>">
                                        <?php _e('Edit', 'cashier'); ?>
                                    </a> |
                                </span>
                            <span class="delete">
                                    <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=delete_plan&id=' . $plan->id), 'delete-plan_' . $plan->id); ?>"
                                       onclick="return confirm('<?php echo esc_js(__('Are you sure you want to delete this plan?', 'cashier')); ?>');">
                                        <?php _e('Delete', 'cashier'); ?>
                                    </a>
                                </span>
                        </div>
                    </td>
                    <td><?php echo esc_html(number_format($plan->price, 2)); ?></td>
                    <td><?php echo $plan->subscription ? __('Yes', 'cashier') : __('No', 'cashier'); ?></td>
                    <td><?php echo $plan->billing_interval ? esc_html($plan->billing_interval) . ' ' . __('months', 'cashier') : '-'; ?></td>
                    <td><?php echo esc_html($plan->stripe_product_id); ?></td>
                    <td><?php echo esc_html($plan->stripe_price_id); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>