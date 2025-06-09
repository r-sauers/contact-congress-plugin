<?php
/**
 * Fired during plugin deactivation
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Congress
 * @subpackage Congress/includes
 */

/**
 * Imports Table Manager for deleting tables.
 */
require_once plugin_dir_path( __FILE__ ) .
	'class-congress-table-manager.php';

/**
 * Imports Congress_Cron for cleaning up cron jobs.
 */
require_once plugin_dir_path( __FILE__ ) .
	'class-congress-cron.php';

/**
 * Imports Congress_Google_Places_API.
 */
require_once plugin_dir_path( __FILE__ ) .
	'api/class-congress-google-places-api.php';

/**
 * Imports Congress_Congress_API.
 */
require_once plugin_dir_path( __FILE__ ) .
	'api/class-congress-congress-api.php';

/**
 * Imports Congress_Captcha.
 */
require_once plugin_dir_path( __FILE__ ) .
	'class-congress-captcha.php';

/**
 * Imports Congress_State_Settings.
 */
require_once plugin_dir_path( __DIR__ ) .
	'admin/partials/states/class-congress-state-settings.php';

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Congress
 * @subpackage Congress/includes
 * @author     Ryan Sauers <ryan.sauers@exploreveg.org>
 */
class Congress_Deactivator {

	/**
	 * Drops the plugin's tables from the database.
	 */
	private static function clean_tables(): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( 'START TRANSACTION' );

		Congress_Table_Manager::delete_table( 'email' );
		Congress_Table_Manager::delete_table( 'referer' );
		Congress_Table_Manager::delete_table( 'active_campaign' );
		Congress_Table_Manager::delete_table( 'archived_campaign' );
		Congress_Table_Manager::delete_table( 'email_template' );
		Congress_Table_Manager::delete_table( 'campaign_excludes_rep' );
		Congress_Table_Manager::delete_table( 'campaign_state' );
		Congress_Table_Manager::delete_table( 'campaign' );
		Congress_Table_Manager::delete_table( 'staffer' );
		Congress_Table_Manager::delete_table( 'representative' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( 'COMMIT' );
	}

	/**
	 * Starts cron jobs for the plugin.
	 */
	private static function clean_cron(): void {
		$congress_cron = new Congress_Cron();
		$congress_cron->clear_cron();
	}

	/**
	 * Delete WordPress options.
	 */
	private static function clean_options(): void {
		Congress_State_Settings::clean_all_settings();
		delete_option( Congress_Captcha::$server_key_options_name );
		delete_option( Congress_Captcha::$client_key_options_name );
		delete_option( Congress_Congress_API::$options_name );
		delete_option( Congress_Google_Places_API::$options_name );
	}

	/**
	 * Handles plugin deactivation.
	 *
	 * Cleans up plugin tables.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate(): void {
		self::clean_cron();
		self::clean_options();
		self::clean_tables();
	}
}
