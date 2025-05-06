<?php
/**
 * An enum to model representative titles consistently.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Congress
 * @subpackage Congress/includes
 */

/**
 * An enum to model representative titles consistently.
 *
 * @since      1.0.0
 * @package    Congress
 * @subpackage Congress/includes
 * @author     Ryan Sauers <ryan.sauers@exploreveg.org>
 */
enum Congress_Title {

	case Representative;
	case Senator;

	/**
	 * Coerces an enum value from $str.
	 *
	 * @param string $str is a string e.g. 'senator'.
	 *
	 * @throws Error If unable to coerce.
	 */
	public static function from_string( string $str ): self {
		if ( 0 === strcasecmp( 'senator', $str ) ) {
			return self::Senator;
		} elseif ( 0 === strcasecmp( 'representative', $str ) ) {
			return self::Representative;
		} else {
			throw new Error( 'String could not be coerced to enum.' );
		}
	}

	/**
	 * Returns the title with the proper capitalization.
	 */
	public function to_display_string(): string {
		return match ( $this ) {
			Congress_Title::Representative => 'Representative',
			Congress_Title::Senator        => 'Senator',
		};
	}

	/**
	 * Returns a string for how the enum should be represented in the database.
	 */
	public function to_db_string(): string {
		return match ( $this ) {
			Congress_Title::Representative => 'Representative',
			Congress_Title::Senator        => 'Senator',
		};
	}
}
