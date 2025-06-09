<?php
/**
 * Defines the class that handles Minnesota's API requests.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Congress
 * @subpackage Congress/includes
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Include PhpSpreadsheet IOFactory to parse xls files.
 */
require_once plugin_dir_path( __DIR__ ) . '../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Include Congress_State_API_Interface.
 */
require_once plugin_dir_path( __FILE__ ) . 'congress-state-api-interface.php';

/**
 * Include Congress_Rep_Interface.
 */
require_once plugin_dir_path( __DIR__ ) . 'class-congress-rep-interface.php';

/**
 * Include Congress_Staffer_Interface.
 */
require_once plugin_dir_path( __DIR__ ) . 'class-congress-staffer-interface.php';

/**
 * Handles Minnesota representative requests.
 *
 * @since      1.0.0
 * @package    Congress
 * @subpackage Congress/includes
 * @author     Ryan Sauers <ryan.sauers@exploreveg.org>
 */
class Congress_MN_API implements Congress_State_API_Interface {

	/**
	 * Downloads an file from $url and generates an array of the
	 * rows with key value pairs based on the header row.
	 *
	 * @param string $url is a .xls or csv file.
	 *
	 * @return string filename
	 */
	private function get_remote_sheet( string $url ): array|false {

		$filename = download_url( $url );
		if ( is_a( $filename, 'WP_Error' ) ) {
			return false;
		}

		$spreadsheet = IOFactory::load( $filename );
		$worksheet   = $spreadsheet->getActiveSheet();

		$headers = array();
		$data    = array();
		foreach ( $worksheet->getRowIterator() as $row ) {

			$cell_iterator = $row->getCellIterator();
			$cell_iterator->setIterateOnlyExistingCells( false ); // Iterate all cells.

			if ( empty( $headers ) ) {
				foreach ( $cell_iterator as $cell ) {
					array_push( $headers, $cell->getValue() );
				}
			} else {
				$row = array();
				$i   = 0;
				foreach ( $cell_iterator as $cell ) {
					$row[ $headers[ $i ] ] = $cell->getValue();
					++$i;
				}
				array_push( $data, $row );
			}
		}

		wp_delete_file( $filename );

		return $data;
	}

	/**
	 * Gets all of the MN representatives.
	 *
	 * @return array|false
	 */
	public function get_all_reps(): array|false {

		$reps = array();

		$house_reps = $this->get_remote_sheet( 'https://www.house.mn.gov/members/Files/MemberInfo/meminfo.xls' );

		if ( ! $house_reps ) {
			return false;
		}

		foreach ( $house_reps as $house_rep ) {
			$new_rep     = new Congress_Rep_Interface(
				level: Congress_Level::State,
				title: Congress_Title::Representative,
				district: $house_rep['district_id'],
				first_name: $house_rep['fname'],
				last_name: $house_rep['lname'],
				state: Congress_State::MN,
			);
			$new_staffer = new Congress_Staffer_Interface(
				first_name: $new_rep->first_name,
				last_name: $new_rep->last_name,
				email: $house_rep['email address'],
				title: Congress_Title::Representative->to_display_string()
			);
			$new_rep->add_staffer( $new_staffer );
			array_push(
				$reps,
				$new_rep
			);
		}

		$results = wp_remote_get( 'https://www.senate.mn/api/members' );

		if ( is_wp_error( $results ) || 200 !== $results['response']['code'] ) {
			return false;
		}

		$body = json_decode( $results['body'], true );

		foreach ( $body['members'] as $senate_rep ) {
			$new_rep     = new Congress_Rep_Interface(
				level: Congress_Level::State,
				title: Congress_Title::Senator,
				district: $senate_rep['dist'],
				first_name: explode( ' ', $senate_rep['preferred_full_name'], 2 )[0],
				last_name: $senate_rep['preferred_last_name'],
				state: Congress_State::MN,
			);
			$new_staffer = new Congress_Staffer_Interface(
				first_name: $new_rep->first_name,
				last_name: $new_rep->last_name,
				email: $senate_rep['email'],
				title: Congress_Title::Senator->to_display_string()
			);
			$new_rep->add_staffer( $new_staffer );
			array_push(
				$reps,
				$new_rep
			);
		}

		return $reps;
	}

	/**
	 * Finds the Minnesota representatives for a given location.
	 *
	 * @param float $latitude is the latitude of the location.
	 * @param float $longitude is the longitude of the location.
	 *
	 * @return array<Congress_Rep_Interface>|false the api results or false on failure.
	 */
	public function get_reps( float $latitude, float $longitude ): array|false {

		$results = wp_remote_get(
			'https://gis.lcc.mn.gov/api/',
			array(
				'body' => array(
					'lat' => $latitude,
					'lng' => $longitude,
				),
			)
		);

		if ( is_a( $results, 'WP_Error' ) || 200 !== $results['response']['code'] ) {
			return false;
		}

		$reps = array();
		$body = json_decode( $results['body'], true );

		$i = 0;
		foreach ( $body['features'] as $feature ) {

			if ( 0 === $i || 1 === $i ) {
				$level = Congress_Level::State;
			} else {
				$level = Congress_Level::Federal;
			}

			if ( 0 === $i || 2 === $i ) {
				$title = Congress_Title::Representative;
			} else {
				$title = Congress_Title::Senator;
			}

			$district = $feature['properties']['district'];
			if ( 0 === $i ) {
				$img = "https://www.gis.lcc.mn.gov/iMaps/districts/images/House/$district.jpg";
			} elseif ( 1 === $i ) {
				$img = "https://www.gis.lcc.mn.gov/iMaps/districts/images/Senate/$district.jpg";
			} elseif ( 2 === $i ) {
				$img = "https://www.gis.lcc.mn.gov/iMaps/districts/images/USHouse/US$district.jpg";
			}

			$name_split = explode( ' ', $feature['properties']['name'] );
			array_push(
				$reps,
				new Congress_Rep_Interface(
					level: $level,
					title: $title,
					district: $district,
					state: Congress_State::MN,
					first_name: $name_split[0],
					last_name: $name_split[ count( $name_split ) - 1 ],
					img: $img,
				)
			);
			++$i;
		}

		return $reps;
	}

	/**
	 * Finds the Minnesota state representatives for a given location.
	 *
	 * @param float $latitude is the latitude of the location.
	 * @param float $longitude is the longitude of the location.
	 *
	 * @return array<Congress_Rep_Interface>|false the api results or false on failure.
	 */
	public function get_state_reps( float $latitude, float $longitude ): array|false {
		$results = $this->get_reps( $latitude, $longitude );
		if ( false === $results ) {
			return false;
		}

		return array(
			$results[0],
			$results[1],
		);
	}

	/**
	 * Finds the Minnesota federal representatives for a given location.
	 *
	 * @param float $latitude is the latitude of the location.
	 * @param float $longitude is the longitude of the location.
	 *
	 * @return array<Congress_Rep_Interface>|false the api results or false on failure.
	 */
	public function get_federal_reps( float $latitude, float $longitude ): array|false {
		$results = $this->get_reps( $latitude, $longitude );
		if ( false === $results ) {
			return false;
		}

		return array(
			$results[2],
		);
	}

	/**
	 * For description @see Congress_State_API_Interface::get_state.
	 */
	public static function get_state(): Congress_State {
		return Congress_State::MN;
	}
}
