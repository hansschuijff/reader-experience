<?php
/**
 * Locale fallback for the bundled translations.
 *
 * @package ReaderExperience\Support
 * @author  Hans Schuijff <@hansschuijff>
 * @license GPL-2.0-only
 * @since   0.2.0
 */

declare( strict_types=1 );

namespace ReaderExperience\Support;

use function add_filter;
use function basename;
use function determine_locale;
use function dirname;
use function glob;
use function is_readable;
use function preg_match;
use function preg_quote;
use function preg_replace;
use function rtrim;
use function str_starts_with;
use function strtok;
use function substr;

/**
 * Bundled translations are looked up by the exact locale. A site in "Nederlands (Formeel)"
 * (nl_NL_formal) or Belgian Dutch (nl_BE) would therefore get English strings, because the plugin
 * only ships nl_NL. This class points those locales at the best matching bundled file: the same
 * language, any region or variant. Exact matches always win.
 */
final class LocaleFallback {

	/**
	 * Constructor.
	 *
	 * @param string $domain        Text domain.
	 * @param string $languages_dir Absolute path of the folder with the bundled translations.
	 */
	public function __construct(
		private readonly string $domain,
		private readonly string $languages_dir
	) {}

	/**
	 * Attaches the hooks.
	 *
	 * @since 0.2.0
	 * @return void
	 */
	public function register(): void {
		add_filter( 'plugin_locale', array( $this, 'filter_locale' ), 10, 2 );
		add_filter( 'load_script_translation_file', array( $this, 'filter_script_file' ), 10, 3 );
	}

	/**
	 * Replaces the locale by a bundled one when there is no file for the exact locale.
	 *
	 * @since 0.2.0
	 * @param string $locale Locale of the current request.
	 * @param string $domain Text domain being loaded.
	 * @return string
	 */
	public function filter_locale( $locale, $domain = '' ) {
		if ( $this->domain !== $domain ) {
			return $locale;
		}

		return $this->best_locale( (string) $locale );
	}

	/**
	 * Same for the JSON files of the editor scripts.
	 *
	 * @since 0.2.0
	 * @param string|false $file   Path of the JSON file WordPress is about to load.
	 * @param string       $handle Script handle.
	 * @param string       $domain Text domain.
	 * @return string|false
	 */
	public function filter_script_file( $file, $handle = '', $domain = '' ) {
		if ( $this->domain !== $domain || ! $file || is_readable( $file ) ) {
			return $file;
		}

		$locale = determine_locale();
		$best   = $this->best_locale( $locale );
		$prefix = $this->domain . '-' . $locale . '-';
		$name   = basename( $file );

		if ( $best === $locale || ! str_starts_with( $name, $prefix ) ) {
			return $file;
		}

		$alternative = dirname( $file ) . '/' . $this->domain . '-' . $best . '-' . substr( $name, strlen( $prefix ) );

		return is_readable( $alternative ) ? $alternative : $file;
	}

	/**
	 * Finds the locale to load: the exact one if a file exists, otherwise another region or
	 * variant of the same language, otherwise the locale unchanged.
	 *
	 * @param string $locale Requested locale, for example "nl_NL_formal".
	 * @return string
	 */
	private function best_locale( string $locale ): string {
		$dir = rtrim( $this->languages_dir, '/' );

		if ( '' === $locale || is_readable( "{$dir}/{$this->domain}-{$locale}.mo" ) ) {
			return $locale;
		}

		// Only the variant removed: nl_NL_formal becomes nl_NL.
		$base = (string) preg_replace( '/_(?:in)?formal$/', '', $locale );
		if ( $base !== $locale && is_readable( "{$dir}/{$this->domain}-{$base}.mo" ) ) {
			return $base;
		}

		// Another region of the same language: nl_BE becomes nl_NL.
		$language = (string) strtok( $locale, '_' );
		$matches  = glob( "{$dir}/{$this->domain}-{$language}_*.mo" );
		if ( $matches ) {
			$pattern = '/^' . preg_quote( $this->domain, '/' ) . '-(.+)\.mo$/';
			if ( preg_match( $pattern, basename( $matches[0] ), $found ) ) {
				return $found[1];
			}
		}

		return $locale;
	}
}
