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
 * Imports Table Manager for getting table names;
 */
require_once plugin_dir_path( __FILE__ ) .
	'../../../includes/class-congress-table-manager.php';

/**
 * Responsible for displaying active campaigns in the admin menu and fetching data from the DB.
 */
class Congress_Admin_Email {

	/**
	 * The id of the email.
	 *
	 * If this is -1, that means it isn't in the DB.
	 *
	 * @var int $id
	 */
	private int $id;

	/**
	 * The string id of the email.
	 *
	 * If id is -1, this will be 'email-id'
	 *
	 * @var string $id
	 */
	private string $str_id;

	/**
	 * The id of the email's campaign.
	 *
	 * @var int $campaign_id
	 */
	private int $campaign_id;

	/**
	 * The string id of the email's campaign.
	 *
	 * If @see id is -1, this will be 'campaign-id'
	 *
	 * @var string $id
	 */
	private string $str_campaign_id;


	/**
	 * The subject of the email.
	 *
	 * @var string $subject
	 */
	private string $subject;

	/**
	 * If this is set to true, that means the email is for representatives
	 * that are in favor of the campaign. (e.g. a thank you email).
	 *
	 * @var bool $favorable
	 */
	private bool $favorable;

	/**
	 * The email body template.
	 *
	 * @var string $template
	 */
	private string $template;

	/**
	 * Constructs the Campaign object.
	 *
	 * @param int    $id The id of the email.
	 * @param int    $campaign_id The id of the email's campaign.
	 * @param string $subject The subject of the email.
	 * @param bool   $favorable If this is set to true, that means the email is for representatives
	 * that are in favor of the campaign. (e.g. a thank you email).
	 * @param string $template The email body template.
	 */
	public function __construct( int $id, int $campaign_id, string $subject, bool $favorable, string $template ) {
		$this->id          = $id;
		$this->campaign_id = $campaign_id;
		$this->subject     = $subject;
		$this->favorable   = $favorable;
		$this->template    = $template;

		if ( -1 === $this->id ) {
			$this->str_id          = 'email-id';
			$this->str_campaign_id = 'campaign-id';
		} else {
			$this->str_id          = strval( $this->id );
			$this->str_campaign_id = strval( $this->campaign_id );
		}
	}

	/**
	 * Displays the html for the campaign.
	 */
	public function display(): void {
		?>
		<form
			class="congress-campaign-email-container congress-campaign-email-edit-form"
			method="post"
			action="update_email_template"
		>
			<h3><?php echo esc_html( "Template $this->str_id " ); ?></h3>
			<div class="congress-flex-row">
				<?php
				$email_for = $this->favorable ? 'favored' : 'opposed';
				Congress_Admin_Stacked_Input::display_text(
					id: "congress-campaign-$this->str_campaign_id-email-$this->str_id-subject",
					label: 'Subject',
					name: 'subject',
					value: $this->subject,
				);
				Congress_Admin_Stacked_Input::display_dropdown(
					id: "congress-campaign-$this->str_campaign_id-email-$this->str_id-subject",
					label: 'For',
					name: 'for',
					value: $email_for,
					options: array(
						array(
							'label' => 'representatives in favor',
							'value' => 'favored',
						),
						array(
							'label' => 'representatives opposed',
							'value' => 'opposed',
						),
					),
				);
				?>
			</div>
			<div class="congress-flex-row">
				<?php
				Congress_Admin_Stacked_Input::display_textarea(
					id: "congress-campaign-$this->str_campaign_id-email-$this->str_id-template",
					label: 'Template',
					name: 'template',
					value: $this->template,
					rows: 10,
				);
				Congress_Admin_Stacked_Input::display_textarea(
					id: "congress-campaign-$this->str_campaign_id-email-$this->str_id-preview",
					label: 'Preview',
					name: 'preview',
					value: '',
					editable: false,
					rows: 10,
				);

				wp_nonce_field( "edit-email-template_$this->str_campaign_id-$this->str_id" );
				?>
				<input type="hidden" name="id" value="<?php echo esc_attr( $this->id ); ?>" />
				<input type="hidden" name="campaign_id" value="<?php echo esc_attr( $this->campaign_id ); ?>" />
			</div>
			<div style="margin-top: 1em; display: flex; justify-content: center; gap: 2em; align-items: center;">
				<div
					style="width: 50%; display: flex; justify-content: flex-end;"
				>
					<span class="congress-form-error" style="margin-top: 0.3em;"></span>
					<input type="submit" formaction="update_email_template" class="button button-primary" style="width: 50%;" value="Update Email" />
				</div>
				<div
					style="width: 50%; display: flex; justify-content: flex-start;"
				>
					<input type="submit" formaction="delete_email_template" class="button congress-button-danger" style="width: 50%;" value="Delete Email" />
					<span class="congress-form-error" style="margin-top: 0.3em;"></span>
				</div>
			</div>
		</form>
		<?php
	}

	/**
	 * Returns an HTML template to be used by JQuery when new representatives are added.
	 */
	public static function get_html_template(): void {
		$template = new Congress_Admin_Email( 0, 0, 'Subject', false, '' );
		$template->display();
	}

	/**
	 * Retrieves email templates fromt he db.
	 *
	 * @param int $campaign_id is the campaign the templates are for.
	 *
	 * @return array<Congress_Admin_Email>
	 */
	public static function get_from_db( $campaign_id ): array {

		global $wpdb;
		$email_t = Congress_Table_Manager::get_table_name( 'email' );
		// phpcs:disable;
		$result  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $email_t WHERE campaign_id = %d",
				array(
					$campaign_id,
				)
			)
		);
		// phpcs:enable

		$campaigns = array();
		foreach ( $result as $email_result ) {
			array_push(
				$campaigns,
				new Congress_Admin_Email(
					$email_result->id,
					$email_result->campaign_id,
					$email_result->subject,
					$email_result->favorable,
					$email_result->template,
				),
			);
		}
		return $campaigns;
	}
}
