<?php
/**
 * Congress Form Block
 *
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 *
 * @package Congress
 */

/**
 * Imports utils for html helper functions and retrieving block attributes and table information.
 */
require_once plugin_dir_path( __FILE__ ) .
	'class-congress-form-block-utils.php';

/**
 * Imports enums.
 */
require_once plugin_dir_path( __DIR__ ) .
	'../../includes/enum-congress-state.php';
require_once plugin_dir_path( __DIR__ ) .
	'../../includes/enum-congress-level.php';

$utils = new Congress_Form_Block_Utils();

$campaign_template = $utils->get_campaign_template( $attributes );

if ( false === $campaign_template ) {
	if ( ! isset( $_GET['action'] ) || 'edit' !== $_GET['action'] ) { // phpcs:ignore
		wp_die( 'There has been an error with the Contact Congress Form Block! Please contact the admin of the site to resolve this issue.' );
	}
} else {

	$campaign_id = $campaign_template->campaign_id;

	$default_pfp = 'data:image/svg+xml,<%3Fxml version="1.0" encoding="utf-8"%3F><svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path opacity="0.4" d="M12 22.01C17.5228 22.01 22 17.5329 22 12.01C22 6.48716 17.5228 2.01001 12 2.01001C6.47715 2.01001 2 6.48716 2 12.01C2 17.5329 6.47715 22.01 12 22.01Z" fill="%23292D32"/><path d="M12 6.93994C9.93 6.93994 8.25 8.61994 8.25 10.6899C8.25 12.7199 9.84 14.3699 11.95 14.4299C11.98 14.4299 12.02 14.4299 12.04 14.4299C12.06 14.4299 12.09 14.4299 12.11 14.4299C12.12 14.4299 12.13 14.4299 12.13 14.4299C14.15 14.3599 15.74 12.7199 15.75 10.6899C15.75 8.61994 14.07 6.93994 12 6.93994Z" fill="%23292D32"/><path d="M18.7807 19.36C17.0007 21 14.6207 22.01 12.0007 22.01C9.3807 22.01 7.0007 21 5.2207 19.36C5.4607 18.45 6.1107 17.62 7.0607 16.98C9.7907 15.16 14.2307 15.16 16.9407 16.98C17.9007 17.62 18.5407 18.45 18.7807 19.36Z" fill="%23292D32"/></svg>';

	try {
		$region = Congress_Level::from_string( $campaign_template->region );
	} catch ( Error $e ) {
		$region = Congress_State::from_string( $campaign_template->region );
	}
	?>
	<div <?php echo get_block_wrapper_attributes(); // phpcs:ignore ?>>
		<h3 class="wp-block-heading">Contact Your <?php echo esc_html( $region->to_display_string() ); ?> Representatives</h3>
		<h4><strong>Step 1:</strong> Find Your Representatives 
			<?php
			if ( is_a( $region, 'Congress_State' ) ) {
				?>
					<span
						style="font-size: 0.9em; color: #777; margin-left: 0.5em;"
					>(<?php echo esc_html( $region->to_display_string() ); ?> residents only!)</span>
				<?php
			}
			?>
		</h4>
		<form 
			<?php $utils->id( 'get-reps-form' ); ?>
			action="get_reps"
			method="post"
		>
		<div <?php $utils->class_name( 'form-group' ); ?>>
			<label <?php $utils->html_for( 'street-address' ); ?>>Street Address: </label>
			<div <?php $utils->class_name( 'form-control' ); ?>>
				<select 
					<?php $utils->id( 'street-address' ); ?>
					type="text" name="address"
					required>
				</select>
			</div>
		</div>
			<input type="hidden" name="campaignRegion" value="<?php echo esc_attr( $campaign_template->region ); ?>"/>
			<input type="hidden" name="campaignID" value="<?php echo esc_attr( $campaign_id ); ?>"/>
			<input type="hidden" name="placeId" value=""/>
			<?php
			wp_nonce_field( "get-reps_$campaign_id" );
			?>
			<button type="submit" class="wp-element-button <?php $utils->inline_class( 'wide' ); ?>">Find</button>
			<span <?php $utils->class_name( 'form-hint' ); ?>></span>
		</form>

		<h4><strong>Step 2:</strong> Send Some Emails!</h4>
		<form action="" <?php $utils->id( 'email-form' ); ?>>
			<div <?php $utils->class_name( 'form-group' ); ?>>
				<label <?php $utils->html_for( 'first-name' ); ?>>First Name: </label>
				<div <?php $utils->class_name( 'form-control' ); ?>>
					<input type="text" name="firstName" <?php $utils->id( 'first-name' ); ?> required/>
				</div>
			</div>
			<div <?php $utils->class_name( 'form-group' ); ?>>
				<label <?php $utils->html_for( 'last-name' ); ?>>Last Name: </label>
				<div <?php $utils->class_name( 'form-control' ); ?>>
					<input type="text" name="lastName" <?php $utils->id( 'last-name' ); ?> required/>
				</div>
			</div>
			<div <?php $utils->class_name( 'form-group' ); ?>>
				<label <?php $utils->html_for( 'email' ); ?>>Email: </label>
				<div <?php $utils->class_name( 'form-control' ); ?>>
					<input type="email" name="email" <?php $utils->id( 'email' ); ?> required/>
				</div>
			</div>
			<div <?php $utils->class_name( 'form-group' ); ?>>
				<label <?php $utils->html_for( 'subject' ); ?>>Subject: </label>
				<div <?php $utils->class_name( 'form-control' ); ?>>
					<input type="text" name="subject" value="<?php echo esc_attr( $campaign_template->subject ); ?>" <?php $utils->id( 'subject' ); ?>required/>
				</div>
			</div>
			<div>
				<textarea name="template" <?php $utils->id( 'email-template' ); ?> style="white-space: pre;"><?php echo esc_textarea( $campaign_template->template ); ?></textarea>
				<textarea name="body" <?php $utils->id( 'email-body' ); ?> style="white-space: pre;" disabled></textarea>
			</div>
			<a
				<?php $utils->id( 'preview-toggle' ); ?>
				<?php $utils->class_name( 'preview-open', 'preview-toggle' ); ?>
			>Show Preview</a>
			<div>
				<button type="submit" class="wp-element-button <?php $utils->inline_class( 'wide' ); ?>" disabled>Send</button>
			</div>
			<ul <?php $utils->id( 'rep-container' ); ?> style="list-style-type: none; padding: 0px;">
				<div style="width: 100%; text-align: center;">No Representatives.</div>
			</ul>
		</form>
		<template <?php $utils->id( 'rep-template' ); ?>>
			<div <?php $utils->class_name( 'rep-form' ); ?>>
				<img src="<?php echo esc_attr( $default_pfp ); ?>" <?php $utils->class_name( 'pfp' ); ?>/>
				<div <?php $utils->class_name( 'rep-details' ); ?>>
					<div>
						<span <?php $utils->class_name( 'rep-title' ); ?>>Senator</span>
						<span <?php $utils->class_name( 'rep-first' ); ?>>Amy</span>
						<span <?php $utils->class_name( 'rep-last' ); ?>>Klobuchar</span>
					</div>
					<div>
						(<span <?php $utils->class_name( 'rep-state' ); ?>>MN</span><span <?php $utils->class_name( 'rep-district' ); ?>>District 5</span>)
					</div>
				</div>
				<button type="submit" class="wp-element-button">Send</button>
			</div>
		</template>
	</div>
	<?php
}
