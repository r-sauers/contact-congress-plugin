<?php
/**
 * A class for managing state settings.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Congress
 * @subpackage Congress/admin/partials
 */

/**
 * Import Congress_State enum.
 */
require_once plugin_dir_path( __DIR__ ) . '../../includes/enum-congress-state.php';

/**
 * Import Congress_State_API_Factory to determine if a state has API support.
 */
require_once plugin_dir_path( __DIR__ ) . '../../includes/api/class-congress-state-api-factory.php';

/**
 * Responsible for managing state settings
 */
class Congress_State_Settings {

	/**
	 * Define field names.
	 */
	private const FIELD_NAME_ACTIVE       = 'active';
	private const FIELD_NAME_FEDERAL_SYNC = 'federal_sync_enabled';
	private const FIELD_NAME_STATE_SYNC   = 'state_sync_enabled';
	private const FIELD_NAME_SYNC_EMAIL   = 'sync_email';
	private const FIELD_NAME_API_ENABLED  = 'api_enabled';
	private const OPT_NAME_DEFAULT_EMAIL  = 'congress-default-sync-email';

	/**
	 * Define field defaults.
	 */
	private const FIELD_DEFAULT_ACTIVE       = false;
	private const FIELD_DEFAULT_FEDERAL_SYNC = false;
	private const FIELD_DEFAULT_STATE_SYNC   = false;
	private const FIELD_DEFAULT_SYNC_EMAIL   = '';
	private const FIELD_DEFAULT_API_ENABLED  = true;

	/**
	 * Gets a list of states that have state syncing enabled.
	 *
	 * @return array<Congress_State>
	 */
	public static function get_state_syncing_states(): array {
		$states = Congress_State::cases();

		$active_states = array_filter(
			$states,
			function ( $state ) {
				$state_settings = new Congress_State_Settings( $state );
				$is_active      = $state_settings->is_state_sync_enabled();
				if ( is_wp_error( $is_active ) ) {
					$is_active = false;
				}
				return $is_active;
			}
		);

		return $active_states;
	}

	/**
	 * Gets a list of states that have federal syncing enabled.
	 *
	 * @return array<Congress_State>
	 */
	public static function get_federal_syncing_states(): array {
		$states = Congress_State::cases();

		$active_states = array_filter(
			$states,
			function ( $state ) {
				$state_settings = new Congress_State_Settings( $state );
				$is_active      = $state_settings->is_federal_sync_enabled();
				if ( is_wp_error( $is_active ) ) {
					$is_active = false;
				}
				return $is_active;
			}
		);

		return array_values( $active_states );
	}

	/**
	 * Gets a list of states actively being used.
	 *
	 * @return array<Congress_State>
	 */
	public static function get_active_states(): array {
		$states = Congress_State::cases();

		$active_states = array_filter(
			$states,
			function ( $state ) {
				$state_settings = new Congress_State_Settings( $state );
				$is_active      = $state_settings->is_active();
				if ( is_wp_error( $is_active ) ) {
					$is_active = false;
				}
				return $is_active;
			}
		);

		return $active_states;
	}

	/**
	 * Cleans all the settings from WordPress.
	 */
	public static function clean_all_settings(): void {
		$states = Congress_State::cases();

		foreach ( $states as $state ) {
			$state_settings = new Congress_State_Settings( $state );
			$state_settings->clean_options();
		}

		delete_option( self::OPT_NAME_DEFAULT_EMAIL );
	}

	/**
	 * Sets the default email for syncing alerts.
	 *
	 * @param string $email is the default email.
	 *
	 * @return ?WP_Error null if successful.
	 */
	public static function set_default_sync_email( $email ): ?WP_Error {
		$res = update_option(
			self::OPT_NAME_DEFAULT_EMAIL,
			$email
		);

		if ( false === $res ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( new Error( 'Could not set option: ' . self::OPT_NAME_DEFAULT_EMAIL ) );
			return new WP_Error( 'OPTIONS_FAILURE', 'Error setting value!' );
		}

		return null;
	}

	/**
	 * Gets the default email for syncing alerts.
	 */
	public static function get_default_sync_email(): string|WP_Error {

		$default_email = get_option( self::OPT_NAME_DEFAULT_EMAIL );

		if ( false === $default_email ) {
			$err = self::set_default_sync_email( '' );

			if ( is_wp_error( $err ) ) {
				return $err;
			}

			return '';
		}

		return $default_email;
	}

	/**
	 * The setting's state.
	 *
	 * @var Congress_State $state is the setting's state.
	 */
	private Congress_State $state;

	/**
	 * The name of the WordPress option.
	 *
	 * @var string
	 */
	private string $options_name;

	/**
	 * Constructs the setting, configuring defaults if required.
	 *
	 * @param Congress_State $state is the state the settings are assciated with.
	 */
	public function __construct( Congress_State $state ) {

		$this->state = $state;

		$this->options_name = 'congress_' . strtolower( $state->to_state_code() ) . '_settings';

		$state_options = get_option( $this->options_name );

		if ( false === $state_options ) {
			$state_options = array();
			add_option( $this->options_name, $state_options );
		}

		/**
		 * Set up defaults if the settings don't exist.
		 */
		if ( ! isset( $state_options[ self::FIELD_NAME_ACTIVE ] ) ) {
			$state_options[ self::FIELD_NAME_ACTIVE ] = self::FIELD_DEFAULT_ACTIVE;
		}
		if ( ! isset( $state_options[ self::FIELD_NAME_FEDERAL_SYNC ] ) ) {
			$state_options[ self::FIELD_NAME_FEDERAL_SYNC ] = self::FIELD_DEFAULT_FEDERAL_SYNC;
		}
		if ( ! isset( $state_options[ self::FIELD_NAME_STATE_SYNC ] ) ) {
			$state_options[ self::FIELD_NAME_STATE_SYNC ] = self::FIELD_DEFAULT_STATE_SYNC;
		}
		if ( ! isset( $state_options[ self::FIELD_NAME_SYNC_EMAIL ] ) ) {
			$state_options[ self::FIELD_NAME_SYNC_EMAIL ] = self::FIELD_DEFAULT_SYNC_EMAIL;
		}
		if (
			! isset( $state_options[ self::FIELD_NAME_API_ENABLED ] ) ||
			! is_bool( $state_options[ self::FIELD_NAME_API_ENABLED ] )
		) {
			$state_options[ self::FIELD_NAME_API_ENABLED ] = self::FIELD_DEFAULT_API_ENABLED;
		}

		update_option( $this->options_name, $state_options );
	}

	/**
	 * Helper function to get state options from WordPress.
	 *
	 * @param ?array $cache is an associative array that is used to store and use db
	 * info to speed up multiple calls to this and related functions.
	 */
	private function get_state_options( ?array &$cache = null ): array|WP_Error {

		if ( null !== $cache && ! empty( $cache ) ) {
			return $cache;
		}

		$state_options = get_option( $this->options_name );
		if ( false === $state_options ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( new Error( $this->options_name . ' option does not exist, it was deleted.' ) );
			return new WP_Error( 'OPTIONS_FAILURE', 'Error getting value!' );
		}
		if ( null !== $cache ) {
			foreach ( array_keys( $state_options ) as $key ) {
				$cache[ $key ] = $state_options[ $key ];
			}
		}
		return $state_options;
	}

	/**
	 * Helper function to get a state option field from WordPress.
	 *
	 * @param string $field_name is one of the class constants FIELD_NAME_XXXX.
	 * @param ?array $cache is an associative array that is used to store and use db
	 * info to speed up multiple calls to this and related functions.
	 */
	private function get_state_option_field( string $field_name, ?array &$cache = null ): mixed {
		$state_options = $this->get_state_options( $cache );
		if ( is_wp_error( $state_options ) ) {
			return $state_options;
		}
		return $state_options[ $field_name ];
	}

	/**
	 * Helper function to set state options.
	 *
	 * @param string $field_name is one of the class constants FIELD_NAME_XXXX.
	 * @param mixed  $value is the value for the field.
	 *
	 * @return null if no error.
	 */
	private function set_state_option_field( string $field_name, mixed $value ): ?WP_Error {
		// Caching is a bad idea here because it could cause race conditions.

		$state_options = get_option( $this->options_name );
		if ( false === $state_options ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( new Error( $this->options_name . ' option does not exist, it was deleted.' ) );
			return new WP_Error( 'OPTIONS_FAILURE', 'Error getting value!' );
		}

		$state_options[ $field_name ] = $value;

		$res = update_option( $this->options_name, $state_options );

		if ( false === $res ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( new Error( 'Could not set option: ' . $this->options_name ) );
			return new WP_Error( 'OPTIONS_FAILURE', 'Error setting value!' );
		}

		return null;
	}

	/**
	 * Getter for the setting's state.
	 */
	public function get_state(): Congress_State {
		return $this->state;
	}

	/**
	 * Getter for the email used for sync alerts.
	 *
	 * @param bool   $use_default will return the email default
	 * if the state sync email is an empty string.
	 * @param ?array $cache is an associative array that is used to store and use db
	 * info to speed up multiple calls to this and related functions.
	 */
	public function get_sync_email( bool $use_default = false, ?array &$cache = null ): string|WP_Error {
		$field_res = $this->get_state_option_field( self::FIELD_NAME_SYNC_EMAIL, $cache );

		if ( is_wp_error( $field_res ) ) {
			return $field_res;
		}

		if ( $use_default && '' === $field_res ) {
			return self::get_default_sync_email();
		} else {
			return $field_res;
		}
	}

	/**
	 * Setter for the email used for sync alerts.
	 *
	 * @param string $email is the email alerts are sent to.
	 *
	 * @return null if no error.
	 */
	public function set_sync_email( string $email ): ?WP_Error {
		return $this->set_state_option_field( self::FIELD_NAME_SYNC_EMAIL, $email );
	}

	/**
	 * Determines whether or not the state has an API.
	 */
	public function is_api_supported(): bool {
		$instance = Congress_State_API_Factory::get_instance();
		return $instance->has_state_api( $this->state );
	}

	/**
	 * Determines whether or not the state is being actively used.
	 *
	 * @param ?array $cache is an associative array that is used to store and use db
	 * info to speed up multiple calls to this and related functions.
	 */
	public function is_active( ?array &$cache = null ): bool|WP_Error {
		return $this->get_state_option_field( self::FIELD_NAME_ACTIVE, $cache );
	}

	/**
	 * Activates the state for use throughout the plugin.
	 */
	public function activate(): ?WP_Error {
		return $this->set_state_option_field( self::FIELD_NAME_ACTIVE, true );
	}

	/**
	 * Dectivates the state for use throughout the plugin.
	 */
	public function deactivate(): ?WP_Error {
		return $this->set_state_option_field( self::FIELD_NAME_ACTIVE, false );
	}

	/**
	 * Determines whether or not the state should use its API.
	 *
	 * @param ?bool $dependency if given as false will cause the function to not
	 * check for other settings that would turn this setting to false. If
	 * $dependency is given as true, the reference variable will be set to false if
	 * the 'other settings' would have turned this setting to false.
	 * @param array $cache is an associative array that is used to store and use db
	 * info to speed up multiple calls to this and related functions.
	 */
	public function is_api_enabled( ?bool &$dependency = null, array &$cache = array() ): bool|WP_Error {

		$check_dependency = ( null === $dependency || true === $dependency );
		if ( $check_dependency ) {
			$is_active = $this->is_active();
			if ( is_wp_error( $is_active ) ) {
				return $is_active;
			}
			$is_api_supported = $this->is_api_supported( $cache );
			if ( ! $is_active || ! $is_api_supported ) {
				if ( null === $dependency ) {
					return false;
				} else {
					$dependency = false;
				}
			}
		}

		return $this->get_state_option_field( self::FIELD_NAME_API_ENABLED, $cache );
	}

	/**
	 * Enables API use for the state.
	 */
	public function enable_api(): ?WP_Error {
		return $this->set_state_option_field( self::FIELD_NAME_API_ENABLED, true );
	}

	/**
	 * Disables API use for the state.
	 */
	public function disable_api(): ?WP_Error {
		return $this->set_state_option_field( self::FIELD_NAME_API_ENABLED, false );
	}

	/**
	 * Determines whether or not the state should be used for state-level Syncing.
	 *
	 * @param ?bool $dependency if given as false will cause the function to not
	 * check for other settings that would turn this setting to false. If
	 * $dependency is given as true, the reference variable will be set to false if
	 * the 'other settings' would have turned this setting to false.
	 * @param array $cache is an associative array that is used to store and use db
	 * info to speed up multiple calls to this and related functions.
	 */
	public function is_state_sync_enabled( ?bool &$dependency = null, array &$cache = array() ): bool|WP_Error {

		$check_dependency = ( null === $dependency || true === $dependency );

		if ( $check_dependency ) {
			$is_api_enabled = $this->is_api_enabled( cache: $cache );
			if ( is_wp_error( $is_api_enabled ) ) {
				return $is_api_enabled;
			}
			if ( ! $is_api_enabled ) {
				if ( null === $dependency ) {
					return false;
				} else {
					$dependency = false;
				}
			}
		}

		return $this->get_state_option_field( self::FIELD_NAME_STATE_SYNC, $cache );
	}

	/**
	 * Enables state-level syncing for the state.
	 */
	public function enable_state_sync(): ?WP_Error {
		return $this->set_state_option_field( self::FIELD_NAME_STATE_SYNC, true );
	}

	/**
	 * Disables state-level syncing for the state.
	 */
	public function disable_state_sync(): ?WP_Error {
		return $this->set_state_option_field( self::FIELD_NAME_STATE_SYNC, false );
	}


	/**
	 * Determines whether or not the state should be used for federal-level Syncing.
	 *
	 * @param ?bool $dependency if given as false will cause the function to not
	 * check for other settings that would turn this setting to false. If
	 * $dependency is given as true, the reference variable will be set to false if
	 * the 'other settings' would have turned this setting to false.
	 * @param array $cache is an associative array that is used to store and use db
	 * info to speed up multiple calls to this and related functions.
	 */
	public function is_federal_sync_enabled( ?bool &$dependency = null, array &$cache = array() ): bool|WP_Error {

		$check_dependency = ( null === $dependency || true === $dependency );

		if ( $check_dependency ) {
			$is_active = $this->is_active( $cache );
			if ( is_wp_error( $is_active ) ) {
				return $is_active;
			}
			if ( ! $is_active ) {
				if ( null === $dependency ) {
					return false;
				} else {
					$dependency = false;
				}
			}
		}

		return $this->get_state_option_field( self::FIELD_NAME_FEDERAL_SYNC, $cache );
	}

	/**
	 * Enables federal-level syncing for the state.
	 */
	public function enable_federal_sync(): ?WP_Error {
		return $this->set_state_option_field( self::FIELD_NAME_FEDERAL_SYNC, true );
	}

	/**
	 * Disables federal-level syncing for the state.
	 */
	public function disable_federal_sync(): ?WP_Error {
		return $this->set_state_option_field( self::FIELD_NAME_FEDERAL_SYNC, false );
	}

	/**
	 * Function to clean settings data from the database.
	 */
	public function clean_options(): void {
		delete_option( $this->options_name );
	}
}
