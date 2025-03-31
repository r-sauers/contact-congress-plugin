<?php
/**
 * Defines the class for storing and handling state APIs.
 *
 * @package Congress
 * @subpackage Congress/includes
 */

/**
 * Imports Congress_State_API_Interface.
 */
require_once plugin_dir_path( __FILE__ ) . 'congress-state-api-interface.php';

/**
 * Imports Congress_MN_API.
 */
require_once plugin_dir_path( __FILE__ ) . 'class-congress-mn-api.php';

/**
 * Imports Congress_State enum.
 */
require_once plugin_dir_path( __DIR__ ) . 'enum-congress-state.php';

/**
 * The class for storing and handling state APIs.
 */
class Congress_State_API_Factory {

	/**
	 * A singleton instance of this class.
	 *
	 * @var ?Congress_State_API_Factory
	 */
	public static ?Congress_State_API_Factory $instance = null;

	/**
	 * Gets the singleton instance of this class.
	 *
	 * @var Congress_State_API_Factory
	 */
	public static function get_instance(): Congress_State_API_Factory {
		if ( null === self::$instance ) {
			self::$instance = new Congress_State_API_Factory();
		}
		return self::$instance;
	}

	/**
	 * Stores the existing state APIs.
	 *
	 * @var array<string,Congress_State_API_Interface>
	 */
	private array $state_apis;

	/**
	 * Constructs the factory.
	 */
	private function __construct() {
		$this->state_apis = array(
			Congress_MN_API::get_state()->name => new Congress_MN_API(),
		);
	}

	/**
	 * Checks if the given state API exists.
	 *
	 * @param Congress_State $state is the desired API's state.
	 */
	public function has_state_api( Congress_State $state ): bool {
		return isset( $this->state_apis[ $state->name ] );
	}

	/**
	 * Retrieves the given state API.
	 *
	 * @see has_state_api to ensure if the API exists,
	 * this function returns false if the API doesn't exist.
	 *
	 * @param Congress_State $state is the desired API's state.
	 */
	public function get_state_api( Congress_State $state ): Congress_State_API_Interface|false {
		if ( ! isset( $this->state_apis[ $state->name ] ) ) {
			return false;
		}
		return $this->state_apis[ $state->name ];
	}

	/**
	 * Retrieves a list of all of the enabled state APIs.
	 *
	 * @return array<Congress_State_API_Interface>
	 */
	public function get_enabled_apis(): array {
		return array_values( $this->state_apis );
	}
}
