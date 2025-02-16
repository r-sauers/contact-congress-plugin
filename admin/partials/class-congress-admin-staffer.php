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
 * Responsible for displaying staffers in the admin menu.
 */
class Congress_Admin_Staffer {

	/**
	 * The id of the staffer in the DB.
	 *
	 * @var string $rep_id
	 */
	private string $staffer_id;

	/**
	 * The id of the staffer's representative in the DB.
	 *
	 * @var string $rep_id
	 */
	private string $rep_id;

	/**
	 * The first name of the staffer.
	 *
	 * @var string $first_name
	 */
	private string $first_name;

	/**
	 * The last name of the staffer.
	 *
	 * @var string $last_name
	 */
	private string $last_name;

	/**
	 * The position of the staffer (e.g. Chief of Staff).
	 *
	 * @var string $position
	 */
	private string $position;

	/**
	 * The email of the staffer.
	 *
	 * @var string $email
	 */
	private string $email;

	/**
	 * Constructor
	 *
	 * @param string $rep_id is the staffer's rep's database id.
	 * @param string $staffer_id is the staffer's database id.
	 * @param string $first_name is the staffer's first name.
	 * @param string $last_name is the staffer's last name.
	 * @param string $email is the rep's email.
	 * @param string $position is the staffer's position (e.g. Senator).
	 */
	public function __construct(
		string $rep_id,
		string $staffer_id,
		string $first_name,
		string $last_name,
		string $email,
		string $position
	) {
		$this->rep_id     = $rep_id;
		$this->staffer_id = $staffer_id;
		$this->first_name = $first_name;
		$this->last_name  = $last_name;
		$this->email      = $email;
		$this->position   = $position;
	}

	/**
	 * Displays the representative.
	 *
	 * @param bool $editing toggles whether or not the representative is currently being edited.
	 */
	public function display( bool $editing = false ): void {
		?>
		<div 
			id="<?php echo esc_attr( 'congress__staffer-' . $this->rep_id . '-' . $this->staffer_id ); ?>"
			class="congress__staffer-container congress__closed <?php echo esc_attr( $editing ? 'congress__editable' : '' ) ?>">
			<form class="congress__official-editable">
				<?php
					Congress_Admin_Stacked_Input::display(
						id: 'congress__staffer-' . $this->rep_id . '-' . $this->staffer_id . '-first-name',
						label: 'First Name',
						name: 'first_name',
						value: $this->first_name,
						size: '15',
					);
					Congress_Admin_Stacked_Input::display(
						id: 'congress__staffer-' . $this->rep_id . '-' . $this->staffer_id . '-last-name',
						label: 'Last Name',
						name: 'last_name',
						value: $this->last_name,
						size: '15',
					);
					Congress_Admin_Stacked_Input::display(
						id: 'congress__staffer-' . $this->rep_id . '-' . $this->staffer_id . '-position',
						label: 'Position',
						name: 'position',
						value: $this->position,
						placeholder: 'Chief of Staff',
						size: '20',
					);
					Congress_Admin_Stacked_Input::display(
						id: 'congress__staffer-' . $this->rep_id . '-' . $this->staffer_id . '-email',
						label: 'Email',
						name: 'email',
						value: $this->email,
						size: '20',
					);

				?>
				<div style="flex-shrink: 0;">
					<button value="confirm" class="congress__confirm-button congress__icon-button"></button>
					<button value="cancel" class="congress__cancel-button congress__icon-button"></button>
				</div>
			</form>
			<div class="congress__official-readonly">
				<span><?php echo esc_html( "$this->position $this->first_name $this->last_name" ); ?></span>
				<div style="float: right;">
					<button class="congress__edit-button congress__icon-button"></button>
					<button class="congress__delete-button congress__icon-button"></button>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Returns an HTML template to be used by JQuery when new representatives are added.
	 */
	public static function get_html_template(): void {
		$template = new Congress_Admin_Staffer( '', '', '', '', '', '' );
		$template->display( true );
	}

	public static function get_staffers( $rep_id ): array {

	}

}

?>
