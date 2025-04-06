<?php
/**
 * Defines the class that handles Congress.gov API requests.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Congress
 * @subpackage Congress/includes
 */

/**
 * Handles Congress.gov API requests.
 *
 * @since      1.0.0
 * @package    Congress
 * @subpackage Congress/includes
 * @author     Ryan Sauers <ryan.sauers@exploreveg.org>
 */
class Congress_Congress_API {

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
	public static string $field_name = 'congress_field_congress';

	/**
	 * The Congress.gov api key.
	 *
	 * @var ?string
	 */
	private ?string $api_key = null;

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
	 * Makes a call to Congress.gov/members
	 *
	 * @param ?Congress_State $state_code is the state code.
	 *
	 * @throws Error If missing api key, @see has_api_key.
	 *
	 * @return array|false the api results or false on failure.
	 */
	public function get_reps( ?Congress_State $state_code ): array|false {

		if ( ! $this->has_api_key() ) {
			throw 'Needs API Key';
		}

		$results = wp_remote_get(
			'https://api.congress.gov/v3/member/' . strtoupper( $state_code->to_state_code() ),
			array(
				'body' => array(
					'api_key'       => $this->api_key,
					'currentMember' => 'true',
				),
			)
		);

		if ( is_a( $results, 'WP_Error' ) || 200 !== $results['response']['code'] ) {
			return false;
		}

		$body = json_decode( $results['body'], true );

		$members = $body['members'];

		$reps = array();

		foreach ( $members as &$member ) {

			if ( 'Senate' === $member['terms']['item'][0]['chamber'] ) {

				// check the term isn't up.
				if (
					isset( $member['terms']['item'][0]['endYear'] ) &&
					intval( $member['terms']['item'][0]['endYear'] ) < intval( gmdate( 'Y' ) )
				) {
					continue;
				}

				array_push(
					$reps,
					array(
						'img'        => $member['depiction']['imageUrl'],
						'state'      => $member['state'],
						'fullName'   => $member['name'],
						'first_name' => $member['name'],
						'last_name'  => $member['name'],
					)
				);
			} elseif ( 'House of Representatives' === $member['terms']['item'][0]['chamber'] ) {

				// check the term isn't up.
				if (
					isset( $member['terms']['item'][0]['endYear'] ) &&
					intval( $member['terms']['item'][0]['endYear'] ) < intval( gmdate( 'Y' ) )
				) {
					continue;
				}

				array_push(
					$reps,
					array(
						'img'      => $member['depiction']['imageUrl'],
						'state'    => $member['state'],
						'fullName' => $member['name'],
					)
				);
			}
		}

		return json_decode( $results['body'], true );
	}
}
