<?php
/**
 * Congress Form Block
 *
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 *
 * @package Congress
 */

if (
	! empty( $attributes['campaign'] ) &&
	! empty( $attributes['isFederalPolicy'] ) &&
	! empty( $attributes['isStatePolicy'] ) &&
	! empty( $attributes['hasEmailTemplates'] )
) {
	$campaign            = $attributes['campaign'];
	$is_federal_policy   = $attributes['isFederalPolicy'];
	$isi_state_policy    = $attributes['isStatePolicy'];
	$has_email_templates = $attributes['hasEmailTemplates'];
} else {
	$campaign = 'idk';
}

$block_wrapper_attributes = get_block_wrapper_attributes();

$class_value_str = strstr( substr( strstr( $block_wrapper_attributes, 'class="' ), 7 ), '"', true );
$class_value     = strtok( $class_value_str, ' ' );
while ( false !== $class_value && ! str_contains( $class_value, 'congress' ) ) {
	$class_value = strtok( ' ' );
}
$class_prefix = $class_value;
define( 'CONGRESS_CLASS_PREFIX', $class_prefix );
define( 'CONGRESS_ID_PREFIX', $class_prefix );

/**
 * Prints the class attribute with the given class names, prefixed to avoid name collisions.
 *
 * @param array<string> ...$class_names are the class names for the element.
 */
function class_name( ...$class_names ) {

	$class_string = 'class=';
	foreach ( $class_names as &$class_name ) {
		$class_string .= CONGRESS_CLASS_PREFIX . '__' . $class_name . ' ';
	}
	unset( $class_name );

	echo esc_attr( $class_string );
}

/**
 * Prints the id attribute with the given id, prefixed to avoid name collisions.
 *
 * @param id $id is the id for the element.
 */
function id( $id ) {
	global $id_prefix;
	echo esc_attr( 'id=' . CONGRESS_ID_PREFIX . '__' . $id );
}

/**
 * Prints the for attribute with the given id, prefixed to avoid name collisions.
 *
 * @param id $id is the id of the element being used in the for attribute.
 */
function html_for( $id ) {
	global $id_prefix;
	echo esc_attr( 'for=' . CONGRESS_ID_PREFIX . '__' . $id );
}

?>
<div <?php echo get_block_wrapper_attributes(); // phpcs:ignore ?>>
	<h3>Contact Your Representative</h3>
	<form action="">
	<div <?php class_name( 'form-group' ); ?>>
		<label <?php html_for( 'name' ); ?>>First Name: </label>
		<div <?php class_name( 'form-control' ); ?>>
			<input type="text" name="name" <?php id( 'name' ); ?> required/>
		</div>

		</div>
		<div <?php class_name( 'form-group' ); ?>>
		<label <?php html_for( 'email' ); ?>>Email: </label>
		<div <?php class_name( 'form-control' ); ?>>
			<input type="email" name="email" <?php id( 'email' ); ?> required/>
		</div>
		</div>
		<div <?php class_name( 'form-group' ); ?>>
		<label <?php html_for( 'street-address' ); ?>>Street Address: </label>
		<div <?php class_name( 'form-control' ); ?>>
			<input type="text" name="streetAddress" <?php id( 'street-address' ); ?> style={{display: "block"}} required/>
			<div <?php class_name( 'form-info' ); ?>>To find your representative</div>
		</div>
		</div>
		<div>
		<textarea name="emailBody" <?php id( 'form-body' ); ?> style="white-space: pre;">Dear Representative...</textarea>
		</div>
		<div>
		<button type="submit">Send</button>
		</div>
	</form>
</div>
