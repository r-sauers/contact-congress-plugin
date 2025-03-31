<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the Representatives admin page.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Congress
 * @subpackage Congress/admin/partials
 */

/**
 * Imports Congress_Admin_Rep to get and display representatives.
 */
require_once plugin_dir_path( __FILE__ ) . 'class-congress-admin-rep.php';

/**
 * Imports Congress_Admin_Staffer to make template for staffers.
 */
require_once plugin_dir_path( __FILE__ ) . 'class-congress-admin-staffer.php';

?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div>
	<h1>Representatives</h1>
	<div id="congress-reps-container">
		<?php
		$reps = Congress_Admin_Rep::get_reps_from_db();
		foreach ( $reps as $rep ) {
			$rep->display();
		}
		?>
	</div>
	<button id="congress-add-rep-button" class="buttton button-primary">Add Representative</button>
	<button id="congress-sync-reps-button" class="buttton button-primary">Sync Representatives</button>
	<template id="congress-staffer-template">
		<?php
			Congress_Admin_Staffer::get_html_template();
		?>
	</template>
	<template id="congress-rep-template">
		<?php
			Congress_Admin_Rep::get_html_template();
		?>
	</template>
</div>
