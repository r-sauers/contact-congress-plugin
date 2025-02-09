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
	 * Handles plugin deactivation.
	 *
	 * Cleans up plugin tables.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate(): void {

		Congress_Table_Manager::delete_table( 'campaign' );
		Congress_Table_Manager::delete_table( 'email_template' );
		Congress_Table_Manager::delete_table( 'representative' );
		Congress_Table_Manager::delete_table( 'staffer' );
		Congress_Table_Manager::delete_table( 'campaign_excludes_rep' );
	}
}
