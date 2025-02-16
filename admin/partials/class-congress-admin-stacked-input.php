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
	private static array $states = array(
		'MN',
	);

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
						foreach ( self::$states as $state ) {
							?>
							<option><?php echo esc_html( $state ); ?></option>
							<?php
						}
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
						<option>Federal</option>
						<option>State</option>
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
