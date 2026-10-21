<?php
/**
 * Migration runner.
 *
 * @package ReaderExperience\Support
 * @author  Hans Schuijff <@hansschuijff>
 * @license GPL-2.0-only
 * @since   0.1.0
 */

declare( strict_types=1 );

namespace ReaderExperience\Support;

use ReaderExperience\Contracts\MigrationInterface;

/**
 * Simple migration runner. Every migration runs at most once, tracked in an option.
 * No core with its own migration system is available, so for now this belongs to the plugin.
 */
final class Migrator {

	/**
	 * Name of the option that holds the ids of the migrations that have run.
	 *
	 * @var string
	 */
	private const OPTION = 'reader_experience_migrations';

	/**
	 * Runs all migrations that have not run yet.
	 *
	 * @since 0.1.0
	 * @param MigrationInterface[] $migrations Migrations, in the order they should run.
	 * @return void
	 */
	public function run( array $migrations ): void {
		if ( ! $migrations ) {
			return;
		}

		global $wpdb;
		$done = get_option( self::OPTION, array() );
		if ( ! is_array( $done ) ) {
			$done = array();
		}

		$changed = false;
		foreach ( $migrations as $migration ) {
			if ( ! $migration instanceof MigrationInterface || in_array( $migration->id(), $done, true ) ) {
				continue;
			}
			$migration->up( $wpdb );
			$done[]  = $migration->id();
			$changed = true;
		}

		if ( $changed ) {
			update_option( self::OPTION, $done, false );
		}
	}
}
