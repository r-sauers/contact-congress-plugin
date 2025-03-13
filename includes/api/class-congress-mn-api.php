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

/**
 * Handles Minnesota representative requests.
 *
 * @since      1.0.0
 * @package    Congress
 * @subpackage Congress/includes
 * @author     Ryan Sauers <ryan.sauers@exploreveg.org>
 */
class Congress_MN_API {


	/**
	 * Finds the Minnesota representatives for a given location.
	 *
	 * @param float $latitude is the latitude of the location.
	 * @param float $longitude is the longitude of the location.
	 *
	 * @return array|false the api results or false on failure.
	 */
	public function get_reps( float $latitude, float $longitude ): array|false {

		$results = wp_remote_get(
			'https://www.gis.lcc.mn.gov/iMaps/districts/php/getPointData.php',
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

		foreach ( $body['features'] as $feature ) {
			array_push( $reps, $feature['properties'] );
		}

		$reps[0]['level'] = 'state';
		$reps[1]['level'] = 'state';
		$reps[2]['level'] = 'federal';

		$reps[0]['title'] = 'Representative';
		$reps[1]['title'] = 'Senator';
		$reps[2]['title'] = 'Representative';

		$district       = $reps[0]['district'];
		$reps[0]['img'] = "https://www.gis.lcc.mn.gov/iMaps/districts/images/House/$district.jpg";

		$district       = $reps[1]['district'];
		$reps[1]['img'] = "https://www.gis.lcc.mn.gov/iMaps/districts/images/Senate/$district.jpg";

		$district       = $reps[2]['district'];
		$reps[2]['img'] = "https://www.gis.lcc.mn.gov/iMaps/districts/images/USHouse/US$district.jpg";

		return $reps;
	}

	/**
	 * Finds the Minnesota state representatives for a given location.
	 *
	 * @param float $latitude is the latitude of the location.
	 * @param float $longitude is the longitude of the location.
	 *
	 * @return array|false the api results or false on failure.
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
	 * @return array|false the api results or false on failure.
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
}
