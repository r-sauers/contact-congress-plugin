<?php
/**
 * An html template for representative fields in the admin page.
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
require_once plugin_dir_path( __FILE__ ) . 'class-congress-admin-stacked-input.php';

/**
 * A component for staffers.
 */
require_once plugin_dir_path( __FILE__ ) . 'class-congress-admin-staffer.php';

/**
 * Responsible for displaying representatives in the admin menu.
 */
class Congress_Admin_Rep {

	/**
	 * The id of the representative in the DB.
	 *
	 * @var string $rep_id
	 */
	private string $rep_id;

	/**
	 * The first name of the representative.
	 *
	 * @var string $first_name
	 */
	private string $first_name;

	/**
	 * The last name of the representative.
	 *
	 * @var string $last_name
	 */
	private string $last_name;

	/**
	 * The title of the representative (e.g. Senator).
	 *
	 * @var string $title
	 */
	private string $title;

	/**
	 * The district of the representative (e.g. 5).
	 *
	 * @var int district
	 */
	private int $district;

	/**
	 * The stae of the representative (e.g. 'MN').
	 *
	 * @var string state
	 */
	private string $state;

	/**
	 * The level of the representative ('state' or 'federal').
	 *
	 * @var string $level
	 */
	private string $level;

	/**
	 * Can the representative be edited or deleted?
	 *
	 * Main use case is for representatives retrieved via an API.
	 *
	 * @var bool $editable
	 */
	private bool $editable;

	/**
	 * Constructor
	 *
	 * @param string $rep_id is the database id.
	 * @param string $first_name is the rep's first name.
	 * @param string $last_name is the rep's last name.
	 * @param string $title is the rep's title (e.g. Senator).
	 * @param int    $district is the rep's district (e.g. 5).
	 * @param string $state is the rep's state (e.g. 'MN').
	 * @param string $level is the rep's level ('federal' or 'state').
	 * @param bool   $editable is whether or not the representative can be modified.
	 */
	public function __construct(
		string $rep_id,
		string $first_name,
		string $last_name,
		string $title,
		int $district,
		string $state,
		string $level,
		bool $editable = true
	) {
		$this->rep_id     = $rep_id;
		$this->first_name = $first_name;
		$this->last_name  = $last_name;
		$this->title      = $title;
		$this->district   = $district;
		$this->state      = $state;
		$this->level      = $level;
		$this->editable   = $editable;
	}

	/**
	 * Displays the representative.
	 *
	 * @param bool $editing toggles whether or not the representative is currently being edited.
	 */
	public function display( bool $editing = false ): void {
		?>
		<div
			id="<?php echo esc_attr( 'congress__rep-' . $this->rep_id ); ?>"
			class="congress__rep-container congress__closed <?php echo esc_attr( $editing ? 'congress__editable' : '' ); ?>"
		>
			<form class="congress__official-editable">
				<?php
					Congress_Admin_Stacked_Input::display(
						id: 'congress__rep-' . $this->rep_id . '-title',
						label: 'Title',
						name: 'title',
						value: $this->title,
						placeholder: 'Senator',
						size: '15',
					);
					Congress_Admin_Stacked_Input::display(
						id: 'congress__rep-' . $this->rep_id . '-first-name',
						label: 'First Name',
						name: 'first_name',
						value: $this->first_name,
						size: '15',
					);
					Congress_Admin_Stacked_Input::display(
						id: 'congress__rep-' . $this->rep_id . '-last_name',
						label: 'Last Name',
						name: 'last_name',
						value: $this->last_name,
						size: '15',
					);
					Congress_Admin_Stacked_Input::display(
						id: 'congress__rep-' . $this->rep_id . '-state',
						label: 'State',
						name: 'state',
						value: $this->state,
						input_type: 'state',
					);
					Congress_Admin_Stacked_Input::display(
						id: 'congress__rep-' . $this->rep_id . '-district',
						label: 'District',
						name: 'district',
						value: $this->district,
						input_type: 'number',
						size: '4em',
					);
					Congress_Admin_Stacked_Input::display(
						id: 'congress__rep-' . $this->rep_id . '-level',
						label: 'Level',
						name: 'level',
						value: $this->level,
						input_type: 'level',
					);

				?>
				<input name="repID" value="<?php echo esc_attr( $this->rep_id ); ?>" hidden />
				<div style="flex-shrink: 0;">
					<button type="submit" value="confirm" class="congress__confirm-button congress__icon-button"></button>
					<button type="submit" value="cancel" class="congress__cancel-button congress__icon-button"></button>
				</div>
			</form>
			<div class="congress__official-readonly">
				<span><?php echo esc_html( "$this->level $this->title $this->first_name $this->last_name ($this->state District $this->district)" ); ?></span>
				<button class="congress__staffer-toggle button">Staffers &gt;</button>
				<div>
					<button class="congress__edit-button congress__icon-button"></button>
					<button class="congress__delete-button congress__icon-button"></button>
				</div>
			</div>
			<div class="congress__staffer-container">
				<div class="congress__staffers-list">
				<?php
					$staffer = new Congress_Admin_Staffer(
						rep_id: '5',
						staffer_id: '6',
						first_name: 'Frank',
						last_name: 'jones',
						position: 'chief of staff',
						email: 'frank.jones@gov',
					);
					$staffer->display();
				?>
				</div>
				<button 
					id="<?php echo esc_attr( 'congress__rep-' . $this->rep_id . '-add-staffer' ); ?>" 
					class="button button-primary congress__add-staffer-button"
				>Add Staffer</button>
			</div>
		</div>
		<?php
	}

	/**
	 * Returns an HTML template to be used by JQuery when new representatives are added.
	 */
	public static function get_html_template(): void {
		$template = new Congress_Admin_Rep( '', '', '', '', 0, '', '', true );
		$template->display( true );
	}

	/**
	 * Gets a list of representatives from the DB.
	 *
	 * @param string $state is the state code e.g. 'MN'.
	 * @return array
	 */
	public static function get_reps_from_db( string $state = 'all' ): array {
		return array();
	}

	/**
	 * Gets a list of representatives from the API.
	 *
	 * @param string $api_key is an api key.
	 * @param string $state is the state code e.g. 'MN'.
	 * @return array
	 */
	public static function get_reps_from_api( string $api_key, string $state = 'all' ): array {
		return array();
	}
}
?>
