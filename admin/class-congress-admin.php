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

		if ( isset( $_GET['page'] ) && 'congress_rep' === $_GET['page'] ) { // phpcs:ignore
			wp_enqueue_script(
				$this->congress,
				plugin_dir_url( __FILE__ ) . 'js/congress-admin-rep.js',
				array( 'jquery' ),
				$this->version,
				false
			);
		} elseif ( isset( $_GET['page'] ) && 'congress_campaign' === $_GET['page'] ) { // phpcs:ignore
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
			'congress_field_google',
			__( 'Google API Key', 'congress' ),
			array( $this, 'congress_field_google_cb' ),
			'congress',
			'congress_section_api_keys',
			array(
				'label_for' => 'congress_field_google',
			)
		);
		add_settings_field(
			'congress_field_democracy',
			__( 'Democracy.io API Key', 'congress' ),
			array( $this, 'congress_field_democracy_cb' ),
			'congress',
			'congress_section_api_keys',
			array(
				'label_for' => 'congress_field_democracy',
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
	 * Democracy.io API Key callback function.
	 *
	 * WordPress has magic interaction with the following keys: label_for, class.
	 * - the "label_for" key value is used for the "for" attribute of the <label>.
	 * - the "class" key value is used for the "class" attribute of the <tr> containing the field.
	 * Note: you can add custom key value pairs to be used inside your callbacks.
	 *
	 * @param array $args include "label_for", "class", and custom arguments defined in add_settings_field.
	 */
	public function congress_field_democracy_cb( $args ): void {
		$options = get_option( 'congress_options' );
		?>
		<input 
			id="<?php echo esc_attr( $args['label_for'] ); ?>"
			type="text"
			name="congress_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
			value="<?php echo esc_attr( isset( $options['congress_field_democracy'] ) ? $options['congress_field_democracy'] : '' ); ?>"
		/>
		<p class="description">
			Democracy.io is a service that sends emails to congress members.
			This plugin cannot send emails to federal representatives without it.
			Contact <a href="">Democracy.io</a> for an API key.
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
		$options = get_option( 'congress_options' );
		?>
		<input 
			id="<?php echo esc_attr( $args['label_for'] ); ?>"
			type="text"
			name="congress_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
			value="<?php echo esc_attr( isset( $options['congress_field_google'] ) ? $options['congress_field_google'] : '' ); ?>"
		/>
		<p class="description">
			Google Civic API is a service that identifies representatives using an address
			This plugin cannot identify someone's representative without it.
			See <a href="">Google</a> to get an API key.
		</p>
		<?php
	}

	/**
	 * Add the top level menu page.
	 */
	public function init_options_page(): void {
		add_menu_page(
			'Congress',
			'Congress',
			'manage_options',
			'congress',
			array( $this, 'congress_options_page_html' )
		);
		add_submenu_page(
			'congress',
			'Campaigns',
			'Campaigns',
			'manage_options',
			'congress_campaign',
			array( $this, 'congress_campaign_page_html' )
		);
		add_submenu_page(
			'congress',
			'Representatives',
			'Representatives',
			'manage_options',
			'congress_rep',
			array( $this, 'congress_rep_page_html' )
		);
	}

	/**
	 * Top level menu callback function
	 */
	public function congress_options_page_html(): void {
		// check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// add error/update messages.

		// check if the user have submitted the settings.
		// WordPress will add the "settings-updated" $_GET parameter to the url.
		if ( isset( $_GET['settings-updated'] ) && check_admin_referer( 'update-api-keys' ) ) {
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

				// nonce for security.
				wp_nonce_field( 'update-api-keys' );
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
		if ( ! current_user_can( 'manage_options' ) ) {
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
	 * Campaigns menu callback function.
	 */
	public function congress_campaign_page_html(): void {
		// check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
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
}
