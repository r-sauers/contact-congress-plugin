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

		$referer = Congress_Table_Manager::create_table(
			'referer',
			array(
				'id mediumint(9) NOT NULL AUTO_INCREMENT',
				'campaign_id mediumint(9) NOT NULL',
				'url_name varchar(32) UNIQUE NOT NULL',
				'real_name tinytext NOT NULL',
				'PRIMARY KEY (id, campaign_id)',
				'CHECK (url_name <> "")',
				'CHECK (real_name <> "")',
			)
		);

		$email = Congress_Table_Manager::create_table(
			'email',
			array(
				'id mediumint(9) NOT NULL AUTO_INCREMENT',
				'campaign_id mediumint(9) NOT NULL',
				'referer_id mediumint(9)',
				'sent_date DATE NOT NULL DEFAULT (CURRENT_DATE)',
				"FOREIGN KEY (referer_id, campaign_id) REFERENCES $referer(id, campaign_id)",
				'PRIMARY KEY (id, campaign_id)',
			)
		);

		$campaign = Congress_Table_Manager::create_table(
			'campaign',
			array(
				'id mediumint(9) NOT NULL AUTO_INCREMENT',
				'name tinytext NOT NULL',
				'created_date DATE NOT NULL DEFAULT (CURRENT_DATE)',
				'PRIMARY KEY (id)',
				'CHECK (name <> "")',
			)
		);

		$campaign_state = Congress_Table_Manager::create_table(
			'campaign_state',
			array(
				'campaign_id mediumint(9) NOT NULL UNIQUE',
				'state tinytext NOT NULL',
				'PRIMARY KEY (campaign_id)',
				'CHECK (state <> "")',
				"FOREIGN KEY (campaign_id) REFERENCES $campaign(id) ON DELETE CASCADE",
			)
		);

		$active_campaign = Congress_Table_Manager::get_table_name( 'active_campaign' );
		Congress_Table_Manager::create_table(
			'active_campaign',
			array(
				'id mediumint(9) NOT NULL',
				'PRIMARY KEY (id)',
				"FOREIGN KEY (id) REFERENCES $campaign(id) ON DELETE CASCADE",
			)
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( "ALTER TABLE $referer ADD FOREIGN KEY (campaign_id) REFERENCES $active_campaign(id) ON DELETE CASCADE;" );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( "ALTER TABLE $email ADD FOREIGN KEY (campaign_id) REFERENCES $active_campaign(id) ON DELETE CASCADE;" );

		$archived_campaign = Congress_Table_Manager::create_table(
			'archived_campaign',
			array(
				'id mediumint(9) NOT NULL',
				'email_count int NOT NULL',
				'archived_date DATE NOT NULL DEFAULT (CURRENT_DATE)',
				'PRIMARY KEY (id)',
				"FOREIGN KEY (id) REFERENCES $campaign(id) ON DELETE CASCADE",
			)
		);

		Congress_Table_Manager::create_table(
			'email_template',
			array(
				'id mediumint(9) NOT NULL AUTO_INCREMENT',
				'subject tinytext NOT NULL',
				'favorable bool NOT NULL DEFAULT false',
				'template text NOT NULL',
				'campaign_id mediumint(9) NOT NULL',
				'PRIMARY KEY (id, campaign_id)',
				"FOREIGN KEY (campaign_id) REFERENCES $campaign(id) ON DELETE CASCADE",
				'CHECK (template <> "")',
				'CHECK (subject <> "")',
			)
		);

		$representative = Congress_Table_Manager::create_table(
			'representative',
			array(
				'id mediumint(9) NOT NULL AUTO_INCREMENT',
				'title tinytext NOT NULL',
				'state tinytext NOT NULL',
				'district tinytext',
				'first_name tinytext NOT NULL',
				'last_name tinytext NOT NULL',
				"level ENUM('federal', 'state') NOT NULL",
				'PRIMARY KEY (id)',
				'CHECK (district <> "")',
				'CHECK (level <> "")',
				'CHECK (title <> "")',
				'CHECK (first_name <> "")',
				'CHECK (last_name <> "")',
			)
		);

		Congress_Table_Manager::create_table(
			'staffer',
			array(
				'id mediumint(9) NOT NULL AUTO_INCREMENT',
				'first_name tinytext NOT NULL',
				'last_name tinytext NOT NULL',
				'email tinytext NOT NULL',
				'title tinytext NOT NULL',
				'representative mediumint(9) NOT NULL',
				'PRIMARY KEY (id, representative)',
				"FOREIGN KEY (representative) REFERENCES $representative(id) ON DELETE CASCADE",
				'CHECK (first_name <> "")',
				'CHECK (last_name <> "")',
				'CHECK (email <> "")',
				'CHECK (title <> "")',
			)
		);

		Congress_Table_Manager::create_table(
			'campaign_excludes_rep',
			array(
				'campaign mediumint(9) NOT NULL',
				'representative mediumint(9) NOT NULL',
				'PRIMARY KEY (campaign, representative)',
				"FOREIGN KEY (representative) REFERENCES $representative(id) ON DELETE CASCADE",
				"FOREIGN KEY (campaign) REFERENCES $campaign(id) ON DELETE CASCADE",
			)
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
