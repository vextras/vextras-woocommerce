<?php

/**
 * Created by Vextras.
 *
 * Name: Ryan Hungate
 * Email: ryan@vextras.com
 * Date: 2/22/16
 * Time: 3:45 PM
 */
abstract class Vextras_Woocommerce_Options
{
    protected $plugin_name = 'vextras-woocommerce';
    protected $environment = 'production';
    protected $version = '1.0.0';
    protected $plugin_options = null;
    protected $is_admin = false;

    /**
     * hook calls this so that we know the admin is here.
     */
    public function adminReady()
    {
        $this->is_admin = current_user_can('administrator');
        if (get_option('vextras_woocommerce_plugin_do_activation_redirect', false)) {
            delete_option('vextras_woocommerce_plugin_do_activation_redirect');
            if (!isset($_GET['activate-multi'])) {
                wp_redirect("options-general.php?page=vextras-woocommerce");
            }
        }
    }

    /**
     * @param int $page
     * @param int $limit
     * @return \stdClass
     */
    public function getSkusInStore($page = 1, $limit = 100)
    {
        $response = wc_get_products(array(
            'paginate' => true,
            'page' => $page,
            'limit' => $limit,
            'status' => 'publish',
        ));

        foreach ($response->products as $key => $result) {
            /** @var \WC_Product $result */
            $response->products[$key] = (object) array(
                'id' => $result->get_id(),
                'sku' => $result->sku,
                'qty' => $result->get_stock_quantity()
            );
        }

        return $response;
    }

    /**
     * @return bool
     */
    public function isAdmin()
    {
        return $this->is_admin;
    }

    /**
     * @param $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return string
     */
    public function getUniqueStoreID()
    {
        return md5(get_option('siteurl'));
    }

    /**
     * @param $env
     * @return $this
     */
    public function setEnvironment($env)
    {
        $this->environment = $env;
        return $this;
    }

    /**
     * @return string
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * @return array
     */
    public function getTime()
    {
        return array(
            'wc_timezone' => wc_timezone_string(),
            'server_timezone' => date_default_timezone_get(),
            'stamp' => time(),
        );
    }

    /**
     * @param $key
     * @param null $default
     * @return null
     */
    public function getOption($key, $default = null)
    {
        $options = $this->getOptions();
        if (isset($options[$key])) {
            return $options[$key];
        }
        return $default;
    }

    /**
     * @param $key
     * @param bool $default
     * @return bool
     */
    public function hasOption($key, $default = false)
    {
        return (bool) $this->getOption($key, $default);
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        if (empty($this->plugin_options)) {
            $this->plugin_options = get_option($this->plugin_name);
        }
        return is_array($this->plugin_options) ? $this->plugin_options : array();
    }

    /**
     * @param $data
     * @return array|null
     */
    public function setOptions($data)
    {
        if (!is_array($data)) {
            return null;
        }

        $options = $this->getOptions();

        delete_option($this->plugin_name);

        try {
            return $this->store($data);
        } catch (\Exception $e) {
            return $this->store($options);
        }
    }

    /**
     * @param $data
     * @return array|null
     */
    public function mergeOptions($data)
    {
        $options = (is_array($data)) ? array_merge($this->getOptions(), $data) : $this->getOptions();
        return $this->setOptions($options);
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     */
    public function setData($key, $value)
    {
        update_option($this->plugin_name.'-'.$key, $value);
        return $this;
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed
     */
    public function getData($key, $default = null)
    {
        return get_option($this->plugin_name.'-'.$key, $default);
    }

    /**
     * @param $key
     * @return bool
     */
    public function removeData($key)
    {
        return delete_option($this->plugin_name.'-'.$key);
    }

    /**
     * @param $key
     * @param null $default
     * @return null|mixed
     */
    public function getCached($key, $default = null)
    {
        $cached = $this->getData("cached-$key", false);
        if (empty($cached) || !($cached = unserialize($cached))) {
            return $default;
        }

        if (empty($cached['till']) || (time() > $cached['till'])) {
            $this->removeData("cached-$key");
            return $default;
        }

        return $cached['value'];
    }

    /**
     * @param $key
     * @param $value
     * @param $seconds
     * @return $this
     */
    public function setCached($key, $value, $seconds = 60)
    {
        $time = time();
        $data = array('at' => $time, 'till' => $time + $seconds, 'value' => $value);
        $this->setData("cached-$key", serialize($data));

        return $this;
    }

    /**
     * @param $key
     * @param $callable
     * @param int $seconds
     * @return mixed|null
     */
    public function getCachedWithSetDefault($key, $callable, $seconds = 60)
    {
        if (!($value = $this->getCached($key, false))) {
            $value = call_user_func($callable);
            $this->setCached($key, $value, $seconds);
        }
        return $value;
    }

    /**
     * @return bool
     */
    public function verifiedAccount()
    {
        return (bool) $this->getOption('verified_account', false);
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        if (!$this->verifiedAccount()) return false;

        $public_key = $this->getOption('public_key');
        $token = $this->getOption('unique_install_key');

        if (empty($public_key)) return false;
        if (empty($token)) return false;

        return true;
    }

    /**
     * @param array $data
     * @param $key
     * @param null $default
     * @return null|mixed
     */
    public function array_get(array $data, $key, $default = null)
    {
        if (isset($data[$key])) {
            return $data[$key];
        }

        return $default;
    }

    /**
     * @param $status
     * @return array|null
     */
    public function applyPlanStatus($status)
    {
        return $this->mergeOptions(array('vextras_plan_status' => $status));
    }

    /**
     * @return null|string
     */
    public function getPlanStatus()
    {
        return $this->getOption('vextras_plan_status');
    }

    /**
     * @param $status
     * @return array|null
     */
    public function applyConnectionStatus($status)
    {
        return $this->mergeOptions(array('vextras_connection_status' => $status));
    }

    /**
     * @return null|string
     */
    public function getConnectionStatus()
    {
        return $this->getOption('vextras_connection_status');
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return strtolower($this->getConnectionStatus()) === 'active';
    }

    /**
     * @return bool
     */
    public function isPaused()
    {
        return strtolower($this->getConnectionStatus()) === 'paused';
    }

    /**
     * @return bool
     */
    public function isBroken()
    {
        return strtolower($this->getConnectionStatus()) === 'broken';
    }

    /**
     * @return array|string
     */
    public function updatePluginSettings()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        if (!is_array($data)) return 'invalid_settings_post';
        return $this->mergeOptions($data);
    }

    /**
     * @param string $hook
     * @return array|null
     */
    public function applyNewsletterCheckboxHook($hook = 'woocommerce_after_checkout_billing_form')
    {
        return $this->mergeOptions(array('newsletter_checkbox_action' => $hook));
    }

    /**
     * @return string
     */
    public function getNewsletterCheckboxHook()
    {
        return $this->getOption('newsletter_checkbox_action', 'woocommerce_after_checkout_billing_form');
    }

    /**
     * @param string $label
     * @return array|null
     */
    public function applyNewsletterCheckboxLabel($label = "Yes, add me to your newsletter!")
    {
        return $this->mergeOptions(array('newsletter_label' => $label));
    }

    /**
     * @return null
     */
    public function getNewsletterCheckboxLabel()
    {
        return $this->getOption('newsletter_label', 'Yes, add me to your newsletter!');
    }

    /**
     * @param $option
     * @return array|bool|null
     */
    public function applyNewsletterCheckboxSetting($option)
    {
        if (!in_array($option, array('hide', 'check', 'uncheck'))) return false;

        return $this->mergeOptions(array('vextras_checkbox_defaults' => $option));
    }

    /**
     * @return string
     */
    public function getNewsletterCheckboxSetting()
    {
        return $this->getOption('vextras_checkbox_defaults', 'check');
    }

    /**
     * @param $bool
     * @return array|null
     */
    public function applyDisableEmailNotifications($bool)
    {
        return $this->mergeOptions(array('vextras_disable_woo_notifications' => (bool) $bool));
    }

    /**
     * @return bool
     */
    public function getDisableEmailNotifications()
    {
        return (bool) $this->getOption('vextras_disable_woo_notifications', false);
    }

    /**
     * @param array $with
     * @return array|null|string
     */
    public function saveWooCommerceKeys($with = array())
    {
        require_once __DIR__.'/class-vextras-woocommerce-activator.php';

        try {

            $data = Vextras_Woocommerce_Activator::create_keys('Vextras', 'USER_ID', 'read_write');

            $with['woo_consumer_key'] = $data['consumer_key'];
            $with['woo_consumer_secret'] = $data['consumer_secret'];

            return $this->mergeOptions($with);

        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * @return array
     */
    public function getWooCommerceKeys()
    {
        return array(
            'woo_consumer_key' => $this->getOption('woo_consumer_key'),
            'woo_consumer_secret' => $this->getOption('woo_consumer_secret'),
        );
    }

    /**
     * @return bool|null
     */
    public function currentUserIsSubscribed()
    {
        // if the user is logged in, we will pull the 'is_subscribed' property out of the meta for the value.
        // otherwise we use the default settings.
        if (is_user_logged_in()) {
            $status = get_user_meta(get_current_user_id(), 'vextras_woocommerce_is_subscribed', true);
            if ($status === '' || $status === null) {
                return false;
            }
            return (bool) $status;
        }

        return null;
    }

    /**
     * @return bool
     */
    public function shouldPreventApiRequests()
    {
        return !$this->verifiedAccount() || $this->isPaused() || $this->isBroken();
    }

    /**
     * @param $data
     */
    protected function json($data)
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * @param $data
     * @return mixed|void
     */
    private function store($data)
    {
        global $wpdb;

        $autoload = 'no';
        $data = maybe_serialize($data);
        $sql = $wpdb->prepare("INSERT INTO `$wpdb->options` (`option_name`, `option_value`, `autoload`) VALUES (%s, %s, %s) ON DUPLICATE KEY UPDATE `option_name` = VALUES(`option_name`), `option_value` = VALUES(`option_value`), `autoload` = VALUES(`autoload`)", $this->plugin_name, $data, $autoload );
        $wpdb->query($sql);

        return get_option($this->plugin_name);
    }
}
