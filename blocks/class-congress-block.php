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

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get Captcha information.
 */
require_once plugin_dir_path( __DIR__ ) . 'includes/class-congress-captcha.php';

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
			'congress-select2',
			plugins_url( 'public/css/select2.min.css', __DIR__ ),
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
			false,
			array( 'jquery' ),
			$this->version,
			true
		);
		wp_register_script(
			'congress-select2',
			plugins_url( 'public/js/select2.min.js', __DIR__ ),
			array( 'jquery' ),
			$this->version,
			true
		);
		$option_name = Congress_Captcha::$client_key_options_name;
		$options     = get_option( $option_name );
		$field_name  = Congress_Captcha::$client_key_field_name;
		if ( false !== $options && isset( $options[ $field_name ] ) ) {
			$captcha_client = $options[ $field_name ];
			wp_register_script(
				'congress-captcha',
				"https://www.google.com/recaptcha/api.js?render=$captcha_client",
				array(),
				$this->version,
				true
			);
		} else {
			$url = admin_url( 'admin.php?page=' . Congress_Admin::$main_page_slug );

			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log(
				"Error: Please set the client google recaptcha key for Contact Congress plugin at $url"
			);
			wp_register_script(
				'congress-captcha',
				'https://www.google.com/recaptcha/api.js',
				array(),
				$this->version,
				true
			);
		}
		wp_localize_script(
			'congress-form-block',
			'congressCaptchaObj',
			array(
				'clientSecret' => $captcha_client,
			),
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
