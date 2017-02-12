<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://vextras.com
 * @since             1.0.0
 * @package           Vextras_Woocommerce
 *
 * @wordpress-plugin
 * Plugin Name:       Vextras for WooCommerce
 * Plugin URI:        https://vextras.com/connect-your-store/
 * Description:       Vextras - WooCommerce plugin
 * Version:           2.0.0
 * Author:            Vextras
 * Author URI:        https://vextras.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       vextras-woocommerce
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * @return object
 */
function vextras_environment_variables() {
	return require 'env.php';
}

/**
 * @return string
 */
function vextras_get_store_id() {
	return vextras_get_option('public_key');
}

/**
 * @param $key
 * @param null $default
 * @return null
 */
function vextras_get_option($key, $default = null) {
	$options = get_option('vextras-woocommerce');
	if (!is_array($options)) {
		return $default;
	}
	if (!array_key_exists($key, $options)) {
		return $default;
	}
	return $options[$key];
}

/**
 * @param $key
 * @param $default
 * @return mixed|void
 */
function vextras_get_data($key, $default) {
	return get_option('vextras-woocommerce-'.$key, $default);
}

/**
 * @param $date
 * @return DateTime
 */
function vextras_date_utc($date) {
	$timezone = wc_timezone_string();
	if (is_numeric($date)) {
		$stamp = $date;
		$date = new \DateTime('now', new DateTimeZone($timezone));
		$date->setTimestamp($stamp);
	} else {
		$date = new \DateTime($date, new DateTimeZone($timezone));
	}

	$date->setTimezone(new DateTimeZone('UTC'));
	return $date;
}

/**
 * @param $date
 * @return DateTime
 */
function vextras_date_local($date) {
    $timezone = vextras_get_option('store_timezone', 'America/New_York');
	if (is_numeric($date)) {
		$stamp = $date;
		$date = new \DateTime('now', new DateTimeZone('UTC'));
		$date->setTimestamp($stamp);
	} else {
		$date = new \DateTime($date, new DateTimeZone('UTC'));
	}

    $date->setTimezone(new DateTimeZone($timezone));
    return $date;
}

/**
 * @param array $data
 * @return mixed
 */
function vextras_array_remove_empty($data) {
	if (empty($data) || !is_array($data)) {
		return array();
	}
	foreach ($data as $key => $value) {
		if ($value === null || $value === '') {
			unset($data[$key]);
		}
	}
	return $data;
}

/**
 * @return array
 */
function vextras_get_timezone_list() {
	$zones_array = array();
	$timestamp = time();
	$current = date_default_timezone_get();

	foreach(timezone_identifiers_list() as $key => $zone) {
		date_default_timezone_set($zone);
		$zones_array[$key]['zone'] = $zone;
		$zones_array[$key]['diff_from_GMT'] = 'UTC/GMT ' . date('P', $timestamp);
	}

	date_default_timezone_set($current);

	return $zones_array;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-vextras-woocommerce-activator.php
 */
function activate_vextras_woocommerce()
{
	// if we don't have woocommerce we need to display a horrible error message before the plugin is installed.
	if (!is_plugin_active('woocommerce/woocommerce.php')) {
		// Deactivate the plugin
		deactivate_plugins(__FILE__);
		$error_message = __('The Vextras For WooCommerce plugin requires the <a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a> plugin to be active!', 'woocommerce');
		wp_die($error_message);
	}

	// ok we can activate this thing.
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-vextras-woocommerce-activator.php';

	Vextras_Woocommerce_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_vextras_woocommerce() {

}

/**
 * @param $action
 * @param $message
 * @param array $data
 * @return array|WP_Error
 */
function vextras_log($action, $message, $data = array())
{
    if (!class_exists('Vextras_Woocommerce')) {
        require plugin_dir_path( __FILE__ ) . 'includes/clase-vextras-woocommerce.php';
    }

	$options = Vextras_Woocommerce::getLoggingConfig();

	if (!$options->enable_logging || !$options->username) {
		return false;
	}

	$data = array(
		'account_id' => $options->account_id,
		'username' => $options->username,
		'store_domain' => site_url(),
		'platform' => 'woocommerce',
		'action' => $action,
		'message' => $message,
		'data' => $data,
	);

	return wp_remote_post($options->endpoint, array(
		'headers' => array(
			'Accept: application/json',
			'Content-Type: application/json'
		),
		'body' => json_encode($data),
	));
}

/**
 * Determine if a given string contains a given substring.
 *
 * @param  string  $haystack
 * @param  string|array  $needles
 * @return bool
 */
function vextras_string_contains($haystack, $needles)
{
	foreach ((array) $needles as $needle) {
		if ($needle != '' && mb_strpos($haystack, $needle) !== false) {
			return true;
		}
	}

	return false;
}


/**
 * @return int
 */
function vextras_get_product_count() {
	$posts = vextras_count_posts('product');
	$total = 0;
	foreach ($posts as $status => $count) {
		$total += $count;
	}
	return $total;
}

/**
 * @return int
 */
function vextras_get_order_count() {
	$posts = vextras_count_posts('shop_order');
	unset($posts['auto-draft']);
	$total = 0;
	foreach ($posts as $status => $count) {
		$total += $count;
	}
	return $total;
}

/**
 * @param $type
 * @return array|null|object
 */
function vextras_count_posts($type) {
	global $wpdb;
	$query = "SELECT post_status, COUNT( * ) AS num_posts FROM {$wpdb->posts} WHERE post_type = %s GROUP BY post_status";
	$posts = $wpdb->get_results( $wpdb->prepare($query, $type));
	$response = array();
	foreach ($posts as $post) {
		$response[$post->post_status] = $post->num_posts;
	}
	return $response;
}

register_activation_hook( __FILE__, 'activate_vextras_woocommerce' );
register_deactivation_hook( __FILE__, 'deactivate_vextras_woocommerce' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-vextras-woocommerce.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_vextras_woocommerce() {
	$env = vextras_environment_variables();
	$plugin = new Vextras_Woocommerce($env->environment, $env->version);
	$plugin->run();
}

if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
	$forwarded_address = explode(',',$_SERVER['HTTP_X_FORWARDED_FOR']);
	$_SERVER['REMOTE_ADDR'] = $forwarded_address[0];
}

/** Add all the Vextras hooks. */
run_vextras_woocommerce();
