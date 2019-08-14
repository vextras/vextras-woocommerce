<?php

/**
 * Created by Vextras.
 *
 * Name: Ryan Hungate
 * Email: ryan@vextras.com
 * Date: 1/5/17
 * Time: 9:42 AM
 */
class Vextras_Woocommerce_Mail extends Vextras_Woocommerce_Options
{
    /**
     * @param $email_class
     */
    public function disable_order_notifications( $email_class ) {
        /**
         * Hooks for sending emails during store events
         **/
        remove_action( 'woocommerce_low_stock_notification', array($email_class, 'low_stock'));
        remove_action( 'woocommerce_no_stock_notification', array($email_class, 'no_stock'));
        remove_action( 'woocommerce_product_on_backorder_notification', array($email_class, 'backorder'));

        // New order emails
        remove_action( 'woocommerce_order_status_pending_to_processing_notification', array($email_class->emails['WC_Email_New_Order'], 'trigger'));
        remove_action( 'woocommerce_order_status_pending_to_completed_notification', array($email_class->emails['WC_Email_New_Order'], 'trigger'));
        remove_action( 'woocommerce_order_status_pending_to_on-hold_notification', array($email_class->emails['WC_Email_New_Order'], 'trigger'));
        remove_action( 'woocommerce_order_status_failed_to_processing_notification', array($email_class->emails['WC_Email_New_Order'], 'trigger'));
        remove_action( 'woocommerce_order_status_failed_to_completed_notification', array($email_class->emails['WC_Email_New_Order'], 'trigger'));
        remove_action( 'woocommerce_order_status_failed_to_on-hold_notification', array($email_class->emails['WC_Email_New_Order'], 'trigger'));
        // Processing order emails
        remove_action( 'woocommerce_order_status_pending_to_processing_notification', array( $email_class->emails['WC_Email_Customer_Processing_Order'], 'trigger'));
        remove_action( 'woocommerce_order_status_pending_to_on-hold_notification', array($email_class->emails['WC_Email_Customer_Processing_Order'], 'trigger'));

        // Completed order emails
        remove_action( 'woocommerce_order_status_completed_notification', array($email_class->emails['WC_Email_Customer_Completed_Order'], 'trigger'));

        // Note emails
        remove_action( 'woocommerce_new_customer_note_notification', array($email_class->emails['WC_Email_Customer_Note'], 'trigger'));

        // order cancelled email
        if (array_key_exists('WC_Email_Cancelled_Order', $email_class->emails)) {
            remove_action('woocommerce_order_status_pending_to_cancelled_notification', array($email_class->emails['WC_Email_Cancelled_Order'], 'trigger'));
            remove_action('woocommerce_order_status_processing_to_cancelled', array($email_class->emails['WC_Email_Cancelled_Order'], 'trigger'));
            remove_action('woocommerce_order_status_on-hold_to_cancelled', array($email_class->emails['WC_Email_Cancelled_Order'], 'trigger'));
        }
    }
}
