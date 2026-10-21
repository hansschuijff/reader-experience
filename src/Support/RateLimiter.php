<?php
/**
 * Anonymous rate limiting.
 *
 * @package ReaderExperience\Support
 * @author  Hans Schuijff <@hansschuijff>
 * @license GPL-2.0-only
 * @since   0.1.0
 */

declare( strict_types=1 );

namespace ReaderExperience\Support;

use function get_transient;
use function md5;
use function set_transient;

/**
 * Short, anonymous rate limiting based on transients. No IP addresses are stored: the caller
 * passes its own key (already hashed, for example).
 */
final class RateLimiter {

	/**
	 * Counts one attempt and tells whether the limit for this bucket has been reached.
	 *
	 * @since 0.1.0
	 * @param string $bucket         Name of the counter, for example "rating:12:abc".
	 * @param int    $limit          Maximum number of attempts in the window.
	 * @param int    $window_seconds Length of the window in seconds.
	 * @return bool True when there have been too many attempts.
	 */
	public function too_many( string $bucket, int $limit, int $window_seconds ): bool {
		$key   = 'rx_rl_' . md5( $bucket );
		$count = (int) get_transient( $key );

		if ( $count >= $limit ) {
			return true;
		}

		set_transient( $key, $count + 1, $window_seconds );
		return false;
	}

	/**
	 * A short, anonymous fingerprint for rate limiting. Never stored, only hashed.
	 *
	 * @since 0.1.0
	 * @return string
	 */
	public function fingerprint(): string {
		$ip = (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$ua = (string) ( $_SERVER['HTTP_USER_AGENT'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		return md5( $ip . '|' . $ua );
	}
}
