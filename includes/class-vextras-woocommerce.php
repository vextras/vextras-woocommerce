<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://vextras.com
 * @since      1.0.1
 *
 * @package    Vextras_Woocommerce
 * @subpackage Vextras_Woocommerce/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Vextras_Woocommerce
 * @subpackage Vextras_Woocommerce/includes
 * @author     Ryan Hungate <ryan@vextras.com>
 */
class Vextras_Woocommerce {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Vextras_Woocommerce_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * @var string
	 */
	protected $environment = 'production';

	protected static $logging_config = null;

	/**
	 * @return object
	 */
	public static function getLoggingConfig()
	{
		if (is_object(static::$logging_config)) {
			return static::$logging_config;
		}

		$plugin_options = get_option('vextras-woocommerce');

		$username = (is_array($plugin_options) && array_key_exists('public_key', $plugin_options)) ?
            $plugin_options['public_key'] : null;

		$env = vextras_environment_variables();

		return static::$logging_config = (object) array(
			'enable_logging' => true,
			'username' => $username,
			'endpoint' => "{$env->api_endpoint}/log/{$username}",
		);
	}


	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @param string $environment
	 * @param string $version
	 *
	 * @since    1.0.0
	 */
	public function __construct($environment = 'production', $version = '1.0.0') {

		$this->plugin_name = 'vextras-woocommerce';
		$this->version = $version;
		$this->environment = $environment;

		// no autoloading - or poor mans version
		$this->load_dependencies();

		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

		// activate core services
		$this->activateVextrasMail();
		$this->activateVextrasNewsletter();
		$this->activateVextrasService();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Vextras_Woocommerce_Loader. Orchestrates the hooks of the plugin.
	 * - Vextras_Woocommerce_i18n. Defines internationalization functionality.
	 * - Vextras_Woocommerce_Admin. Defines all hooks for the admin area.
	 * - Vextras_Woocommerce_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		$path = plugin_dir_path( dirname( __FILE__ ) );

		/** The abstract options class.*/
		require_once $path . 'includes/class-vextras-woocommerce-options.php';

		/** The class responsible for orchestrating the actions and filters of the core plugin.*/
		require_once $path . 'includes/class-vextras-woocommerce-loader.php';

		/** The class responsible for defining internationalization functionality of the plugin. */
		require_once $path . 'includes/class-vextras-woocommerce-i18n.php';

        /** The mail override class.*/
        require_once $path . 'includes/class-vextras-woocommerce-mail.php';

		/** The service class.*/
		require_once $path . 'includes/class-vextras-woocommerce-service.php';

		/** The newsletter class. */
		require_once $path . 'includes/class-vextras-woocommerce-newsletter.php';

		/** The vextras api connector class. */
		require_once $path . 'includes/class-vextras-api.php';

		/** The class responsible for defining all actions that occur in the admin area.*/
		require_once $path . 'admin/class-vextras-woocommerce-admin.php';

		/** The class responsible for defining all actions that occur in the public-facing side of the site. */
		require_once $path . 'public/class-vextras-woocommerce-public.php';

		// fire up the loader
		$this->loader = new Vextras_Woocommerce_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Vextras_Woocommerce_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Vextras_Woocommerce_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Vextras_Woocommerce_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

		// Add menu item
		$this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');

		// Add Settings link to the plugin
		$plugin_basename = plugin_basename( plugin_dir_path( __DIR__ ) . $this->plugin_name . '.php');
		$this->loader->add_filter('plugin_action_links_' . $plugin_basename, $plugin_admin, 'add_action_links');

		// make sure we're listening for the admin init
		$this->loader->add_action('admin_init', $plugin_admin, 'options_update');

		$this->loader->add_action('plugins_loaded', $plugin_admin, 'update_db_check');

        // this is where we will be able to communicate to the server from Vextras
        $this->loader->add_action('wp_ajax_vextras_sign_up', $plugin_admin, 'sign_up');
        $this->loader->add_action('wp_ajax_vextras_log_in', $plugin_admin, 'log_in');
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Vextras_Woocommerce_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
	}

    /**
     * Woocommerce email related overrides.
     */
	private function activateVextrasMail()
    {
        $service = new Vextras_Woocommerce_Mail();

        $service->setEnvironment($this->environment);
        $service->setVersion($this->version);

        /** Unhook the order notifications from woo and use MC. */
        if ($service->getDisableEmailNotifications()) {
            $this->loader->add_action('woocommerce_email', $service, 'disable_order_notifications', 1);
        }
    }

	/**
	 * Handle the newsletter actions here.
	 */
	private function activateVextrasNewsletter()
	{
		$service = new Vextras_Newsletter();

		if ($service->isConfigured()) {

			$service->setEnvironment($this->environment);
			$service->setVersion($this->version);

			// adding the ability to render the checkbox on another screen of the checkout page.
			$render_on = $service->getOption('vextras_checkbox_action', 'woocommerce_after_checkout_billing_form');
			$this->loader->add_action($render_on, $service, 'applyNewsletterField', 5);

			$this->loader->add_action('woocommerce_ppe_checkout_order_review', $service, 'applyNewsletterField', 5);
			$this->loader->add_action('woocommerce_register_form', $service, 'applyNewsletterField', 5);

			$this->loader->add_action('woocommerce_checkout_order_processed', $service, 'processNewsletterField', 5, 2);
			$this->loader->add_action('woocommerce_ppe_do_payaction', $service, 'processPayPalNewsletterField', 5, 1);
			$this->loader->add_action('woocommerce_register_post', $service, 'processRegistrationForm', 5, 3);
		}
	}

	/**
	 * Handle all the service hooks here.
	 */
	private function activateVextrasService()
	{
		$service = new Vextras_Service();

		$service->setEnvironment($this->environment);
		$service->setVersion($this->version);

		// core hook setup
		$this->loader->add_action('admin_init', $service, 'adminReady');
		$this->loader->add_action('woocommerce_init', $service, 'wooIsRunning');

		// for the data sync we need to configure basic auth.
		$this->loader->add_filter('http_request_args', $service, 'addHttpRequestArgs', 10, 2);

		// campaign tracking
		$this->loader->add_action( 'init', $service, 'handleCampaignTracking' );

		// order hooks
		$this->loader->add_action('woocommerce_api_create_order', $service, 'handleOrderStatusChanged');
		$this->loader->add_action('woocommerce_thankyou', $service, 'handleOrderStatusChanged');
		$this->loader->add_action('woocommerce_order_status_changed', $service, 'handleOrderStatusChanged');

		// trashed and restored
        $this->loader->add_action('wp_trash_post', $service, 'handlePostTrashed', 10);
        $this->loader->add_action('untrashed_post', $service, 'handlePostRestored', 10);

		// save post hook for all posts
        $this->loader->add_action('save_post', $service, 'handlePostSaved', 10, 3);

        // woo api product created
        $this->loader->add_action('woocommerce_new_product', $service, 'handleWooProductUpdated', 10, 1);

        // woo api product updated
        $this->loader->add_action( 'woocommerce_update_product', $service, 'handleWooProductUpdated', 10, 1 );

        // woo api product trashed
        $this->loader->add_action( 'woocommerce_trash_product', $service, 'handleWooProductTrashed', 10, 1 );

        // woo api product deleted
        $this->loader->add_action( 'woocommerce_delete_product', $service, 'handleWooProductTrashed', 10, 1 );

		// cart hooks
        $this->loader->add_action('woocommerce_ajax_added_to_cart', $service, 'handleAjaxAddedToCart');

        // when an item is removed, grab it.
        $this->loader->add_action('woocommerce_cart_item_removed', $service, 'handleCartItemRemoved');

        // when an item is restored grab it.
        $this->loader->add_action('woocommerce_cart_item_restored', $service, 'handleCartItemRestored');

        // when an item quantity is updated grab it.
        $this->loader->add_action('woocommerce_after_cart_item_quantity_update', $service, 'handleCartQuantityUpdated');

        // when the cart is finally updated we grab the hook
        $this->loader->add_filter('woocommerce_update_cart_action_cart_updated', $service, 'handleCartUpdated');
        //$this->loader->add_action('woocommerce_cart_updated', $service, 'handleCartUpdated');

        // if the cart is emptied we listen for that
		$this->loader->add_action('woocommerce_cart_emptied', $service, 'handleCartEmptied');

		// handle the user registration hook
		$this->loader->add_action('user_register', $service, 'handleUserRegistration');

		// handle the user updated profile hook
		$this->loader->add_action('profile_update', $service, 'handleUserUpdated', 10, 2);

		// when someone deletes a user??
		//$this->loader->add_action('delete_user', $service, 'handleUserDeleting');

		$this->loader->add_action('wp_ajax_nopriv_vextras_get_user_by_hash', $service, 'get_user_by_hash');
		$this->loader->add_action('wp_ajax_nopriv_vextras_set_user_by_email', $service, 'set_user_by_email');

		// this is where we will be able to communicate to the server from Vextras
        $this->loader->add_action('wp_ajax_nopriv_vextras_phone_home', $service, 'phone_home');
    }

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.1
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.1
	 * @return    Vextras_Woocommerce_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.1
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}
