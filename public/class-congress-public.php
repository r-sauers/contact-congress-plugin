<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Congress
 * @subpackage Congress/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Congress
 * @subpackage Congress/public
 * @author     Ryan Sauers <ryan.sauers@exploreveg.org>
 */
class Congress_Public {

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
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles(): void {
		wp_enqueue_style(
			$this->congress,
			plugin_dir_url( __FILE__ ) . 'css/congress-public.css',
			array(),
			$this->version,
			'all'
		);
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts(): void {
		wp_enqueue_script(
			$this->congress,
			plugin_dir_url( __FILE__ ) . 'js/congress-public.js',
			array( 'jquery' ),
			$this->version,
			false
		);
	}
}
