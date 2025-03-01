<?php
/**
 * A simple class to represent an Ajax handler.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Congress
 * @subpackage Congress/includes
 */

/**
 * A simple class to represent an Ajax handler.
 *
 * @since      1.0.0
 * @package    Congress
 * @subpackage Congress/includes
 * @author     Ryan Sauers <ryan.sauers@exploreveg.org>
 */
class Congress_AJAX_Handler {

	/**
	 * The name of the ajax handler.
	 *
	 * @var string $ajax_name
	 */
	private string $ajax_name;

	/**
	 * The name of the ajax function.
	 *
	 * @var string $func_name
	 */
	private string $func_name;

	/**
	 * The callee of the ajax function.
	 *
	 * @var object $callee
	 */
	private object $callee;

	/**
	 * Constructs an Congress_AJAX_Handler.
	 *
	 * @param object $callee an obect with the ajax function.
	 * @param string $func_name the name of a function defined in $callee.
	 * @param string $ajax_name the name of the ajax  handler.
	 */
	public function __construct( object $callee, string $func_name, string $ajax_name ) {
		$this->callee    = $callee;
		$this->func_name = $func_name;
		$this->ajax_name = $ajax_name;
	}

	/**
	 * Gets the name of the ajax function defined in @see get_callee.
	 *
	 * @return string
	 */
	public function get_func(): string {
		return $this->func_name;
	}

	/**
	 * The name of the ajax handler.
	 *
	 * @param bool $is_admin should be true if this is an ajax handler for admin pages.
	 * @return string
	 */
	public function get_name( bool $is_admin = false ): string {
		if ( $is_admin ) {
			return 'wp_ajax_' . $this->ajax_name;
		} else {
			return 'wp_ajax_nopriv_' . $this->ajax_name;
		}
	}

	/**
	 * The callee of the ajax function.
	 *
	 * @return object
	 */
	public function get_callee(): object {
		return $this->callee;
	}
}
