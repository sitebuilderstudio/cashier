<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

$form_args = array(
    'nonce' => wp_create_nonce('cashier_register_nonce'),
);

if (empty($args['price_id'])) {
    echo '<div class="error">No price ID provided.</div>';
    return;
}
?>

<div class="cashier-register-form">
    <form id="registration-payment-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="signup-form">
        <input type="hidden" name="action" value="cashier_register_subscribe_form_handler">
        <input type="hidden" name="subscription" value="<?php echo esc_attr($args['price_id']); ?>">
        <?php wp_nonce_field('cashier_register_nonce', 'cashier_register_nonce'); ?>

        <?php if (!$args['is_logged_in']) : ?>
            <!-- Step 1: Account Creation -->
            <div class="form-step" id="step-1">
                <h2>Create Your Account</h2>

                <div class="form-row">
                    <label for="name">Name <span class="required">*</span></label>
                    <input type="text" id="name" name="name" required>
                    <div id="name_response" class="response-message"></div>
                </div>

                <div class="form-row">
                    <label for="username">Username <span class="required">*</span></label>
                    <input type="text" id="username" name="username" required>
                    <div id="username_response" class="response-message"></div>
                </div>

                <div class="form-row">
                    <label for="email">Email <span class="required">*</span></label>
                    <input type="email" id="email" name="email" required>
                    <div id="email_response" class="response-message"></div>
                </div>

                <div class="form-row">
                    <label for="password">Password <span class="required">*</span></label>
                    <input type="password" id="password" name="password" required>
                    <div id="password_strength" class="response-message"></div>
                </div>

                <div class="form-row">
                    <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <div id="password_match" class="response-message"></div>
                </div>

                <div class="form-row">
                    <button type="button" id="next-step" class="button button-primary">Continue to Payment</button>
                </div>
            </div>
        <?php else: ?>
            <!-- Pre-fill hidden fields for logged-in users -->
            <input type="hidden" name="name" value="<?php echo esc_attr($args['user_data']->display_name); ?>">
            <input type="hidden" name="email" value="<?php echo esc_attr($args['user_data']->user_email); ?>">
        <?php endif; ?>

        <!-- Step 2: Payment Information -->
        <div class="form-step" id="step-2" <?php echo !$args['is_logged_in'] ? 'style="display: none;"' : ''; ?>>
            <h3>Payment Information</h3>

            <div class="payment-error-container">
                <div id="payment-error-message" class="error-message" style="display: none;"></div>
            </div>

            <div class="form-group">
                <label for="card-element">Credit or Debit Card</label>
                <div id="card-element"></div>
                <div id="card-errors" role="alert"></div>
            </div>

            <input type="hidden" name="payment_method" id="payment_method">

            <div class="form-buttons">
                <button type="submit" id="submit-button" class="button button-primary">Complete Registration</button>
            </div>
        </div>
    </form>
</div>