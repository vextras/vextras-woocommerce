<?php
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'api_key';
$handler = Vextras_Woocommerce_Admin::connect();
$verified_account = $handler->isConfigured();
?>

<style>
    #sync-status-message strong {
        font-weight:inherit;
    }

    #login_form {
        padding-top:1em;
    }

    #login_form label {
        display: inline-block;
        width: 150px;
        text-align: left;
    }​;

</style>

<!-- Create a header in the default WordPress 'wrap' container -->
<div class="wrap">
    <div id="icon-themes" class="icon32"></div>

    <h1>Connection Information</h1>
    <p>
        Vextras extends your WooCommerce store's functionality with integrations, email messaging workflows and
        customer insights. All settings for our apps and workflows are configured in your Vextras account, not in WooCommerce.
    </p>
    <p style="font-weight:bold;">
        Important: This plugin will only work with stores that have SSL enabled. If you don't have a SSL for your store, contact your host for options.
    </p>

    <div class="vextras_admin">

    <?php if (!$verified_account): ?>
        <form method="post" name="login_form" id="login_form" action="#">

            <fieldset>
                <legend class="screen-reader-text"><span>Email</span></legend>
                <label for="<?php echo $this->plugin_name; ?>-vextras_email">
                    <span><?php esc_attr_e('Email', $this->plugin_name); ?></span>
                </label>
                <input style="width: 30%;" type="email" id="<?php echo $this->plugin_name; ?>-vextras_email" name="<?php echo $this->plugin_name; ?>[vextras_email]" value="" />
            </fieldset>

            <fieldset>
                <legend class="screen-reader-text"><span>Password</span></legend>
                <label for="<?php echo $this->plugin_name; ?>-vextras_password">
                    <span><?php esc_attr_e('Password', $this->plugin_name); ?></span>
                </label>
                <input style="width: 30%;" type="password" id="<?php echo $this->plugin_name; ?>-vextras_password" name="<?php echo $this->plugin_name; ?>[vextras_password]" value="" />
            </fieldset>

            <div id="result_message">
                <p></p>
            </div>

        </form>

        <div style="padding-bottom: 1em;padding-top: .5em;">
            <button class="button button-primary" style="width:265px" onclick="vextras_admin_login_user();" id="vextras_log_in_button" name="vextras_log_in_button">Log In</button>
            <button class="button button-default" style="width:265px" onclick="vextras_admin_create_new_account();" id="vextras_create_new_button" name="vextras_create_new_button">Create New Account</button>
        </div>

    <?php endif ?>

    </div>

    <?php if ($verified_account): ?>
        <?php $api = new VextrasApi(); $account_data = $api->getAccountData(); ?>
        <p>Status :: <?php echo $account_data->status; ?></p>
        <?php if (isset($account_data->error) && !empty($account_data->error)): ?>
        <p style="color:red; font-weight:bold;">Error :: <?php echo $account_data->error; ?></p>
        <?php endif ?>
        <p>Account :: <?php echo $account_data->account; ?></p>
        <p>Subscription :: <?php echo $account_data->subscription; ?></p>
        <p>Plan :: <?php echo $account_data->plan; ?></p>
        <p>Integrations :: <?php echo $account_data->integrations; ?></p>
        <p>Workflows :: <?php echo $account_data->workflows; ?></p>
    <?php endif ?>

    <hr/>

    <h2>Need Help?</h2>
    <p>Check out these resources or contact us anytime for assistance.</p>
    <ul>
        <li><a href="https://app.vextras.com/" target="_blank">Vextras account dashboard</a></li>
        <li><a href="https://vextras.com/docs" target="_blank">Documentation</a></li>
        <li><a href="https://vextras.com/contact-us" target="_blank">Contact us</a></li>
    </ul>

</div><!-- /.wrap -->
