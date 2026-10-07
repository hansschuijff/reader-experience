<?php

declare(strict_types=1);

namespace ReaderExperience\Modules\Reading;

final class ReadingTime {

	private const DEFAULT_WPM = 225;

	public function wordCount( string $content ): int {
		$text  = wp_strip_all_tags( strip_shortcodes( $content ) );
		$words = preg_split( '/\s+/u', trim( $text ), -1, PREG_SPLIT_NO_EMPTY );
		return is_array( $words ) ? count( $words ) : 0;
	}

	public function minutes( string $content ): int {
		/** Woorden per minuut, aanpasbaar per site. */
		$wpm = max( 1, (int) apply_filters( 'reader_experience/words_per_minute', self::DEFAULT_WPM ) );
		return max( 1, (int) ceil( $this->wordCount( $content ) / $wpm ) );
	}
}
