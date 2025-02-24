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
	 * The name of the campaign.
	 *
	 * @var string $name
	 */
	private string $name;

	/**
	 * The level of the campaign ('Federal' or 'State')
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
	 * @param int    $id The id of the campaign.
	 * @param string $name The name of the campaign.
	 * @param string $level The level of the campaign ('Federal' or 'State').
	 * @param int    $num_emails The number of emails sent in the campaign.
	 */
	public function __construct( int $id, string $name, string $level, int $num_emails ) {
		$this->id         = $id;
		$this->name       = $name;
		$this->level      = $level;
		$this->num_emails = $num_emails;
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
		<span><?php echo esc_html( "$this->name ($this->level)" ); ?></span>
		<button class="congress-campaign-archive button">Archive</button>
		<button class="congress-campaign-toggle button button-primary"><?php echo esc_html( $editing ? 'Less ^' : 'More >' ); ?></button>
	</div>
	<div class="congress-card-body">
		<div class="congress-campaign-pages-container">

			<!-- Links must be in the same order as pages. -->
			<ul class="congress-nav">
				<li><a>Edit</a></li>
				<li><a>Metrics</a></li>
				<li><a class="congress-active">Email Templates</a></li>
				<li></li>
			</ul>
			<div class="congress-campaign-page-container congress-hidden">
				<form id="">
					<h2>Edit Campaign</h2>
					<div class="congress-form-group">
						<label>Name:</label>
						<input type="text"/>
					</div>
					<div class="congress-form-group">
						<label>Level:</label>
						<select>
							<option>Federal</option>
							<option>State</option>
						</select>
					</div>
					<div class="congress-form-group">
						<button type="submit" class="button button-primary">Update</button>
					</div>
				</form>
			</div>
			<div class="congress-campaign-page-container congress-hidden">
				<div>
					<p>Graph Goes Here</p>
				</div>
			</div>
			<div class="congress-campaign-page-container">
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
		$template = new Congress_Admin_Active_Campaign( '', '', '', 0 );
		$template->display( true );
	}

	/**
	 * Retrieves the active campaigns from the DB.
	 *
	 * @return array<Congress_Admin_Campaign>
	 */
	public static function get_from_db(): array {
		return array();
	}
}
