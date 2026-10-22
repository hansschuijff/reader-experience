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

use function apply_filters;
use function get_transient;
use function in_array;
use function is_array;
use function max;
use function md5;
use function set_transient;
use function time;
use function wp_get_environment_type;

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
		/**
		 * Filters whether rate limiting is active. It is off on local and development
		 * environments (see wp_get_environment_type()), where one developer clicks the same
		 * thumb many times and would otherwise be throttled silently.
		 *
		 * @since 0.2.0
		 *
		 * @param bool $enabled Whether to rate limit.
		 */
		$enabled = (bool) apply_filters(
			'reader_experience/rate_limit_enabled',
			! in_array( wp_get_environment_type(), array( 'local', 'development' ), true )
		);

		if ( ! $enabled ) {
			return false;
		}

		$key  = 'rx_rl_' . md5( $bucket );
		$now  = time();
		$data = get_transient( $key );

		// A fixed window that starts at the first attempt. Refreshing the expiry on every attempt
		// (as an earlier version did) kept a visitor blocked for as long as they kept trying.
		if ( ! is_array( $data ) || ! isset( $data['count'], $data['reset'] ) || $data['reset'] <= $now ) {
			$data = array(
				'count' => 0,
				'reset' => $now + $window_seconds,
			);
		}

		if ( $data['count'] >= $limit ) {
			return true;
		}

		++$data['count'];
		set_transient( $key, $data, max( 1, $data['reset'] - $now ) );
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
