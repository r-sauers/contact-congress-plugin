<?php
/**
 * Handles Cronjobs.
 *
 * @package Congress
 * @subpackage Congress/includes
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Import Congress_Rep_Sync
 */
require_once plugin_dir_path( __FILE__ ) . 'class-congress-rep-sync.php';

/**
 * Import Congress_State_Settings
 */
require_once plugin_dir_path( __DIR__ ) . 'admin/partials/states/class-congress-state-settings.php';

/**
 * Import Congress_Admin for page names.
 */
require_once plugin_dir_path( __DIR__ ) . 'admin/class-congress-admin.php';

/**
 * CronJob handlers, hooks, etc.
 */
class Congress_Cron {

	/**
	 * Name of the cron hook for syncing representatives.
	 *
	 * @var {string} $sync_hook
	 */
	private static string $sync_hook = 'congress_cron_sync';

	/**
	 * Loads the actions that the cronjobs run.
	 *
	 * This should be loaded on every page load that cron is run on.
	 *
	 * @param Congress_Loader $loader is the plugin's loader.
	 */
	public function load_actions( Congress_Loader &$loader ): void {
		$loader->add_action(
			self::$sync_hook,
			$this,
			'sync'
		);
	}

	/**
	 * Schedules the cron actions if they haven't already been scheduled.
	 */
	public function schedule_cron(): void {

		$time = new DateTime();
		$time->add( new DateInterval( 'P1D' ) );
		$time->setTime( hour: 3, minute: 0, second: 0 );
		$time = $time->getTimestamp();

		if ( ! wp_next_scheduled( self::$sync_hook ) ) {
			$res = wp_schedule_event(
				$time,
				'daily',
				self::$sync_hook
			);
		}
	}

	/**
	 * Clears the scheduled cron actions from WordPress.
	 */
	public function clear_cron(): void {
		wp_clear_scheduled_hook( self::$sync_hook );
	}

	/**
	 * Cron handler to sync representatives.
	 *
	 * If mail is supported, will send an email to the
	 * email set up in @see Congress_State_Settings.
	 */
	public static function sync(): void {

		$res = Congress_Rep_Sync::sync_reps();

		/**
		 * The errors encountered during syncing.
		 *
		 * @type array<WP_Error>
		 */
		$errors = $res['errors'];

		/**
		 * The representatives added during syncing.
		 *
		 * @type array<Congress_Rep_Interface>
		 */
		$added = $res['reps_added'];

		/**
		 * The representatives removed during syncing.
		 *
		 * @type array<Congress_Rep_Interface>
		 */
		$removed = $res['reps_removed'];

		$states         = array();
		$general_errors = array();

		foreach ( $errors as &$error ) {
			$state_data = self::ensure_state( $states, $error->get_error_data() );
			if ( is_wp_error( $state_data ) ) {
				array_push( $general_errors, $error );
				continue;
			}
			array_push( $state_data['errors'], $error );
		}
		foreach ( $added as &$rep ) {
			$state_data = &self::ensure_state( $states, $rep->state );
			if ( is_wp_error( $state_data ) ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( $state_data );
				continue;
			}
			array_push( $state_data['reps_added'], $rep );
		}
		foreach ( $errors as &$error ) {
			$state_data = self::ensure_state( $states, $rep->state );
			if ( is_wp_error( $state_data ) ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( $state_data );
				continue;
			}
			array_push( $state_data['reps_removed'], $rep );
		}

		foreach ( $states as $state_data ) {
			$errors  = $state_data['errors'];
			$added   = $state_data['reps_added'];
			$removed = $state_data['reps_removed'];
			$email   = $state_data['email'];
			$state   = $state_data['state'];

			$has_error         = 0 < count( $errors );
			$reps_were_added   = 0 < count( $added );
			$reps_were_removed = 0 < count( $removed );
			$has_update        = $reps_were_added || $reps_were_removed;

			if ( $has_error || $has_update ) {

				$message = 'While syncing the representative information for the Contact Congress Plugin with state and federal records, ';

				if ( $has_update && $has_error ) {
					$message .= 'there was an update to representatives and an error occured.';
				} elseif ( $has_update ) {
					$message .= 'there was an update to representatives.';
				} elseif ( $has_error ) {
					$message .= 'an error occured.';
				}

				if ( $has_error ) {
					$message .= "\n\n[Errors]\n";

					foreach ( $errors as $error ) {
						$code        = $error->get_error_code();
						$err_message = $error->get_error_message();
						$message    .= "\nError ($code): $message\n";
					}
				}

				if ( $reps_were_added ) {
					$message .= "\n\n[Representatives Added]\n";

					$rep_url = admin_url( 'admin.php?page=' . Congress_Admin::$rep_page_slug );

					$need_staffer_str = '';
					$has_staffer_str  = '';

					foreach ( $added as $rep ) {

						$level     = $rep->level->to_display_string();
						$title     = $rep->title->to_display_string();
						$first     = $rep->first_name;
						$last      = $rep->last_name;
						$state_txt = $state->to_display_string();

						$district_text = '';
						if ( $rep->has_district() ) {
							$district_text .= '(District ' . $rep->get_district() . ')';
						}

						if ( 0 === count( $rep->get_staffers() ) ) {
							$need_staffer_str .= "- $state_txt $level $title $first $last $district_text\n";
						} else {
							$has_staffer_str .= "- $state_txt $level $title $first $last $district_text\n";
						}
					}

					$message .= "\nThe following representatives don't have any staffer emails, please update staffers at $rep_url ASAP.\n";
					$message .= $need_staffer_str;
					$message .= "\nThe following representatives were added with staffer emails, but you may wish to add more at $rep_url.\n";
					$message .= $has_staffer_str;
				}

				if ( $reps_were_removed ) {
					$message .= "\n\n[Representatives Removed]\n";

					foreach ( $removed as $rep ) {
						$level     = $rep->level->to_display_string();
						$title     = $rep->title->to_display_string();
						$first     = $rep->first_name;
						$last      = $rep->last_name;
						$state_txt = $state->to_display_string();

						$district_text = '';
						if ( $rep->has_district() ) {
							$district_text .= '(District ' . $rep->get_district() . ')';
						}

						$message .= "- $state_txt $level $title $first $last $district_text\n";
					}
				}

				$state_url = admin_url( 'admin.php?page=' . Congress_Admin::$state_page_slug );
				$message  .= "\n\nIf you would like to change who gets this email, you may update that at $state_url.";

				$header_hint = $state->to_display_string();
				wp_mail(
					$email,
					"Contact Congress Plugin: Representative Sync Update ($header_hint)",
					$message
				);
			}
		}

		if ( 0 < count( $general_errors ) ) {

			$email = Congress_State_Settings::get_default_sync_email();
			if ( is_wp_error( $email ) || '' === $email ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log(
					new WP_Error(
						'INVALID_EMAIL',
						'The Default email for Contact Congress representative syncing is invalid.',
						$email
					)
				);
			}

			$message  = 'While syncing the representative information for the Contact ';
			$message .= "Congress Plugin with state and federal records, an error occured.\n";
			$message .= "\n\n[Errors]\n";

			foreach ( $general_errors as $error ) {
				$code        = $error->get_error_code();
				$err_message = $error->get_error_message();
				$message    .= "\nError ($code): $message\n";
			}

			$state_url = admin_url( 'admin.php?page=' . Congress_Admin::$state_page_slug );
			$message  .= "\n\nIf you would like to change who gets this email, you may update the default email at $state_url.";

			wp_mail(
				$email,
				'Contact Congress Plugin: Representative Sync Update (Errors)',
				$message
			);
		}
	}

	/**
	 * A helper function that tests if $state is a Congress_State and returns the entry in $states for $state.
	 *
	 * If the entry for $state does not exist, adds an associative array entry in $states for $state.
	 *
	 * The associative array entry contains the following fields:
	 * 'state' (Congress_State)
	 * 'errors' (array<WP_Error>)
	 * 'reps_added' (array<Congress_Rep_Interface>)
	 * 'reps_removed' (array<Congress_Rep_Interface>)
	 *
	 * @param array $states is an associative array that stores the state data specified above.
	 * @param mixed $state is Congress_State or a WP_Error is thrown.
	 *
	 * @return array|WP_Error The state entry with the fields as defined above, or WP_Error if $state is not a state.
	 */
	private static function &ensure_state( array &$states, mixed $state ): array|WP_Error {
		if ( is_a( $state, 'Congress_State' ) ) {
			if ( ! isset( $states[ $state->name ] ) ) {

				$settings = new Congress_State_Settings( $state );
				$email    = $settings->get_sync_email( true );

				if ( is_wp_error( $email ) ) {
					return $email;
				}
				if ( '' === $email ) {
					return new WP_Error( 'INVALID_EMAIL', 'Sync email for ' . $state->to_display_string() . ' is invalid.' );
				}

				$states[ $state->name ] = array(
					'state'        => $state,
					'errors'       => array(),
					'reps_added'   => array(),
					'reps_removed' => array(),
					'email'        => $email,
				);
			}
		} else {
			return new WP_Error( 'INVALID_PARAM', 'The state parameter is not of type Congress_State.' );
		}

		return $states[ $state->name ];
	}
}
