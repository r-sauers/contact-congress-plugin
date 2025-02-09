<?php
/**
 * A collection of functions to help manage the plugin tables.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Congress
 * @subpackage Congress/includes
 */

/**
 * Imports dbDelta for creating/deleting tables.
 */
require_once ABSPATH . 'wp-admin/includes/upgrade.php';

/**
 * A collection of functions to help manage the plugin tables.
 *
 * @since      1.0.0
 * @package    Congress
 * @subpackage Congress/includes
 * @author     Ryan Sauers <ryan.sauers@exploreveg.org>
 */
class Congress_Table_Manager {

	/**
	 * Gets the real name of a WordPress MySQL table.
	 *
	 * @since    1.0.0
	 * @param string $name is the name of the table.
	 */
	public static function get_table_name( string $name ): string {
		global $wpdb;
		$table_name = $wpdb->prefix . 'congress_' . $name;
		return $table_name;
	}

	/**
	 * Creates a WordPress MySQL table.
	 *
	 * @since    1.0.0
	 * @param string $name is the name of the table.
	 * @param array  $statements is an array of MySQL strings describing table columns.
	 */
	public static function create_table( string $name, array $statements ): void {
		global $wpdb;

		$table_name      = self::get_table_name( $name );
		$charset_collate = $wpdb->get_charset_collate();

		$sql                = "CREATE TABLE $table_name (\n";
		$foreign_statements = array();
		foreach ( $statements as $statement ) {
			if ( str_contains( $statement, 'FOREIGN' ) ) {
				array_push( $foreign_statements, $statement );
			} else {
				$sql .= "$statement,\n";
			}
		}
		$sql = substr( $sql, 0, strlen( $sql ) - 2 ) . "\n) $charset_collate;";
		dbDelta( $sql );

		foreach ( $foreign_statements as $statement ) {
			$sql = "ALTER TABLE $table_name ADD $statement;";
			dbDelta( $sql );
		}
	}

	/**
	 * Gets the real name of a WordPress MySQL table.
	 *
	 * @since    1.0.0
	 * @param string $name is the name of the table.
	 */
	public static function delete_table( string $name ): void {
		$table_name = self::get_table_name( $name );
		dbDelta( "DROP TABLE $table_name" );
	}
}
