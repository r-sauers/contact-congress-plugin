<?php
/**
 * Utils for displaying some flat field inputs.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Congress
 * @subpackage Congress/admin/partials
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Utils for displaying some flat field inputs.
 */
class Congress_Admin_Flat_Input {

	/**
	 * Displays a dropdown with label text to the left.
	 *
	 * @param string $id is the id of the input.
	 * @param string $label is the label text.
	 * @param string $name is the input name.
	 * @param string $value is the input value.
	 * @param array  $options are the values that can be selected in the dropdown.
	 * The array may be a list of strings, or a list of associative arrays with the keys: 'label' and 'value'.
	 */
	public static function display_dropdown( string $id, string $label, string $name, string $value, array $options ): void {
		?>
		<span>
			<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
		</span>
		<select
			id="<?php echo esc_attr( $id ); ?>"
			name="<?php echo esc_attr( $name ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
		>
		<?php
		foreach ( $options as $option ) {
			if ( is_string( $option ) ) {
				?>
				<option
					<?php echo esc_html( $option === $value ? 'selected' : '' ); ?>
				><?php echo esc_html( $option ); ?></option>
				<?php

			} elseif ( is_array( $option ) ) {
				$option_label = $option['label'];
				$option_value = $option['value'];
				?>
				<option
					value="<?php echo esc_attr( $option_value ); ?>"
					<?php echo esc_html( $option_value === $value ? 'selected' : '' ); ?>
				><?php echo esc_html( $option_label ); ?></option>
				<?php
			}
		}
		?>
		</select>
		<?php
	}
}
