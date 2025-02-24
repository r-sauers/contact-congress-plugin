<?php
/**
 * A class for creating a component.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Congress
 * @subpackage Congress/admin/partials
 */

/**
 * Responsible for displaying stacked inputs.
 */
class Congress_Admin_Stacked_Input {

	/**
	 * An array of states.
	 *
	 * @var array $states
	 */
	private static array $states = array( 'AL', 'AK', 'AZ', 'AR', 'CA', 'CO', 'CT', 'DE', 'FL', 'GA', 'HI', 'ID', 'IL', 'IN', 'IA', 'KS', 'KY', 'LA', 'ME', 'MD', 'MA', 'MI', 'MN', 'MS', 'MO', 'MT', 'NE', 'NV', 'NH', 'NJ', 'NM', 'NY', 'NC', 'ND', 'OH', 'OK', 'OR', 'PA', 'RI', 'SC', 'SD', 'TN', 'TX', 'UT', 'VT', 'VA', 'WA', 'WV', 'WI', 'WY' );

	/**
	 * Displays a text field with small label text above.
	 *
	 * @param string $id is the id of the input.
	 * @param string $label is the label text.
	 * @param string $name is the input name.
	 * @param string $value is the input value.
	 * @param string $placeholder is the input placeholder.
	 * @param string $size is the size of the input.
	 */
	public static function display_text( string $id, string $label, string $name, string $value, string $placeholder = '', string $size = '20' ): void {
		?>
		<div class="congress-stacked-input">
			<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
			<input 
				id="<?php echo esc_attr( $id ); ?>"
				type="text"
				value="<?php echo esc_attr( $value ); ?>"
				name="<?php echo esc_attr( $name ); ?>"
				placeholder="<?php echo esc_attr( $placeholder ); ?>"
				size="<?php echo esc_attr( $size ); ?>"
			/>
		</div>
		<?php
	}

	/**
	 * Displays a textarea field with small label text above.
	 *
	 * @param string $id is the id of the input.
	 * @param string $label is the label text.
	 * @param string $name is the input name.
	 * @param string $value is the input value.
	 * @param string $placeholder is the input placeholder.
	 * @param int    $cols are the number of columns.
	 * @param int    $rows are the number of rows.
	 * @param bool   $editable toggles the readonly attribute.
	 */
	public static function display_textarea(
		string $id,
		string $label,
		string $name,
		string $value,
		string $placeholder = '',
		int $cols = 20,
		int $rows = 5,
		bool $editable = true,
	): void {
		?>
		<div class="congress-stacked-input">
			<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
			<textarea 
				id="<?php echo esc_attr( $id ); ?>"
				type="text"
				name="<?php echo esc_attr( $name ); ?>"
				placeholder="<?php echo esc_attr( $placeholder ); ?>"
				cols="<?php echo esc_attr( $cols ); ?>"
				rows="<?php echo esc_attr( $rows ); ?>"
				<?php echo esc_attr( $editable ? '' : 'readonly' ); ?>
			><?php echo esc_html( $value ); ?></textarea>
		</div>
		<?php
	}

	/**
	 * Displays an email field with small label text above.
	 *
	 * @param string $id is the id of the input.
	 * @param string $label is the label text.
	 * @param string $name is the input name.
	 * @param string $value is the input value.
	 * @param string $placeholder is the input placeholder.
	 * @param string $size is the size of the input.
	 */
	public static function display_email( string $id, string $label, string $name, string $value, string $placeholder = '', string $size = '20' ): void {
		?>
		<div class="congress-stacked-input">
			<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
			<input 
				id="<?php echo esc_attr( $id ); ?>"
				type="email"
				value="<?php echo esc_attr( $value ); ?>"
				name="<?php echo esc_attr( $name ); ?>"
				placeholder="<?php echo esc_attr( $placeholder ); ?>"
				size="<?php echo esc_attr( $size ); ?>"
			/>
		</div>
		<?php
	}

	/**
	 * Displays a number field with small label text above.
	 *
	 * @param string $id is the id of the input.
	 * @param string $label is the label text.
	 * @param string $name is the input name.
	 * @param string $value is the input value.
	 * @param string $size is the size of the input.
	 */
	public static function display_number( string $id, string $label, string $name, string $value, string $size = '6em' ): void {
		?>
		<div class="congress-stacked-input">
			<label for='<?php echo esc_attr( $id ); ?>'><?php echo esc_html( $label ); ?></label>
			<input 
				id="<?php echo esc_attr( $id ); ?>"
				type="number"
				value="<?php echo esc_attr( $value ); ?>"
				name="<?php echo esc_attr( $name ); ?>"
				style="<?php echo esc_attr( 'width: ' . $size . ';' ); ?>"
			/>
		</div>
		<?php
	}

	/**
	 * Displays a dropdown with small label text above.
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
		<div class="congress-stacked-input">
			<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
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
		</div>
		<?php
	}

	/**
	 * Displays a dropdown with states and a small label text above.
	 *
	 * @param string $id is the id of the input.
	 * @param string $label is the label text.
	 * @param string $name is the input name.
	 * @param string $value is the input value.
	 */
	public static function display_state_dropdown( string $id, string $label, string $name, string $value ): void {
		self::display_dropdown( $id, $label, $name, $value, self::$states );
	}
}
?>
