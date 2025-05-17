<?php
/**
 * An html template for active campaign fields in the admin page.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Congress
 * @subpackage Congress/admin/partials
 */

/**
 * A component for stacked inputs in forms.
 */
require_once plugin_dir_path( __FILE__ ) . '../class-congress-admin-stacked-input.php';

/**
 * A component for email templates.
 */
require_once plugin_dir_path( __FILE__ ) . 'class-congress-admin-email.php';

/**
 * Imports Table Manager for getting table names;
 */
require_once plugin_dir_path( __FILE__ ) .
	'../../../includes/class-congress-table-manager.php';

/**
 * Import enums.
 */
require_once plugin_dir_path( __DIR__ ) .
	'../../includes/enum-congress-state.php';
require_once plugin_dir_path( __DIR__ ) .
	'../../includes/enum-congress-level.php';

/**
 * Import Congress_State_Settings.
 */
require_once plugin_dir_path( __DIR__ ) .
	'states/class-congress-state-settings.php';

/**
 * Responsible for displaying active campaigns in the admin menu and fetching data from the DB.
 */
class Congress_Admin_Active_Campaign {

	/**
	 * The id of the campaign.
	 *
	 * @var int $id
	 */
	private int $id;

	/**
	 * The id of the campaign.
	 *
	 * When the id is -1, this will be 'campaign_id'.
	 *
	 * @var string $id
	 */
	private string $string_id;

	/**
	 * The name of the campaign.
	 *
	 * @var string $name
	 */
	private string $name;

	/**
	 * The level of the campaign.
	 *
	 * @var Congress_Level $level
	 */
	private Congress_Level $level;

	/**
	 * The state of the campaign.
	 *
	 * Null if it's a federal level campaign @see $level.
	 *
	 * @var ?Congress_State $state
	 */
	private ?Congress_State $state;

	/**
	 * The number of emails sent in the campaign.
	 *
	 * @var int $num_emails
	 */
	private int $num_emails;

	/**
	 * Constructs the Campaign object.
	 *
	 * @param int                           $id The id of the campaign (-1 means it doesn't exist in db).
	 * @param string                        $name The name of the campaign.
	 * @param Congress_Level|Congress_State $region The region of the campaign (Congress_Level::Federal or a state).
	 * @param int                           $num_emails The number of emails sent in the campaign.
	 *
	 * @throws Error If $region is not specified properly.
	 */
	public function __construct( int $id, string $name, Congress_Level|Congress_State $region, int $num_emails ) {
		$this->id         = $id;
		$this->name       = $name;
		$this->num_emails = $num_emails;

		if ( is_a( $region, 'Congress_Level' ) ) {
			if ( Congress_Level::State === $region ) {
				throw 'Bad Argument for $region.';
			}
			$this->state = null;
			$this->level = $region;
		} else {
			$this->state = $region;
			$this->level = Congress_Level::State;
		}

		if ( -1 === $this->id ) {
			$this->string_id = 'campaign_id';
		} else {
			$this->string_id = strval( $id );
		}
	}

	/**
	 * Displays the html for the campaign.
	 *
	 * @param bool $editing makes the campaign display in it's expanded editable form.
	 */
	public function display( bool $editing ): void {
		$region_value   = '';
		$region_display = '';
		if ( Congress_Level::Federal === $this->level ) {
			$region_display = $this->level->to_display_string();
			$region_value   = $this->level->to_db_string();
		} else {
			$region_display = $this->state->to_display_string();
			$region_value   = $this->level->to_db_string();
		}
		?>
<div class="congress-card">
	<div class="congress-card-header">
		<span><?php echo esc_html( "$this->name (" . $region_display . ')' ); ?></span>
		<form method="post" action="archive_campaign" class="congress-campaign-archive-form">
			<div class="congress-form-group">
				<button class="congress-campaign-archive button">Archive</button>
				<span class="congress-form-error"></span>
			</div>
			<input type="hidden" name="id" value="<?php echo esc_attr( $this->id ); ?>">
			<?php
			wp_nonce_field( "archive-campaign_$this->string_id" );
			?>
		</form>
		<button class="congress-campaign-toggle button button-primary">
			<?php
			if ( $editing ) {
				?>
				Less <span class="material-symbols-outlined">remove</span>
				<?php
			} else {
				?>
				More <span class="material-symbols-outlined">add</span>
				<?php
			}
			?>
		</button>
	</div>
	<div class="congress-card-body<?php echo esc_attr( $editing ? '' : ' congress-hidden' ); ?>">
		<div class="congress-campaign-pages-container">
			<ul class="congress-nav">
				<li>
					<a 
					href="#<?php echo esc_attr( "congress-campaign-$this->string_id-edit-page" ); ?>" 
					>Edit</a>
				</li>
				<li>
					<a 
					href="#<?php echo esc_attr( "congress-campaign-$this->string_id-templates-page" ); ?>" 
					>Email Templates</a>
				</li>
				<li>
					<a 
					href="#<?php echo esc_attr( "congress-campaign-$this->string_id-metrics-page" ); ?>" 
					>Metrics</a>
				</li>
				<li></li>
			</ul>
			<div 
				id="<?php echo esc_attr( "congress-campaign-$this->string_id-edit-page" ); ?>" 
				class="congress-campaign-page-container congress-hidden"
			>
				<form 
					class="congress-campaign-edit-form"
					action="update_campaign"
					method="post"
				>
					<h2>Edit Campaign</h2>
					<div class="congress-form-group">
						<label
							for="<?php echo esc_attr( "congress-campaign-$this->string_id-edit-name" ); ?>"
						>Name:</label>
						<input
							id="<?php echo esc_attr( "congress-campaign-$this->string_id-edit-name" ); ?>"
							type="text"
							name="name"
							value="<?php echo esc_attr( $this->name ); ?>"/>
					</div>
					<div class="congress-form-group">
						<label
							for="<?php echo esc_attr( "congress-campaign-$this->string_id-edit-region" ); ?>"
						>Region:</label>
						<select 
							id="<?php echo esc_attr( "congress-campaign-$this->string_id-edit-region" ); ?>"
							name="region"
						>
							<option 
							value="<?php echo esc_attr( Congress_Level::Federal->to_db_string() ); ?>"
								<?php echo esc_attr( Congress_Level::Federal === $this->level ? 'selected' : '' ); ?>
							>Federal</option>
							<?php
							$states = Congress_State_Settings::get_active_states();
							foreach ( $states as $state ) {
								?>
								<option 
									value="<?php echo esc_attr( strtoupper( $state->to_state_code() ) ); ?>"
									<?php echo esc_attr( $state === $this->state ? 'selected' : '' ); ?>
								><?php echo esc_html( $state->to_db_string() ); ?></option>
								<?php
							}
							?>
						</select>
					</div>
					<div class="congress-form-group">
						<div>
							<button type="submit" class="button button-primary">Update</button>
							<span class="congress-form-error"></span>
						</div>
					</div>
					<div class="congress-form-group">
					</div>
					<input type="hidden" name="id" value="<?php echo esc_attr( $this->id ); ?>"/>
					<?php
						wp_nonce_field( "update-campaign_$this->id" );
					?>
				</form>
			</div>
			<div
				id="<?php echo esc_attr( "congress-campaign-$this->string_id-metrics-page" ); ?>" 
				class="congress-campaign-page-container congress-hidden"
			>
				<div>
					<p>Graph Goes Here</p>
				</div>
			</div>
			<div 
				id="<?php echo esc_attr( "congress-campaign-$this->string_id-templates-page" ); ?>" 
				class="congress-campaign-page-container"
			>
				<div style="display: flex; justify-content: space-between;">
					<form 
						action="create_email_template"
						method="post"
						class="congress-campaign-email-create-form"
						style="display: flex; align-items: flex-start; flex-direction: column;"
					>
						<?php wp_nonce_field( 'create-email_campaign-id' ); ?>
						<input type="hidden" name="campaign_id" />
						<div>
							<input type="text" placeholder="Subject" aria-label="Subject" name="subject"/>
							<button class="button button-primary">Add Email Template</button>
						</div>
						<span class="congress-form-error"></span>
					</form>
					<form 
						action="upload_csv_email_templates"
						method="post"
						class="congress-campaign-email-upload-csv-form"
						style="display: flex; align-items: center; flex-direction: column;"
					>
						<?php wp_nonce_field( 'upload-csv-emails_campaign-id' ); ?>
						<input type="hidden" name="campaign_id" />
						<input type="file" name="csv" accept="text/csv" hidden/>
						<button class="button button-primary">Add Templates From CSV</button>
						<span class="congress-form-error"></span>
					</form>
					<form 
						action="delete_all_email_templates"
						method="post"
						class="congress-campaign-email-delete-all-form"
						style="display: flex; align-items: center; flex-direction: column;"
					>
						<?php wp_nonce_field( 'delete-all-emails_campaign-id' ); ?>
						<input type="hidden" name="campaign_id" />
						<button class="button congress-button-danger">Delete All Templates</button>
						<span class="congress-form-error"></span>
					</form>
				</div>
				<ul class="congress-campaign-email-list congress-empty"></ul>
			</div>
		</div>
	</div>
</div>
		<?php
	}

	/**
	 * Gets the campaign id.
	 *
	 * @return int
	 */
	public function get_id(): int {
		return $this->id;
	}

	/**
	 * Returns an HTML template to be used by JQuery when new campaigns are added.
	 */
	public static function get_html_template(): void {
		$template = new Congress_Admin_Active_Campaign( -1, '', Congress_Level::Federal, 0 );
		$template->display( false );
	}

	/**
	 * Retrieves the active campaigns from the DB.
	 *
	 * @return array<Congress_Admin_Campaign>
	 */
	public static function get_from_db(): array {
		global $wpdb;
		$campaign_t = Congress_Table_Manager::get_table_name( 'campaign' );
		$active_t   = Congress_Table_Manager::get_table_name( 'active_campaign' );
		$state_t    = Congress_Table_Manager::get_table_name( 'campaign_state' );
		// phpcs:disable
		$result     = $wpdb->get_results(
			"SELECT $active_t.id, name, state " .
			"FROM $active_t " .
			"LEFT JOIN $campaign_t ON $active_t.id = $campaign_t.id " .
			"LEFT JOIN $state_t ON $active_t.id = $state_t.campaign_id"
		);
		// phpcs:enable

		$campaigns = array();
		foreach ( $result as $campaign_result ) {
			$region = null;
			if ( null === $campaign_result->state ) {
				$region = Congress_Level::Federal;
			} else {
				$region = Congress_State::from_string( $campaign_result->state );
			}
			array_push(
				$campaigns,
				new Congress_Admin_Active_Campaign(
					$campaign_result->id,
					$campaign_result->name,
					$region,
					0,
				),
			);
		}
		return $campaigns;
	}
}
