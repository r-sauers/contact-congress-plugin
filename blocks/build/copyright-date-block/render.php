<?php
/**
 * Copyright block
 *
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 *
 * @package Congress
 */

$current_year = gmdate( 'Y' );

if (
	! empty( $attributes['startingYear'] ) &&
	! empty( $attributes['showStartingYear'] )
) {
	$display_date = $attributes['startingYear'] . '–' . $current_year;
} else {
	$display_date = $current_year;
}
?>
<p <?php echo esc_attr( get_block_wrapper_attributes() ); ?>>
© <?php echo esc_html( $display_date ); ?>
</p>
