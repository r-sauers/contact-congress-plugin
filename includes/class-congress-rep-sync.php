<?php
/**
 * Collection of functions to help handling syncing operations between
 * representatives in the database and representatives from APIs.
 *
 * @package Congress
 * @subpackage Congress/includes
 */

/**
 * Imports Congress_State_API_Factory.
 */
require_once plugin_dir_path( __FILE__ ) . 'api/class-congress-state-api-factory.php';

/**
 * Imports Congress_State_Settings.
 */
require_once plugin_dir_path( __DIR__ ) . 'admin/partials/states/class-congress-state-settings.php';

/**
 * Imports Congress_State enum.
 */
require_once plugin_dir_path( __FILE__ ) . 'enum-congress-state.php';

/**
 * Imports Congress_Level enum.
 */
require_once plugin_dir_path( __FILE__ ) . 'enum-congress-level.php';

/**
 * Imports Congress_Table_Manager.
 */
require_once plugin_dir_path( __FILE__ ) . 'class-congress-table-manager.php';

/**
 * Imports Congress_Congress_API.
 */
require_once plugin_dir_path( __FILE__ ) . 'api/class-congress-congress-api.php';

/**
 * Imports Congress_Rep_Interface.
 */
require_once plugin_dir_path( __FILE__ ) . 'class-congress-rep-interface.php';

/**
 * Collection of functions to help handling syncing operations between
 * representatives in the database and representatives from APIs.
 */
class Congress_Rep_Sync {

	/**
	 * Matches $db_reps and $api_reps by position, and adds new database representatives from $api_reps,
	 * and removes database representatives not found in $api_reps.
	 *
	 * @param array<Congress_Rep_Interface> $db_reps are the reps from a DB request.
	 * @param array<Congress_Rep_Interface> $api_reps are the reps from an API call.
	 *
	 * @return array<string,array> an array with the following fields:
	 * - 'reps_removed' : array<Congress_Rep_Interface>
	 * - 'reps_added' : array<Congress_Rep_Interface>
	 */
	private static function sync( array $db_reps, array $api_reps ): array {

		$db_i           = 0;
		$api_i          = 0;
		$api_count      = count( $api_reps );
		$db_count       = count( $db_reps );
		$reps_to_insert = array();
		$reps_to_remove = array();

		while ( $api_count > $api_i || $db_count > $db_i ) {

			$cmp     = 0;
			$api_rep = null;
			$db_rep  = null;

			if ( $api_count > $api_i ) {
				$api_rep = $api_reps[ $api_i ];
				$cmp     = 1;
			}
			if ( $db_count > $db_i ) {
				$db_rep = $db_reps[ $db_i ];
				$cmp    = -1;
			}
			if ( null !== $api_rep && null !== $db_rep ) {
				$cmp = Congress_Rep_Interface::cmp_by_position( $db_rep, $api_rep );
			}

			if ( -1 >= $cmp ) {
				array_push( $reps_to_remove, $db_reps[ $db_i ] );
				++$db_i;
			} elseif ( 1 <= $cmp ) {
				++$api_i;
				array_push( $reps_to_insert, $api_rep );
			} elseif ( ! $db_rep->equals( $api_rep, true, true, true ) ) {
				array_push( $reps_to_remove, $db_reps[ $db_i ] );
				array_push( $reps_to_insert, $api_rep );
				++$db_i;
				++$api_i;
			} else {
				++$db_i;
				++$api_i;
			}
		}

		global $wpdb;
		$rep_t        = Congress_Table_Manager::get_table_name( 'representative' );
		$staffer_t    = Congress_Table_Manager::get_table_name( 'staffer' );
		$reps_removed = array();
		foreach ( $reps_to_remove as &$rep ) {

			if ( ! $rep->has_id() ) {
				continue;
			}

			$res = $wpdb->delete( // phpcs:ignore
				$rep_t,
				array(
					'id' => $rep->get_id(),
				),
				array(
					'%d',
				)
			);

			if ( false !== $res ) {
				array_push( $reps_removed, $rep );
			}
		}

		$reps_inserted = array();
		foreach ( $reps_to_insert as &$rep ) {
			$values = array(
				'first_name' => $rep->first_name,
				'last_name'  => $rep->last_name,
				'title'      => $rep->title->to_db_string(),
				'level'      => $rep->level->to_db_string(),
				'state'      => $rep->state->to_db_string(),
			);
			$types  = array(
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
			);
			if ( $rep->has_district() ) {
				$values['district'] = $rep->get_district();
				array_push( $types, '%s' );
			}
			$res = $wpdb->insert( // phpcs:ignore
				$rep_t,
				$values,
				$types
			);

			if ( false === $res ) {
				continue;
			}

			$rep->set_id( $wpdb->insert_id );

			$staffers = &$rep->get_staffers();
			foreach ( $staffers as &$staffer ) {
				$wpdb->insert( // phpcs:ignore
					$staffer_t,
					array(
						'first_name'     => $staffer->first_name,
						'last_name'      => $staffer->last_name,
						'title'          => $staffer->title,
						'representative' => $rep->get_id(),
						'email'          => $staffer->email,
					),
					array(
						'%s',
						'%s',
						'%s',
						'%d',
						'%s',
					)
				);
				$staffer->set_id( $wpdb->insert_id );
			}

			if ( false !== $res ) {
				array_push( $reps_inserted, $rep );
			}
		}

		return array(
			'reps_removed' => $reps_removed,
			'reps_added'   => $reps_inserted,
		);
	}

	/**
	 * Gets federal representatives from the Congress.gov API.
	 *
	 * @param Congress_Congress_API $federal_api is the instance of the Congress.gov API the function will use.
	 * @param ?Congress_State       $state is the state to filter by.
	 * @param ?array                $errors will be filled with any errors that occur.
	 *
	 * @return array<Congress_Rep_Interface> the resulting representatives.
	 */
	private static function get_federal_reps( Congress_Congress_API $federal_api, ?Congress_State $state, ?array &$errors = null ): array {

		$api_reps = array();

		if ( $federal_api->has_api_key() ) {

			if ( null === $state ) {
				foreach ( Congress_State_Settings::get_federal_syncing_states() as $active_state ) {
					$federal_api_reps = $federal_api->get_reps( $active_state );
					if ( false !== $federal_api_reps ) {
						$api_reps = array_merge( $api_reps, $federal_api_reps );
					} elseif ( null !== $errors ) {
						array_push(
							$errors,
							new WP_Error(
								'API_FAILURE',
								'Congress.gov API failed for ' . $active_state->to_display_string() . '.'
							)
						);
					}
				}
			} else {
				$federal_api_reps = $federal_api->get_reps( $state );
				if ( false !== $federal_api_reps ) {
					$api_reps = array_merge( $api_reps, $federal_api_reps );
				} elseif ( null !== $errors ) {
					array_push(
						$errors,
						new WP_Error(
							'API_FAILURE',
							'Congress.gov API failed for ' . $state->to_display_string() . '.'
						)
					);
				}
			}
		}

		return $api_reps;
	}

	/**
	 * Syncs the database entries filtered by $state and $level with the appropriate external apis.
	 *
	 * If $state or $level is null, it will use Congress_State_Settings->is_state_sync_enabled and
	 * Congress_State_Settings->is_federal_sync_enabled to determine which states / levels to sync.
	 *
	 * @param ?Congress_State $state is the state to filter by.
	 * @param ?Congress_Level $level is the level of government to filter by.
	 *
	 * @return array<string,array> an array with the following fields:
	 * - 'reps_removed' : array<Congress_Rep_Interface>
	 * - 'reps_added' : array<Congress_Rep_Interface>
	 * - 'errors' : array<WP_Error>
	 */
	public static function sync_reps( ?Congress_State $state, ?Congress_Level $level ): array {

		$api_reps = array();
		$errors   = array();

		$federal_api       = new Congress_Congress_API();
		$state_api_factory = Congress_State_API_Factory::get_instance();

		$sync_federal = null === $level || Congress_Level::Federal === $level;
		$sync_state   = null === $level || Congress_Level::State === $level;

		if ( $sync_federal && ! $federal_api->has_api_key() ) {
			array_push(
				$errors,
				new WP_Error( 'MISSING_API_KEY', 'Missing Congress.gov API key.' )
			);
			$sync_federal = false;
		}

		if ( $sync_state && null !== $state && ! $state_api_factory->has_state_api( $state ) ) {
			array_push(
				$errors,
				new WP_Error( 'API_NOT_IMPLEMENTED', 'The API for ' . $state->to_display_string() . ' is not implemented.' )
			);
			$sync_state = false;
		}

		if ( $sync_federal ) {
			$reps = self::get_federal_reps( $federal_api, $state );
			if ( is_wp_error( $reps ) ) {
				return $reps;
			}
			$api_reps = array_merge( $api_reps, $reps );
		}

		$state_level_states   = array();
		$federal_level_states = array();

		if ( $sync_state ) {

			$state_apis = null;
			if ( null === $state ) {
				$state_apis = $state_api_factory->get_enabled_apis();
				$state_apis = array_filter(
					$state_apis,
					function ( Congress_State_API_Interface $state_api ) {
						$state    = $state_api->get_state();
						$settings = new Congress_State_Settings( $state );
						return $settings->is_state_sync_enabled();
					}
				);
			} else {
				$state_api = $state_api_factory->get_state_api( $state );
				if ( false === $state_api ) {
					$state_apis = array();
				} else {
					$state_api = array( $state_api );
				}
			}

			foreach ( $state_apis as &$state_api ) {
				$res = $state_api->get_all_reps();
				if ( false !== $res ) {
					$api_reps = array_merge( $api_reps, $res );
					array_push( $state_level_states, $state_api->get_state()->to_db_string() );
				} else {
					array_push(
						$errors,
						new WP_Error(
							'API_FAILURE',
							'Failed to get ' .
								$state_api->get_state()->to_display_string() .
								' representatives from the API.'
						)
					);
				}
			}
		} else {
			$state_level_states = array_map(
				function ( Congress_State $state ) {
					return $state->to_db_string();
				},
				Congress_State_Settings::get_state_syncing_states(),
			);
		}

		if ( null === $state ) {
			$federal_level_states = array_map(
				function ( Congress_State $state ) {
					return $state->to_db_string();
				},
				Congress_State_Settings::get_federal_syncing_states()
			);
		} else {
			$federal_level_states = array( $state->to_db_string() );
		}

		usort( $api_reps, 'Congress_Rep_Interface::cmp_by_position_and_name' );

		global $wpdb;

		$where_clause = 'WHERE ';
		$placeholders = array();
		$first_clause = true;

		if ( null === $level || Congress_Level::Federal === $level ) {
			if ( 1 === count( $federal_level_states ) ) {
				$where_clause .= '(r.level = %s AND r.state = %s) ';
				array_push(
					$placeholders,
					Congress_Level::Federal->to_db_string(),
					$federal_level_states[0]
				);
			} else {
				$state_placeholders = join( ', ', array_fill( 0, count( $federal_level_states ), '%s', ) );
				$where_clause      .= "(r.level = %s AND r.state IN ($state_placeholders)) ";
				array_push(
					$placeholders,
					Congress_Level::Federal->to_db_string(),
					$federal_level_states
				);
			}
			$first_clause = false;
		}

		if ( null === $level || Congress_Level::State === $level ) {

			if ( ! $first_clause ) {
				$where_clause .= 'OR ';
			}

			if ( 1 === count( $state_level_states ) ) {
				$where_clause .= '(r.level = %s AND r.state = %s) ';
				array_push(
					$placeholders,
					Congress_Level::State->to_db_string(),
					$state_level_states[0]
				);
			} else {
				$state_placeholders = join( ', ', array_fill( 0, count( $state_level_states ), '%s', ) );
				$where_clause      .= "(r.level = %s AND r.state IN ($state_placeholders)) ";
				array_push(
					$placeholders,
					Congress_Level::State->to_db_string(),
					$state_level_states
				);
			}
		}

		$rep_t = Congress_Table_Manager::get_table_name( 'representative' );

		// phpcs:disable
		$db_reps = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT id, first_name, last_name, district, title, level, state ' .
				"FROM $rep_t AS r " .
				$where_clause .
				'ORDER BY state, district, last_name, first_name',
				$placeholders
			)
		);
		// phpcs:enable

		if ( null === $db_reps ) {
			return new WP_Error( 'DB_FAILURE', 'Failed to get representatives from database.' );
		}

		$db_reps = array_map(
			function ( $db_rep ) {
				return Congress_Rep_Interface::from_db_result( $db_rep );
			},
			$db_reps
		);

		$ret           = self::sync( $db_reps, $api_reps );
		$ret['errors'] = $errors;
		return $ret;
	}
}
