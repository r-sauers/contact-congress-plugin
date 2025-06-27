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

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A class to make transactions easier to write.
 *
 * @since      1.0.0
 * @package    Congress
 * @subpackage Congress/includes
 * @author     Ryan Sauers <ryan.sauers@exploreveg.org>
 */
class Congress_Table_Transaction {

	/**
	 * Whether or not any commands failed.
	 *
	 * @var boolean
	 */
	private $failed;

	/**
	 * Errors from the command.
	 *
	 * @var any?
	 */
	private $fail_result;

	/**
	 * Errors from the database.
	 *
	 * @var string?
	 */
	private $wpdb_error;

	/**
	 * A stored list of query results.
	 *
	 * @var array
	 */
	private $results;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->failed      = false;
		$this->wpdb_error  = null;
		$this->fail_result = null;
		$this->results     = array();

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( 'START TRANSACTION' );
	}

	/**
	 * Run a query in the transaction.
	 *
	 * @param function $query_handler is a handler that returns the result of $wpdb sql functions.
	 * Takes as argument insert_id: int.
	 * @param bool     $error_on_0 when set to true will cause a failure.
	 * if 0 is returned as a result from $query_handler.
	 */
	public function query( $query_handler, $error_on_0 = false ): Congress_Table_Transaction {
		if ( $this->failed ) {
			return $this;
		}
		global $wpdb;
		$res = $query_handler( $wpdb->insert_id );
		if ( null === $res || false === $res || is_wp_error( $res ) || ( $error_on_0 && 0 === $res ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( 'ROLLBACK' );
			$this->failed      = true;
			$this->fail_result = $res;
			if ( 0 !== $res ) {
				$this->wpdb_error = $wpdb->last_error;
			}
		} else {
			array_push( $this->results, $res );
		}
		return $this;
	}

	/**
	 * Submits the transaction.
	 *
	 * @param function $error is an error handler, takes arguments:
	 * query_return_value {any}, and wp_error {string}.
	 * @param function $success is a success handler, takes arguments:
	 * results {array<any>} of query results.
	 * @return bool is true if successful.
	 */
	public function submit( $error, $success ): boolean {
		global $wpdb;

		if ( $this->failed ) {
			$error( $this->fail_result, $this->wpdb_error );
			return false;
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( 'COMMIT' );
			$success( $this->results );
			return true;
		}
	}
}
