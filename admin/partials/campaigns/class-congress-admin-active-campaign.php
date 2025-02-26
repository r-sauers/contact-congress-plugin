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
	 * The level of the campaign ('federal' or 'state')
	 *
	 * @var string $level
	 */
	private string $level;

	/**
	 * The number of emails sent in the campaign.
	 *
	 * @var int $num_emails
	 */
	private int $num_emails;

	/**
	 * Constructs the Campaign object.
	 *
	 * @param int    $id The id of the campaign (-1 means it doesn't exist in db).
	 * @param string $name The name of the campaign.
	 * @param string $level The level of the campaign ('federal' or 'state').
	 * @param int    $num_emails The number of emails sent in the campaign.
	 */
	public function __construct( int $id, string $name, string $level, int $num_emails ) {
		$this->id         = $id;
		$this->name       = $name;
		$this->level      = $level;
		$this->num_emails = $num_emails;

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
		?>
<div class="congress-card">
	<div class="congress-card-header">
		<span><?php echo esc_html( "$this->name (" . ucwords( $this->level ) . ')' ); ?></span>
		<button class="congress-campaign-archive button">Archive</button>
		<button class="congress-campaign-toggle button button-primary"><?php echo esc_html( $editing ? 'Less ^' : 'More >' ); ?></button>
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
							for="<?php echo esc_attr( "congress-campaign-$this->string_id-edit-level" ); ?>"
						>Level:</label>
						<select 
							id="<?php echo esc_attr( "congress-campaign-$this->string_id-edit-level" ); ?>"
							name="level"
						>
							<option 
								value="federal"
								<?php echo esc_attr( 'federal' === $this->level ? 'selected' : '' ); ?>
							>Federal</option>
							<option 
								value="state"
								<?php echo esc_attr( 'state' === $this->level ? 'selected' : '' ); ?>
							>State</option>
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
						wp_nonce_field( "update-campaign-$this->id" );
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
					<div>
						<input type="text" placeholder="Subject" aria-label="Subject" name="subject"/>
						<button class="button button-primary">Add Email Template</button>
					</div>
					<div>
						<button class="button button-primary">Add Templates From CSV</button>
						<span></span>
					</div>
					<button class="button congress-button-danger">Delete All Templates</button>
				</div>
				<ul>
					<li>
					<?php
					$email = new Congress_Admin_Email( 0, 0, 'EATS act', false, "Dear [[TITLE]] [[LAST]],\n" );
					$email->display();
					?>
					</li>
					<li>
					<?php
					$email = new Congress_Admin_Email( 0, 0, 'EATS act', false, "Dear [[TITLE]] [[LAST]],\n" );
					$email->display();
					?>
					</li>
				</ul>
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
	 * Returns an HTML template to be used by JQuery when new representatives are added.
	 */
	public static function get_html_template(): void {
		$template = new Congress_Admin_Active_Campaign( -1, '', '', 0 );
		$template->display( false );
	}

	/**
	 * Retrieves the active campaigns from the DB.
	 *
	 * @return array<Congress_Admin_Campaign>
	 */
	public static function get_from_db(): array {
		global $wpdb;
		$tablename = Congress_Table_Manager::get_table_name( 'campaign' );
		$result    = $wpdb->get_results( "SELECT * FROM $tablename" ); // phpcs:ignore

		$campaigns = array();
		foreach ( $result as $campaign_result ) {
			array_push(
				$campaigns,
				new Congress_Admin_Active_Campaign(
					$campaign_result->id,
					$campaign_result->name,
					$campaign_result->level,
					0,
				),
			);
		}
		return $campaigns;
	}
}
