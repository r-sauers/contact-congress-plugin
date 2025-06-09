<?php
/**
 * An enum to model representative levels consistently.
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
 * An enum to model representative levels consistently.
 *
 * @since      1.0.0
 * @package    Congress
 * @subpackage Congress/includes
 * @author     Ryan Sauers <ryan.sauers@exploreveg.org>
 */
enum Congress_Level {

	case Federal;
	case State;

	/**
	 * Coerces an enum value from $str.
	 *
	 * @param string $str is a string e.g. 'state'.
	 *
	 * @throws Error If unable to coerce.
	 */
	public static function from_string( string $str ): self {
		if ( 0 === strcasecmp( 'state', $str ) ) {
			return self::State;
		} elseif ( 0 === strcasecmp( 'federal', $str ) ) {
			return self::Federal;
		} else {
			throw new Error( 'String could not be coerced to enum.' );
		}
	}

	/**
	 * Returns the level with the proper capitalization.
	 */
	public function to_display_string(): string {
		return match ( $this ) {
			Congress_Level::State   => 'State',
			Congress_Level::Federal => 'Federal',
		};
	}

	/**
	 * Returns a string for how the enum should be represented in the database.
	 */
	public function to_db_string(): string {
		return match ( $this ) {
			Congress_Level::State   => 'state',
			Congress_Level::Federal => 'federal',
		};
	}
}
