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

		$wpdb->query( 'START TRANSACTION' ); // phpcs:ignore

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

		$wpdb->query( 'COMMIT' ); // phpcs:ignore
	}

	/**
	 * Starts cron jobs for the plugin.
	 */
	private static function clean_cron(): void {
		$congress_cron = new Congress_Cron();
		$congress_cron->clear_cron();
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
		self::clean_tables();
	}
}
