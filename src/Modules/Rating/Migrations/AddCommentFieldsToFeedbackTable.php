<?php

declare(strict_types=1);

namespace ReaderExperience\Modules\Rating\Migrations;

use ReaderExperience\Contracts\MigrationInterface;
use ReaderExperience\Modules\Rating\FeedbackRepository;

/**
 * dbDelta() vergelijkt de volledige gewenste tabeldefinitie met wat er al staat en past
 * alleen aan wat ontbreekt of afwijkt: bestaande rijen blijven staan, dit is dus veilig
 * te draaien op een tabel die al bestaat uit CreateFeedbackTable.
 */
final class AddCommentFieldsToFeedbackTable implements MigrationInterface {

	public function id(): string {
		return '2026_09_30_feedback_comment_fields';
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
			comment_id BIGINT UNSIGNED NULL,
			wants_contact TINYINT(1) NOT NULL DEFAULT 0,
			contact_name VARCHAR(100) NULL,
			contact_email VARCHAR(100) NULL,
			created DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY post_id (post_id),
			KEY created (created)
		) {$collate};";

		dbDelta( $sql );
	}
}
