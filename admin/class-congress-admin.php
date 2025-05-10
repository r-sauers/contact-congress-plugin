<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Congress
 * @subpackage Congress/admin
 */

/**
 * The active campaign class to get campaign ids to generate nonces.
 */
require_once plugin_dir_path( __DIR__ ) .
	'admin/partials/campaigns/class-congress-admin-active-campaign.php';

/**
 * The Congress.gov API to get options field.
 */
require_once plugin_dir_path( __DIR__ ) .
	'includes/api/class-congress-congress-api.php';

/**
 * The Google Places API to get options field.
 */
require_once plugin_dir_path( __DIR__ ) .
	'includes/api/class-congress-google-places-api.php';

/**
 * The Captcha class to get options field.
 */
require_once plugin_dir_path( __DIR__ ) .
	'includes/class-congress-captcha.php';

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Congress
 * @subpackage Congress/admin
 * @author     Your Name <email@example.com>
 */
class Congress_Admin {

	/**
	 * The name of the page for basic settings.
	 *
	 * @var {string}
	 */
	public static string $main_page_slug = 'congress';

	/**
	 * The name of the page for managing campaigns.
	 *
	 * @var {string}
	 */
	public static string $campaign_page_slug = 'congress_campaign';

	/**
	 * The name of the page for managing representatives.
	 *
	 * @var {string}
	 */
	public static string $rep_page_slug = 'congress_rep';

	/**
	 * The name of the page for managing state settings.
	 *
	 * @var {string}
	 */
	public static string $state_page_slug = 'congress_state';

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $congress    The ID of this plugin.
	 */
	private $congress;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $congress       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $congress, $version ) {
		$this->congress = $congress;
		$this->version  = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles(): void {
		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Congress_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Congress_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style(
			$this->congress,
			plugin_dir_url( __FILE__ ) . 'css/congress-admin.css',
			array(),
			$this->version,
			'all'
		);

		if ( isset( $_GET['page'] ) && self::$state_page_slug === $_GET['page'] ) { // phpcs:ignore
			wp_enqueue_style(
				$this->congress . '-state',
				plugin_dir_url( __FILE__ ) . 'css/congress-admin-state.css',
				array(),
				$this->version,
				'all'
			);
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts(): void {
		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Congress_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Congress_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		if ( isset( $_GET['page'] ) && self::$rep_page_slug === $_GET['page'] ) { // phpcs:ignore
			wp_enqueue_script(
				$this->congress,
				plugin_dir_url( __FILE__ ) . 'js/congress-admin-rep.js',
				array( 'jquery' ),
				$this->version,
				false
			);
			wp_localize_script(
				$this->congress,
				'congressSyncRepsNonce',
				wp_create_nonce( 'sync-reps' ),
			);
		} elseif ( isset( $_GET['page'] ) && self::$campaign_page_slug === $_GET['page'] ) { // phpcs:ignore
			wp_enqueue_script(
				$this->congress,
				plugin_dir_url( __FILE__ ) . 'js/congress-admin-campaign.js',
				array( 'jquery' ),
				$this->version,
				false
			);
			$load_template_nonces = array();
			$campaigns            = Congress_Admin_Active_Campaign::get_from_db();
			foreach ( $campaigns as $campaign ) {
				$load_template_nonces[ $campaign->get_id() ] = wp_create_nonce( 'load-templates_' . $campaign->get_id() );
			}
			wp_localize_script(
				$this->congress,
				'congressAjaxObj',
				array(
					'loadTemplateNonce' => $load_template_nonces,
				),
			);
		} elseif ( isset( $_GET['page'] ) && self::$state_page_slug === $_GET['page'] ) { // phpcs:ignore
			wp_enqueue_script(
				$this->congress,
				plugin_dir_url( __FILE__ ) . 'js/congress-admin-state.js',
				array( 'jquery' ),
				$this->version,
				false
			);
		}
	}

	/**
	 * Initializes admin settings for the plugin.
	 *
	 * @since 1.0.0
	 */
	public function init_settings(): void {

		register_setting( 'congress', 'congress_options' );
		add_settings_section(
			'congress_section_api_keys',
			__( 'Contact Congress API keys', 'congress' ),
			array( $this, 'section_api_keys_callback' ),
			'congress'
		);

		add_settings_field(
			Congress_Google_Places_API::$field_name,
			__( 'Google API Key', 'congress' ),
			array( $this, 'congress_field_google_cb' ),
			'congress',
			'congress_section_api_keys',
			array(
				'label_for' => Congress_Google_Places_API::$field_name,
			)
		);
		add_settings_field(
			Congress_Congress_API::$field_name,
			__( 'Congress.gov API Key', 'congress' ),
			array( $this, 'congress_field_congress_cb' ),
			'congress',
			'congress_section_api_keys',
			array(
				'label_for' => Congress_Congress_API::$field_name,
			)
		);

		add_settings_field(
			Congress_Captcha::$server_key_field_name,
			__( "Server's Google Captcha Key", 'congress' ),
			array( $this, 'congress_field_captcha_server_cb' ),
			'congress',
			'congress_section_api_keys',
			array(
				'label_for' => Congress_Captcha::$server_key_field_name,
			)
		);
		add_settings_field(
			Congress_Captcha::$client_key_field_name,
			__( "Client's Google Captcha Key", 'congress' ),
			array( $this, 'congress_field_captcha_client_cb' ),
			'congress',
			'congress_section_api_keys',
			array(
				'label_for' => Congress_Captcha::$client_key_field_name,
			)
		);
	}

	/**
	 * API keys section callback function.
	 */
	public function section_api_keys_callback(): void {
		?>
		<?php
	}

	/**
	 * Captcha server key field callback function.
	 *
	 * WordPress has magic interaction with the following keys: label_for, class.
	 * - the "label_for" key value is used for the "for" attribute of the <label>.
	 * - the "class" key value is used for the "class" attribute of the <tr> containing the field.
	 * Note: you can add custom key value pairs to be used inside your callbacks.
	 *
	 * @param array $args include "label_for", "class", and custom arguments defined in add_settings_field.
	 */
	public function congress_field_captcha_server_cb( $args ): void {
		$options_name = Congress_Captcha::$server_key_options_name;
		$options      = get_option( $options_name );
		$field_name   = Congress_Captcha::$server_key_field_name;
		?>
		<input 
			id="<?php echo esc_attr( $args['label_for'] ); ?>"
			type="text"
			name=<?php echo esc_attr( $options_name . '[' . $args['label_for'] . ']' ); ?>
			value="<?php echo esc_attr( isset( $options[ $field_name ] ) ? $options[ $field_name ] : '' ); ?>"
		/>
		<p class="description">
			This plugin requires a Captcha to keep your website secure if you track metrics.
			Campaign Metrics will be disabled if this is not set. Set up your captcha
			<a href="https://www.google.com/recaptcha/admin/create">here</a>.
		</p>
		<?php
	}

	/**
	 * Captcha client key field callback function.
	 *
	 * WordPress has magic interaction with the following keys: label_for, class.
	 * - the "label_for" key value is used for the "for" attribute of the <label>.
	 * - the "class" key value is used for the "class" attribute of the <tr> containing the field.
	 * Note: you can add custom key value pairs to be used inside your callbacks.
	 *
	 * @param array $args include "label_for", "class", and custom arguments defined in add_settings_field.
	 */
	public function congress_field_captcha_client_cb( $args ): void {
		$options_name = Congress_Captcha::$client_key_options_name;
		$options      = get_option( $options_name );
		$field_name   = Congress_Captcha::$client_key_field_name;
		?>
		<input 
			id="<?php echo esc_attr( $args['label_for'] ); ?>"
			type="text"
			name=<?php echo esc_attr( $options_name . '[' . $args['label_for'] . ']' ); ?>
			value="<?php echo esc_attr( isset( $options[ $field_name ] ) ? $options[ $field_name ] : '' ); ?>"
		/>
		<p class="description">
			This plugin requires a Captcha to keep your website secure if you track metrics.
			Campaign Metrics will be disabled if this is not set. Set up your captcha
			<a href="https://www.google.com/recaptcha/admin/create">here</a>.
		</p>
		<?php
	}

	/**
	 * Congress.gov API Key callback function.
	 *
	 * WordPress has magic interaction with the following keys: label_for, class.
	 * - the "label_for" key value is used for the "for" attribute of the <label>.
	 * - the "class" key value is used for the "class" attribute of the <tr> containing the field.
	 * Note: you can add custom key value pairs to be used inside your callbacks.
	 *
	 * @param array $args include "label_for", "class", and custom arguments defined in add_settings_field.
	 */
	public function congress_field_congress_cb( $args ): void {
		$options_name = Congress_Congress_API::$options_name;
		$options      = get_option( $options_name );
		$field_name   = Congress_Congress_API::$field_name;
		?>
		<input 
			id="<?php echo esc_attr( $args['label_for'] ); ?>"
			type="text"
			name=<?php echo esc_attr( $options_name . '[' . $args['label_for'] . ']' ); ?>
			value="<?php echo esc_attr( isset( $options[ $field_name ] ) ? $options[ $field_name ] : '' ); ?>"
		/>
		<p class="description">
			Congress.gov API allows the plugin to look up federal representative information.
			Sign up for an API key <a href="https://gpo.congress.gov/sign-up/">here</a>.
		</p>
		<?php
	}

	/**
	 * Google API Key callback function.
	 *
	 * WordPress has magic interaction with the following keys: label_for, class.
	 * - the "label_for" key value is used for the "for" attribute of the <label>.
	 * - the "class" key value is used for the "class" attribute of the <tr> containing the field.
	 * Note: you can add custom key value pairs to be used inside your callbacks.
	 *
	 * @param array $args include "label_for", "class", and custom arguments defined in add_settings_field.
	 */
	public function congress_field_google_cb( $args ): void {
		$options_name = Congress_Google_Places_API::$options_name;
		$options      = get_option( $options_name );
		$field_name   = Congress_Google_Places_API::$field_name;
		?>
		<input 
			id="<?php echo esc_attr( $args['label_for'] ); ?>"
			type="text"
			name=<?php echo esc_attr( $options_name . '[' . $args['label_for'] . ']' ); ?>
			value="<?php echo esc_attr( isset( $options[ $field_name ] ) ? $options[ $field_name ] : '' ); ?>"
		/>
		<p class="description">
			Google Maps is a service that can autocomplete an address and determine the latitude and longitude.
			This enables the plugin to find a reader's representative.
			See <a href="https://mapsplatform.google.com/">Google</a> to get an API key.
		</p>
		<?php
	}

	/**
	 * Add the top level menu page.
	 */
	public function init_options_page(): void {
		if (
			current_user_can( 'congress_manage_representatives' ) ||
			current_user_can( 'congress_manage_campaigns' ) ||
			current_user_can( 'congress_manage_keys' )
		) {
			add_menu_page(
				'Congress',
				'Congress',
				'manage_options',
				self::$main_page_slug,
				array( $this, 'congress_options_page_html' )
			);
		}
		if ( current_user_can( 'congress_manage_campaigns' ) ) {
			add_submenu_page(
				self::$main_page_slug,
				'Campaigns',
				'Campaigns',
				'manage_options',
				self::$campaign_page_slug,
				array( $this, 'congress_campaign_page_html' )
			);
		}
		if ( current_user_can( 'congress_manage_representatives' ) ) {
			add_submenu_page(
				self::$main_page_slug,
				'Representatives',
				'Representatives',
				'manage_options',
				self::$rep_page_slug,
				array( $this, 'congress_rep_page_html' )
			);
		}
		if ( current_user_can( 'congress_manage_states' ) ) {
			add_submenu_page(
				self::$main_page_slug,
				'States',
				'States',
				'manage_options',
				self::$state_page_slug,
				array( $this, 'congress_state_page_html' )
			);
		}
	}

	/**
	 * Top level menu callback function
	 */
	public function congress_options_page_html(): void {
		// check user capabilities.
		if ( ! current_user_can( 'congress_manage_keys' ) ) {
			$form_url = admin_url( 'admin.php?page=' . self::$rep_page_slug );
			?>
			<h1>Contact Congress Admin Panel</h1>
			<h2>Settings</h2>
			<p>You do not have permissions to manage the settings for the Contact Congress Plugin. A website admin must give you one of the following roles:<p>
			<ul style="list-style: disc; padding-left: 1.5em;">
				<li>Administrator</li>
			</ul>
			<?php
			if ( current_user_can( 'congress_manage_representatives' ) ) {
				?>
				<h2>Representatives</h2>
				<p>You can manage representatives <a href="<?php echo esc_attr( $form_url ); ?>">here</a>!</p>
				<?php
			}
			?>
			<?php
			if ( current_user_can( 'congress_manage_campaigns' ) ) {
				?>
				<h2>Campaigns</h2>
				<p>You can manage campaigns <a href="<?php echo esc_attr( './admin.php?page=' . self::$campaign_page_slug ); ?>">here</a>!</p>
				<?php
			}
			return;
		}

		// add error/update messages.

		// check if the user have submitted the settings.
		// WordPress will add the "settings-updated" $_GET parameter to the url.
		if ( isset( $_GET['settings-updated'] ) ) { // phpcs:ignore
			// add settings saved message with the class of "updated".
			add_settings_error( 'congress_messages', 'congress_message', __( 'Settings Saved', 'congress' ), 'updated' );
		}

		// show error/update messages.
		settings_errors( 'congress_messages' );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				// output security fields for the registered setting "congress".
				settings_fields( 'congress' );
				// output setting sections and their fields.
				// (sections are registered for "wporg", each field is registered to a specific section).
				do_settings_sections( 'congress' );

				// output save settings button.
				submit_button( 'Save Settings' );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Representatives menu callback function.
	 */
	public function congress_rep_page_html(): void {
		// check user capabilities.
		if ( ! current_user_can( 'congress_manage_representatives' ) ) {
			?>
			<h1>Representatives</h1>
			<p>Whoops! You do not have permissions to manage representatives, a website admin must give you one of the following roles:<p>
			<ul style="list-style: disc; padding-left: 1.5em;">
				<li>Author</li>
				<li>Editor</li>
				<li>Administrator</li>
			</ul>
			<?php
			return;
		}

		?>
		<div class="wrap">
			<?php
				require_once plugin_dir_path( __FILE__ ) . 'partials/representatives/congress-admin-rep-display.php';
			?>
		</div>
		<?php
	}

	/**
	 * States menu callback function.
	 */
	public function congress_state_page_html(): void {
		// check user capabilities.
		if ( ! current_user_can( 'congress_manage_states' ) ) {
			?>
			<h1>State Settings</h1>
			<p>Whoops! You do not have permissions to manage stats, a website admin must give you one of the following roles:<p>
			<ul style="list-style: disc; padding-left: 1.5em;">
				<li>Administrator</li>
			</ul>
			<?php
			return;
		}

		?>
		<div class="wrap">
			<?php
				require_once plugin_dir_path( __FILE__ ) . 'partials/states/congress-admin-state-display.php';
			?>
		</div>
		<?php
	}

	/**
	 * Campaigns menu callback function.
	 */
	public function congress_campaign_page_html(): void {
		// check user capabilities.
		if ( ! current_user_can( 'congress_manage_campaigns' ) ) {
			?>
			<h1>Campaigns</h1>
			<p>Whoops! You do not have permissions to manage campaigns, a website admin must give you one of the following roles:<p>
			<ul style="list-style: disc; padding-left: 1.5em;">
				<li>Author</li>
				<li>Editor</li>
				<li>Administrator</li>
			</ul>
			<?php
			return;
		}

		?>
		<div class="wrap">
			<?php
				require_once plugin_dir_path( __FILE__ ) . 'partials/campaigns/congress-admin-campaign-display.php';
			?>
		</div>
		<?php
	}

	/**
	 * Alerts admins to any problems with the plugin.
	 */
	public function admin_notices(): void {
		$missing_extensions = array();

		$required_extensions = array( 'zip', 'xml', 'gd' );
		foreach ( $required_extensions as $ext ) {
			if ( ! extension_loaded( $ext ) ) {
				$missing_extensions[] = $ext;
			}
		}

		if ( ! empty( $missing_extensions ) ) {
			echo '<div class="notice notice-error"><p>';
			echo 'Warning: Your server is missing required PHP extensions: ' . esc_html( implode( ', ', $missing_extensions ) );
			echo '. Please install them to use this plugin.';
			echo '</p></div>';
		}
	}

	/**
	 * Defines custom roles and capabilities for the plugin.
	 */
	public function define_roles(): void {

		$editor_role = get_role( 'editor' );
		$editor_role->add_cap( 'congress_manage_campaigns' );
		$editor_role->add_cap( 'congress_manage_representatives' );

		$author_role = get_role( 'author' );
		$author_role->add_cap( 'congress_manage_campaigns' );
		$author_role->add_cap( 'congress_manage_representatives' );

		$admin_role = get_role( 'administrator' );
		$admin_role->add_cap( 'congress_manage_campaigns' );
		$admin_role->add_cap( 'congress_manage_representatives' );
		$admin_role->add_cap( 'congress_manage_keys' );
		$admin_role->add_cap( 'congress_manage_states' );
	}
}
