<?php
/**
 * Database access for share counts.
 *
 * @package ReaderExperience\Modules\Sharing
 * @author  Hans Schuijff <@hansschuijff>
 * @license GPL-2.0-only
 * @since   0.1.0
 */

declare( strict_types=1 );

namespace ReaderExperience\Modules\Sharing;

use function current_time;

/**
 * Counts per post, channel and day. No personal data in this table.
 */
final class ShareRepository {

	/**
	 * Name of the table.
	 *
	 * @since 0.1.0
	 * @return string
	 */
	public function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'dwp_rx_share_daily';
	}

	/**
	 * One more click for post_id + channel + today.
	 *
	 * @since 0.1.0
	 * @param int    $post_id Post id.
	 * @param string $channel Channel slug.
	 * @return void
	 */
	public function increment( int $post_id, string $channel ): void {
		global $wpdb;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name only; values are placeholders.
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$this->table()} (post_id, channel, day, count) VALUES (%d, %s, %s, 1)
				 ON DUPLICATE KEY UPDATE count = count + 1",
				$post_id,
				$channel,
				current_time( 'Y-m-d' )
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Total clicks per channel for a post, highest first. Only for use in the admin.
	 *
	 * @since 0.1.0
	 * @param int $post_id Post id.
	 * @return array<string,int>
	 */
	public function totals_for_post( int $post_id ): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT channel, SUM(count) AS total FROM {$this->table()} WHERE post_id = %d GROUP BY channel ORDER BY total DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$post_id
			),
			ARRAY_A
		);

		$totals = array();
		foreach ( (array) $rows as $row ) {
			$totals[ (string) $row['channel'] ] = (int) $row['total'];
		}
		return $totals;
	}
}
