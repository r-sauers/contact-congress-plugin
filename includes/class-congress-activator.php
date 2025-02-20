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
	 * Handles everything needed for plugin activation.
	 *
	 * Plugin activation mainly refers to setting up tables in the database.
	 *
	 * @since    1.0.0
	 */
	public static function activate(): void {

		Congress_Table_Manager::create_table(
			'campaign',
			array(
				'id mediumint(9) NOT NULL AUTO_INCREMENT',
				'name tinytext NOT NULL',
				'email_count int NOT NULL DEFAULT 0',
				"level ENUM('federal', 'state')",
				'PRIMARY KEY (id)',
			)
		);

		$campaign_table = Congress_Table_Manager::get_table_name( 'campaign' );
		Congress_Table_Manager::create_table(
			'email_template',
			array(
				'id mediumint(9) NOT NULL AUTO_INCREMENT',
				'subject tinytext NOT NULL',
				'body text NOT NULL',
				'campaign mediumint(9) NOT NULL',
				'PRIMARY KEY (id, campaign)',
				"FOREIGN KEY (campaign) REFERENCES $campaign_table(id) ON DELETE CASCADE",
			)
		);

		Congress_Table_Manager::create_table(
			'representative',
			array(
				'id mediumint(9) NOT NULL AUTO_INCREMENT',
				'title tinytext NOT NULL',
				'state tinytext NOT NULL',
				'district tinytext NOT NULL',
				'first_name tinytext NOT NULL',
				'last_name tinytext NOT NULL',
				"level ENUM('federal', 'state')",
				'PRIMARY KEY (id)',
			)
		);

		$representative_table = Congress_Table_Manager::get_table_name( 'representative' );
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
				"FOREIGN KEY (representative) REFERENCES $representative_table(id) ON DELETE CASCADE",
			)
		);

		Congress_Table_Manager::create_table(
			'campaign_excludes_rep',
			array(
				'campaign mediumint(9) NOT NULL',
				'representative mediumint(9) NOT NULL',
				'PRIMARY KEY (campaign, representative)',
				"FOREIGN KEY (representative) REFERENCES $representative_table(id) ON DELETE CASCADE",
				"FOREIGN KEY (campaign) REFERENCES $campaign_table(id) ON DELETE CASCADE",
			)
		);
	}
}
