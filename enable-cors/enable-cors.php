<?php
/**
 * Enable CORS
 *
 * @package           Enable\Cors
 * @author            Dev Kabir
 * @copyright         2023 Dev Kabir
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Enable CORS
 * Plugin URI:        https://www.fiverr.com/share/7kXeLW
 * Description:       Enable Cross-Origin Resource Sharing for any or specific origin.
 * Version:           3.0.0
 * Requires at least: 6.5
 * Tested up to:      7.1
 * Requires PHP:      8.2.35
 * Author:            Dev Kabir
 * Author URI:        https://devkabir.github.io/
 * Text Domain:       enable-cors
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

/*
|--------------------------------------------------------------------------
| If this file is called directly, abort.
|--------------------------------------------------------------------------
*/
if ( ! defined( 'WPINC' ) ) {
	exit;
}

/*
|--------------------------------------------------------------------------
| Load class autoloader, constants.
|--------------------------------------------------------------------------
*/
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/constants.php';
use Enable\Cors\Plugin;

/*
|--------------------------------------------------------------------------
| Activation, deactivation and uninstall event.
|--------------------------------------------------------------------------
*/
register_activation_hook( __FILE__, Plugin::activate( ... ) );
register_deactivation_hook( __FILE__, Plugin::deactivate( ... ) );
register_uninstall_hook( __FILE__, array( Plugin::class, 'deactivate' ) );

/*
|--------------------------------------------------------------------------
| Start the plugin
|--------------------------------------------------------------------------
*/
add_action( 'plugins_loaded', Plugin::init( ... ) );
