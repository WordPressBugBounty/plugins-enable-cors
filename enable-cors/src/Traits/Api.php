<?php

namespace Enable\Cors\Traits;

/*
|--------------------------------------------------------------------------
| If this file is called directly, abort.
|--------------------------------------------------------------------------
*/
if ( ! defined( 'Enable\Cors\SLUG' ) ) {
	exit;
}

use WP_Error;
use const Enable\Cors\VERSION;

trait Api {
	/**
	 * The namespace of the API.
	 *
	 * @var string
	 */
	protected $namespace = 'enable-cors/v' . VERSION;

	/**
	 * Initial response data
	 *
	 * @var array
	 */
	private $response = array(
		'data'    => null,
		'message' => null,
		'success' => false,
	);


	/**
	 * Check if a given request has access to get data from custom table
	 */
	public function permissions_check(): WP_Error|true {
		// Settings live in `get_site_option`, so on multisite the gate must be network-wide.
		$capability = is_multisite() ? 'manage_network_options' : 'manage_options';

		if ( ! current_user_can( $capability ) ) {
			return new WP_Error(
				'forbidden',
				__( 'You are not allowed to access this endpoint.', 'enable-cors' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}
}
