<?php

declare(strict_types=1);

namespace ReaderExperience\Modules\Rating\Migrations;

use ReaderExperience\Contracts\MigrationInterface;
use ReaderExperience\Modules\Rating\FeedbackRepository;

final class CreateFeedbackTable implements MigrationInterface {

	public function id(): string {
		return '2026_09_29_feedback';
	}

	public function up( \wpdb $db ): void {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table   = ( new FeedbackRepository() )->table();
		$collate = $db->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			post_id BIGINT UNSIGNED NOT NULL,
			value VARCHAR(10) NOT NULL,
			text TEXT NULL,
			token_hash CHAR(64) NULL,
			token_expires DATETIME NULL,
			created DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY post_id (post_id),
			KEY created (created)
		) {$collate};";

		dbDelta( $sql );
	}
}
