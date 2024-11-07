<div class="wrap">
    <h2>API Settings</h2>
    <hr />

    <form method="post" action="options.php">
        <?php
        settings_fields('cashier_settings_group');
        $settings = get_option('cashier_settings');
        ?>
        <table class="form-table">

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