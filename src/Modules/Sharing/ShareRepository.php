<?php

declare(strict_types=1);

namespace ReaderExperience\Modules\Sharing;

final class ShareRepository {

	public function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'rx_share_daily';
	}

	/**
	 * Eén klik erbij voor post_id + kanaal + vandaag. Geen persoonsgegevens in deze tabel.
	 */
	public function increment( int $postId, string $channel ): void {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$this->table()} (post_id, channel, day, count) VALUES (%d, %s, %s, 1)
				 ON DUPLICATE KEY UPDATE count = count + 1",
				$postId,
				$channel,
				current_time( 'Y-m-d' )
			)
		);
	}

	/** @return array<string,int> Totaal per kanaal, aflopend, alleen voor gebruik in de admin. */
	public function totalsForPost( int $postId ): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT channel, SUM(count) AS total FROM {$this->table()} WHERE post_id = %d GROUP BY channel ORDER BY total DESC",
				$postId
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
