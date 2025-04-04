<?php
/**
 * An interface to model representatives consistently.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Congress
 * @subpackage Congress/includes
 */

/**
 * Include the Congress_Level enum.
 */
require_once plugin_dir_path( __FILE__ ) . 'enum-congress-level.php';

/**
 * Include the Congress_Title enum.
 */
require_once plugin_dir_path( __FILE__ ) . 'enum-congress-title.php';

/**
 * Include the Congress_State enum.
 */
require_once plugin_dir_path( __FILE__ ) . 'enum-congress-state.php';

/**
 * Include Congress_Staffer_Interface.
 */
require_once plugin_dir_path( __FILE__ ) . 'class-congress-staffer-interface.php';

/**
 * An interface to model representatives consistently.
 *
 * @since      1.0.0
 * @package    Congress
 * @subpackage Congress/includes
 * @author     Ryan Sauers <ryan.sauers@exploreveg.org>
 */
class Congress_Rep_Interface {

	/**
	 * The first name of the representative.
	 *
	 * @var string
	 */
	public string $first_name;

	/**
	 * The last name of the representative.
	 *
	 * @var string
	 */
	public string $last_name;

	/**
	 * The representative's state.
	 *
	 * @var Congress_State
	 */
	public Congress_State $state;

	/**
	 * The level of the representative.
	 *
	 * @var Congress_Level
	 */
	public Congress_Level $level;

	/**
	 * The representative's title.
	 *
	 * @var Congress_Title
	 */
	public Congress_Title $title;

	/**
	 * The representative's district.
	 *
	 * @var ?string
	 */
	protected ?string $district;

	/**
	 * A url to an image of the representative.
	 *
	 * @var ?string
	 */
	protected ?string $img;

	/**
	 * The representative's staffers.
	 *
	 * @var array<Congress_Staffer_Interface>
	 */
	protected array $staffers;

	/**
	 * The representative's id.
	 *
	 * @var ?int
	 */
	protected ?int $id;

	/**
	 * A helper function for creating a representative from database results.
	 *
	 * @param array $db_result is the results of a database call with the fields:
	 * first_name, last_name, state, level, title, and district.
	 */
	public static function from_db_result( $db_result ): Congress_Rep_Interface {
		$rep = new Congress_Rep_Interface(
			first_name: $db_result->first_name,
			last_name: $db_result->last_name,
			state: Congress_State::from_string( $db_result->state ),
			level: Congress_Level::from_string( $db_result->level ),
			title: Congress_Title::from_string( $db_result->title )
		);

		if ( isset( $db_result->district ) ) {
			$rep->set_district( $db_result->district );
		}

		if ( isset( $db_result->id ) ) {
			$rep->set_id( $db_result->id );
		}

		return $rep;
	}

	/**
	 * A helper function for usort to sort representatives by state and district.
	 *
	 * @param Congress_Rep_Interface $a is one representative to compare.
	 * @param Congress_Rep_Interface $b is the other representative to compare.
	 */
	public static function cmp_by_position( Congress_Rep_Interface $a, Congress_Rep_Interface $b ): int {

		$state_cmp = strcasecmp( $a->state->name, $b->state->name );

		if ( 0 !== $state_cmp ) {
			return $state_cmp;
		}

		$district_cmp = 0;
		if ( $a->has_district() && $b->has_district() ) {
			$district_cmp = strcasecmp( $a->get_district(), $b->get_district() );
		} elseif ( $a->has_district() ) {
			$district_cmp = 1;
		} elseif ( $b->has_district() ) {
			$district_cmp = -1;
		}

		return $district_cmp;
	}

	/**
	 * A helper function for usort to sort representatives by state, district, first name, and last name.
	 *
	 * @param Congress_Rep_Interface $a is one representative to compare.
	 * @param Congress_Rep_Interface $b is the other representative to compare.
	 */
	public static function cmp_by_position_and_name( Congress_Rep_Interface $a, Congress_Rep_Interface $b ): int {

		$position_cmp = self::cmp_by_position( $a, $b );

		if ( 0 !== $position_cmp ) {
			return $position_cmp;
		}

		$last_name_cmp = strcasecmp( $a->last_name, $b->last_name );
		if ( 0 !== $last_name_cmp ) {
			return $last_name_cmp;
		}

		$first_name_cmp = strcasecmp( $a->first_name, $b->first_name );
		return $first_name_cmp;
	}

	/**
	 * Constructs the representative.
	 *
	 * @param string         $first_name The first name of the representative.
	 * @param string         $last_name The last name of the representative.
	 * @param Congress_State $state The representative's state.
	 * @param Congress_Level $level The level of the representative.
	 * @param Congress_Title $title The representative's title.
	 * @param ?string        $district The representative's district.
	 * @param ?string        $img The url for the representative's image.
	 * @param ?int           $id The representative's id.
	 */
	public function __construct(
		string $first_name,
		string $last_name,
		Congress_State $state,
		Congress_Level $level,
		Congress_Title $title,
		?string $district = null,
		?string $img = null,
		?int $id = null
	) {
		$this->first_name = $first_name;
		$this->last_name  = $last_name;
		$this->state      = $state;
		$this->level      = $level;
		$this->title      = $title;
		$this->district   = $district;
		$this->img        = $img;
		$this->id         = $id;
		$this->staffers   = array();
	}

	/**
	 * Returns if this is a federal representative.
	 */
	public function is_federal_rep(): bool {
		return Congress_Level::Federal === $this->level;
	}

	/**
	 * Returns if this is a state representative.
	 */
	public function is_state_rep(): bool {
		return Congress_Level::State === $this->level;
	}

	/**
	 * Returns if this is a Senator.
	 */
	public function is_senator(): bool {
		return Congress_Title::Senator === $this->title;
	}

	/**
	 * Returns if this is a member of the House of Representatives.
	 */
	public function is_rep(): bool {
		return Congress_Title::Representative === $this->title;
	}

	/**
	 * Returns whether or not the representative has a district.
	 * (federal senators do not have districts)
	 */
	public function has_district(): bool {
		return null !== $this->district && '' !== $this->district;
	}

	/**
	 * Sets the district.
	 *
	 * @param string $district is the representative's district.
	 */
	public function set_district( string $district ): void {
		$this->district = $district;
	}

	/**
	 * Gets the representative's district.
	 *
	 * @throws Error If representative has no district, see @has_district.
	 */
	public function get_district(): string {
		if ( ! $this->has_district() ) {
			throw 'Could not get district.';
		}

		return $this->district;
	}

	/**
	 * Returns whether or not the representative has an image.
	 */
	public function has_img(): bool {
		return null !== $this->img && '' !== $this->img;
	}

	/**
	 * Sets the image.
	 *
	 * @param string $img is url for the representative's image.
	 */
	public function set_img( string $img ): void {
		$this->img = $img;
	}

	/**
	 * Gets the representative's image.
	 *
	 * @throws Error If representative has no image, see @has_img.
	 */
	public function get_img(): string {
		if ( ! $this->has_img() ) {
			throw 'Could not get image.';
		}

		return $this->img;
	}

	/**
	 * Adds a staffer.
	 *
	 * @param Congress_Staffer_Interface $staffer is one of the representative's staffers.
	 */
	public function add_staffer( Congress_Staffer_Interface $staffer ): void {
		array_push( $this->staffers, $staffer );
	}

	/**
	 * Gets the representative's staffers.
	 *
	 * @return array<Congress_Staffer_Interface>
	 */
	public function get_staffers(): array {
		return $this->staffers;
	}

	/**
	 * Returns whether or not the representative has an id.
	 */
	public function has_id(): bool {
		return null !== $this->id;
	}

	/**
	 * Sets the id.
	 *
	 * @param int $id is the representative's id.
	 */
	public function set_id( int $id ): void {
		$this->id = $id;
	}

	/**
	 * Gets the representative's id.
	 *
	 * @throws Error If representative has no id, see @has_id.
	 */
	public function get_id(): string {
		if ( ! $this->has_id() ) {
			throw 'Could not get id.';
		}

		return $this->id;
	}

	/**
	 * Returns whether or not this representative is equal to another.
	 *
	 * @param Congress_Rep_Interface $other is the other representative.
	 * @param bool                   $ignore_image determines if the representative image is used in comparison.
	 * @param bool                   $ignore_staffers determines if the representative staffers are used in comparison.
	 * @param bool                   $ignore_id determines if the representative id is used in comparison.
	 */
	public function equals( Congress_Rep_Interface $other, bool $ignore_image = false, bool $ignore_staffers = false, bool $ignore_id = false ): bool {

		$staffer_cmp = true;
		if ( ! $ignore_staffers ) {
			$staffer_count       = count( $this->staffers );
			$other_staffer_count = count( $other->staffers );
			if ( $staffer_count === $other_staffer_count ) {

				for ( $i = 0; $i < $staffer_count; ++$i ) {
					if ( ! $this->staffers[ $i ]->equals( $other->staffers[ $i ] ) ) {
						$staffer_cmp = false;
						break;
					}
				}
			} else {
				$staffer_cmp = false;
			}
		}

		return (
			$this->first_name === $other->first_name &&
			$this->last_name === $other->last_name &&
			$this->district === $other->district &&
			$this->level === $other->level &&
			$this->title === $other->title &&
			$staffer_cmp &&
			( $ignore_image || $this->img === $other->img ) &&
			( $ignore_id || $this->id === $other->id )
		);
	}

	/**
	 * Converts the representative to a JSON representation.
	 *
	 * @return array<string,mixed>
	 */
	public function to_json(): array {
		$json_array = array(
			'firstName'    => $this->first_name,
			'lastName'     => $this->last_name,
			'level'        => $this->level->to_db_string(),
			'levelDisplay' => $this->level->to_display_string(),
			'title'        => $this->title->to_db_string(),
			'titleDisplay' => $this->title->to_display_string(),
			'state'        => $this->state->to_db_string(),
			'stateDisplay' => $this->state->to_display_string(),
		);

		if ( 0 < count( $this->staffers ) ) {
			$json_array['staffers'] = array();
			foreach ( $this->staffers as &$staffer ) {
				array_push(
					$json_array['staffers'],
					$staffer->to_json()
				);
			}
		}

		if ( $this->has_img() ) {
			$json_array['img'] = $this->get_img();
		}

		if ( $this->has_district() ) {
			$json_array['district'] = $this->get_district();
		}

		if ( $this->has_id() ) {
			$json_array['id'] = $this->get_id();
		}

		return $json_array;
	}
}
