<?php
/**
 * Plugin Name:       Reader Experience
 * Description:       Leesfuncties en interactie voor lange artikelen: inhoudsopgave, leestijd, delen en waardering.
 * Version:           0.1.0
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Text Domain:       reader-experience
 * Domain Path:       /languages
 */

declare(strict_types=1);

namespace ReaderExperience;

defined( 'ABSPATH' ) || exit;

const VERSION = '0.1.0';
const PLUGIN_FILE = __FILE__;

if ( is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
} else {
	// Fallback zonder Composer: eenvoudige PSR-4 autoloader.
	spl_autoload_register(
		static function ( string $class ): void {
			$prefix = __NAMESPACE__ . '\\';
			if ( ! str_starts_with( $class, $prefix ) ) {
				return;
			}
			$relative = str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) );
			$file     = __DIR__ . '/src/' . $relative . '.php';
			if ( is_readable( $file ) ) {
				require_once $file;
			}
		}
	);
}

// Start op de standaard plugins_loaded-prioriteit. Er is geen afhankelijkheid
// van de core-plugin: beide starten onafhankelijk van elkaar.
add_action(
	'plugins_loaded',
	static function (): void {
		Plugin::boot( __FILE__ );
	}
);

register_activation_hook(
	__FILE__,
	static function (): void {
		Plugin::onActivate( __FILE__ );
	}
);
