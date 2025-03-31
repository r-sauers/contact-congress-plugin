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
	 * @param array                         $db_reps are the reps from a DB request.
	 * Requires the following fields:
	 * first_name, last_name, state, level, title, and district.
	 * @param array<Congress_Rep_Interface> $api_reps are the reps from an API call.
	 *
	 * @return array<string,array> an array with the following fields:
	 * - 'ids_removed' : array<int>
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
				$db_rep = Congress_Rep_Interface::from_db_result( $db_reps[ $db_i ] );
				$cmp    = -1;
			}
			if ( null !== $api_rep && null !== $db_rep ) {
				$cmp = Congress_Rep_Interface::cmp_by_position( $db_rep, $api_rep );
			}

			if ( -1 >= $cmp ) {
				array_push( $reps_to_remove, $db_reps[ $db_i ]->id );
				++$db_i;
			} elseif ( 1 <= $cmp ) {
				++$api_i;
				array_push( $reps_to_insert, $api_rep );
			} elseif ( ! $db_rep->equals( $api_rep, true, true ) ) {
				array_push( $reps_to_remove, $db_reps[ $db_i ]->id );
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
		foreach ( $reps_to_remove as $id ) {
			$res = $wpdb->delete( // phpcs:ignore
				$rep_t,
				array(
					'id' => $id,
				),
				array(
					'%d',
				)
			);
			if ( false !== $res ) {
				array_push( $reps_removed, $id );
			}
		}

		$reps_inserted = array();
		foreach ( $reps_to_insert as $rep ) {
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

			if ( $rep->has_email() ) {
				$wpdb->insert( // phpcs:ignore
					$staffer_t,
					array(
						'first_name'     => $rep->first_name,
						'last_name'      => $rep->last_name,
						'title'          => $rep->title->to_db_string(),
						'representative' => $wpdb->insert_id,
						'email'          => $rep->get_email(),
					),
					array(
						'%s',
						'%s',
						'%s',
						'%d',
					)
				);
			}

			if ( false !== $res ) {
				array_push( $reps_inserted, $rep );
			}
		}

		return array(
			'ids_removed' => $reps_removed,
			'reps_added'  => $reps_inserted,
		);
	}

	/**
	 * Syncs the database entries with the all external apis that are enabled.
	 *
	 * @return array<string,array> an array with the following fields:
	 * - 'ids_removed' : array<int>
	 * - 'reps_added' : array<Congress_Rep_Interface>
	 */
	public static function sync_all_reps(): array {

		$api_reps = array();

		$federal_api = new Congress_Congress_API();
		if ( $federal_api->has_api_key() ) {
			$raw_api_reps = $federal_api->get_reps();

			if ( false !== $raw_api_reps ) {

				$members = $raw_api_reps['members'];

				foreach ( $members as &$member ) {

					$last_term = count( $member['terms']['item'] ) - 1;

					// Assert that the last term is actually the last term.
					if (
						isset( $member['terms']['item'][ $last_term ]['endYear'] ) &&
						intval( $member['terms']['item'][ $last_term ]['endYear'] ) < intval( gmdate( 'Y' ) )
					) {
						error_log( // phpcs:ignore
							new Error( 'Assertion failed for Congress.gov API.' )
						);
						continue;
					}

					$title = null;
					if ( 'Senate' === $member['terms']['item'][ $last_term ]['chamber'] ) {
						$title = Congress_Title::Senator;
					} else {
						$title = Congress_Title::Representative;
					}

					array_push(
						$api_reps,
						new Congress_Rep_Interface(
							first_name: $member['name'],
							first_name: $member['name'],
							district: $member['district'],
							title: $title,
							level: Congress_Level::Federal,
							state: Congress_State::from_string( $member['state'] ),
						)
					);
				}
			}
		}

		$db_reps = array();

		return sync( $db_reps, $api_reps );
	}

	/**
	 * Syncs the database entries filtered by $state and $level with the appropriate external apis.
	 *
	 * If an appropriate API is not found, returns WP_Error.
	 *
	 * @param Congress_State $state is the state to filter by.
	 * @param Congress_Level $level is the level of government to filter by.
	 *
	 * @return array<string,array>|WP_Error an array with the following fields:
	 * - 'ids_removed' : array<int>
	 * - 'reps_added' : array<Congress_Rep_Interface>
	 */
	public static function sync_reps( Congress_State $state, Congress_Level $level ): array|WP_Error {

		if ( Congress_Level::Federal === $level ) {
			$federal_api = new Congress_Congress_API();
			if ( ! $federal_api->has_api_key() ) {
				return new WP_Error(
					'MISSING_API_KEY',
					'Error, Contact the page admin.',
					'Missing Congress.gov API key.'
				);
			}
			$raw_api_reps = $federal_api->get_reps( $state );
			$api_reps     = false;

			if ( false !== $raw_api_reps ) {

				$api_reps = array();
				$members  = $raw_api_reps['members'];

				foreach ( $members as &$member ) {

					$last_term = count( $member['terms']['item'] ) - 1;

					// Assert that the last term is actually the last term.
					if (
						isset( $member['terms']['item'][ $last_term ]['endYear'] ) &&
						intval( $member['terms']['item'][ $last_term ]['endYear'] ) < intval( gmdate( 'Y' ) )
					) {
						error_log( // phpcs:ignore
							new Error( 'Assertion failed for Congress.gov API.' )
						);
						continue;
					}

					$title = null;
					if ( 'Senate' === $member['terms']['item'][ $last_term ]['chamber'] ) {
						$title = Congress_Title::Senator;
					} else {
						$title = Congress_Title::Representative;
					}

					$name_explode = explode( ' ', $member['name'] );
					array_push(
						$api_reps,
						new Congress_Rep_Interface(
							last_name: substr( $name_explode[0], 0, -1 ),
							first_name: $name_explode[ count( $name_explode ) - 1 ],
							district: $member['district'],
							title: $title,
							level: Congress_Level::Federal,
							state: Congress_State::from_string( $member['state'] ),
						)
					);
				}
			}
		} else {
			$state_api_factory = Congress_State_API_Factory::get_instance();
			if ( ! $state_api_factory->has_state_api( $state ) ) {
				return new WP_Error(
					'API_NOT_IMPLEMENTED',
					'The API for ' . $state->to_display_string() . ' is not implementd.'
				);
			}
			$state_api = $state_api_factory->get_state_api( $state );
			$api_reps  = $state_api->get_all_reps();
		}

		if ( false === $api_reps ) {
			return new WP_Error( 'API_FAILURE', 'Failed to get representative from the API.' );
		}

		usort( $api_reps, 'Congress_Rep_Interface::cmp_by_position_and_name' );

		global $wpdb;
		$rep_t   = Congress_Table_Manager::get_table_name( 'representative' );
		$db_reps = $wpdb->get_results( // phpcs:ignore
			$wpdb->prepare(
				'SELECT id, first_name, last_name, district, title, level, state ' .
				"FROM $rep_t ". // phpcs:ignore
				'WHERE state=%s AND level=%s ' .
				'ORDER BY state, district, last_name, first_name',
				array(
					$state->to_db_string(),
					$level->to_db_string(),
				)
			)
		);

		if ( null === $db_reps ) {
			return new WP_Error( 'DB_FAILURE', 'Failed to get representatives from database.' );
		}

		return self::sync( $db_reps, $api_reps );
	}
}
