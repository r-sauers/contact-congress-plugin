<?php
/**
 * An html template for archived campaign fields in the admin page.
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
 * Responsible for displaying archived campaigns in the admin menu and fetching data from the DB.
 */
class Congress_Admin_Archived_Campaign {

	/**
	 * The id of the campaign.
	 *
	 * @var int $id
	 */
	private int $id;

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
	 * The date the campaign was created.
	 *
	 * @var int $created_date
	 */
	private int $created_date;

	/**
	 * The date the campaign was archived
	 *
	 * @var int $archived_date
	 */
	private int $archived_date;

	/**
	 * Constructs the Campaign object.
	 *
	 * @param int                           $id The id of the campaign.
	 * @param string                        $name The name of the campaign.
	 * @param Congress_Level|Congress_State $region The region of the campaign (Congress_Level::Federal or a state).
	 * @param int                           $num_emails The number of emails sent in the campaign.
	 * @param int                           $created_date The date the campaign was created.
	 * @param int                           $archived_date The date the campaign was archived.
	 *
	 * @throws Error If $region is not specified properly.
	 */
	public function __construct( int $id, string $name, Congress_Level|Congress_State $region, int $num_emails, int $created_date, int $archived_date ) {
		$this->id            = $id;
		$this->name          = $name;
		$this->num_emails    = $num_emails;
		$this->created_date  = $created_date;
		$this->archived_date = $archived_date;

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
	}

	/**
	 * Displays the html for the campaign.
	 */
	public function display(): void {
		$created_string  = gmdate( 'm/d/Y', $this->created_date );
		$archived_string = gmdate( 'm/d/Y', $this->created_date );

		$region_display = '';
		if ( Congress_Level::Federal === $this->level ) {
			$region_display = $this->level->to_display_string();
		} else {
			$region_display = $this->state->to_display_string();
		}
		?>
		<div class="congress-card">
			<div class="congress-card-header">
				<span style="width: 30%; display: inline-block;">
					<?php echo esc_html( "$this->name ($region_display)" ); ?></span>
				<span style="width: 15%; display: inline-block;">
					<?php echo esc_html( "$this->num_emails emails sent!" ); ?></span>
				<span style="width: 30%; display: inline-block;">
					<?php echo esc_html( "$created_string - $archived_string" ); ?></span>
				<form method="post" action="delete_archived_campaign" class="congress-campaign-delete-form">
					<input type="hidden" name="id" value="<?php echo esc_attr( $this->id ); ?>">
					<?php wp_nonce_field( "delete-archived-campaign_$this->id" ); ?>
					<div class="congress-form-group">
						<button type="submit" class="button congress-button-danger">Delete</button>
						<span class="congress-form-error"></span>
					</div>
				</form>
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
		$template = new Congress_Admin_Archived_Campaign( -1, '', Congress_Level::Federal, 0, 0, 0 );
		$template->display( false );
	}

	/**
	 * Retrieves the archived campaigns from the DB.
	 *
	 * @return array<Congress_Admin_Archived_Campaign>
	 */
	public static function get_from_db(): array {

		global $wpdb;
		$campaign_t = Congress_Table_Manager::get_table_name( 'campaign' );
		$archived_t = Congress_Table_Manager::get_table_name( 'archived_campaign' );
		$state_t    = Congress_Table_Manager::get_table_name( 'campaign_state' );
		// phpcs:disable;
		$result     = $wpdb->get_results(  // phpcs:ignore
			"SELECT $archived_t.id, email_count, name, state, UNIX_TIMESTAMP(created_date) AS created_date, UNIX_TIMESTAMP(archived_date) AS archived_date " .
			"FROM $archived_t " . 
			"LEFT JOIN $campaign_t ON $archived_t.id = $campaign_t.id " .
			"LEFT JOIN $state_t ON $archived_t.id = $state_t.campaign_id"
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
				new Congress_Admin_Archived_Campaign(
					$campaign_result->id,
					$campaign_result->name,
					$region,
					$campaign_result->email_count,
					$campaign_result->created_date,
					$campaign_result->archived_date,
				),
			);
		}
		return $campaigns;
	}
}
