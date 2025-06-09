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
require_once plugin_dir_path( __FILE__ ) . '../class-congress-admin-stacked-input.php';

/**
 * A component for staffers.
 */
require_once plugin_dir_path( __FILE__ ) . 'class-congress-admin-staffer.php';

/**
 * Imports Table Manager for getting table names;
 */
require_once plugin_dir_path( __FILE__ ) .
	'../../../includes/class-congress-table-manager.php';

/**
 * Imports enums.
 */
require_once plugin_dir_path( __DIR__ ) .
	'../../includes/enum-congress-state.php';
require_once plugin_dir_path( __DIR__ ) .
	'../../includes/enum-congress-level.php';
require_once plugin_dir_path( __DIR__ ) .
	'../../includes/enum-congress-title.php';

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
	 * @var ?string district
	 */
	private ?string $district;

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
	 * @param string  $rep_id is the database id.
	 * @param string  $first_name is the rep's first name.
	 * @param string  $last_name is the rep's last name.
	 * @param string  $title is the rep's title (e.g. Senator).
	 * @param ?string $district is the rep's district (e.g. 5).
	 * @param string  $state is the rep's state (e.g. 'MN').
	 * @param string  $level is the rep's level ('federal' or 'state').
	 * @param bool    $editable is whether or not the representative can be modified.
	 */
	public function __construct(
		string $rep_id,
		string $first_name,
		string $last_name,
		string $title,
		?string $district,
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

		$staffers      = array();
		$staffer_count = 0;
		if ( '' !== $this->rep_id ) {
			$staffers      = Congress_Admin_Staffer::get_staffers( $this->rep_id );
			$staffer_count = count( $staffers );
		}

		?>
		<div
			id="<?php echo esc_attr( 'congress-rep-' . $this->rep_id ); ?>"
			class="congress-rep-container congress-closed <?php echo esc_attr( $editing ? 'congress-editable' : '' ); ?>"
		>
			<form class="congress-official-editable congress-rep-edit-form congress-official-edit-form">
				<?php
				Congress_Admin_Stacked_Input::display_text(
					id: 'congress-rep-' . $this->rep_id . '-title',
					label: 'Title',
					name: 'title',
					value: $this->title,
					placeholder: 'Senator',
					size: '15',
				);
				Congress_Admin_Stacked_Input::display_text(
					id: 'congress-rep-' . $this->rep_id . '-first-name',
					label: 'First Name',
					name: 'first_name',
					value: $this->first_name,
					size: '15',
				);
				Congress_Admin_Stacked_Input::display_text(
					id: 'congress-rep-' . $this->rep_id . '-last_name',
					label: 'Last Name',
					name: 'last_name',
					value: $this->last_name,
					size: '15',
				);
				Congress_Admin_Stacked_Input::display_state_dropdown(
					id: 'congress-rep-' . $this->rep_id . '-state',
					label: 'State',
					name: 'state',
					value: $this->state,
				);
				$district_input = $this->district;
				if ( null === $this->district ) {
					$district_input = '';
				}
				Congress_Admin_Stacked_Input::display_text(
					id: 'congress-rep-' . $this->rep_id . '-district',
					label: 'District',
					name: 'district',
					value: $district_input,
					size: '6',
				);
				Congress_Admin_Stacked_Input::display_dropdown(
					id: 'congress-rep-' . $this->rep_id . '-level',
					label: 'Level',
					name: 'level',
					value: $this->level,
					options: array(
						array(
							'value' => 'federal',
							'label' => 'Federal',
						),
						array(
							'value' => 'state',
							'label' => 'State',
						),
					),
				);

				if ( '' === $this->rep_id ) {
					wp_nonce_field( 'create-rep' );
				} else {
					wp_nonce_field( 'edit-rep_' . $this->rep_id );
				}

				$district_text = '';
				if ( null !== $this->district ) {
					$district_text = " District $this->district";
				}

				$level_text = ucwords( $this->level );

				?>
				<input type="hidden" name="rep_id" value="<?php echo esc_attr( $this->rep_id ); ?>"/>
				<div style="flex-shrink: 0;">
					<button
						type="submit"
						value="confirm"
						class="congress-icon-button congress-inline-dashicon dashicons-yes congress-success"
					></button>
					<button
						type="submit"
						value="cancel"
						class="congress-icon-button congress-inline-dashicon dashicons-no congress-danger"
					></button>
				</div>
			</form>
			<div class="congress-official-readonly">
				<span style="width: 50%;"><?php echo esc_html( "$level_text $this->title $this->first_name $this->last_name ($this->state$district_text)" ); ?></span>
				<button class="congress-staffer-toggle button">
					Emails 
					(<span class="congress-staffer-count"><?php echo esc_html( $staffer_count ); ?></span>)
					<span class="congress-inline-dashicon dashicons-plus-alt2"></span>
				</button>
				<div>
					<button class="congress-icon-button congress-inline-dashicon dashicons-edit"></button>
					<form class="congress-official-delete-form congress-rep-delete-form">
						<button class="congress-icon-button congress-inline-dashicon dashicons-trash congress-danger"></button>
						<?php
						wp_nonce_field( 'delete-rep_' . $this->rep_id );
						?>
					</form>
				</div>
			</div>
			<div class="congress-staffer-container">
				<div class="congress-staffers-list">
				<?php
				foreach ( $staffers as $staffer ) {
					$staffer->display();
				}
				?>
				</div>
				<button 
					id="<?php echo esc_attr( 'congress-rep-' . $this->rep_id . '-add-staffer' ); ?>" 
					class="button button-primary congress-add-staffer-button"
					<?php
					if ( '' !== $this->rep_id ) {
						$nonce = wp_create_nonce( 'create-staffer_' . $this->rep_id );
						?>
						createNonce="<?php echo esc_attr( $nonce ); ?>"
						<?php
					}
					?>
				>Add Staffer Email</button>
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
	 * @param ?Congress_Level $level filters reps by level.
	 * @param ?Congress_State $state filters reps by state.
	 * @param ?Congress_Title $title filters reps by title.
	 *
	 * @return array<Congress_Admin_Rep>
	 */
	public static function get_reps_from_db(
		?Congress_Level $level = null,
		?Congress_State $state = null,
		?Congress_Title $title = null
	): array {
		global $wpdb;
		$tablename = Congress_Table_Manager::get_table_name( 'representative' );

		$query      = "SELECT * FROM $tablename";
		$query_args = array();
		$first_arg  = true;

		if ( null !== $state ) {
			if ( $first_arg ) {
				$query    .= ' WHERE';
				$first_arg = false;
			} else {
				$query .= ' AND';
			}
			$query .= ' state=%s';
			array_push( $query_args, $state->to_db_string() );
		}

		if ( null !== $level ) {
			if ( $first_arg ) {
				$query    .= ' WHERE';
				$first_arg = false;
			} else {
				$query .= ' AND';
			}
			$query .= ' level=%s';
			array_push( $query_args, $level->to_db_string() );
		}

		if ( null !== $title ) {
			if ( $first_arg ) {
				$query    .= ' WHERE';
				$first_arg = false;
			} else {
				$query .= ' AND';
			}
			$query .= ' title=%s';
			array_push( $query_args, $title->to_db_string() );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->get_results(
			$wpdb->prepare( $query, $query_args )
		);

		if ( null === $result ) {
			wp_die();
		}

		$reps = array();
		foreach ( $result as $rep_result ) {
			array_push(
				$reps,
				new Congress_Admin_Rep(
					$rep_result->id,
					$rep_result->first_name,
					$rep_result->last_name,
					$rep_result->title,
					$rep_result->district,
					$rep_result->state,
					$rep_result->level,
				)
			);
		}
		return $reps;
	}

	/**
	 * Gets a list of representatives from the API.
	 *
	 * @param string $api_key is an api key.
	 * @param string $state is the state code e.g. 'MN'.
	 * @return array<Congress_Admin_Rep>
	 */
	public static function get_reps_from_api( string $api_key, string $state = 'all' ): array {
		return array( $api_key, $state );
	}
}
?>
