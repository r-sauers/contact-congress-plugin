<?php
/**
 * A collection of AJAX handlers.
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
 * Imports Table Manager for getting table names;
 */
require_once plugin_dir_path( __FILE__ ) .
	'../class-congress-table-manager.php';

/**
 * Imports Congress_AJAX_Collection interface.
 */
require_once plugin_dir_path( __FILE__ ) .
	'class-congress-ajax-collection.php';

/**
 * Imports the representatives ajax collection.
 */
require_once plugin_dir_path( __FILE__ ) .
	'class-congress-rep-ajax.php';

/**
 * Imports the staffers ajax collection.
 */
require_once plugin_dir_path( __FILE__ ) .
	'class-congress-staffer-ajax.php';

/**
 * Imports the campaigns ajax collection.
 */
require_once plugin_dir_path( __FILE__ ) .
	'class-congress-campaign-ajax.php';

/**
 * Imports the email templates ajax collection.
 */
require_once plugin_dir_path( __FILE__ ) .
	'class-congress-template-ajax.php';

/**
 * Imports the locations ajax collection.
 */
require_once plugin_dir_path( __FILE__ ) .
	'class-congress-location-ajax.php';

/**
 * Imports the locations ajax collection.
 */
require_once plugin_dir_path( __FILE__ ) .
	'class-congress-email-ajax.php';

/**
 * Imports the state ajax collection.
 */
require_once plugin_dir_path( __FILE__ ) .
	'class-congress-state-ajax.php';

/**
 * A collection of AJAX handlers.
 *
 * @since      1.0.0
 * @package    Congress
 * @subpackage Congress/includes
 * @author     Ryan Sauers <ryan.sauers@exploreveg.org>
 */
class Congress_AJAX {

	/**
	 * The singleton instance.
	 *
	 * @var Congress_AJAX $instance
	 */
	private static ?Congress_AJAX $instance = null;

	/**
	 * Retrieves the singleton instance.
	 *
	 * @return Congress_AJAX
	 */
	public static function get_instance(): Congress_AJAX {
		if ( null === self::$instance ) {
			self::$instance = new Congress_AJAX();
		}
		return self::$instance;
	}

	/**
	 * An array of ajax collections.
	 *
	 * @var array<Congress_AJAX_Collection> $ajax_collections
	 */
	private $ajax_collections;

	/**
	 * Constructs this class.
	 */
	public function __construct() {
		$this->ajax_collections = array(
			new Congress_Rep_AJAX(),
			new Congress_Staffer_AJAX(),
			new Congress_Campaign_AJAX(),
			new Congress_Template_AJAX(),
			new Congress_Location_AJAX(),
			new Congress_Email_AJAX(),
			new Congress_State_AJAX(),
		);
	}

	/**
	 * Returns a list of ajax handlers for admin page.
	 *
	 * @return array<Congress_AJAX_Handler>
	 */
	public function get_admin_handlers(): array {
		$handlers = array();
		foreach ( $this->ajax_collections as $ajax_collection ) {
			array_push( $handlers, ...$ajax_collection->get_admin_handlers() );
		}
		return $handlers;
	}

	/**
	 * Returns a list of ajax handlers for public page.
	 *
	 * @return array<Congress_AJAX_Handler>
	 */
	public function get_public_handlers(): array {

		$handlers = array();
		foreach ( $this->ajax_collections as $handler_group ) {
			array_push( $handlers, ...$handler_group->get_public_handlers() );
		}
		return $handlers;
	}
}
