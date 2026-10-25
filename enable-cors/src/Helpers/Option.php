<?php

namespace Enable\Cors\Helpers;

/*
|--------------------------------------------------------------------------
| If this file is called directly, abort.
|--------------------------------------------------------------------------
*/
if ( ! defined( 'Enable\Cors\SLUG' ) ) {
	exit;
}

use WP_Error;
use const Enable\Cors\SLUG;
use const Enable\Cors\VERSION;

/**
 * Handle plugin option.
 */
final class Option {


	/**
	 * Plugin option key.
	 *
	 * @var string
	 */
	private const KEY = SLUG . '-options';

	/**
	 * Default options for the API.
	 *
	 * @var array
	 */
	private const DEFAULT_OPTION = array(
		'enable'            => false,
		'allow_font'        => false,
		'allow_image'       => false,
		'allow_credentials' => false,
		'allowed_for'       => array( array( 'value' => '*' ) ),
		'allowed_methods'   => array( 'GET', 'POST' ),
		'allowed_header'    => array(),
	);

	/**
	 * Configurable methods; OPTIONS is always allowed.
	 *
	 * @var array
	 */
	private const METHODS = array(
		'GET',
		'POST',
		'PUT',
		'DELETE',
		'PATCH',
	);

	/**
	 * List of allowed headers.
	 *
	 * @var array
	 */
	private const HEADERS = array(
		'Accept',
		'Authorization',
		'Content-Type',
		'Origin',
		'X-WP-Nonce',
	);

	public const VKEY = SLUG . '_version';

	/**
	 * Array of allowed headers.
	 *
	 * @var array
	 */
	private $allowed_header;

	/**
	 * Array of allowed HTTP methods.
	 *
	 * @var array
	 */
	private $allowed_methods;

	/**
	 * Array of allowed domains for CORS.
	 *
	 * @var array
	 */
	private $allowed_for;

	/**
	 * Whether to allow credentials in CORS requests.
	 *
	 * @var bool
	 */
	private $allow_credentials;

	/**
	 * Whether to allow font access in CORS requests.
	 *
	 * @var bool
	 */
	private $allow_image;

	/**
	 * Whether to allow image access in CORS requests.
	 *
	 * @var bool
	 */
	private $allow_font;

	/**
	 * Whether to enable CORS.
	 *
	 * @var bool
	 */
	private $enable;

	/**
	 * Set up options and set defaults.
	 */
	public function __construct() {
		$options = get_site_option( self::KEY, self::DEFAULT_OPTION );
		if ( array_key_exists( 'allowed_for', $options ) && is_string( $options['allowed_for'] ) ) {
			$options['allowed_for'] = array(
				array( 'value' => $options['allowed_for'] ),
			);
			$this->save( $options );
		}

		$this->set_option( $options );
	}

	/**
	 * Save options
	 *
	 * @param array $options from request.
	 *
	 * @return bool|WP_Error
	 */
	public function save( array $options ) {
		$validated = $this->validate( $options );

		// Unknown keys (e.g. camelCase) would otherwise be dropped silently.
		if ( array() === $validated || array() !== array_diff_key( $options, self::DEFAULT_OPTION ) ) {
			return new WP_Error( 'invalid', __( 'Invalid Settings!', 'enable-cors' ) );
		}

		return update_site_option( self::KEY, array_merge( $this->get(), $validated ) );
	}

	/**
	 * Verify data before saving
	 *
	 * @param array $get_json_params from request.
	 *
	 * @return array of verified data
	 */
	private function validate( array $get_json_params ): array {
		$data = array_intersect_key( $get_json_params, self::DEFAULT_OPTION );

		array_walk(
			$data,
			function ( &$value, $key ): void {
				switch ( $key ) {
					case 'allowed_for':
						$value = is_array( $value )
							? array_filter( $value, static fn( $origin ) => is_array( $origin ) && is_string( $origin['value'] ?? null ) )
							: array();
						if ( in_array( '*', array_column( $value, 'value' ), true ) ) {
							$value = self::DEFAULT_OPTION['allowed_for'];
							break;
						}

						$value = array() === $value
							? null
							: array_map( $this->prepend_value( ... ), $value );
						break;
					case 'allowed_methods':
						if ( ! is_array( $value ) ) {
							$value = self::DEFAULT_OPTION['allowed_methods'];
						}

						$value = array_map( sanitize_text_field( ... ), array_filter( array_intersect( $value, self::METHODS ) ) );
						break;
					case 'allowed_header':
						if ( ! is_array( $value ) ) {
							$value = self::DEFAULT_OPTION['allowed_header'];
						}

						$value = array_map( sanitize_text_field( ... ), array_filter( array_intersect( $value, self::HEADERS ) ) );
						break;
					default:
						$value = is_scalar( $value ) && (bool) sanitize_text_field( (string) $value );
						break;
				}
			}
		);

		return array_filter( $data, static fn( $value ) => null !== $value );
	}

	/**
	 * Set options
	 *
	 * @param array $options from request.
	 */
	private function set_option( array $options ): void {
		$this->enable            = array_key_exists( 'enable', $options ) ? $options['enable'] : self::DEFAULT_OPTION['enable'];
		$this->allow_font        = array_key_exists( 'allow_font', $options ) ? $options['allow_font'] : self::DEFAULT_OPTION['allow_font'];
		$this->allow_image       = array_key_exists( 'allow_image', $options ) ? $options['allow_image'] : self::DEFAULT_OPTION['allow_image'];
		$this->allow_credentials = array_key_exists( 'allow_credentials', $options ) ? $options['allow_credentials'] : self::DEFAULT_OPTION['allow_credentials'];
		$this->allowed_for       = array_key_exists( 'allowed_for', $options ) ? $options['allowed_for'] : self::DEFAULT_OPTION['allowed_for'];
		$this->allowed_methods   = array_key_exists( 'allowed_methods', $options ) ? $options['allowed_methods'] : self::DEFAULT_OPTION['allowed_methods'];
		$this->allowed_header    = array_key_exists( 'allowed_header', $options ) ? $options['allowed_header'] : self::DEFAULT_OPTION['allowed_header'];
	}

	/**
	 * Adds a default option.
	 */
	public static function add_default(): void {
		update_site_option( self::KEY, self::DEFAULT_OPTION );
		update_site_option( self::VKEY, VERSION );
	}

	/**
	 * Delete options
	 */
	public static function delete(): void {
		delete_site_option( self::KEY );
		delete_site_option( self::VKEY );
	}

	/**
	 * Should allow image for cors?
	 */
	public function should_allow_image(): bool {
		return $this->allow_image;
	}

	/**
	 * Should allow font for cors?
	 */
	public function should_allow_font(): bool {
		return $this->allow_font;
	}

	/**
	 * Gets the options array.
	 *
	 * @return array The options array.
	 */
	public function get(): array {
		return get_site_option( self::KEY, self::DEFAULT_OPTION );
	}

	/**
	 * Determines if the cors is enabled.
	 *
	 * @return bool The cors enable status.
	 */
	public function is_enable(): bool {
		return $this->enable;
	}

	/**
	 * Retrieves the allowed header.
	 *
	 * @return array The allowed header.
	 */
	public function get_allowed_header(): array {
		return $this->allowed_header;
	}

	/**
	 * Retrieves the allowed methods.
	 *
	 * @return array The list of allowed methods.
	 */
	public function get_allowed_methods(): array {
		return $this->allowed_methods;
	}

	/**
	 * Checks if credentials are allowed.
	 */
	public function should_allow_credentials(): bool {
		// Browsers reject credentials with a wildcard origin.
		return $this->allow_credentials && ! $this->has_wildcard();
	}

	/**
	 * Checks whether the current origin is allowed.
	 *
	 * @return bool Returns true if the current origin is allowed, false otherwise.
	 */
	public function is_current_origin_allowed(): bool {
		$origin = rtrim( get_http_origin(), '/' );

		return '' !== $origin && in_array( $origin, $this->get_domains(), true );
	}

	/**
	 * Check if the method is allowed.
	 */
	public function has_methods(): bool {
		return ! empty( $this->allowed_methods );
	}

	/**
	 * Checks if the object has a header.
	 *
	 * @return bool Returns true if the object has a header, false otherwise.
	 */
	public function has_header(): bool {
		return ! empty( $this->allowed_header );
	}

	/**
	 * Check if the method is allowed.
	 *
	 * @return bool Returns true if the method is allowed, false otherwise.
	 */
	public function is_method_allowed(): bool {
		$method = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) );

		// Preflights bypass method selection.
		return 'OPTIONS' === $method || in_array( $method, $this->allowed_methods, true );
	}

	/**
	 * Checks if the array of allowed websites contains a wildcard value.
	 */
	public function has_wildcard(): bool {
		$websites = array_column( $this->allowed_for, 'value' );

		return 1 === count( $websites ) && in_array( '*', $websites, true );
	}

	/**
	 * Retrieves the exact origins (scheme://host[:port]) from the 'allowed_for' property.
	 *
	 * Malformed entries are dropped; an empty branch in the .htaccess alternation would match any origin.
	 *
	 * @return array An array of origins built from the 'value' field of each website in the 'allowed_for' property.
	 */
	public function get_domains(): array {
		$origins = array_map(
			static function ( array $website ) {
				$parts = wp_parse_url( $website['value'] ?? '' );

				if ( empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
					return null;
				}

				return $parts['scheme'] . '://' . $parts['host'] . ( empty( $parts['port'] ) ? '' : ':' . $parts['port'] );
			},
			$this->allowed_for
		);

		return array_values( array_filter( $origins ) );
	}

	/**
	 * Updates the version number in the options table.
	 */
	public function update_version(): void {
		update_site_option( self::VKEY, VERSION );
	}

	/**
	 * Prepend value to url
	 *
	 * @param array $url from allowed_for.
	 *
	 * @return array of formatted url
	 */
	private function prepend_value( array $url ): array {
		if ( ! array_key_exists( 'value', $url ) ) {
			return $url;
		}

		if ( '*' === $url['value'] ) {
			return $url;
		}

		$url['value'] = sanitize_url( rtrim( $url['value'], '/' ) );

		return $url;
	}

	/**
	 * Retrieves the version number from the options table.
	 *
	 * @return string The version number stored in the options table associated with the VKEY.
	 */
	public function get_version(): string {
		return get_site_option( self::VKEY, '1.0.0' );
	}
}
