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
 * Import Congress_Rep_Interface.
 */
require_once plugin_dir_path( __DIR__ ) . 'class-congress-rep-interface.php';

/**
 * Import enums.
 */
require_once plugin_dir_path( __DIR__ ) . 'enum-congress-state.php';
require_once plugin_dir_path( __DIR__ ) . 'enum-congress-level.php';
require_once plugin_dir_path( __DIR__ ) . 'enum-congress-title.php';

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
	 * @param Congress_State $state_code is the state code.
	 *
	 * @throws Error If missing api key, @see has_api_key.
	 *
	 * @return array<Congress_Rep_Interface>|false the api results or false on failure.
	 */
	public function get_reps( Congress_State $state_code ): array|false {

		if ( ! $this->has_api_key() ) {
			throw 'Needs API Key';
		}

		// Congress.gov does not support pagination very well, so I have decided
		// not to support the '/member' endpoint.
		$member_path = 'member/' . strtoupper( $state_code->to_state_code() );

		$results = wp_remote_get(
			'https://api.congress.gov/v3/' . $member_path,
			array(
				'body' => array(
					'api_key'       => $this->api_key,
					'currentMember' => 'true',
					'limit'         => 250,
				),
			)
		);

		if ( is_a( $results, 'WP_Error' ) || 200 !== $results['response']['code'] ) {
			error_log( 'Failed to fetch from Congress.gov API' ); // phpcs:ignore
			return false;
		}

		$body = json_decode( $results['body'], true );

		$members = $body['members'];

		$reps = array();

		foreach ( $members as &$member ) {

			$last_term = null;

			foreach ( $member['terms']['item'] as &$term ) {
				if (
					! isset( $term['endYear'] )
				) {
					$last_term = &$term;
				}
			}

			if ( null === $last_term ) {
				error_log( // phpcs:ignore
					new Error( 'Assertion failed for Congress.gov API.' )
				);
				continue;
			}

			try {
				$state = Congress_State::from_string( $member['state'] );
			} catch ( Error $e ) {
				continue;
			}

			$title = null;
			if ( 'Senate' === $last_term['chamber'] ) {
				$title = Congress_Title::Senator;
			} else {
				$title = Congress_Title::Representative;
			}

			$name_split = explode( ', ', $member['name'] );
			$first_name = $name_split[0];
			$name_split = explode( ' ', $name_split[1] );
			$last_name  = $name_split[0];

			if ( isset( $member['district'] ) ) {
				$rep = new Congress_Rep_Interface(
					first_name: $first_name,
					last_name: $last_name,
					img: $member['depiction']['imageUrl'],
					district: $member['district'],
					title: $title,
					level: Congress_Level::Federal,
					state: $state,
				);
			} else {
				$rep = new Congress_Rep_Interface(
					first_name: $first_name,
					last_name: $last_name,
					img: $member['depiction']['imageUrl'],
					title: $title,
					level: Congress_Level::Federal,
					state: $state,
				);
			}
			array_push( $reps, $rep );
		}

		return $reps;
	}
}
