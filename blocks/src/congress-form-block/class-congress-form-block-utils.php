<?php
/**
 * Defines php utils for the 'render.php'.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Congress
 * @subpackage Congress/blocks
 */

/**
 * Imports Table Manager for getting table names;
 */
require_once plugin_dir_path( __FILE__ ) .
	'../../../includes/class-congress-table-manager.php';

/**
 * Defines php utils for the 'render.php'.
 *
 * @since      1.0.0
 * @package    Congress
 * @subpackage Congress/blocks
 */
class Congress_Form_Block_Utils {

	/**
	 * The name of the block.
	 *
	 * @var string $block_name
	 */
	private string $block_name;

	/**
	 * The prefix for the block's ids and classes.
	 *
	 * @var string $prefix
	 */
	private string $prefix;

	/**
	 * Constructs the class.
	 */
	public function __construct() {

		$block_wrapper_attributes = get_block_wrapper_attributes();

		$class_value_str = strstr( substr( strstr( $block_wrapper_attributes, 'class="' ), 7 ), '"', true );
		$class_value     = strtok( $class_value_str, ' ' );
		while ( false !== $class_value && ! str_contains( $class_value, 'congress' ) ) {
			$class_value = strtok( ' ' );
		}
		$block_name = $class_value;

		$this->block_name = $block_name;
		$this->prefix     = $block_name . '__';
	}

	// phpcs:disable
	/**
	 * Draws the class attribute on the page with the correct prefix. e.g. 'class="foo bar"'.
	 *
	 * @param array<string> $class_names is the list of class names. // phpcs:enable
	 */
	public function class_name( ...$class_names ): void {
		$class_string = '';
		foreach ( $class_names as &$class_name ) {
			$class_string .= $this->prefix . $class_name . ' ';
		}
		unset( $class_name );

		echo 'class="' . esc_attr( $class_string ) . '"';
	}

	// phpcs:disable
	/**
	 * Outputs $class_names as a ' ' separated list with the correct prefix added to each class.
	 *
	 * @param array<string> $class_names is the list of class names. // phpcs:enable
	 */
	public function inline_class( ...$class_names ): void {
		$class_string = ' ';
		foreach ( $class_names as &$class_name ) {
			$class_string .= $this->prefix . $class_name . ' ';
		}
		unset( $class_name );

		echo esc_attr( $class_string );
	}

	/**
	 * Outputs the id attribute with the correct prefix. e.g. 'id="foo"'
	 *
	 * @param string $id is the id of the element.
	 */
	public function id( string $id ): void {
		echo 'id="' . esc_attr( $this->prefix . $id ) . '"';
	}

	/**
	 * Outputs the for attribute with the correct prefix. e.g. 'for="foo"'
	 *
	 * @param string $id is the id of the element pointed to.
	 */
	public function html_for( $id ): void {
		echo 'for="' . esc_attr( $this->prefix . $id ) . '"';
	}

	/**
	 * Outputs the href attribute with the correct prefix. e.g. 'href="#foo"'
	 *
	 * @param string $id is the id of the element the href points to.
	 */
	public function href( $id ): void {
		echo 'href="#' . esc_attr( $this->prefix . $id ) . '"';
	}

	/**
	 * Fetches 1 campaign email template from the db.
	 *
	 * @param array $attributes are the block attributes.
	 *
	 * @return stdClass|false the campaign email template data or false on failure.
	 */
	public function get_campaign_template( $attributes ): stdClass|false {
		if (
			! isset( $attributes['campaignName'] ) &&
			! isset( $attributes['campaignID'] )
		) {
			return false;
		}

		$campaign_name = $attributes['campaignName'];
		$campaign_id   = $attributes['campaignID'];

		global $wpdb;
		$campaign_t = Congress_Table_Manager::get_table_name( 'campaign' );
		$template_t = Congress_Table_Manager::get_table_name( 'email_template' );

		$results = false;
		$results = $wpdb->get_results( // phpcs:ignore
			$wpdb->prepare(
				"SELECT * FROM $campaign_t LEFT JOIN $template_t " . // phpcs:ignore
				"ON $campaign_t.id = $template_t.campaign_id " . // phpcs:ignore
				"WHERE $campaign_t.id = %d " . // phpcs:ignore
				'ORDER BY	RAND() LIMIT 1',
				array(
					$campaign_id,
				)
			)
		);
		if ( false === $results ) {
			return false;
		}
		if ( 0 === $results ) {
			return false;
		}

		return $results[0];
	}
}
