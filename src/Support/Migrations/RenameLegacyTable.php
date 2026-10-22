<?php
/**
 * Migration that renames a table from its old "rx_" name to the vendor-prefixed name.
 *
 * @package ReaderExperience\Support\Migrations
 * @author  Hans Schuijff <@hansschuijff>
 * @license GPL-2.0-only
 * @since   0.3.0
 */

declare( strict_types=1 );

namespace ReaderExperience\Support\Migrations;

use ReaderExperience\Contracts\MigrationInterface;
use wpdb;

use function esc_sql;

/**
 * Renames "{prefix}rx_<name>" to the current table name, when the old one exists and the new one does not.
 * On a fresh install neither exists yet or only the new one does, so this does nothing.
 */
final class RenameLegacyTable implements MigrationInterface {

	/**
	 * Constructor.
	 *
	 * @since 0.3.0
	 * @param string $id          Migration id (see MigrationInterface::id()).
	 * @param string $old_suffix  Old table name without the WordPress prefix, for example "rx_feedback".
	 * @param string $new_table   Current full table name, including the WordPress prefix.
	 */
	public function __construct(
		private readonly string $id,
		private readonly string $old_suffix,
		private readonly string $new_table
	) {}

	/**
	 * {@inheritDoc}
	 *
	 * @since 0.3.0
	 */
	public function id(): string {
		return $this->id;
	}

	/**
	 * Applies the migration.
	 *
	 * @since 0.3.0
	 * @param wpdb $db WordPress database object.
	 * @return void
	 */
	public function up( wpdb $db ): void {
		$old = $db->prefix . $this->old_suffix;
		if ( $old === $this->new_table || ! $this->exists( $db, $old ) || $this->exists( $db, $this->new_table ) ) {
			return;
		}

		// Table names cannot be prepared as values; both come from this plugin, never from input.
		$db->query( 'RENAME TABLE `' . esc_sql( $old ) . '` TO `' . esc_sql( $this->new_table ) . '`' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Whether a table exists.
	 *
	 * @param wpdb   $db    WordPress database object.
	 * @param string $table Full table name.
	 * @return bool
	 */
	private function exists( wpdb $db, string $table ): bool {
		return $table === $db->get_var( $db->prepare( 'SHOW TABLES LIKE %s', $db->esc_like( $table ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}
}
