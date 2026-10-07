<?php

declare(strict_types=1);

namespace ReaderExperience\Modules\Sharing\Migrations;

use ReaderExperience\Contracts\MigrationInterface;
use ReaderExperience\Modules\Sharing\ShareRepository;

final class CreateShareDailyTable implements MigrationInterface {

	public function id(): string {
		return '2026_09_29_share_daily';
	}

	public function up( \wpdb $db ): void {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table   = ( new ShareRepository() )->table();
		$collate = $db->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			post_id BIGINT UNSIGNED NOT NULL,
			channel VARCHAR(20) NOT NULL,
			day DATE NOT NULL,
			count INT UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			UNIQUE KEY post_channel_day (post_id, channel, day)
		) {$collate};";

		dbDelta( $sql );
	}
}
