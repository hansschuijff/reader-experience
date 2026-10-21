<?php
/**
 * Plugin Name:       Reader Experience
 * Plugin URI:        https://github.com/hansschuijff/reader-experience
 * Description:       Reading tools and interaction for long articles: table of contents, reading time, sharing and ratings.
 * Version:           0.2.0
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Author:            Hans Schuijff
 * Author URI:        https://dewitteprins.nl
 * License:           GPL-2.0-only
 * License URI:       https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain:       reader-experience
 * Domain Path:       /languages
 *
 * GitHub Plugin URI: https://github.com/hansschuijff/reader-experience
 * Primary Branch:    main
 * Release Asset:     true
 *
 * @package ReaderExperience
 * @author  Hans Schuijff <@hansschuijff>
 * @license GPL-2.0-only
 * @since   0.1.0
 */

declare( strict_types=1 );

namespace ReaderExperience;

use function add_action;
use function defined;
use function is_readable;
use function register_activation_hook;
use function spl_autoload_register;
use function str_replace;
use function str_starts_with;
use function strlen;
use function substr;

defined( 'ABSPATH' ) || exit;

const VERSION     = '0.2.0';
const PLUGIN_FILE = __FILE__;

if ( is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
} else {
	// Fallback without Composer: a simple PSR-4 autoloader.
	spl_autoload_register(
		static function ( string $class_name ): void {
			$prefix = __NAMESPACE__ . '\\';
			if ( ! str_starts_with( $class_name, $prefix ) ) {
				return;
			}
			$relative = str_replace( '\\', '/', substr( $class_name, strlen( $prefix ) ) );
			$file     = __DIR__ . '/src/' . $relative . '.php';
			if ( is_readable( $file ) ) {
				require_once $file;
			}
		}
	);
}

// Start on the default plugins_loaded priority. There is no dependency on the core plugin:
// both start independently of each other.
add_action(
	'plugins_loaded',
	static function (): void {
		Plugin::boot( __FILE__ );
	}
);

register_activation_hook(
	__FILE__,
	static function (): void {
		Plugin::on_activate( __FILE__ );
	}
);
