<?php

namespace Enable\Cors;

/*
|--------------------------------------------------------------------------
| If this file is called directly, abort.
|--------------------------------------------------------------------------
*/
if ( ! defined( 'Enable\Cors\SLUG' ) ) {
	exit;
}

use Enable\Cors\Helpers\Option;
use const Enable\Cors\VERSION;

class Upgrade {

	/**
	 * Handles plugin upgrade routines based on version comparisons.
	 *
	 * @since 1.2.4
	 * @return void
	 */
	public static function run() {
		$option          = new Option();
		$current_version = $option->get_version();
		if ( ! version_compare( $current_version, self::get_version(), '<' ) ) {
			return;
		}
		// Run upgrade routines based on version comparison.
		self::upgrade();
		// Update current version.
		$option->update_version();
	}


	/**
	 * Upgrade routines for latest version.
	 *
	 * @since 1.2.4
	 */
	private static function upgrade(): void {
		switch ( VERSION ) {
			case '1.2.4':
				$option = new Option();
				$data   = $option->get();
				// Rename keys.
				$data['allow_font'] = $data['allowFont'];
				unset( $data['allowFont'] );

				$data['allow_image'] = $data['allowImage'];
				unset( $data['allowImage'] );

				$data['allow_credentials'] = $data['allowCredentials'];
				unset( $data['allowCredentials'] );

				$data['allowed_for'] = $data['allowedFor'];
				unset( $data['allowedFor'] );

				$data['allowed_methods'] = $data['allowedMethods'];
				unset( $data['allowedMethods'] );

				$data['allowed_header'] = $data['allowedHeader'];
				unset( $data['allowedHeader'] );

				$option->save( $data );
				break;
			default:
				break;
		}
	}

	/**
	 * Retrieve the current plugin version.
	 *
	 * @return string The current plugin version.
	 * @since 1.2.4
	 */
	private static function get_version(): string {
		// Define or retrieve the plugin version dynamically.
		return defined( 'Enable\Cors\VERSION' ) ? VERSION : '1.2.3'; // Fallback to last version.
	}
}
