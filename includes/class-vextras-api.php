<?php

/**
 * Created by Vextras.
 *
 * Name: Ryan Hungate
 * Email: ryan@vextras.com
 * Date: 12/16/16
 * Time: 3:56 PM
 */
class VextrasApi extends Vextras_Woocommerce_Options
{
    protected $endpoints = array();

    /**
     * VextrasApi constructor.
     */
    public function __construct()
    {
        $env = vextras_environment_variables();
        $public_key = $this->getOption('public_key');
        $install_key = $this->getOption('unique_install_key');

        $this->endpoints = array(
            'install_key' => "{$env->api_endpoint}/install_unique_key",
            'install_finish' => "{$env->api_endpoint}/install/{$install_key}",
            'uninstall' => "{$env->api_endpoint}/un-install/{$install_key}",
            'account' => "{$env->api_endpoint}/account/{$install_key}",
            'orders' => "{$env->api_endpoint}/orders/{$public_key}",
            'products' => "{$env->api_endpoint}/products/{$public_key}",
            'carts' => "{$env->api_endpoint}/carts/{$public_key}",
        );
    }

    /**
     * @return bool|mixed
     */
    public function installUniqueKey()
    {
        $install_key = $this->getOption('unique_install_key');

        if (empty($install_key)) return false;

        $env = vextras_environment_variables();

        return $this->post($this->endpoints['install_key'], array(
            'is_ssl' => is_ssl(),
            'is_multisite' => is_multisite(),
            'unique_key' => $install_key,
            'store_name' => get_option('blogname'),
            'url' => str_replace(array('http://', 'https://'), '', site_url()),
            'ajax_url' => admin_url('admin-ajax.php'),
            'wp_version' => $env->wp_version,
            'php_version' => defined('PHP_VERSION_ID') ? PHP_VERSION_ID : 0,
            'options' => json_encode($this->getOptions()),
        ));
    }

    /**
     * @return array|mixed|object
     */
    public function getAccountData()
    {
        $account = $this->get($this->endpoints['account']);

        return  json_decode($account['result']['body']);
    }

    /**
     * @param $event
     * @param $username
     * @param $password
     * @return array
     */
    public function finishInstall($event, $username, $password)
    {
        $options = $this->getOptions();

        $options['action'] = $event;
        $options['username'] = $username;
        $options['password'] = $password;

        $meta = $this->post($this->endpoints['install_finish'], $options);

        $json = json_decode($meta['result']['body']);

        if (isset($json->success) && $json->success) {
            $json->data->verified_account = true;
            $this->mergeOptions((array) $json->data);
            vextras_log('install', 'tracing install success', $this->getOptions());
            return array('success' => true, 'data' => (array) $json->data);
        }

        if (isset($json->success) && $json->success == false) {
            vextras_log('install', 'tracing install failure', array('options' => $options, 'response' => (array) $json));
            return array('success' => false, 'data' => (array) $json);
        }

        vextras_log('install', 'tracing install failure fallback', array('options' => $options, 'response' => (array) $json));

        return array('success' => false, 'data' => null, 'message' => 'Server Error');
    }

    /**
     * @return bool|mixed
     */
    public function deleteStore()
    {
        return $this->post($this->endpoints['uninstall'], $this->getOptions());
    }

    /**
     * @param $email
     * @param $prev
     * @param $campaign
     * @param $cart
     * @return bool|mixed
     */
    public function cart($email, $prev, $campaign, $cart)
    {
        if ($this->shouldPreventApiRequests()) return false;

        $checkout_url = wc_get_checkout_url();

        if (vextras_string_contains($checkout_url, '?')) {
            $checkout_url .= '&vextras_cart_id='.md5($email);
        } else {
            $checkout_url .= '?vextras_cart_id='.md5($email);
        }

        $submission = array();

        foreach ($cart as $hash => $item) {
            $product = new WC_Product($item['product_id']);

            $data = array();

            $data['name'] = $product->get_title();
            $data['price'] = $product->get_price();
            $data['quantity'] = $item['quantity'];
            $data['code'] = $product->get_sku();
            $data['product_id'] = $item['product_id'];

            $submission[] = $data;
        }

        $post_data = array(
            'user_id' => is_user_logged_in() ? get_current_user_id() : null,
            'user' => $email,
            'previous' => isset($prev) ? $prev : null,
            'campaign' => $campaign,
            'subscribed' => $this->currentUserIsSubscribed(),
            'checkout_url' => $checkout_url,
            'cart' => $submission,
        );

        // push to vextras
        return $this->post($this->endpoints['carts'], $post_data);
    }

    /**
     * @param $id
     * @param $campaign
     * @return bool|mixed
     */
    public function order($id, $campaign)
    {
        if ($this->shouldPreventApiRequests()) return false;

        $order = new WC_Order($id);

        return $this->post($this->endpoints['orders'], array(
            'order_id' => $id,
            'campaign' => $campaign,
            'status' => $order->get_status(),
            'date' => $order->get_date_created(),
        ));
    }

    /**
     * @param $id
     * @param null $action
     * @return array|bool
     */
    public function product($id, $action = null)
    {
        if ($this->shouldPreventApiRequests()) return false;

        $product = wc_get_product($id);
        if (empty($product) || !$product->get_sku()) return false;

        return $this->post($this->endpoints['products'], array(
            'product_id' => $id,
            'product_code' => $product->get_sku(),
            'action' => $action,
        ));
    }

    /**
     * @param $route
     * @param array $args
     * @return array
     */
    private function get($route, $args = array() )
    {
        $result = wp_remote_get(
            $route,
            array(
                'sslverify' 	=> false,
                'timeout' 		=> 15,
                'httpversion'   => '1.1',
                'headers'       => array('Content-Type'   => 'application/json'),
                'user-agent'	=> 'Vextras'
            )
        );

        return array('url' => $route, 'args' => $args, 'result' => $result);
    }

    /**
     * @param $route
     * @param array $args
     * @return array
     */
    private function post($route, $args = array() )
    {
        $result = wp_remote_post(
            $route,
            array(
                'body' 			=> json_encode( $args),
                'sslverify' 	=> false,
                'timeout' 		=> 15,
                'httpversion'   => '1.1',
                'headers'       => array('Content-Type'   => 'application/json'),
                'user-agent'	=> 'Vextras'
            )
        );

        return array('url' => $route, 'args' => $args, 'result' => $result);
    }
}
