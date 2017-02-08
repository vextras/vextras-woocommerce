<?php

/**
 * Created by Vextras.
 *
 * Name: Ryan Hungate
 * Email: ryan@vextras.com
 * Date: 2/22/16
 * Time: 9:09 AM
 */
class Vextras_Newsletter extends Vextras_Woocommerce_Options
{
    /**
     * @param WC_Checkout $checkout
     */
    public function applyNewsletterField($checkout)
    {
        if (!is_admin()) {

            $default_setting = $this->getNewsletterCheckboxSetting();

            // if the user has chosen to hide the checkbox, don't do anything.
            if ($default_setting === 'hide') {
                return;
            }

            // if the user chose 'check' or nothing at all, we default to true.
            $default_checked = $default_setting === 'check';

            // if the user is logged in, we will pull the 'is_subscribed' property out of the meta for the value.
            // otherwise we use the default settings.
            if (($status = $this->currentUserIsSubscribed()) === null) {
                $status = $default_checked;
            }

            // echo out the checkbox.
            $checkbox = '<p class="form-row form-row-wide create-account">';
            $checkbox .= '<input class="input-checkbox" id="vextras_woocommerce_newsletter" type="checkbox" ';
            $checkbox .= 'name="vextras_woocommerce_newsletter" value="1"'.($status ? ' checked="checked"' : '').'>';
            $checkbox .= '<label for="vextras_woocommerce_newsletter" class="checkbox">'.$this->getNewsletterCheckboxLabel().'</label></p>';
            $checkbox .= '<div class="clear"></div>';

            echo $checkbox;
        }
    }

    /**
     * @param $order_id
     * @param $posted
     */
    public function processNewsletterField($order_id, $posted)
    {
        $this->handleStatus($order_id);
    }

    /**
     * @param WC_Order $order
     */
    public function processPayPalNewsletterField($order)
    {
        $this->handleStatus($order->id);
    }

    /**
     * @param $sanitized_user_login
     * @param $user_email
     * @param $reg_errors
     */
    public function processRegistrationForm($sanitized_user_login, $user_email, $reg_errors)
    {
        if (defined('WOOCOMMERCE_CHECKOUT')) {
            return; // Ship checkout
        }

        $this->handleStatus();
    }

    /**
     * @param null $order_id
     * @return bool|int
     */
    protected function handleStatus($order_id = null)
    {
        $status = isset($_POST['vextras_woocommerce_newsletter']) ? (int) $_POST['vextras_woocommerce_newsletter'] : 0;

        if ($order_id) {
            update_post_meta($order_id, 'vextras_woocommerce_is_subscribed', $status);
        }

        if (is_user_logged_in()) {
            update_user_meta(get_current_user_id(), 'vextras_woocommerce_is_subscribed', $status);

            return $status;
        }

        return false;
    }
}
