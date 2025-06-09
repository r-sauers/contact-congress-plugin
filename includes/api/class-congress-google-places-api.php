<?php
/**
 * Defines the class that handles Google Places API requests.
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
 * Handles Google Places API requests.
 *
 * @since      1.0.0
 * @package    Congress
 * @subpackage Congress/includes
 * @author     Ryan Sauers <ryan.sauers@exploreveg.org>
 */
class Congress_Google_Places_API {

	/**
	 * The WordPress options name the api key is stored in.
	 *
	 * @var string
	 */
	public static string $options_name = 'congress_options';

	/**
	 * The WordPress option field the api key is stored in.
	 *
	 * @var string
	 */
	public static string $field_name = 'congress_field_google';

	/**
	 * The Google Places API key.
	 *
	 * @var ?string
	 */
	private ?string $api_key = null;

	/**
	 * Generates a version 4 uuid to track Autocomplete sessions.
	 *
	 * Using session results in lower billing costs.
	 * The code is adapted from: https://stackoverflow.com/a/15875555
	 * Under the Attribution-ShareAlike 4.0 International License: https://creativecommons.org/licenses/by-sa/4.0/
	 *
	 * @return string
	 */
	public function get_session(): string {
		$data = random_bytes( 16 );

		$data[6] = chr( ord( $data[6] ) & 0x0f | 0x40 ); // set version to 0100.
		$data[8] = chr( ord( $data[8] ) & 0x3f | 0x80 ); // set bits 6-7 to 10.

		return vsprintf( '%s%s-%s-%s-%s-%s%s%s', str_split( bin2hex( $data ), 4 ) );
	}

	/**
	 * Returns true if the api key has been set in settings.
	 *
	 * @return bool
	 */
	public function has_api_key(): bool {
		if ( null !== $this->api_key ) {
			return true;
		}
		$options = get_option( self::$options_name );
		if ( ! isset( $options[ self::$field_name ] ) ) {
			return false;
		}
		$api_key = $options[ self::$field_name ];
		if ( '' === $api_key ) {
			return false;
		}
		$this->api_key = $api_key;
		return true;
	}

	/**
	 * Makes a call to Google Places : Autocomplete API.
	 *
	 * @param string  $address is the partial address to autocomplete.
	 * @param ?string $session_token @see get_session.
	 *
	 * @throws Error If missing api key, @see has_api_key.
	 *
	 * @return array|false the api results or false on failure.
	 */
	public function autocomplete_address( string $address, ?string $session_token ): array|false {

		if ( ! $this->has_api_key() ) {
			throw 'Needs API Key';
		}

		$api_key = $this->api_key;
		$headers = array(
			'Content-Type'   => 'application/json',
			'X-Goog-Api-Key' => $api_key,
			'Referer'        => get_site_url(),
		);

		$body = array(
			'input' => $address,
		);
		if ( $session_token ) {
			$body['sessionToken'] = $session_token;
		}

		$results = wp_remote_post(
			'https://places.googleapis.com/v1/places:autocomplete',
			array(
				'body'    => wp_json_encode( $body ),
				'headers' => $headers,
			)
		);

		if ( is_a( $results, 'WP_Error' ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'Contact Congress Plugin Autocomplete Error: ' . $results->get_error_message() );
			return false;
		}
		if ( 200 !== $results['response']['code'] ) {
			$json = json_decode( wp_remote_retrieve_body( $results ), false );
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'Contact Congress Plugin Google Places Autocomplete Error: ' . $json->error->message );
			return false;
		}

		return json_decode( $results['body'], true );
	}

	/**
	 * Makes a call to Google Places : PlaceDetails API.
	 *
	 * @param string  $place_id is the place id retrieved from @see autocomplete_address.
	 * @param ?string $session_token @see get_session.
	 *
	 * @throws Error If missing api key, @see has_api_key.
	 *
	 * @return array|false the api results or false on failure.
	 */
	public function get_location_and_address_components( string $place_id, ?string $session_token ): array|false {
		if ( ! $this->has_api_key() ) {
			throw 'Needs API Key';
		}

		$api_key = $this->api_key;
		$headers = array(
			'Content-Type'     => 'application/json',
			'X-Goog-Api-Key'   => $api_key,
			'X-Goog-FieldMask' => 'location,addressComponents',
			'Referer'          => get_site_url(),
		);

		$body = array();
		if ( $session_token ) {
			$body['sessionToken'] = $session_token;
		}

		$results = wp_remote_get(
			'https://places.googleapis.com/v1/places/' . $place_id,
			array(
				'body'    => $body,
				'headers' => $headers,
			)
		);

		if ( is_a( $results, 'WP_Error' ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'Contact Congress Plugin Place Details Error: ' . $results->get_error_message() );
			return false;
		}
		if ( 200 !== $results['response']['code'] ) {
			$json = json_decode( wp_remote_retrieve_body( $results ), false );
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'Contact Congress Plugin Google Places Place Details Error: ' . $json->error->message );
			return false;
		}

		return json_decode( $results['body'], true );
	}
}
