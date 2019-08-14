<?php
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.1
 * @package    Vextras_Woocommerce
 * @subpackage Vextras_Woocommerce/includes
 * @author     Ryan Hungate <ryan@vextras.com>
 */
class Vextras_Woocommerce_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		// only do this if the option has never been set before.
		if (get_option('vextras_woocommerce_plugin_do_activation_redirect', null) === null) {
			add_option('vextras_woocommerce_plugin_do_activation_redirect', true);
		}

		// create the abandoned cart tables because we need them for the sync jobs.
		static::create_abandoned_cart_tables();

        $keys = Vextras_Woocommerce_Activator::create_keys('Vextras', 'USER_ID', 'read_write');

        update_option('vextras-woocommerce', $data = array(
            'verified_account' => false,
            'unique_install_key' => wc_rand_hash(),
            'woo_consumer_key' => $keys['consumer_key'],
            'woo_consumer_secret' => $keys['consumer_secret']
        ));

        $api = new VextrasApi();
        $api->installUniqueKey();
	}

	/**
	 * Create keys.
	 *
	 * @since  2.4.0
	 *
	 * @param  string $app_name
	 * @param  string $app_user_id
	 * @param  string $scope
	 *
	 * @return array
	 */
	public static function create_keys( $app_name, $app_user_id, $scope ) {
		global $wpdb;

		$description = sprintf( __( '%s - API %s (created on %s at %s).', 'woocommerce' ), wc_clean( $app_name ), __( 'Read/Write', 'woocommerce' ), date_i18n( wc_date_format() ), date_i18n( wc_time_format() ) );
		$user        = wp_get_current_user();

		// Created API keys.
		$permissions     = 'read_write';
		$consumer_key    = 'ck_' . wc_rand_hash();
		$consumer_secret = 'cs_' . wc_rand_hash();

		$wpdb->insert(
			$wpdb->prefix . 'woocommerce_api_keys',
			array(
				'user_id'         => $user->ID,
				'description'     => $description,
				'permissions'     => $permissions,
				'consumer_key'    => wc_api_hash( $consumer_key ),
				'consumer_secret' => $consumer_secret,
				'truncated_key'   => substr( $consumer_key, -7 )
			),
			array('%d', '%s', '%s', '%s', '%s', '%s')
		);

		return array(
			'key_id'          => $wpdb->insert_id,
			'user_id'         => $app_user_id,
			'consumer_key'    => $consumer_key,
			'consumer_secret' => $consumer_secret,
			'key_permissions' => $permissions
		);
	}

	/**
	 * Create the queue tables in the DB so we can use it for syncing.
	 */
	public static function create_abandoned_cart_tables()
	{
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		global $wpdb;

		$wpdb->hide_errors();

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}vextras_carts (
				id VARCHAR (255) NOT NULL,
				email VARCHAR (100) NOT NULL,
				user_id INT (11) DEFAULT NULL,
                cart text NOT NULL,
                created_at datetime NOT NULL
				) $charset_collate;";

		dbDelta( $sql );

		// set the vextras woocommerce version at the time of install
		update_site_option('vextras_woocommerce_version', vextras_environment_variables()->version);
	}
}
