<?php

/**
 * Created by Vextras.
 *
 * Name: Ryan Hungate
 * Email: ryan@vextras.com
 * Date: 2/17/16
 * Time: 12:03 PM
 */
class Vextras_Service extends Vextras_Woocommerce_Options
{
    protected $user_email = null;
    protected $previous_email = null;
    protected $force_cart_post = false;
    protected $pushed_orders = array();
    protected $cart = array();
    protected $cart_changed = null;

    /**
     * @var VextrasApi
     */
    protected $api;

    /**
     * hook fired when we know everything is booted
     */
    public function wooIsRunning()
    {
        // make sure the site option for setting the vextras_carts has been saved.
        $this->is_admin = current_user_can('administrator');
        $this->api = new VextrasApi();
    }

    /**
     * @param $r
     * @param $url
     * @return mixed
     */
    public function addHttpRequestArgs($r, $url) {
        // not sure whether or not we need to implement something like this yet.
        //$r['headers']['Authorization'] = 'Basic ' . base64_encode('username:password');
        return $r;
    }

    /**
     * siteurl.com/?action=vextras_phone_home&service=some_service
     *
     * Home base makes calls to this service based on the 'service' in the URL.
     */
    public function phone_home()
    {
        if (!defined('DOING_AJAX') || !DOING_AJAX) {
            $this->json(array(
                'success' => false,
                'message' => 'this method is only available through the ajax url.'
            ));
        }

        if (!isset($_GET['api_token']) || !isset($_GET['service'])) {
            $this->json(array(
                'success' => false,
                'message' => 'the "api_token" and "service" params must be defined.'
            ));
        }

        // if the api_token does not match the query string token, return invalid key message.
        if (($stored_token = $this->getOption('unique_install_key')) !== $_GET['api_token']) {
            $this->json(array('success' => false, 'message' => 'invalid_key'));
        }

        // if the method does not exist return a falsy response.
        if (!method_exists($this, $_GET['service'])) {
            $this->json(array('success' => false, 'message' => 'invalid_call'));
        }

        // grab the params out of the query string &params=var_1=this,var_2=that
        $func_params = isset($_GET['params']) ? str_getcsv($_GET['params']) : array();

        $result = call_user_func_array(array($this, $_GET['service']), $func_params);

        $this->json(array('success' => true, 'data' => $result));
    }

    /**
     * This gives basic connection true / false based on min requirements.
     */
    public function checkForValidPlugin()
    {
        return $this->isConfigured();
    }

    /**
     * @return mixed|void
     */
    public function getBlogName()
    {
        return get_option('blogname');
    }

    /**
     * Toggle Abandoned Cart functionality on or off
     * @param $active
     * @return bool
     */
    public function setAbandonedCart($active)
    {
        $this->mergeOptions(array('abandoned_cart_activated' => (bool) $active));
        return true;
    }

    /**
     * @return bool
     */
    public function usesAbandonedCart()
    {
        return $this->getOption('abandoned_cart_activated', false);
    }

    /**
     * @param $order_id
     * @param $customer_id
     * @return array
     */
    public function getOrderMeta($order_id, $customer_id)
    {
        return array(
            'subscriber_status' => $this->getSubscriberStatus($customer_id),
            'order_subscriber_status' => $this->getSubscriberStatusForOrder($order_id),
            'user_meta' => $this->getUserMeta($customer_id),
            'tracking_numbers' => $this->getTrackingNumbers($order_id),
        );
    }

    /**
     * Get the subscriber status on a specific order.
     *
     * @param $order_id
     * @return bool
     */
    public function getTrackingNumbers($order_id)
    {
        return get_post_meta($order_id, 'vextras_woocommerce_tracking_numbers');
    }

    /**
     * @param $order_id
     * @param $tracking_number
     * @return array|bool
     */
    public function addTrackingNumber($order_id, $tracking_number, $provider = null, $date_shipped = null, $custom_url = null)
    {
        if ( function_exists( 'wc_st_add_tracking_number' ) ) {
            wc_st_add_tracking_number($order_id, $tracking_number, $provider, $date_shipped, $custom_url);
        }
        $tracking_numbers = $this->getTrackingNumbers($order_id);
        $tracking_numbers[] = $tracking_number;
        update_post_meta($order_id, $tracking_numbers);
        return $tracking_numbers;
    }

    /**
     * Get subscriber status for a specific user id
     * @param $user_id
     * @return bool
     */
    public function getSubscriberStatus($user_id)
    {
        $status = get_user_meta($user_id, 'vextras_woocommerce_is_subscribed', true);
        if ($status === '' || $status === null) {
            $status = false;
        }
        return (bool) $status;
    }

    /**
     * Update a user's subscriber status
     *
     * @param $user_id
     * @param $status
     * @return bool
     */
    public function updateSubscriberStatus($user_id, $status)
    {
        update_user_meta($user_id, 'vextras_woocommerce_is_subscribed', (bool) $status);
        return true;
    }

    /**
     * Get the subscriber status on a specific order.
     *
     * @param $order_id
     * @return bool
     */
    public function getSubscriberStatusForOrder($order_id)
    {
        $status = get_post_meta($order_id, 'vextras_woocommerce_is_subscribed', true);
        return (bool) $status;
    }

    /**
     * Get the user meta by id.
     * @param $user_id
     * @return array
     */
    public function getUserMeta($user_id)
    {
        return get_user_meta($user_id);
    }

    /**
     * Get plugin options
     * @return array
     */
    public function getPluginOptions()
    {
        return $this->getOptions();
    }

    /**
     * @param $order_id
     */
    public function handleOrderStatusChanged($order_id)
    {
        if (($this->isConfigured() && $this->isActive()) && !array_key_exists($order_id, $this->pushed_orders)) {

            // register this order is already in process..
            $this->pushed_orders[$order_id] = true;

            // see if we have a session id and a campaign id, also only do this when this user is not the admin.
            $campaign_id = $this->getCampaignTrackingID();

            // handle the order update
            $this->api->order($order_id, $campaign_id);
        }
    }

    /**
     * @param $product_id
     */
    public function handleProductHasBeenPublished($product_id)
    {
        if (($this->isConfigured() && $this->isActive())) {
            // handle the order update
            $this->api->product($product_id, 'saved');
        }
    }

    /**
     * @param $product_id
     */
    public function handleProductHasBeenDeleted($product_id)
    {
        if (($this->isConfigured() && $this->isActive())) {
            // handle the order update
            $this->api->product($product_id, 'trashed');
        }
    }

    /**
     * @param $product_id
     */
    public function handleProductHasBeenRestored($product_id)
    {
        if (($this->isConfigured() && $this->isActive())) {
            // handle the order update
            $this->api->product($product_id, 'restored');
        }
    }

    /**
     * On Ajax adds
     */
    public function handleAjaxAddedToCart()
    {
        $this->handleCartUpdated();
    }

    /**
     * On cart item removed.
     */
    public function handleCartItemRemoved()
    {
        $this->handleCartUpdated();
    }

    /**
     * on cart emptied
     */
    public function handleCartEmptied()
    {
        $this->handleCartUpdated();
    }

    /**
     * Restore the item you just deleted.
     */
    public function handleCartItemRestored()
    {
        $this->handleCartUpdated();
    }

    /**
     * on qty updates.
     */
    public function handleCartQuantityUpdated()
    {
        $this->handleCartUpdated();
    }

    /**
     * @param null $updated
     * @return bool|mixed|null
     */
    public function handleCartUpdated($updated = null)
    {
        if ($updated === false || $this->is_admin) {
            return false;
        }

        if (!$this->usesAbandonedCart() || !($this->isConfigured() && $this->isActive())) {
            return !is_null($updated) ? $updated : false;
        }

        if (empty($this->cart)) {
            $this->cart = $this->getCartItems();
        }

        if (($user_email = $this->getCurrentUserEmail())) {

            $previous = $this->getPreviousEmailFromSession();

            $uid = md5(($email_submission = trim(strtolower($user_email))));

            // delete the previous records.
            if (!empty($previous) && $previous !== $user_email) {
                // going to delete the cart because we are switching.
                $this->deleteCart(($previous_email = md5($previous_email_submission = trim(strtolower($previous)))));
            }

            // if the cart is empty, delete it.
            if (empty($this->cart)) {
                $prev = isset($previous_email_submission) ? $previous_email_submission : null;
                $response = $this->api->cart($email_submission, $prev, null, array());
                return !is_null($updated) ? $updated : $response;
            }

            if ($this->cart && !empty($this->cart)) {

                // track the cart locally so we can repopulate things for cross device compatibility.
                $this->trackCart($uid, $email_submission);

                // grab the cookie data that could play important roles in the submission
                $campaign = $this->getCampaignTrackingID();
                $prev = isset($previous_email_submission) ? $previous_email_submission : null;
                $response = $this->api->cart($email_submission, $prev, $campaign, $this->cart);
                return !is_null($updated) ? $updated : $response;
            }
            return !is_null($updated) ? $updated : true;
        }

        return !is_null($updated) ? $updated : false;
    }

    /**
     * Save post metadata when a post is saved.
     *
     * @param int $post_id The post ID.
     * @param WP_Post $post The post object.
     * @param bool $update Whether this is an existing post being updated or not.
     */
    public function handlePostSaved($post_id, $post, $update)
    {
        if ($post->post_status !== 'auto-draft') {
            switch ($post->post_type) {
                case 'shop_order':
                    $this->handleOrderStatusChanged($post_id);
                    break;
                case 'product':
                    if ($post->post_status != 'publish' || !$product = wc_get_product($post)) {
                        return;
                    }
                    $this->handleProductHasBeenPublished($post_id);
                    break;
            }
        }
    }

    /**
     * @param $product_id
     */
    public function handleWooProductUpdated($product_id)
    {
        /** @var \WC_Product $product */
        if (($product = wc_get_product($product_id))) {
            $this->handleProductHasBeenPublished($product->get_id());
        }
    }

    /**
     * @param $product_id
     */
    public function handleWooProductTrashed($product_id)
    {
        /** @var \WC_Product $product */
        $this->handleProductHasBeenDeleted($product_id);
    }

    /**
     * @param $post_id
     */
    public function handlePostTrashed($post_id)
    {
        switch (get_post_type($post_id)) {
            case 'product':
                $this->handleProductHasBeenDeleted($post_id);
                break;
        }
    }

    /**
     * @param $post_id
     */
    public function handlePostRestored($post_id)
    {
        switch(get_post_type($post_id)) {
            case 'product':
                $this->handleProductHasBeenRestored($post_id);
                break;
        }
    }

    /**
     * @param $user_id
     */
    public function handleUserRegistration($user_id)
    {
        $subscribed = (bool) isset($_POST['vextras_woocommerce_newsletter']) ?
            $_POST['vextras_woocommerce_newsletter'] : false;

        // update the user meta with the 'is_subscribed' form element
        update_user_meta($user_id, 'vextras_woocommerce_is_subscribed', $subscribed);
    }

    /**
     * @param $user_id
     * @param $old_user_data
     */
    public function handleUserUpdated($user_id, $old_user_data)
    {
        // only update this person if they were marked as subscribed before
        //$is_subscribed = (bool) get_user_meta($user_id, 'vextras_woocommerce_is_subscribed', true);
    }

    /**
     * @return bool|string
     */
    public function getCurrentUserEmail()
    {
        if (isset($this->user_email) && !empty($this->user_email)) {
            return $this->user_email = strtolower($this->user_email);
        }

        $user = wp_get_current_user();
        $email = ($user->ID > 0 && isset($user->user_email)) ? $user->user_email : $this->getEmailFromSession();

        return $this->user_email = strtolower($email);
    }

    /**
     * @return bool|array
     */
    public function getCartItems()
    {
        return WC()->cart->cart_contents;
    }

    /**
     * @return array|bool
     */
    public function getCartItemsOld()
    {
        if (!($this->cart = $this->getWooSession('cart', false))) {
            $this->cart = WC()->cart->get_cart();
        } else {
            $cart_session = array();
            foreach ( $this->cart as $key => $values ) {
                $cart_session[$key] = $values;
                unset($cart_session[$key]['data']); // Unset product object
            }
            return $this->cart = $cart_session;
        }

        return is_array($this->cart) ? $this->cart : false;
    }

    /**
     * Set the cookie of the vextras campaigns if we have one.
     */
    public function handleCampaignTracking()
    {
        $cookie_duration = $this->getCookieDuration();

        // if we have a query string of the vextras_cart_id in the URL, that means we are sending a campaign from MC
        if (isset($_GET['vextras_cart_id']) && !isset($_GET['removed_item'])) {

            // try to pull the cart from the database.
            if (($cart = $this->getCart($_GET['vextras_cart_id'])) && !empty($cart)) {

                // set the current user email
                $this->user_email = trim(str_replace(' ','+', $cart->email));

                if (($current_email = $this->getEmailFromSession()) && $current_email !== $this->user_email) {
                    $this->previous_email = $current_email;
                    @setcookie('vextras_user_previous_email',$this->user_email, $cookie_duration, '/' );
                }

                // cookie the current email
                @setcookie('vextras_user_email', $this->user_email, $cookie_duration, '/' );

                // set the cart data.
                $this->setWooSession('cart', unserialize($cart->cart));
            }
        }

        if (isset($_REQUEST['mc_cid'])) {
            $this->setCampaignTrackingID($_REQUEST['mc_cid'], $cookie_duration);
        }

        if (isset($_REQUEST['mc_eid'])) {
            @setcookie('vextras_email_id', trim($_REQUEST['mc_eid']), $cookie_duration, '/' );
        }
    }

    /**
     * @return mixed|null
     */
    public function getCampaignTrackingID()
    {
        $cookie = $this->cookie('vextras_campaign_id', false);
        if (empty($cookie)) {
            $cookie = $this->getWooSession('vextras_tracking_id', false);
        }

        return $cookie;
    }

    /**
     * @param $id
     * @param $cookie_duration
     * @return $this
     */
    public function setCampaignTrackingID($id, $cookie_duration)
    {
        $cid = trim($id);

        @setcookie('vextras_campaign_id', $cid, $cookie_duration, '/' );
        $this->setWooSession('vextras_campaign_id', $cid);

        return $this;
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed|null
     */
    public function getWooSession($key, $default = null)
    {
        if (!($woo = WC()) || empty($woo->session)) {
            return $default;
        }
        return $woo->session->get($key, $default);
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     */
    public function setWooSession($key, $value)
    {
        if (!($woo = WC()) || empty($woo->session)) {
            return $this;
        }

        $woo->session->set($key, $value);

        return $this;
    }

    /**
     * @param $key
     * @return $this
     */
    public function removeWooSession($key)
    {
        if (!($woo = WC()) || empty($woo->session)) {
            return $this;
        }

        $woo->session->__unset($key);
        return $this;
    }

    /**
     *
     */
    public function get_user_by_hash()
    {
        if (defined('DOING_AJAX') && DOING_AJAX && isset($_GET['hash'])) {
            if (($cart = $this->getCart($_GET['hash']))) {
                $this->json(array('success' => true, 'email' => $cart->email));
            }
        }

        $this->json(array('success' => false, 'email' => false));
    }

    /**
     *
     */
    public function set_user_by_email()
    {
        if ($this->is_admin) {
            $this->json(array('success' => false));
        }

        if (defined('DOING_AJAX') && DOING_AJAX && isset($_GET['email'])) {

            $cookie_duration = $this->getCookieDuration();

            $this->user_email = trim(str_replace(' ','+', $_GET['email']));

            if (($current_email = $this->getEmailFromSession()) && $current_email !== $this->user_email) {
                $this->previous_email = $current_email;
                $this->force_cart_post = true;
                @setcookie('vextras_user_previous_email',$this->user_email, $cookie_duration, '/' );
            }

            @setcookie('vextras_user_email', $this->user_email, $cookie_duration, '/' );

            $this->getCartItems();

            try {
                $this->json(array(
                    'success' => true,
                    'email' => $this->user_email,
                    'previous' => $this->previous_email,
                    'cart' => $this->handleCartUpdated(),
                ));
            } catch (\Exception $e) {
                $this->json(array('success' => false, 'message' => $e->getMessage()));
            }
        }

        $this->json(array('success' => false, 'email' => false));
    }

    /**
     * @param $key
     * @param $default
     * @return mixed
     */
    protected function cookie($key, $default = null)
    {
        if ($this->is_admin) {
            return $default;
        }

        return isset($_COOKIE[$key]) ? $_COOKIE[$key] : $default;
    }

    /**
     * @return bool
     */
    protected function getEmailFromSession()
    {
        return $this->cookie('vextras_user_email', false);
    }

    /**
     * @return bool
     */
    protected function getPreviousEmailFromSession()
    {
        if ($this->previous_email) {
            return $this->previous_email = strtolower($this->previous_email);
        }
        $email = $this->cookie('vextras_user_previous_email', false);
        return $email ? strtolower($email) : false;
    }

    /**
     * @param string $time
     * @return int
     */
    protected function getCookieDuration($time = 'thirty_days')
    {
        $durations = array(
            'one_day' => 86400, 'seven_days' => 604800, 'fourteen_days' => 1209600, 'thirty_days' => 2419200,
        );

        if (!array_key_exists($time, $durations)) {
            $time = 'thirty_days';
        }

        return time() + $durations[$time];
    }

    /**
     * @param $uid
     * @return array|bool|null|object
     */
    protected function getCart($uid)
    {
        global $wpdb;

        $table = "{$wpdb->prefix}vextras_carts";
        $statement = "SELECT * FROM $table WHERE id = %s";
        $sql = $wpdb->prepare($statement, $uid);

        if (($saved_cart = $wpdb->get_row($sql)) && !empty($saved_cart)) {
            return $saved_cart;
        }

        return false;
    }

    /**
     * @param $uid
     * @return true
     */
    protected function deleteCart($uid)
    {
        global $wpdb;
        $table = "{$wpdb->prefix}vextras_carts";
        $sql = $wpdb->prepare("DELETE FROM $table WHERE id = %s", $uid);
        $wpdb->query($sql);

        return true;
    }

    /**
     * @param $uid
     * @param $email
     * @return bool
     */
    protected function trackCart($uid, $email)
    {
        $this->cart_changed = null;

        global $wpdb;

        $table = "{$wpdb->prefix}vextras_carts";

        $statement = "SELECT * FROM $table WHERE id = %s";
        $sql = $wpdb->prepare($statement, $uid);

        $user_id = get_current_user_id();

        $cart_data = maybe_serialize($this->cart);

        if (($saved_cart = $wpdb->get_row($sql)) && is_object($saved_cart)) {

            // let's tell the system whether or not the card has been changed based on the last record.
            $this->cart_changed = ($cart_data !== $saved_cart->cart);

            $statement = "UPDATE {$table} SET `cart` = '%s', `email` = '%s', `user_id` = %s WHERE `id` = '%s'";
            $sql = $wpdb->prepare($statement, array($cart_data, $email, $user_id, $uid));
            $wpdb->query($sql);

            return true;
        }

        // it's certainly a changed cart here.
        $this->cart_changed = true;

        $wpdb->insert("{$wpdb->prefix}vextras_carts", array(
            'id' => $uid,
            'email' => $email,
            'user_id' => (int) $user_id,
            'cart'  => $cart_data,
            'created_at'   => gmdate('Y-m-d H:i:s', time()),
        ));

        return true;
    }
}
