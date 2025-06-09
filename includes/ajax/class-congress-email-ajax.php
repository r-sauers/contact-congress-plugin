<?php
/**
 * Ajax handler for registering a sent email.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Congress
 * @subpackage Congress/includes
 */

/**
 * Imports Table Manager for getting table names;
 */
require_once plugin_dir_path( __FILE__ ) .
	'../class-congress-table-manager.php';

/**
 * Imports Congress_AJAX_Collection interface.
 */
require_once plugin_dir_path( __FILE__ ) .
	'class-congress-ajax-collection.php';

/**
 * Imports Congress_AJAX_Handler for creating handlers.
 */
require_once plugin_dir_path( __FILE__ ) .
	'class-congress-ajax-handler.php';

/**
 * Imports Captcha information.
 */
require_once plugin_dir_path( __DIR__ ) .
	'class-congress-captcha.php';

/**
 * Ajax handler for registering a sent email.
 *
 * @since      1.0.0
 * @package    Congress
 * @subpackage Congress/includes
 * @author     Ryan Sauers <ryan.sauers@exploreveg.org>
 */
class Congress_Email_AJAX implements Congress_AJAX_Collection {

	/**
	 * Returns a list of ajax handlers for admin page.
	 *
	 * @return array<Congress_AJAX_Handler>
	 */
	public function get_admin_handlers(): array {
		return array(
			new Congress_AJAX_Handler(
				callee: $this,
				func_name: 'register_email',
				ajax_name: 'register_email'
			),
		);
	}

	/**
	 * Returns a list of ajax handlers for public page.
	 *
	 * @return array<Congress_AJAX_Handler>
	 */
	public function get_public_handlers(): array {
		return array(
			new Congress_AJAX_Handler(
				callee: $this,
				func_name: 'register_email',
				ajax_name: 'register_email'
			),
		);
	}

	/**
	 * Registers an email has been sent with the database and sends a JSON response with success/failure.
	 */
	public function register_email(): void {

		if (
			! isset( $_POST['g-recaptcha-response'] ) ||
			! isset( $_POST['campaignID'] )
		) {
			wp_send_json(
				array(
					'error' => 'Missing parameters',
				),
				400
			);
		}

		$token       = sanitize_text_field(
			wp_unslash( $_POST['g-recaptcha-response'] )
		);
		$campaign_id = sanitize_text_field(
			wp_unslash( $_POST['campaignID'] )
		);

		if ( ! check_ajax_referer( "register-email_$campaign_id", false, false ) ) {
			wp_send_json(
				array(
					'error' => 'Incorrect Nonce',
				),
				403
			);
		}

		$captcha = new Congress_Captcha();
		if ( ! $captcha->has_server_key() ) {
			wp_send_json(
				array(
					'error' => 'No captcha server key.',
				),
				500
			);
		}

		if ( ! isset( $_SERVER['REMOTE_ADDR'] ) ) {
			wp_send_json(
				array(
					'error' => 'Failed to get IP.',
				),
				500
			);
		}

		$ip = sanitize_text_field(
			wp_unslash( $_SERVER['REMOTE_ADDR'] )
		);

		$result = $captcha->verify_captcha( $token, $ip );

		if ( false === $result ) {
			wp_send_json(
				array(
					'error' => 'Captcha Request Failed.',
				),
				500
			);
		}

		if ( ! $result['success'] ) {
			wp_send_json(
				array(
					'error'   => 'Invalid Captcha.',
					'details' => $result['error-codes'],
				),
				500
			);
		}

		$success = false;
		if ( isset( $_POST['referer'] ) ) {
			$referer_url = sanitize_text_field(
				wp_unslash( $_POST['referer'] )
			);
			$success     = $this->register_email_with_referer( $campaign_id, $referer_url );
		}

		if ( ! $success ) {
			$success = $this->register_email_without_referer( $campaign_id );
		}

		if ( ! $success ) {
			wp_send_json(
				array(
					'error' => 'Failed to register.',
				),
				500
			);
		}

		wp_send_json(
			array(
				'success' => true,
			)
		);
	}

	/**
	 * Registers an email being sent with a referer to the database.
	 *
	 * @param int    $campaign_id is the campaign id.
	 * @param string $referer_url is the referer url.
	 *
	 * @return bool true if successful.
	 */
	private function register_email_with_referer( int $campaign_id, string $referer_url ): bool {
		global $wpdb;

		if ( '' === $referer_url ) {
			return false;
		}

		$referer_t = Congress_Table_Manager::get_table_name( 'referer' );
		$email_t   = Congress_Table_Manager::get_table_name( 'email' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT id FROM %i AS referer WHERE url_name = %s AND campaign_id = %s',
				array(
					$referer_t,
					$referer_url,
					$campaign_id,
				)
			)
		);

		if ( null === $result ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->insert(
			$email_t,
			array(
				'campaign_id' => $campaign_id,
				'referer_id'  => $result->id,
			)
		);

		return (bool) $result;
	}

	/**
	 * Registers an email being sent without a referer to the database.
	 *
	 * @param int $campaign_id is the campaign id.
	 *
	 * @return bool true if successful.
	 */
	private function register_email_without_referer( int $campaign_id ): bool {
		global $wpdb;

		$email_t = Congress_Table_Manager::get_table_name( 'email' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->insert(
			$email_t,
			array(
				'campaign_id' => $campaign_id,
			)
		);

		return (bool) $result;
	}
}
