<?php
/**
 * Migration: comment and contact fields on the feedback table.
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
 * Adds the fields for a posted comment and a contact request.
 *
 * The function dbDelta() compares the complete desired table definition with what is there and only changes
 * what is missing or different: existing rows stay, so this is safe to run on a table that
 * already exists from CreateFeedbackTable.
 */
final class AddCommentFieldsToFeedbackTable implements MigrationInterface {

	/**
	 * {@inheritDoc}
	 *
	 * @since 0.1.0
	 */
	public function id(): string {
		return '2026_09_30_feedback_comment_fields';
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
