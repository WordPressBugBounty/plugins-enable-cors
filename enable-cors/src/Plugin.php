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
use Enable\Cors\Helpers\Headers;
use Enable\Cors\Helpers\Htaccess;


final class Plugin {


	/**
	 * It will load during activation
	 */
	public static function activate(): void {
		// enable plugin's auto-update.
		self::enable_updates();
		// set default option.
		Option::add_default();
		// modify htaccess.
		( new Htaccess() )->modify();
	}

	/**
	 * Enable plugin's auto-update on activation.
	 */
	private static function enable_updates(): void {
		$auto_updates = (array) get_site_option( 'auto_update_plugins', array() );
		$plugin       = plugin_basename( FILE );
		if ( false === in_array( $plugin, $auto_updates, true ) ) {
			$auto_updates[] = $plugin;
			update_site_option( 'auto_update_plugins', $auto_updates );
		}
	}

	/**
	 * It will load during deactivation
	 */
	public static function deactivate(): void {
		self::disable_updates();
		( new Htaccess() )->restore();
		Option::delete();
	}

	/**
	 * Disable auto-update deactivation or uninstall
	 */
	private static function disable_updates(): void {
		$auto_updates = (array) get_site_option( 'auto_update_plugins', array() );
		$plugin       = plugin_basename( FILE );
		$update       = array_diff( $auto_updates, array( $plugin ) );
		update_site_option( 'auto_update_plugins', $update );
	}

	/**
	 * Plugin Initiator.
	 *
	 * This function is the entry point for the plugin. It sets up the
	 * admin page and adds the necessary hooks for the plugin to work.
	 */
	public static function init(): void {
		try {
			if ( is_admin() ) {
				// Add the settings link to the plugin actions.
				add_filter( 'plugin_action_links_' . plugin_basename( FILE ), self::actions( ... ) );
				// Initialize the admin page.
				new AdminPage();
			}

			// Initialize the Settings API.
			add_action(
				'rest_api_init',
				static function (): void {
					new SettingsApi();
				}
			);

			// Get the option instance.
			$option = new Option();

			if ( ! $option->is_enable() ) {
				return;
			}

			// Register before method rejection or preflight exit.
			add_action(
				'rest_api_init',
				static function (): void {
					remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );

					// Add a new filter to add the CORS headers.
					add_filter(
						'rest_pre_serve_request',
						static function ( $value ) {
							// Get the option instance.
							$option = new Option();

							// Add the CORS headers.
							Headers::add( $option );

							// Return the value.
							return $value;
						}
					);
				}
			);

			if ( ! $option->is_method_allowed() ) {
				return;
			}

			Headers::add( $option );

			// Run the upgrade routines.
			Upgrade::run();
		} catch ( \Throwable $throwable ) {
			error_log( $throwable->getMessage() ); // phpcs:ignore
		}
	}

	/**
	 * This PHP function adds a "Settings" link to an array of actions.
	 *
	 * @param array $actions collections.
	 */
	public static function actions( array $actions ): array {
		$actions[] = sprintf( '<a href="%s">%s</a>', esc_url( get_admin_url( null, 'admin.php?page=enable-cors' ) ), esc_attr__( 'Settings', 'enable-cors' ) );

		return $actions;
	}
}
