<?php
/**
 * An interface to model staffers consistently.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Congress
 * @subpackage Congress/includes
 */

/**
 * An interface to model staffers consistently.
 *
 * @since      1.0.0
 * @package    Congress
 * @subpackage Congress/includes
 * @author     Ryan Sauers <ryan.sauers@exploreveg.org>
 */
class Congress_Staffer_Interface {

	/**
	 * The first name of the staffer.
	 *
	 * @var string
	 */
	public string $first_name;

	/**
	 * The last name of the staffer.
	 *
	 * @var string
	 */
	public string $last_name;

	/**
	 * The staffer's title.
	 *
	 * @var string
	 */
	public string $title;

	/**
	 * The staffer's email.
	 *
	 * @var ?string
	 */
	public string $email;

	/**
	 * The staffer's id.
	 *
	 * @var ?int
	 */
	protected ?int $id;

	/**
	 * Constructs the staffer.
	 *
	 * @param string $first_name The first name of the staffer.
	 * @param string $last_name The last name of the staffer.
	 * @param string $title The staffer's title.
	 * @param string $email The staffer's email.
	 * @param ?int   $id The staffer's id.
	 */
	public function __construct(
		string $first_name,
		string $last_name,
		string $title,
		string $email,
		?int $id = null
	) {
		$this->first_name = $first_name;
		$this->last_name  = $last_name;
		$this->title      = $title;
		$this->email      = $email;
		$this->id         = $id;
	}

	/**
	 * Returns whether or not the staffer has an id.
	 */
	public function has_id(): bool {
		return null !== $this->id;
	}

	/**
	 * Sets the id.
	 *
	 * @param int $id is the staffer's id.
	 */
	public function set_id( int $id ): void {
		$this->id = $id;
	}

	/**
	 * Gets the staffer's id.
	 *
	 * @throws Error If staffer has no id, see @has_id.
	 */
	public function get_id(): string {
		if ( ! $this->has_id() ) {
			throw 'Could not get id.';
		}

		return $this->id;
	}

	/**
	 * Returns whether or not this staffer is equal to another.
	 *
	 * @param Congress_Staffer_Interface $other is the other staffer.
	 * @param bool                       $ignore_id determines if the staffer id is used in comparison.
	 */
	public function equals( Congress_Staffer_Interface $other, bool $ignore_id ): bool {

		return (
			$this->first_name === $other->first_name &&
			$this->last_name === $other->last_name &&
			$this->email === $other->email &&
			$this->title === $other->title &&
			( $ignore_id || $this->id === $other->id )
		);
	}

	/**
	 * Converts the staffer to a JSON representation.
	 *
	 * @return array<string,mixed>
	 */
	public function to_json(): array {
		$json_array = array(
			'firstName' => $this->first_name,
			'lastName'  => $this->last_name,
			'title'     => $this->title,
			'email'     => $this->email,
		);

		if ( $this->has_id() ) {
			$json_array['id'] = $this->get_id();
		}

		return $json_array;
	}
}
