<?php

declare(strict_types=1);

namespace ReaderExperience\Support;

/**
 * Korte, anonieme snelheidsbegrenzing op basis van transients. Geen IP-adressen worden
 * opgeslagen: de aanroeper geeft een eigen sleutel (bijvoorbeeld al gehasht) mee.
 */
final class RateLimiter {

	public function tooMany( string $bucket, int $limit, int $windowSeconds ): bool {
		$key   = 'rx_rl_' . md5( $bucket );
		$count = (int) get_transient( $key );

		if ( $count >= $limit ) {
			return true;
		}

		set_transient( $key, $count + 1, $windowSeconds );
		return false;
	}

	/** Een korte, anonieme vingerafdruk voor rate limiting. Nooit opgeslagen, alleen gehasht. */
	public function fingerprint(): string {
		$ip = (string) ( $_SERVER['REMOTE_ADDR'] ?? '' );
		$ua = (string) ( $_SERVER['HTTP_USER_AGENT'] ?? '' );
		return md5( $ip . '|' . $ua );
	}
}
