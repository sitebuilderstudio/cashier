<div class="wrap">
    <h2>Settings</h2>
    <hr />

    <form method="post" action="options.php">
        <?php
        settings_fields('cashier_settings_group');
        $settings = get_option('cashier_settings');
        ?>
        <table class="form-table">

            <tr><th><h3>Checkout Settings</h3></th></tr>

            <tr valign="top">
                <th scope="row">Plan Selection Page</th>
                <td>
                    <?php
                    $selected_page = isset($settings['options_select_plan_page']) ? $settings['options_select_plan_page'] : '';
                    wp_dropdown_pages(array(
                        'name' => 'cashier_settings[options_select_plan_page]',
                        'show_option_none' => __('Select a page'),
                        'selected' => $selected_page,
                    ));
                    ?>
                    <p class="description">Select the page where plans are displayed for customer to choose from. This plan must contain links to the 'Register Subscribe' page with a Stripe Price ID in the url, such as /signup?price_id=price_123xyz</p>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row">Register / Subscribe Page</th>
                <td>
                    <?php
                    $selected_page = isset($settings['options_register_subscribe_page']) ? $settings['options_register_subscribe_page'] : '';
                    wp_dropdown_pages(array(
                        'name' => 'cashier_settings[options_register_subscribe_page]',
                        'show_option_none' => __('Select a page'),
                        'selected' => $selected_page,
                    ));
                    ?>
                    <p class="description">Select the page where the Cashier shortcode [cashier_register_subscribe_form] is located. If one is not already made create one then select it here. This is the url the links from Plan Selection page should point to.</p>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row">Thank You Page</th>
                <td>
                    <?php
                    $selected_page = isset($settings['options_thank_you_page']) ? $settings['options_thank_you_page'] : '';
                    wp_dropdown_pages(array(
                        'name' => 'cashier_settings[options_thank_you_page]',
                        'show_option_none' => __('Select a page'),
                        'selected' => $selected_page,
                    ));
                    ?>
                    <p class="description">Select the page to redirect customers after successful checkout.</p>
                </td>
            </tr>

            <tr><th><h3>Stripe</h3></th></tr>

            <tr valign="top">
                <th scope="row">Publishable API Key</th>
                <td><input type="text" name="cashier_settings[options_publishable_key]" value="<?php
                    if(isset($settings['options_publishable_key'])) { echo esc_attr($settings['options_publishable_key']); } ?>"</td>
            </tr>

            <tr valign="top">
                <th scope="row">Secret API Key</th>
                <td><input type="text" name="cashier_settings[options_secret_key]" value="<?php if(isset($settings['options_secret_key'])) { echo esc_attr($settings['options_secret_key']); } ?>"</td>
            </tr>

            <tr><th><h3>Active Campaign</h3></th></tr>

            <tr valign="top">
                <th scope="row">API URL</th>
                <td><input type="text" name="cashier_settings[options_active_campaign_api_url]" value="<?php if(isset($settings['options_active_campaign_api_url'])) { echo esc_attr($settings['options_active_campaign_api_url']); } ?>"</td>
            </tr>

            <tr valign="top">
                <th scope="row">API Key</th>
                <td><input type="text" name="cashier_settings[options_active_campaign_api_key]" value="<?php if(isset($settings['options_active_campaign_api_key'])) { echo esc_attr($settings['options_active_campaign_api_key']); } ?>"</td>
            </tr>

        </table>
        <p class="submit">
            <input type="submit" class="button-primary" value="Save Changes" />
        </p>
    </form>
</div>