<?php
// admin/partials/plans/form.php

$is_edit = isset($plan);
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php echo $is_edit ? __('Edit Plan', 'cashier') : __('Add New Plan', 'cashier'); ?>
    </h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=cashier-plans')); ?>" class="page-title-action"><?php _e('← Back to Plans', 'cashier'); ?></a>
    <hr class="wp-header-end">

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="<?php echo $is_edit ? 'edit_plan' : 'add_plan'; ?>">
        <?php if ($is_edit) : ?>
            <input type="hidden" name="plan_id" value="<?php echo esc_attr($plan->id); ?>">
        <?php endif; ?>

        <?php wp_nonce_field($is_edit ? 'cashier_edit_plan' : 'cashier_add_plan'); ?>

        <table class="form-table">
            <tr>
                <th scope="row"><label for="plan_title"><?php _e('Title', 'cashier'); ?></label></th>
                <td>
                    <input name="plan_title" type="text" id="plan_title" class="regular-text"
                           value="<?php echo $is_edit ? esc_attr($plan->title) : ''; ?>" required>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="plan_description"><?php _e('Description', 'cashier'); ?></label></th>
                <td>
                    <textarea name="plan_description" id="plan_description" class="large-text" rows="5"><?php
                        echo $is_edit ? esc_textarea($plan->description) : '';
                        ?></textarea>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="plan_price"><?php _e('Price', 'cashier'); ?></label></th>
                <td>
                    <input name="plan_price" type="number" step="0.01" id="plan_price" class="regular-text"
                           value="<?php echo $is_edit ? esc_attr($plan->price) : ''; ?>" required>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Subscription', 'cashier'); ?></th>
                <td>
                    <label for="plan_subscription">
                        <input name="plan_subscription" type="checkbox" id="plan_subscription"
                            <?php checked($is_edit && $plan->subscription); ?>>
                        <?php _e('This is a subscription plan', 'cashier'); ?>
                    </label>
                </td>
            </tr>
            <tr class="billing-interval-row" <?php echo $is_edit && !$plan->subscription ? 'style="display:none;"' : ''; ?>>
                <th scope="row"><label for="plan_billing_interval"><?php _e('Billing Interval (months)', 'cashier'); ?></label></th>
                <td>
                    <input name="plan_billing_interval" type="number" id="plan_billing_interval" class="small-text"
                           value="<?php echo $is_edit ? esc_attr($plan->billing_interval) : ''; ?>">
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="plan_stripe_product_id"><?php _e('Stripe Product ID', 'cashier'); ?></label></th>
                <td>
                    <input name="plan_stripe_product_id" type="text" id="plan_stripe_product_id" class="regular-text"
                           value="<?php echo $is_edit ? esc_attr($plan->stripe_product_id) : ''; ?>" required>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="plan_stripe_price_id"><?php _e('Stripe Price ID', 'cashier'); ?></label></th>
                <td>
                    <input name="plan_stripe_price_id" type="text" id="plan_stripe_price_id" class="regular-text"
                           value="<?php echo $is_edit ? esc_attr($plan->stripe_price_id) : ''; ?>" required>
                </td>
            </tr>
        </table>

        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary"
                   value="<?php echo $is_edit ? __('Update Plan', 'cashier') : __('Add Plan', 'cashier'); ?>">
        </p>
    </form>
</div>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#plan_subscription').change(function() {
            $('.billing-interval-row').toggle(this.checked);
            if (!this.checked) {
                $('#plan_billing_interval').val('');
            }
        });
    });
</script>