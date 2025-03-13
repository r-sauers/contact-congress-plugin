<?php
/**
 * A collection of AJAX handlers for handling location lookups.
 * Including address autocompletion, and getting reader representatives.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Congress
 * @subpackage Congress/includes
 */

/**
 * Imports Congress_AJAX_Collection interface.
 */
require_once plugin_dir_path( __FILE__ ) .
	'class-congress-ajax-collection.php';

/**
 * Imports Table Manager for getting table names;
 */
require_once plugin_dir_path( __FILE__ ) .
	'../class-congress-table-manager.php';

/**
 * Imports Congress_AJAX_Handler for creating handlers.
 */
require_once plugin_dir_path( __FILE__ ) .
	'class-congress-ajax-handler.php';

/**
 * Imports Congress_MN_API.
 */
require_once plugin_dir_path( __FILE__ ) .
	'../api/class-congress-mn-api.php';

/**
 * Imports Congress_Congress_API.
 */
require_once plugin_dir_path( __FILE__ ) .
	'../api/class-congress-congress-api.php';

/**
 * Imports Congress_Gooogle_Places_API.
 */
require_once plugin_dir_path( __FILE__ ) .
	'../api/class-congress-google-places-api.php';

/**
 * A collection of AJAX handlers for handling location lookups.
 * Including address autocompletion, and getting reader representatives.
 *
 * @since      1.0.0
 * @package    Congress
 * @subpackage Congress/includes
 * @author     Ryan Sauers <ryan.sauers@exploreveg.org>
 */
class Congress_Location_AJAX implements Congress_AJAX_Collection {

	private static string $places_session_name = 'wp_congress_places_session';

	/**
	 * Google Places API.
	 *
	 * @var Congress_Google_Places_API
	 */
	private Congress_Google_Places_API $places_api;

	/**
	 * Congress.gov API.
	 *
	 * @var Congress_Congress_API
	 */
	private Congress_Congress_API $congress_api;

	/**
	 * Minnesota state API.
	 *
	 * @var Congress_MN_API
	 */
	private Congress_MN_API $mn_api;

	/**
	 * Constructs the Congress_Location_AJAX class.
	 */
	public function __construct() {
		$this->places_api   = new Congress_Google_Places_API();
		$this->congress_api = new Congress_Congress_API();
		$this->mn_api       = new Congress_MN_API();
	}

	/**
	 * Returns a list of ajax handlers for admin page.
	 *
	 * @return array<Congress_AJAX_Handler>
	 */
	public function get_admin_handlers(): array {
		return array(
			new Congress_AJAX_Handler(
				callee: $this,
				func_name: 'get_session',
				ajax_name: 'get_session'
			),
			new Congress_AJAX_Handler(
				callee: $this,
				func_name: 'autocomplete',
				ajax_name: 'autocomplete'
			),
			new Congress_AJAX_Handler(
				callee: $this,
				func_name: 'get_reps',
				ajax_name: 'get_reps'
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
				func_name: 'get_session',
				ajax_name: 'get_session'
			),
			new Congress_AJAX_Handler(
				callee: $this,
				func_name: 'autocomplete',
				ajax_name: 'autocomplete'
			),
			new Congress_AJAX_Handler(
				callee: $this,
				func_name: 'get_reps',
				ajax_name: 'get_reps'
			),
		);
	}

	/**
	 * Session cookie is UUIDv4 used for Google's autocomplete/details sessionToken.
	 * Client adds session cookie: https://www.w3schools.com/js/js_cookies.asp
	 */
	public function get_session(): void {
		wp_send_json(
			array(
				'uuid' => $this->places_api->get_session(),
				'name' => self::$places_session_name,
			)
		);
	}

	/**
	 * Returns autocomplete results for street addresses.
	 *
	 * Uses Google Places API.
	 */
	public function autocomplete(): void {
		if (
			! isset( $_POST['address'] )
		) {
			wp_send_json(
				array(
					'error' => 'Missing Parameters',
				),
				400
			);
			return;
		}

		if ( ! $this->places_api->has_api_key() ) {
			wp_send_json(
				array(
					'error'       => 'Error: contact website admin',
					'description' => 'Missing API key for Google Places API',
				),
				500
			);
			return;
		}

		$session_token = null;
		if ( isset( $_COOKIE[ self::$places_session_name ] ) ) {
			$session_token = sanitize_text_field(
				wp_unslash( $_COOKIE[ self::$places_session_name ] )
			);
		}

		$results = $this->places_api->autocomplete_address(
			sanitize_text_field( wp_unslash( $_POST['address'] ) ),
			$session_token
		);

		if ( false === $results ) {
			wp_send_json(
				array(
					'error' => 'Failed to autocomplete',
				),
				500
			);
			return;
		}

		wp_send_json( $results );
	}

	/**
	 * Returns representative results for a given placeID as determined in @see autocomplete.
	 *
	 * Uses Google Places API.
	 */
	public function get_reps(): void {

		if (
			! isset( $_POST['placeId'] ) || 
			! isset( $_POST['campaignLevel'] )
		) {
			wp_send_json(
				array(
					'error' => 'Missing parameters',
				),
				400
			);
			return;
		}

		$campaign_level = sanitize_text_field(
			wp_unslash( $_POST['campaignLevel'] )
		);
		$place_id       = sanitize_text_field(
			wp_unslash( $_POST['placeId'] )
		);
		$session_token  = null;
		if ( isset( $_COOKIE[ self::$places_session_name ] ) ) {
			$session_token = sanitize_text_field(
				wp_unslash( $_COOKIE[ self::$places_session_name ] )
			);
		}

		if ( ! $this->places_api->has_api_key() ) {
			wp_send_json(
				array(
					'error'       => 'Error: contact website admin',
					'description' => 'Missing API key for Google Places API',
				),
				500
			);
			return;
		}

		$results = $this->places_api->get_location_and_address_components( $place_id, $session_token );

		if ( false === $results ) {
			wp_send_json(
				array(
					'error' => 'Failed to get location',
				),
				500
			);
			return;
		}

		$latitude  = floatval( $results['location']['latitude'] );
		$longitude = floatval( $results['location']['longitude'] );
		if ( 0 === $latitude || 0 === $longitude ) {
			wp_send_json(
				array(
					'error' => 'Failed to get location',
				),
				500
			);
			return;
		}

		$address_components = $results['addressComponents'];
		$state              = null;

		foreach ( $address_components as $address_component ) {
			if ( in_array( 'administrative_area_level_1', $address_component['types'], true ) ) {
				$state = $address_component['shortText'];
			}
		}

		if ( null === $state ) {
			wp_send_json(
				array(
					'error' => 'Failed to get state',
				),
				500
			);
			return;
		}

		if ( 'state' === $campaign_level ) {
			$this->send_state_level_reps( $state, $latitude, $longitude );
		} elseif ( 'federal' === $campaign_level ) {
			$this->send_federal_level_reps( $state, $latitude, $longitude );
		} else {
			wp_send_json(
				array(
					'error' => 'Invalid parameters',
				),
				400
			);
		}
	}

	/**
	 * Gets an associative array of reps from the database, indexed by rep id.
	 *
	 * @param string        $where_clause is the SQL where clause with 'r' as an alias for the representatives table
	 * and 's' as an alias for the staffers table.
	 * @param array<string> $where_vars are the variables to fill the where clause.
	 *
	 * @return array|false The associative array of representatives or false on error.
	 */
	private function get_reps_from_db( string $where_clause, array $where_vars ): array|false {
		global $wpdb;

		$rep_t     = Congress_Table_Manager::get_table_name( 'representative' );
		$staffer_t = Congress_Table_Manager::get_table_name( 'staffer' );

		$reps = array();

		$results = $wpdb->get_results( // phpcs:ignore
			$wpdb->prepare(
				'SELECT ' .
				'	r.id AS rep_id, ' .
				'	r.first_name AS rep_first_name, ' .
				'	r.last_name AS rep_last_name, ' .
				'	r.title AS rep_title, ' .
				'	r.state, ' .
				'	r.district, ' .
				'	r.level, ' .
				'	s.first_name AS staffer_first_name, ' .
				'	s.last_name AS staffer_last_name, ' .
				'	s.email AS staffer_email, ' .
				'	s.title AS staffer_title ' .
				"FROM $rep_t AS r JOIN $staffer_t AS s ON s.representative = r.id " . // phpcs:ignore
				$where_clause . ' ', // phpcs:ignore
				$where_vars,
			),
			ARRAY_A
		);

		if ( null === $results ) {
			return false;
		}

		foreach ( $results as $rep_staffer ) {
			if ( ! isset( $reps[ $rep_staffer['rep_id'] ] ) ) {
				$reps[ $rep_staffer['rep_id'] ] = array(
					'first_name' => $rep_staffer['rep_first_name'],
					'last_name'  => $rep_staffer['rep_last_name'],
					'state'      => $rep_staffer['state'],
					'level'      => $rep_staffer['level'],
					'district'   => $rep_staffer['district'],
					'title'      => $rep_staffer['rep_title'],
					'staffers'   => array(),
				);
			}

			$rep = $reps[ $rep_staffer['rep_id'] ];

			array_push(
				$reps[ $rep_staffer['rep_id'] ]['staffers'],
				array(
					'first_name' => $rep_staffer['staffer_first_name'],
					'last_name'  => $rep_staffer['staffer_last_name'],
					'title'      => $rep_staffer['staffer_title'],
					'email'      => $rep_staffer['staffer_email'],
				)
			);
		}

		return $reps;
	}

	/**
	 * Sends a JSON response with state level reps for the given location.
	 *
	 * @param string $state_code is the state abbreviation e.g. 'MN'.
	 * @param float  $latitude is the location's latitude.
	 * @param float  $longitude is the location's longitude.
	 */
	private function send_state_level_reps( string $state_code, float $latitude, float $longitude ): void {

		$api_reps = array();
		$success  = false;

		if ( 'MN' === $state_code ) {
			$results = $this->mn_api->get_state_reps( $latitude, $longitude );

			if ( false !== $results ) {
				$success = true;

				foreach ( $results as $rep ) {
					array_push(
						$api_reps,
						array(
							'img'      => $rep['img'],
							'district' => $rep['district'],
							'state'    => $state_code,
							'fullName' => $rep['name'],
						)
					);
				}
			}
		}

		global $wpdb;
		$reps = null;

		if ( $success ) {
			$reps = array();
			foreach ( $api_reps as $api_rep ) {
				$db_reps = $this->get_reps_from_db(
					"WHERE r.state=%s AND r.district=%s AND r.level='federal' AND INSTR(%s, r.first_name) AND INSTR(%s, r.last_name)",
					array(
						$api_rep['state'],
						$api_rep['district'],
						$api_rep['fullName'],
						$api_rep['fullName'],
					),
				);

				if ( false === $db_reps || count( $db_reps ) === 0 ) {
					$success = false;
					break;
				}

				$rep_id = array_key_first( $db_reps );
				$rep    = $db_reps[ $rep_id ];

				$rep['img'] = $api_rep['img'];

				array_push( $reps, $rep );
			}
		}

		if ( ! $success ) {
			$reps = array();

			$db_reps = $this->get_reps_from_db(
				"WHERE r.state=%s AND r.level='state'",
				array(
					$state_code,
				),
			);

			if ( false === $db_reps ) {
				wp_send_json(
					array(
						'error' => 'Failed to get reps',
					),
					500
				);
				return;
			}

			$rep_ids = array_keys( $db_reps );
			foreach ( $rep_ids as $rep_id ) {
				array_push(
					$reps,
					$db_reps[ $rep_id ],
				);
			}
		}

		wp_send_json(
			array(
				'success'         => $success,
				'representatives' => $reps,
			)
		);
	}

	/**
	 * Gets representatives from the federal house of representatives using a state code and location.
	 *
	 * @param string $state_code is the state abbreviation e.g. 'MN'.
	 * @param float  $latitude is the location's latitude.
	 * @param float  $longitude is the location's longitude.
	 * @return array|false false if there is an error, and an associative array with the
	 * fields 'houseSuccess' (bool) and 'houseMembers' (array). If 'houseSuccess' is true
	 * that means that it was successfully able to determine the representatives from the location
	 * meaning. If it is false, all the reps from the db will be returned.
	 */
	private function get_federal_house_reps( string $state_code, float $latitude, float $longitude ): array|false {

		$api_reps      = array();
		$house_success = false;

		// Get from API.
		if ( 'MN' === $state_code ) {
			$results = $this->mn_api->get_federal_reps( $latitude, $longitude );

			if ( false !== $results ) {
				$house_success = true;

				foreach ( $results as $rep ) {
					array_push(
						$api_reps,
						array(
							'img'      => $rep['img'],
							'district' => $rep['district'],
							'state'    => $state_code,
							'fullName' => $rep['name'],
						)
					);
				}
			}
		}

		$house_reps = null;

		// Reconcile api with db.
		if ( $house_success ) {

			$house_reps  = array();

			foreach ( $api_reps as $api_rep ) {

				$where_clause = 'WHERE ' .
					'r.state=%s AND ' .
					"r.level='federal' AND " .
					'INSTR(%s, r.first_name) AND ' .
					'INSTR(%s, r.last_name) AND ' .
					'r.district=%s';
				$where_vars   = array(
					$state_code,
					$api_rep['fullName'],
					$api_rep['fullName'],
					$api_rep['district'],
				);
				$db_reps = $this->get_reps_from_db( $where_clause, $where_vars );

				if ( false === $db_reps || count( $db_reps ) === 0 ) {
					$house_success = false;
					break;
				}

				$rep_id = array_key_first( $db_reps );
				$rep    = $db_reps[ $rep_id ];

				$rep['img'] = $api_rep['img'];

				array_push( $house_reps, $rep );
			}
		}

		// Recover senators using database.
		if ( ! $house_success ) {
			$house_reps = array();

			$db_reps = $this->get_reps_from_db(
				"WHERE r.state=%s AND r.level='federal' AND r.district IS NOT NULL",
				array(
					$state_code,
				),
			);

			if ( false === $db_reps ) {
				return false;
			}

			$rep_ids = array_keys( $db_reps );
			foreach ( $rep_ids as $rep_id ) {
				array_push(
					$house_reps,
					$db_reps[ $rep_id ],
				);
			}
		}

		return array(
			'houseSuccess' => $house_success,
			'houseMembers' => $house_reps,
		);
	}

	/**
	 * Gets federal senators using the state code.
	 *
	 * @param string $state_code is the state abbreviation e.g. 'MN'.
	 *
	 * @return array|false false if there is an error, and an associative array with the
	 * fields 'senateSuccess' (bool) and 'senators' (array). If 'senateSuccess' is true
	 * that means that it was successfully able to determine the representatives from the API
	 * meaning that images exist.
	 */
	private function get_federal_senate_reps( string $state_code ): array|false {

		$api_senators    = array();
		$senator_success = false;

		// Get from API.
		if ( $this->congress_api->has_api_key() ) {
			$results = $this->congress_api->get_reps( $state_code );

			if ( false !== $results ) {
				$senator_success = true;

				$members = $results['members'];

				foreach ( $members as &$member ) {

					if ( 'Senate' !== $member['terms']['item'][0]['chamber'] ) {
						continue;
					}

					array_push(
						$api_senators,
						array(
							'img'      => $member['depiction']['imageUrl'],
							'state'    => $member['state'],
							'fullName' => $member['name'],
						)
					);
				}
			}
		}

		// Reconcile api with db.
		if ( $senator_success ) {
			$senate_reps = array();
			foreach ( $api_senators as $api_rep ) {

				$where_clause = 'WHERE ' .
					'r.state=%s AND' .
					"r.level='federal' AND" .
					'INSTR(%s, r.first_name) AND ' .
					'INSTR(%s, r.last_name) AND ' .
					'r.district IS NULL';
				$where_vars   = array(
					$state_code,
					$api_rep['fullName'],
					$api_rep['fullName'],
				);

				$db_reps = $this->get_reps_from_db( $where_clause, $where_vars );

				if ( false === $db_reps || count( $db_reps ) === 0 ) {
					$senator_success = false;
					break;
				}

				$rep_id = array_key_first( $db_reps );
				$rep    = $db_reps[ $rep_id ];

				$rep['img'] = $api_rep['img'];

				array_push( $senate_reps, $rep );
			}
		}

		$senator_success = false;

		// Recover senators using database.
		if ( ! $senator_success ) {
			$senate_reps = array();

			$db_reps = $this->get_reps_from_db(
				"WHERE r.state=%s AND r.level='federal' AND r.district IS NULL",
				array(
					$state_code,
				),
			);

			if ( false === $db_reps ) {
				return false;
			}

			$rep_ids = array_keys( $db_reps );
			foreach ( $rep_ids as $rep_id ) {
				array_push(
					$senate_reps,
					$db_reps[ $rep_id ],
				);
			}
		}

		return array(
			'senateSuccess' => $senator_success,
			'senators'      => $senate_reps,
		);
	}

	/**
	 * Sends a JSON response with federal level reps for the given state.
	 *
	 * @param string $state_code is the state abbreviation e.g. 'MN'.
	 * @param float  $latitude is the location's latitude.
	 * @param float  $longitude is the location's longitude.
	 */
	private function send_federal_level_reps( string $state_code, float $latitude, float $longitude ): void {

		$senate_results = $this->get_federal_senate_reps( $state_code );

		if ( false === $senate_results ) {
			wp_send_json(
				array(
					'error' => 'Failed to get senators!',
				),
				500
			);
			return;
		}

		$house_results = $this->get_federal_house_reps( $state_code, $latitude, $longitude );

		if ( false === $house_results ) {
			wp_send_json(
				array(
					'error' => 'Failed to get members of the house!',
				),
				500
			);
			return;
		}

		wp_send_json( array_merge( $senate_results, $house_results ) );
	}
}
