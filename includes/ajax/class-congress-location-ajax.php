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
 * Imports Congress_Captcha handler.
 */
require_once plugin_dir_path( __FILE__ ) .
	'../class-congress-captcha.php';

/**
 * Imports Congress_State_API_Factory.
 */
require_once plugin_dir_path( __FILE__ ) .
	'../api/class-congress-state-api-factory.php';

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
 * Imports enum types.
 */
require_once plugin_dir_path( __DIR__ ) . 'enum-congress-state.php';
require_once plugin_dir_path( __DIR__ ) . 'enum-congress-level.php';
require_once plugin_dir_path( __DIR__ ) . 'enum-congress-title.php';

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

	/**
	 * The cookie name of the google session token. @see get_session
	 *
	 * @var string
	 */
	private static string $places_session_name = 'wp_congress_places_session';

	/**
	 * Google Places API.
	 *
	 * @var Congress_Google_Places_API
	 */
	private Congress_Google_Places_API $places_api;

	/**
	 * An instance for captcha handling.
	 *
	 * @var Congress_Captcha
	 */
	private Congress_Captcha $captcha;

	/**
	 * Congress.gov API.
	 *
	 * @var Congress_Congress_API
	 */
	private Congress_Congress_API $congress_api;

	/**
	 * State APIs.
	 *
	 * @var Congress_State_API_Factory
	 */
	private Congress_State_API_Factory $state_api_factory;

	/**
	 * Constructs the Congress_Location_AJAX class.
	 */
	public function __construct() {
		$this->captcha           = new Congress_Captcha();
		$this->places_api        = new Congress_Google_Places_API();
		$this->congress_api      = new Congress_Congress_API();
		$this->state_api_factory = Congress_State_API_Factory::get_instance();
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

		if ( ! check_ajax_referer( 'get-session', false, false ) ) {
			wp_send_json(
				array(
					'error' => 'Incorrect Nonce',
				),
				403
			);
			return;
		}

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
			! isset( $_POST['g-recaptcha-response'] ) ||
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

		if ( ! check_ajax_referer( 'autocomplete', false, false ) ) {
			wp_send_json(
				array(
					'error' => 'Incorrect Nonce',
				),
				403
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

		if ( ! $this->captcha->has_server_key() ) {
			wp_send_json(
				array(
					'error' => 'No captcha server key.',
				),
				500
			);
		}

		$token = sanitize_text_field(
			wp_unslash( $_POST['g-recaptcha-response'] )
		);

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

		$result = $this->captcha->verify_captcha( $token, $ip );

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
			! isset( $_POST['campaignRegion'] ) ||
			! isset( $_POST['campaignID'] )
		) {
			wp_send_json(
				array(
					'error' => 'Missing parameters',
				),
				400
			);
			return;
		}

		$campaign_id = sanitize_text_field(
			wp_unslash( $_POST['campaignID'] )
		);

		if ( ! check_ajax_referer( "get-reps_$campaign_id", false, false ) ) {
			wp_send_json(
				array(
					'error' => 'Incorrect Nonce',
				),
				403
			);
			return;
		}

		try {
			$campaign_region = sanitize_text_field(
				wp_unslash( $_POST['campaignRegion'] )
			);
			try {
				$campaign_level = Congress_Level::from_string( $campaign_region );
				$campaign_state = null;
			} catch ( Error $e ) {
				$campaign_level = Congress_Level::State;
				$campaign_state = Congress_State::from_string( $campaign_region );
			}
		} catch ( Error $e ) {
			wp_send_json(
				array(
					'error' => 'Invalid parameters',
				),
				400
			);
		}

		$place_id      = sanitize_text_field(
			wp_unslash( $_POST['placeId'] )
		);
		$session_token = null;
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

		try {
			foreach ( $address_components as $address_component ) {
				if ( in_array( 'administrative_area_level_1', $address_component['types'], true ) ) {
					$state = Congress_State::from_string( $address_component['shortText'] );
				}
			}
		} catch ( Error $e ) {
			wp_send_json(
				array(
					'error' => 'Failed to get state',
				),
				500
			);
			return;
		}

		if ( Congress_Level::State === $campaign_level && $state !== $campaign_state ) {
			wp_send_json(
				array(
					'error' => 'The campaign is for ' . $campaign_state->to_display_string() . ', not ' . $state->to_display_string() . '.',
				),
				400
			);
		}

		$state_settings = new Congress_State_Settings( $state );
		if ( ! $state_settings->is_active() ) {
			wp_send_json(
				array(
					'error' => 'Representatives for ' . $state->to_display_string() . ' are not maintained, sorry!',
				),
				500
			);
		}

		$response = array(
			'registerEmailNonce' => wp_create_nonce( "register-email_$campaign_id" ),
		);

		if ( Congress_Level::State === $campaign_level ) {
			$this->send_state_level_reps( $state, $latitude, $longitude, $response );
		} elseif ( Congress_Level::Federal === $campaign_level ) {
			$this->send_federal_level_reps( $state, $latitude, $longitude, $response );
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
					'id'         => $rep_staffer['rep_id'],
					'first_name' => $rep_staffer['rep_first_name'],
					'last_name'  => $rep_staffer['rep_last_name'],
					'state'      => $rep_staffer['state'],
					'level'      => $rep_staffer['level'],
					'district'   => $rep_staffer['district'],
					'title'      => $rep_staffer['rep_title'],
					'staffers'   => array(),
				);
			}

			$rep = &$reps[ $rep_staffer['rep_id'] ];

			array_push(
				$rep['staffers'],
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
	 * @param Congress_State $state_code is the state.
	 * @param float          $latitude is the location's latitude.
	 * @param float          $longitude is the location's longitude.
	 * @param array          $response contains body parameters that must be returned on success.
	 */
	private function send_state_level_reps( Congress_State $state_code, float $latitude, float $longitude, array $response ): void {

		$api_reps = null;
		$success  = false;

		if ( $this->state_api_factory->has_state_api( $state_code ) ) {
			$results = $this->state_api_factory->
				get_state_api( $state_code )->
				get_state_reps( $latitude, $longitude );

			if ( false !== $results ) {
				$success = true;

				$api_reps = $results;
			}
		}

		global $wpdb;
		$reps = null;

		if ( $success ) {
			$reps = array();

			/**
			 * The representative from the API.
			 *
			 * @var Congress_Rep_Interface $api_rep
			 */
			foreach ( $api_reps as $api_rep ) {
				$db_reps = $this->get_reps_from_db(
					'WHERE r.state=%s AND r.district=%s AND r.level=%s AND r.first_name=%s AND r.last_name=%s',
					array(
						$api_rep->state->to_db_string(),
						$api_rep->get_district(),
						$api_rep->level->to_db_string(),
						$api_rep->first_name,
						$api_rep->last_name,
					),
				);

				if ( false === $db_reps || count( $db_reps ) === 0 ) {
					$success = false;
					break;
				}

				$rep_id = array_key_first( $db_reps );
				$rep    = $db_reps[ $rep_id ];

				$rep['img'] = $api_rep->get_img();

				array_push( $reps, $rep );
			}
		}

		if ( ! $success ) {
			$reps = array();

			$db_reps = $this->get_reps_from_db(
				"WHERE r.state=%s AND r.level='state'",
				array(
					$state_code->to_db_string(),
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

		$final_response = array_merge(
			$response,
			array(
				'level'           => Congress_Level::State->to_db_string(),
				'stateCode'       => $state_code->to_state_code(),
				'success'         => $success,
				'representatives' => $reps,
			)
		);

		wp_send_json( $final_response );
	}

	/**
	 * Gets representatives from the federal house of representatives using a state code and location.
	 *
	 * @param Congress_State $state_code is the state abbreviation e.g. 'MN'.
	 * @param float          $latitude is the location's latitude.
	 * @param float          $longitude is the location's longitude.
	 * @return array|false false if there is an error, and an associative array with the
	 * fields 'houseSuccess' (bool) and 'houseMembers' (array). If 'houseSuccess' is true
	 * that means that it was successfully able to determine the representatives from the location
	 * meaning. If it is false, all the reps from the db will be returned.
	 */
	private function get_federal_house_reps( Congress_State $state_code, float $latitude, float $longitude ): array|false {

		$api_reps      = null;
		$house_success = false;

		// Get from API.
		if ( $this->state_api_factory->has_state_api( $state_code ) ) {
			$results = $this->state_api_factory->
				get_state_api( $state_code )->
				get_federal_reps( $latitude, $longitude );

			if ( false !== $results ) {
				$house_success = true;

				$api_reps = $results;
			}
		}

		$house_reps = null;

		// Reconcile api with db.
		if ( $house_success ) {

			$house_reps = array();

			foreach ( $api_reps as &$api_rep ) {

				$db_reps = $this->get_reps_from_db(
					'WHERE r.state=%s AND r.district=%s AND r.level=%s AND r.first_name=%s AND r.last_name=%s',
					array(
						$api_rep->state->to_db_string(),
						$api_rep->get_district(),
						$api_rep->level->to_db_string(),
						$api_rep->first_name,
						$api_rep->last_name,
					),
				);

				if ( false === $db_reps || count( $db_reps ) === 0 ) {
					$house_success = false;
					break;
				}

				$rep_id = array_key_first( $db_reps );
				$rep    = $db_reps[ $rep_id ];

				$rep['img'] = $api_rep->get_img();

				array_push( $house_reps, $rep );
			}
		}

		// Recover senators using database.
		if ( ! $house_success ) {
			$house_reps = array();

			$db_reps = $this->get_reps_from_db(
				'WHERE r.state=%s AND r.level=%s AND r.district IS NOT NULL',
				array(
					$state_code->to_db_string(),
					Congress_Level::Federal->to_db_string(),
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
	 * @param Congress_State $state_code is the state abbreviation e.g. 'MN'.
	 *
	 * @return array|false false if there is an error, and an associative array with the
	 * fields 'senateSuccess' (bool) and 'senators' (array). If 'senateSuccess' is true
	 * that means that it was successfully able to determine the representatives from the API
	 * meaning that images exist.
	 */
	private function get_federal_senate_reps( Congress_State $state_code ): array|false {

		$api_senators = array();

		$senator_success = false;

		// Get from API.
		if ( $this->congress_api->has_api_key() ) {
			$results = $this->congress_api->get_reps( $state_code );

			if ( false !== $results ) {
				$senator_success = true;
				$api_senators    = array_merge( $api_senators, $results );
			}
		}

		// Reconcile api with db.
		if ( $senator_success ) {
			$senate_reps = array();
			foreach ( $api_senators as &$api_rep ) {

				$where_clause = 'WHERE ' .
					'r.state=%s AND ' .
					'r.level=%s AND ' .
					'r.first_name=%s AND ' .
					'r.last_name=%s AND ' .
					'r.district IS NULL';
				$where_vars   = array(
					$state_code->to_db_string(),
					Congress_Level::Federal->to_db_string(),
					$api_rep->first_name,
					$api_rep->last_name,
				);

				$db_reps = $this->get_reps_from_db( $where_clause, $where_vars );

				if ( false === $db_reps || count( $db_reps ) === 0 ) {
					$senator_success = false;
					break;
				}

				$rep_id = array_key_first( $db_reps );
				$rep    = $db_reps[ $rep_id ];

				$rep['img'] = $api_rep->get_img();

				array_push( $senate_reps, $rep );
			}
		}

		// Recover senators using database.
		if ( ! $senator_success ) {
			$senate_reps = array();

			$db_reps = $this->get_reps_from_db(
				'WHERE r.state=%s AND r.level=%s AND r.district IS NULL',
				array(
					$state_code->to_db_string(),
					Congress_Level::Federal->to_db_string(),
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
	 * @param Congress_State $state_code is the state abbreviation e.g. 'MN'.
	 * @param float          $latitude is the location's latitude.
	 * @param float          $longitude is the location's longitude.
	 * @param array          $response contains body parameters that must be returned on success.
	 */
	private function send_federal_level_reps( Congress_State $state_code, float $latitude, float $longitude, array $response ): void {

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

		$results              = array_merge( $senate_results, $house_results, $response );
		$results['level']     = Congress_Level::Federal->to_db_string();
		$results['stateCode'] = $state_code->to_state_code();
		wp_send_json( $results );
	}
}
