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
	 * The title of the staffer (e.g. Chief of Staff).
	 *
	 * @var string $title
	 */
	private string $title;

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
	 * @param string $title is the staffer's title (e.g. Chief of Staff).
	 */
	public function __construct(
		string $rep_id,
		string $staffer_id,
		string $first_name,
		string $last_name,
		string $email,
		string $title
	) {
		$this->rep_id     = $rep_id;
		$this->staffer_id = $staffer_id;
		$this->first_name = $first_name;
		$this->last_name  = $last_name;
		$this->email      = $email;
		$this->title      = $title;
	}

	/**
	 * Displays the representative.
	 *
	 * @param bool $editing toggles whether or not the representative is currently being edited.
	 */
	public function display( bool $editing = false ): void {
		?>
		<div 
			id="<?php echo esc_attr( 'congress-staffer-' . $this->rep_id . '-' . $this->staffer_id ); ?>"
			class="congress-staffer-container congress-closed <?php echo esc_attr( $editing ? 'congress-editable' : '' ); ?>">
			<form class="congress-official-editable congress-official-edit-form congress-staffer-edit-form">
				<?php
				Congress_Admin_Stacked_Input::display(
					id: 'congress-staffer-' . $this->rep_id . '-' . $this->staffer_id . '-first-name',
					label: 'First Name',
					name: 'first_name',
					value: $this->first_name,
					size: '15',
				);
				Congress_Admin_Stacked_Input::display(
					id: 'congress-staffer-' . $this->rep_id . '-' . $this->staffer_id . '-last-name',
					label: 'Last Name',
					name: 'last_name',
					value: $this->last_name,
					size: '15',
				);
				Congress_Admin_Stacked_Input::display(
					id: 'congress-staffer-' . $this->rep_id . '-' . $this->staffer_id . '-title',
					label: 'Position',
					name: 'title',
					value: $this->title,
					placeholder: 'Chief of Staff',
					size: '20',
				);
				Congress_Admin_Stacked_Input::display(
					id: 'congress-staffer-' . $this->rep_id . '-' . $this->staffer_id . '-email',
					label: 'Email',
					name: 'email',
					value: $this->email,
					size: '20',
				);

				if ( '' === $this->staffer_id ) {
					wp_nonce_field( 'create-staffer_' . $this->rep_id );
				} else {
					wp_nonce_field( 'edit-staffer_' . $this->rep_id . '-' . $this->staffer_id );
				}

				?>
				<input type="hidden" name="rep_id" value="<?php echo esc_attr( $this->rep_id ); ?>"/>
				<input type="hidden" name="staffer_id" value="<?php echo esc_attr( $this->staffer_id ); ?>"/>
				<div style="flex-shrink: 0;">
					<button value="confirm" class="congress-confirm-button congress-icon-button"></button>
					<button value="cancel" class="congress-cancel-button congress-icon-button"></button>
				</div>
			</form>
			<div class="congress-official-readonly">
				<span><?php echo esc_html( "$this->title $this->first_name $this->last_name ($this->email)" ); ?></span>
				<div style="float: right;">
					<button class="congress-edit-button congress-icon-button"></button>
					<form class="congress-official-delete-form congress-staffer-delete-form">
						<button class="congress-delete-button congress-icon-button"></button>
						<?php
						wp_nonce_field( 'delete-staffer_' . $this->rep_id . '-' . $this->staffer_id );
						?>
					</form>
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

	/**
	 * Gets a list of staffers from the database.
	 *
	 * @param int $rep_id is the id of the staffer's representative.
	 * @return array<Congress_Admin_Staffer>
	 */
	public static function get_staffers( int $rep_id ): array {
		global $wpdb;
		$tablename = Congress_Table_Manager::get_table_name( 'staffer' );
		$result    = $wpdb->get_results( // phpcs:ignore
			$wpdb->prepare(
				"SELECT * FROM $tablename WHERE representative=%d", // phpcs:ignore
				array(
					$rep_id,
				)
			)
		);

		$staffers = array();
		foreach ( $result as $staffer_result ) {
			array_push(
				$staffers,
				new Congress_Admin_Staffer(
					$staffer_result->representative,
					$staffer_result->id,
					$staffer_result->first_name,
					$staffer_result->last_name,
					$staffer_result->email,
					$staffer_result->title,
				)
			);
		}
		return $staffers;
	}
}

?>
