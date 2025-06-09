<?php
/**
 * The file that defines Captcha handling and attributes.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Congress
 * @subpackage Congress/includes
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The class that defines Captcha handling and attributes.
 *
 * See https://developers.google.com/recaptcha/docs/verify
 *
 * @since      1.0.0
 * @package    Congress
 * @subpackage Congress/includes
 */
class Congress_Captcha {

	/**
	 * The options name for the server key.
	 *
	 * @var string
	 */
	public static string $server_key_options_name = 'congress_options';

	/**
	 * The options name for the client key.
	 *
	 * @var string
	 */
	public static string $client_key_options_name = 'congress_options';

	/**
	 * The field name for the server key.
	 *
	 * @var string
	 */
	public static string $server_key_field_name = 'captcha_server_key';

	/**
	 * The field name for the client key.
	 *
	 * @var string
	 */
	public static string $client_key_field_name = 'captcha_client_key';

	/**
	 * The URL to verify the captcha with.
	 *
	 * @var string
	 */
	private static string $captcha_url = 'https://www.google.com/recaptcha/api/siteverify';

	/**
	 * The server key to verify the request to recaptcha.
	 *
	 * @var ?string
	 */
	private ?string $server_key = null;

	/**
	 * Returns true if the server key has been set in settings.
	 *
	 * @return bool
	 */
	public function has_server_key(): bool {
		if ( null !== $this->server_key ) {
			return true;
		}
		$options = get_option( self::$server_key_options_name );
		if ( ! isset( $options[ self::$server_key_field_name ] ) ) {
			return false;
		}
		$server_key = $options[ self::$server_key_field_name ];
		if ( '' === $server_key ) {
			return false;
		}
		$this->server_key = $server_key;
		return true;
	}


	/**
	 * Verifies that the captcha didn't find any suspicious behavior.
	 * Returns false if the request fails, otherwise an array with the fields:
	 * - bool success
	 * - string error-codes.
	 *
	 * You should call @see has_server_key to make sure this function will work.
	 *
	 * @param string  $token is the token passed from the client.
	 * @param ?string $ip is the ip of the client.
	 *
	 * @return array|false
	 */
	public function verify_captcha( string $token, ?string $ip = null ): array|false {

		if ( ! $this->has_server_key() ) {
			return false;
		}

		$body = array(
			'secret'   => $this->server_key,
			'response' => $token,
		);
		if ( null !== $ip ) {
			$body['remoteip'] = $ip;
		}
		$query_params = http_build_query( $body );
		$result       = wp_remote_post(
			self::$captcha_url . '?' . $query_params,
			array(
				'headers' => array(
					'Content-Type' => 'application/x-www-form-urlencoded',
				),
			)
		);

		if ( is_a( $result, 'WP_Error' ) || 200 !== $result['response']['code'] ) {
			return false;
		}

		return json_decode( $result['body'], true );
	}
}
