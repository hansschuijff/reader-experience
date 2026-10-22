<?php
/**
 * Reading time calculation.
 *
 * @package ReaderExperience\Modules\Reading
 * @author  Hans Schuijff <@hansschuijff>
 * @license GPL-2.0-only
 * @since   0.1.0
 */

declare( strict_types=1 );

namespace ReaderExperience\Modules\Reading;

use function apply_filters;
use function ceil;
use function count;
use function is_array;
use function max;
use function mb_strlen;
use function preg_split;
use function strip_shortcodes;
use function trim;
use function wp_strip_all_tags;

/**
 * Counts words and converts that to minutes of reading.
 */
final class ReadingTime {

	/**
	 * Default reading speed in words per minute.
	 *
	 * @var int
	 */
	private const DEFAULT_WPM = 225;

	/**
	 * Number of words in the content, without tags and shortcodes.
	 *
	 * @since 0.1.0
	 * @param string $content Post content.
	 * @return int
	 */
	public function word_count( string $content ): int {
		$text  = wp_strip_all_tags( strip_shortcodes( $content ) );
		$words = preg_split( '/\s+/u', trim( $text ), -1, PREG_SPLIT_NO_EMPTY );
		return is_array( $words ) ? count( $words ) : 0;
	}

	/**
	 * Number of characters in the content, without tags and shortcodes, spaces included.
	 *
	 * @since 0.3.0
	 * @param string $content Post content.
	 * @return int
	 */
	public function character_count( string $content ): int {
		return mb_strlen( trim( wp_strip_all_tags( strip_shortcodes( $content ) ) ) );
	}

	/**
	 * Estimated reading time in whole minutes, at least one.
	 *
	 * @since 0.1.0
	 * @param string $content Post content.
	 * @return int
	 */
	public function minutes( string $content ): int {
		/**
		 * Filters the reading speed in words per minute.
		 *
		 * @since 0.1.0
		 *
		 * @param int $wpm Words per minute. Default 225.
		 */
		$wpm = max( 1, (int) apply_filters( 'reader_experience/words_per_minute', self::DEFAULT_WPM ) );
		return max( 1, (int) ceil( $this->word_count( $content ) / $wpm ) );
	}
}
