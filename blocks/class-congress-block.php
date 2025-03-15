<?php
/**
 * The block functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Congress
 * @subpackage Congress/blocks
 */

/**
 * The block functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Congress
 * @subpackage Congress/block
 */
class Congress_Block {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $congress    The ID of this plugin.
	 */
	private $congress;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $congress       The name of the plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $congress, $version ) {
		$this->congress = $congress;
		$this->version  = $version;
	}

	/**
	 * Register the stylesheets for the blocks of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles(): void {
		wp_register_style(
			'congress-form-block',
			'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
			array(),
			$this->version,
			'all'
		);
	}

	/**
	 * Register the JavaScript for the blocks of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts(): void {
		wp_register_script(
			'congress-form-block',
			'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		wp_localize_script(
			'congress-form-block',
			'congressAjaxObj',
			array(
				'ajaxurl'           => admin_url( 'admin-ajax.php' ),
				'autocompleteNonce' => wp_create_nonce( 'autocomplete' ),
				'sessionNonce'      => wp_create_nonce( 'get-session' ),
			),
		);
	}

	/**
	 * Creates the blocks for the site.
	 */
	public function create_blocks(): void {
		register_block_type( __DIR__ . '/build/congress-form-block' );
	}
}
