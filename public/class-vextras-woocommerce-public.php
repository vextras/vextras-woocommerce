<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://vextras.com
 * @since      1.0.1
 *
 * @package    Vextras_Woocommerce
 * @subpackage Vextras_Woocommerce/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Vextras_Woocommerce
 * @subpackage Vextras_Woocommerce/public
 * @author     Ryan Hungate <ryan@vextras.com>
 */
class Vextras_Woocommerce_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	protected $version_flag = '1';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Vextras_Woocommerce_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Vextras_Woocommerce_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		//wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/vextras-woocommerce-public.css?version='.$this->version_flag, array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Vextras_Woocommerce_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Vextras_Woocommerce_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_register_script($this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/vextras-woocommerce-public.min.js?version='.$this->version_flag, array(), $this->version, false);

		wp_localize_script($this->plugin_name, 'vextras_public_data', array(
			'site_url' => site_url(),
			'ajax_url' => admin_url('admin-ajax.php'),
		));

		// Enqueued script with localized data.
		wp_enqueue_script($this->plugin_name);

	}
}
