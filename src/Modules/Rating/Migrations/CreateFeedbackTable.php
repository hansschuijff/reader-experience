<?php
/**
 * Migration: feedback table.
 *
 * @package ReaderExperience\Modules\Rating\Migrations
 * @author  Hans Schuijff <@hansschuijff>
 * @license GPL-2.0-only
 * @since   0.1.0
 */

declare( strict_types=1 );

namespace ReaderExperience\Modules\Rating\Migrations;

use ReaderExperience\Contracts\MigrationInterface;
use ReaderExperience\Modules\Rating\FeedbackRepository;
use wpdb;

/**
 * Creates the table for ratings and their optional comments.
 */
final class CreateFeedbackTable implements MigrationInterface {

	/**
	 * {@inheritDoc}
	 *
	 * @since 0.1.0
	 */
	public function id(): string {
		return '2026_09_29_feedback';
	}

	/**
	 * Applies the migration.
	 *
	 * @since 0.1.0
	 * @param wpdb $db WordPress database object.
	 * @return void
	 */
	public function up( wpdb $db ): void {
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
