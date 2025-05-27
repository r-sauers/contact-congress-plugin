<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://github.com/r-sauers/contact-congress-plugin
 * @since             1.0.0
 * @package           Congress
 *
 * @wordpress-plugin
 * Plugin Name:       Contact Congress
 * Plugin URI:        https://github.com/r-sauers/contact-congress-plugin
 * Description:       A plugin for visitors of your site to contact members of congress with pre-filled emails.
 * Version:           1.0.0
 * Author:            Ryan Sauers
 * Author URI:        https://github.com/r-sauers
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       contact-congress
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die();
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'CONTACTCONGRESS', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-congress-activator.php
 */
function activate_congress() {
	require_once plugin_dir_path( __FILE__ ) .
		'includes/class-congress-activator.php';
	Congress_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-congress-deactivator.php
 */
function deactivate_congress() {
	require_once plugin_dir_path( __FILE__ ) .
		'includes/class-congress-deactivator.php';
	Congress_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_congress' );
register_deactivation_hook( __FILE__, 'deactivate_congress' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-congress.php';

/**
 * Autoload dependencies.
 */
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_congress() {
	$plugin = new Congress();
	$plugin->run();
}
run_congress();
