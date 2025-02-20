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
	 * Draws select option tags.
	 *
	 * @param array<string> $options is an array of option text.
	 * @param string        $value is the sleect value.
	 */
	private static function display_select_options( array $options, string $value ): void {
		foreach ( $options as $option ) {
			?>
			<option <?php echo esc_html( $option === $value ? 'selected' : '' ); ?>
				><?php echo esc_html( $option ); ?></option>
			<?php
		}
	}

	/**
	 * Displays an input field with small label text above.
	 *
	 * @param string $id is the id of the input.
	 * @param string $label is the label text.
	 * @param string $name is the input name.
	 * @param string $value is the input value.
	 * @param string $input_type is the input type.
	 * @param string $placeholder is the input placeholder.
	 * @param string $size is the size of the input.
	 */
	public static function display( string $id, string $label, string $name, string $value, string $input_type = 'text', string $placeholder = '', string $size = '20' ): void {
		?>
		<div class="congress-stacked-input">
			<label for='<?php echo esc_attr( $id ); ?>'><?php echo esc_html( $label ); ?></label>
			<?php
			if ( strcmp( $input_type, 'state' ) === 0 ) {
				?>
					<select
						id='<?php echo esc_attr( $id ); ?>'
						name='<?php echo esc_attr( $name ); ?>'
						value='<?php echo esc_attr( $value ); ?>'
					>
						<?php
							self::display_select_options( self::$states, $value );
						?>
					</select>
				<?php
			} elseif ( strcmp( $input_type, 'level' ) === 0 ) {
				?>
					<select
						id='<?php echo esc_attr( $id ); ?>'
						name='<?php echo esc_attr( $name ); ?>'
						value='<?php echo esc_attr( $value ); ?>'
					>
						<?php
						self::display_select_options(
							array(
								'Federal',
								'State',
							),
							$value,
						);
						?>
					</select>
				<?php
			} else {
				?>
					<input 
						id='<?php echo esc_attr( $id ); ?>'
						type='<?php echo esc_attr( $input_type ); ?>'
						value='<?php echo esc_attr( $value ); ?>'
						name='<?php echo esc_attr( $name ); ?>'
						placeholder='<?php echo esc_attr( $placeholder ); ?>'
						<?php
						if ( strcmp( $input_type, 'text' ) === 0 ) {
							echo esc_attr( "size=$size" );
						} elseif ( strcmp( $input_type, 'number' ) === 0 ) {
							?>
								style="<?php echo esc_attr( 'width: ' . $size . ';' ); ?>"
							<?php
						}
						?>
					/>
				<?php
			}

			?>
		</div>
		<?php
	}
}
?>
