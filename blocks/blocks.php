<?php
/**
 * Creates blocks
 *
 * @package Congress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit(); // Exit if accessed directly.
}

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function create_block_copyright_date_block_block_init() {
	register_block_type( __DIR__ . '/build/copyright-date-block' );
}
add_action( 'init', 'create_block_copyright_date_block_block_init' );
