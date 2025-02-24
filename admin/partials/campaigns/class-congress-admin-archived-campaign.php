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
	 * @param int    $id The id of the campaign.
	 * @param string $name The name of the campaign.
	 * @param string $level The level of the campaign ('Federal' or 'State').
	 * @param int    $num_emails The number of emails sent in the campaign.
	 * @param int    $created_date The date the campaign was created.
	 * @param int    $archived_date The date the campaign was archived.
	 */
	public function __construct( int $id, string $name, string $level, int $num_emails, int $created_date, int $archived_date ) {
		$this->id            = $id;
		$this->name          = $name;
		$this->level         = $level;
		$this->num_emails    = $num_emails;
		$this->created_date  = $created_date;
		$this->archived_date = $archived_date;
	}

	/**
	 * Displays the html for the campaign.
	 */
	public function display(): void {
		$created_string  = gmdate( 'm/d/Y', $this->created_date - ( 3600 * 24 * 120 ) );
		$archived_string = gmdate( 'm/d/Y', $this->created_date );
		?>
		<div class="congress-campaign-readonly">
			<span><?php echo esc_html( "$this->name ($this->level)" ); ?></span>
			<span><?php echo esc_html( "$this->num_emails emails sent!" ); ?></span>
			<span><?php echo esc_html( "$created_string - $archived_string" ); ?></span>
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
	 * Retrieves the archived campaigns from the DB.
	 *
	 * @return array<Congress_Admin_Archived_Campaign>
	 */
	public static function get_from_db(): array {
		return array();
	}
}
