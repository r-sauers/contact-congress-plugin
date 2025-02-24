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
	 * @var int $id
	 */
	private int $id;

	/**
	 * The id of the email's campaign.
	 *
	 * @var int $campaign_id
	 */
	private int $campaign_id;

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
	}

	/**
	 * Displays the html for the campaign.
	 */
	public function display(): void {
		?>
		<form
			id="<?php echo esc_attr( "congress-campaign-$this->campaign_id-email-$this->id" ); ?>"
			class="congress-campaign-email-container"
		>
			<h3><?php echo esc_html( "Template $this->id " ); ?></h3>
			<div class="congress-flex-row">
				<?php
				$email_for = $this->favorable ? 'favored' : 'opposed';
				Congress_Admin_Stacked_Input::display_text(
					id: "congress-campaign-$this->campaign_id-email-$this->id-subject",
					label: 'Subject',
					name: 'subject',
					value: $this->subject,
				);
				Congress_Admin_Stacked_Input::display_dropdown(
					id: "congress-campaign-$this->campaign_id-email-$this->id-subject",
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
					id: "congress-campaign-$this->campaign_id-email-$this->id-template",
					label: 'Template',
					name: 'template',
					value: $this->template,
				);
				Congress_Admin_Stacked_Input::display_textarea(
					id: "congress-campaign-$this->campaign_id-email-$this->id-preview",
					label: 'Preview',
					name: 'preview',
					value: '',
					editable: false,
				);
				?>
			</div>
			<div style="margin-top: 1em; display: flex; justify-content: center; gap: 2em;">
				<button type="submit" action="post" class="button button-primary" style="width: 25%;">Update Email (no changes)</button>
				<button type="submit" action="delete" class="button congress-button-danger" style="width: 25%;">Delete Email</button>
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
	 * Retrieves the active campaigns from the DB.
	 *
	 * @return array<Congress_Admin_Campaign>
	 */
	public static function get_from_db(): array {
		return array();
	}
}
