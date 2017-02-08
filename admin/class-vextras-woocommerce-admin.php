<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://vextras.com
 * @since      1.0.1
 *
 * @package    Vextras_Woocommerce
 * @subpackage Vextras_Woocommerce/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Vextras_Woocommerce
 * @subpackage Vextras_Woocommerce/admin
 * @author     Ryan Hungate <ryan@vextras.com>
 */
class Vextras_Woocommerce_Admin extends Vextras_Woocommerce_Options {

    protected $version_flag = '1.01';

	/**
	 * @return Vextras_Woocommerce_Admin
	 */
	public static function connect()
	{
		$env = vextras_environment_variables();

		return new self('vextras-woocommerce', $env->version);
	}

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/vextras-woocommerce-admin.css?version='.$this->version_flag, array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

        wp_register_script($this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/vextras-woocommerce-admin.js?version='.$this->version_flag, array(), $this->version, false);

        wp_localize_script($this->plugin_name, 'vextras_plugin_data', array(
            'ajax_url' => admin_url('admin-ajax.php'),
        ));

        // Enqueued script with localized data.
        wp_enqueue_script($this->plugin_name);
	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */

	public function add_plugin_admin_menu() {
		/*
         *  Documentation : http://codex.wordpress.org/Administration_Menus
         */
		add_options_page( 'Vextras - WooCommerce Setup', 'Vextras', 'manage_options', $this->plugin_name, array($this, 'display_plugin_setup_page'));
	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    1.0.0
	 */
	public function add_action_links($links) {
		/*
        *  Documentation : https://codex.wordpress.org/Plugin_API/Filter_Reference/plugin_action_links_(plugin_file_name)
        */
		$settings_link = array(
			'<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_name ) . '">' . __('Settings', $this->plugin_name) . '</a>',
		);

		return array_merge($settings_link, $links);
	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_setup_page() {
		include_once( 'partials/vextras-woocommerce-admin-tabs.php' );
	}

	/**
	 *
	 */
	public function options_update() {

		register_setting($this->plugin_name, $this->plugin_name, array($this, 'validate'));
	}

	/**
	 * Depending on the version we're on we may need to run some sort of migrations.
	 */
	public function update_db_check() {
		// grab the current version set in the plugin variables
		$version = vextras_environment_variables()->version;

		// grab the saved version or default to 1.0.3 since that's when we first did this.
		$saved_version = get_site_option('vextras_woocommerce_version', '1.0.3');

		// if the saved version is less than the current version
		if (version_compare($version, $saved_version) > 0) {
			// resave the site option so this only fires once.
			update_site_option('vextras_woocommerce_version', $version);
		}
	}

	/**
	 * @param $input
	 * @return array
	 */
	public function validate($input) {

		$active_tab = isset($input['vextras_active_tab']) ? $input['vextras_active_tab'] : null;

		if (empty($active_tab)) {
			return $this->getOptions();
		}

		return (isset($data) && is_array($data)) ? array_merge($this->getOptions(), $data) : $this->getOptions();
	}

	public function log_in()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        if (!is_array($data)) $this->json(array('success' => false, 'message' => 'Invalid post!', 'data' => $data));

        $api = new VextrasApi();
        $this->json($api->finishInstall('login', $data['username'], $data['password']));
    }

    public function sign_up()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        if (!is_array($data)) $this->json(array('success' => false, 'message' => 'Invalid post!'));

        $api = new VextrasApi();
        $this->json($api->finishInstall('create', $data['username'], $data['password']));
    }
}
