<?php
/**
 * Validation of values that end up inside a style attribute.
 *
 * @package ReaderExperience\Support
 * @author  Hans Schuijff <@hansschuijff>
 * @license GPL-2.0-only
 * @since   0.2.0
 */

declare( strict_types=1 );

namespace ReaderExperience\Support;

use function preg_match;
use function trim;

/**
 * Escaping with esc_attr() makes a value safe for an attribute, not for CSS: a "colour" such as
 * "red;background:url(…)" would still add declarations to the style attribute. Block attributes can
 * be set by anyone who may edit a post, so colours are checked against a strict whitelist first.
 */
final class CssValue {

	/**
	 * Returns the colour when it is a plain CSS colour, otherwise an empty string.
	 *
	 * Accepts hex (#rgb, #rrggbb, with alpha), keywords (red, currentcolor), rgb()/hsl() with
	 * numbers, and var(--custom-property), which is what theme palette colours become.
	 *
	 * @since 0.2.0
	 * @param string $value Value from a block attribute.
	 * @return string
	 */
	public static function color( string $value ): string {
		$value = trim( $value );

		$patterns = array(
			'/^#[0-9a-f]{3,8}$/i',
			'/^[a-z]{3,30}$/i',
			'/^(?:rgb|hsl)a?\(\s*[0-9.]+(?:%|deg)?(?:\s*[,\s\/]\s*[0-9.]+(?:%|deg)?){2,3}\s*\)$/i',
			'/^var\(\s*--[a-z0-9_-]+\s*\)$/i',
		);

		foreach ( $patterns as $pattern ) {
			if ( 1 === preg_match( $pattern, $value ) ) {
				return $value;
			}
		}

		return '';
	}
}
