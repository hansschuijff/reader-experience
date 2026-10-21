<?php
/**
 * Contract for a database migration.
 *
 * @package ReaderExperience\Contracts
 * @author  Hans Schuijff <@hansschuijff>
 * @license GPL-2.0-only
 * @since   0.1.0
 */

declare( strict_types=1 );

namespace ReaderExperience\Contracts;

use wpdb;

/**
 * A migration runs at most once; the Migrator keeps track of that.
 */
interface MigrationInterface {

	/**
	 * Stable, ascending key, for example "2026_09_29_share_daily". Never change an existing one.
	 *
	 * @since 0.1.0
	 * @return string
	 */
	public function id(): string;

	/**
	 * Applies the migration.
	 *
	 * @since 0.1.0
	 * @param wpdb $db WordPress database object.
	 * @return void
	 */
	public function up( wpdb $db ): void;
}
