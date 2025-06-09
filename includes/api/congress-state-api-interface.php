<?php
/**
 * Defines the interface for state-level API requests.
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
 * Include Congress_State enum.
 */
require_once plugin_dir_path( __DIR__ ) . 'enum-congress-state.php';

/**
 * Defines the interface for state-level API requests.
 *
 * @since      1.0.0
 * @package    Congress
 * @subpackage Congress/includes
 * @author     Ryan Sauers <ryan.sauers@exploreveg.org>
 */
interface Congress_State_API_Interface {

	/**
	 * Gets the API's state.
	 */
	public static function get_state(): Congress_State;

	/**
	 * Gets all of the state representatives.
	 *
	 * @return array<Congress_Rep_Interface>|false
	 */
	public function get_all_reps(): array|false;

	/**
	 * Finds the state senators and representatives for a given location.
	 *
	 * @param float $latitude is the latitude of the location.
	 * @param float $longitude is the longitude of the location.
	 *
	 * @return array<Congress_Rep_Interface>|false the api results or false on failure.
	 */
	public function get_state_reps( float $latitude, float $longitude ): array|false;

	/**
	 * Finds the federal representative for a given location.
	 *
	 * @param float $latitude is the latitude of the location.
	 * @param float $longitude is the longitude of the location.
	 *
	 * @return array<Congress_Rep_Interface>|false the api results or false on failure.
	 */
	public function get_federal_reps( float $latitude, float $longitude ): array|false;
}
