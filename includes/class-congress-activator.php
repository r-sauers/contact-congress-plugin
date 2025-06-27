<?php
/**
 * Fired during plugin activation
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Congress
 * @subpackage Congress/includes
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Imports Table Manager for creating tables.
 */
require_once plugin_dir_path( __FILE__ ) .
	'class-congress-table-manager.php';

/**
 * Imports Congress_Cron for setting up cron jobs.
 */
require_once plugin_dir_path( __FILE__ ) .
	'class-congress-cron.php';

/**
 * Imports dbDelta for altering tables.
 */
require_once ABSPATH . 'wp-admin/includes/upgrade.php';


/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 * This mainly means setting up tables
 *
 * @since      1.0.0
 * @package    Congress
 * @subpackage Congress/includes
 * @author     Ryan Sauers <ryan.sauers@exploreveg.org>
 */
class Congress_Activator {

	/**
	 * Initializes plugin tables.
	 */
	private static function init_tables(): void {

		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$referer = Congress_Table_Manager::get_table_name( 'referer' );
		dbDelta(
			$wpdb->prepare(
				'CREATE TABLE %i(' .
					'id mediumint(9) NOT NULL AUTO_INCREMENT, ' .
					'campaign_id mediumint(9) NOT NULL, ' .
					'url_name varchar(32) UNIQUE NOT NULL, ' .
					'real_name tinytext NOT NULL, ' .
					'PRIMARY KEY (id, campaign_id), ' .
					'CHECK (url_name <> ""), ' .
					'CHECK (real_name <> "") ' .
				')',
				array(
					$referer,
				)
			) . " $charset_collate;"
		);

		$email = Congress_Table_Manager::get_table_name( 'email' );
		dbDelta(
			$wpdb->prepare(
				'CREATE TABLE %i(' .
					'id mediumint(9) NOT NULL AUTO_INCREMENT, ' .
					'campaign_id mediumint(9) NOT NULL, ' .
					'referer_id mediumint(9), ' .
					'sent_date DATE NOT NULL DEFAULT (CURRENT_DATE), ' .
					'PRIMARY KEY (id, campaign_id), ' .
				')',
				array(
					$email,
				)
			) . " $charset_collate;"
		);

		$campaign = Congress_Table_Manager::get_table_name( 'campaign' );
		dbDelta(
			$wpdb->prepare(
				'CREATE TABLE %i(' .
					'id mediumint(9) NOT NULL AUTO_INCREMENT, ' .
					'name tinytext NOT NULL, ' .
					'created_date DATE NOT NULL DEFAULT (CURRENT_DATE), ' .
					'PRIMARY KEY (id), ' .
					'CHECK (name <> ""), ' .
				')',
				array(
					$campaign,
				)
			) . " $charset_collate;"
		);

		$campaign_state = Congress_Table_Manager::get_table_name( 'campaign_state' );
		dbDelta(
			$wpdb->prepare(
				'CREATE TABLE %i(' .
					'campaign_id mediumint(9) NOT NULL UNIQUE, ' .
					'state tinytext NOT NULL, ' .
					'PRIMARY KEY (campaign_id), ' .
					'CHECK (state <> ""), ' .
				')',
				array(
					$campaign_state,
				)
			) . " $charset_collate;"
		);

		$active_campaign = Congress_Table_Manager::get_table_name( 'active_campaign' );
		dbDelta(
			$wpdb->prepare(
				'CREATE TABLE %i(' .
					'id mediumint(9) NOT NULL, ' .
					'PRIMARY KEY (id), ' .
				')',
				array(
					$active_campaign,
				)
			) . " $charset_collate;"
		);

		$archived_campaign = Congress_Table_Manager::get_table_name( 'archived_campaign' );
		dbDelta(
			$wpdb->prepare(
				'CREATE TABLE %i(' .
					'id mediumint(9) NOT NULL, ' .
					'email_count int NOT NULL, ' .
					'archived_date DATE NOT NULL DEFAULT (CURRENT_DATE), ' .
					'PRIMARY KEY (id), ' .
				')',
				array(
					$archived_campaign,
				)
			) . " $charset_collate;"
		);

		$email_template = Congress_Table_Manager::get_table_name( 'email_template' );
		dbDelta(
			$wpdb->prepare(
				'CREATE TABLE %i(' .
					'id mediumint(9) NOT NULL AUTO_INCREMENT, ' .
					'subject tinytext NOT NULL, ' .
					'favorable bool NOT NULL DEFAULT false, ' .
					'template text NOT NULL, ' .
					'campaign_id mediumint(9) NOT NULL, ' .
					'PRIMARY KEY (id, campaign_id), ' .
					'CHECK (template <> ""), ' .
					'CHECK (subject <> ""), ' .
				')',
				array(
					$email_template,
				)
			) . " $charset_collate;"
		);

		$representative = Congress_Table_Manager::get_table_name( 'representative' );
		dbDelta(
			$wpdb->prepare(
				'CREATE TABLE %i(' .
					'id mediumint(9) NOT NULL AUTO_INCREMENT, ' .
					'title tinytext NOT NULL, ' .
					'state tinytext NOT NULL, ' .
					'district tinytext, ' .
					'first_name tinytext NOT NULL, ' .
					'last_name tinytext NOT NULL, ' .
					"level ENUM('federal', 'state') NOT NULL" .
					'PRIMARY KEY (id), ' .
					'CHECK (district <> ""), ' .
					'CHECK (level <> ""), ' .
					'CHECK (title <> ""), ' .
					'CHECK (first_name <> ""), ' .
					'CHECK (last_name <> ""), ' .
				')',
				array(
					$representative,
				)
			) . " $charset_collate;"
		);

		$staffer = Congress_Table_Manager::get_table_name( 'staffer' );
		dbDelta(
			$wpdb->prepare(
				'CREATE TABLE %i(' .
					'id mediumint(9) NOT NULL AUTO_INCREMENT, ' .
					'first_name tinytext NOT NULL, ' .
					'last_name tinytext NOT NULL, ' .
					'email tinytext NOT NULL, ' .
					'title tinytext NOT NULL, ' .
					'representative mediumint(9) NOT NULL, ' .
					'PRIMARY KEY (id, representative), ' .
					'CHECK (first_name <> ""), ' .
					'CHECK (last_name <> ""), ' .
					'CHECK (email <> ""), ' .
					'CHECK (title <> ""), ' .
				')',
				array(
					$staffer,
				)
			) . " $charset_collate;"
		);

		$campaign_excludes_rep = Congress_Table_Manager::get_table_name( 'campaign_excludes_rep' );
		dbDelta(
			$wpdb->prepare(
				'CREATE TABLE %i(' .
					'campaign mediumint(9) NOT NULL, ' .
					'representative mediumint(9) NOT NULL, ' .
					'PRIMARY KEY (campaign, representative), ' .
				')',
				array(
					$campaign_excludes_rep,
				)
			) . " $charset_collate;"
		);
	}

	/**
	 * Handles everything needed for plugin activation.
	 *
	 * Plugin activation mainly refers to setting up tables in the database.
	 *
	 * @since    1.0.0
	 */
	public static function activate(): void {
		self::init_tables();
	}
}
