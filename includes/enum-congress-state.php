<?php
/**
 * An enum to model US states consistently.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Congress
 * @subpackage Congress/includes
 */

/**
 * An enum to model US states consistently.
 *
 * @since      1.0.0
 * @package    Congress
 * @subpackage Congress/includes
 * @author     Ryan Sauers <ryan.sauers@exploreveg.org>
 */
enum Congress_State {

	case AL;
	case AK;
	case AZ;
	case AR;
	case CA;
	case CO;
	case CT;
	case DE;
	case FL;
	case GA;
	case HI;
	case ID;
	case IL;
	case IN;
	case IA;
	case KS;
	case KY;
	case LA;
	case ME;
	case MD;
	case MA;
	case MI;
	case MN;
	case MS;
	case MO;
	case MT;
	case NE;
	case NV;
	case NH;
	case NJ;
	case NM;
	case NY;
	case NC;
	case ND;
	case OH;
	case OK;
	case OR;
	case PA;
	case RI;
	case SC;
	case SD;
	case TN;
	case TX;
	case UT;
	case VT;
	case VA;
	case WA;
	case WV;
	case WI;
	case WY;

	/**
	 * Coerces an enum value from $str.
	 *
	 * @param string $str is a string e.g. 'senator'.
	 *
	 * @throws Error If unable to coerce.
	 */
	public static function from_string( string $str ): self {
		$str = strtolower( $str );
		try {
			return match ( $str ) {
				'al' => Congress_State::AL,
				'alabama' => Congress_State::AL,
				'ak' => Congress_State::AK,
				'alaska' => Congress_State::AK,
				'az' => Congress_State::AZ,
				'arizona' => Congress_State::AZ,
				'ar' => Congress_State::AR,
				'arkansas' => Congress_State::AR,
				'ca' => Congress_State::CA,
				'california' => Congress_State::CA,
				'co' => Congress_State::CO,
				'colorado' => Congress_State::CO,
				'ct' => Congress_State::CT,
				'connecticut' => Congress_State::CT,
				'de' => Congress_State::DE,
				'deleware' => Congress_State::DE,
				'fl' => Congress_State::FL,
				'florida' => Congress_State::FL,
				'ga' => Congress_State::GA,
				'georgia' => Congress_State::GA,
				'hi' => Congress_State::HI,
				'hawaii' => Congress_State::HI,
				'id' => Congress_State::ID,
				'idaho' => Congress_State::ID,
				'il' => Congress_State::IL,
				'illinois' => Congress_State::IL,
				'in' => Congress_State::IN,
				'indiana' => Congress_State::IN,
				'ia' => Congress_State::IA,
				'iowa' => Congress_State::IA,
				'ks' => Congress_State::KS,
				'kansas' => Congress_State::KS,
				'ky' => Congress_State::KY,
				'kentucky' => Congress_State::KY,
				'la' => Congress_State::LA,
				'louisiana' => Congress_State::LA,
				'me' => Congress_State::ME,
				'maine' => Congress_State::ME,
				'md' => Congress_State::MD,
				'maryland' => Congress_State::MD,
				'ma' => Congress_State::MA,
				'massachusetts' => Congress_State::MA,
				'mi' => Congress_State::MI,
				'michigan' => Congress_State::MI,
				'mn' => Congress_State::MN,
				'minnesota' => Congress_State::MN,
				'ms' => Congress_State::MS,
				'mississippi' => Congress_State::MS,
				'mo' => Congress_State::MO,
				'missouri' => Congress_State::MO,
				'mt' => Congress_State::MT,
				'montana' => Congress_State::MT,
				'ne' => Congress_State::NE,
				'nebraska' => Congress_State::NE,
				'nv' => Congress_State::NV,
				'nevada' => Congress_State::NV,
				'nh' => Congress_State::NH,
				'new hampshire' => Congress_State::NH,
				'nj' => Congress_State::NJ,
				'new jersey' => Congress_State::NJ,
				'nm' => Congress_State::NM,
				'new mexico' => Congress_State::NM,
				'ny' => Congress_State::NY,
				'new york' => Congress_State::NY,
				'nc' => Congress_State::NC,
				'north carolina' => Congress_State::NC,
				'nd' => Congress_State::ND,
				'north dakota' => Congress_State::ND,
				'oh' => Congress_State::OH,
				'ohio' => Congress_State::OH,
				'ok' => Congress_State::OK,
				'oklahoma' => Congress_State::OK,
				'or' => Congress_State::OR,
				'oregon' => Congress_State::OR,
				'pa' => Congress_State::PA,
				'pennsylvania' => Congress_State::PA,
				'ri' => Congress_State::RI,
				'rhode island' => Congress_State::RI,
				'sc' => Congress_State::SC,
				'south carolina' => Congress_State::SC,
				'sd' => Congress_State::SD,
				'south dakota' => Congress_State::SD,
				'tn' => Congress_State::TN,
				'tennessee' => Congress_State::TN,
				'tx' => Congress_State::TX,
				'texas' => Congress_State::TX,
				'ut' => Congress_State::UT,
				'utah' => Congress_State::UT,
				'vt' => Congress_State::VT,
				'vermont' => Congress_State::VT,
				'va' => Congress_State::VA,
				'virginia' => Congress_State::VA,
				'wa' => Congress_State::WA,
				'washington' => Congress_State::WA,
				'wv' => Congress_State::WV,
				'west virginia' => Congress_State::WV,
				'wi' => Congress_State::WI,
				'wisconsin' => Congress_State::WI,
				'wy' => Congress_State::WY,
				'wyoming' => Congress_State::WY
			};
		} catch ( UnhandledMatchError $e ) {
			throw 'Could not coerce string.';
		}
	}

	/**
	 * Returns the title with the proper capitalization.
	 */
	public function to_display_string(): string {
		return match ( $this ) {
			Congress_State::AL => 'Alabama',
			Congress_State::AK => 'Alaska',
			Congress_State::AZ => 'Arizona',
			Congress_State::AR => 'Arkansas',
			Congress_State::CA => 'California',
			Congress_State::CO => 'Colorado',
			Congress_State::CT => 'Connecticut',
			Congress_State::DE => 'Deleware',
			Congress_State::FL => 'Florida',
			Congress_State::GA => 'Georgia',
			Congress_State::HI => 'Hawaii',
			Congress_State::ID => 'Idaho',
			Congress_State::IL => 'Illinois',
			Congress_State::IN => 'Indiana',
			Congress_State::IA => 'Iowa',
			Congress_State::KS => 'Kansas',
			Congress_State::KY => 'Kentucky',
			Congress_State::LA => 'Louisiana',
			Congress_State::ME => 'Maine',
			Congress_State::MD => 'Maryland',
			Congress_State::MA => 'Massachusetts',
			Congress_State::MI => 'Michigan',
			Congress_State::MN => 'Minnesota',
			Congress_State::MS => 'Mississippi',
			Congress_State::MO => 'Missouri',
			Congress_State::MT => 'Montana',
			Congress_State::NE => 'Nebraska',
			Congress_State::NV => 'Nevada',
			Congress_State::NH => 'New Hampshire',
			Congress_State::NJ => 'New Jersey',
			Congress_State::NM => 'New Mexico',
			Congress_State::NY => 'New York',
			Congress_State::NC => 'North Carolina',
			Congress_State::ND => 'North Dakota',
			Congress_State::OH => 'Ohio',
			Congress_State::OK => 'Oklahoma',
			Congress_State::OR => 'Oregon',
			Congress_State::PA => 'Pennsylvania',
			Congress_State::RI => 'Rhode Island',
			Congress_State::SC => 'South Carolina',
			Congress_State::SD => 'South Dakota',
			Congress_State::TN => 'Tennessee',
			Congress_State::TX => 'Texas',
			Congress_State::UT => 'Utah',
			Congress_State::VT => 'Vermont',
			Congress_State::VA => 'Virginia',
			Congress_State::WA => 'Washington',
			Congress_State::WV => 'West Virginia',
			Congress_State::WI => 'Wisconsin',
			Congress_State::WY => 'Wyoming'
		};
	}

	/**
	 * Returns a string for how the enum should be represented in the database.
	 */
	public function to_db_string(): string {
		return strtoupper( $this->to_state_code() );
	}

	/**
	 * Converts to the state code string in lowercase e.g. 'mn'.
	 */
	public function to_state_code(): string {
		return match ( $this ) {
			Congress_State::AL => 'al',
			Congress_State::AK => 'ak',
			Congress_State::AZ => 'az',
			Congress_State::AR => 'ar',
			Congress_State::CA => 'ca',
			Congress_State::CO => 'co',
			Congress_State::CT => 'ct',
			Congress_State::DE => 'de',
			Congress_State::FL => 'fl',
			Congress_State::GA => 'ga',
			Congress_State::HI => 'hi',
			Congress_State::ID => 'id',
			Congress_State::IL => 'il',
			Congress_State::IN => 'in',
			Congress_State::IA => 'ia',
			Congress_State::KS => 'ks',
			Congress_State::KY => 'ky',
			Congress_State::LA => 'la',
			Congress_State::ME => 'me',
			Congress_State::MD => 'md',
			Congress_State::MA => 'ma',
			Congress_State::MI => 'mi',
			Congress_State::MN => 'mn',
			Congress_State::MS => 'ms',
			Congress_State::MO => 'mo',
			Congress_State::MT => 'mt',
			Congress_State::NE => 'ne',
			Congress_State::NV => 'nv',
			Congress_State::NH => 'nh',
			Congress_State::NJ => 'nj',
			Congress_State::NM => 'nm',
			Congress_State::NY => 'ny',
			Congress_State::NC => 'nc',
			Congress_State::ND => 'nd',
			Congress_State::OH => 'oh',
			Congress_State::OK => 'ok',
			Congress_State::OR => 'or',
			Congress_State::PA => 'pa',
			Congress_State::RI => 'ri',
			Congress_State::SC => 'sc',
			Congress_State::SD => 'sd',
			Congress_State::TN => 'tn',
			Congress_State::TX => 'tx',
			Congress_State::UT => 'ut',
			Congress_State::VT => 'vt',
			Congress_State::VA => 'va',
			Congress_State::WA => 'wa',
			Congress_State::WV => 'wv',
			Congress_State::WI => 'wi',
			Congress_State::WY => 'wy'
		};
	}
}
