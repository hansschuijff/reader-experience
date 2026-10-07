<?php

declare(strict_types=1);

namespace ReaderExperience\Support;

use ReaderExperience\Contracts\MigrationInterface;

/**
 * Simpele migratierunner. Elke migratie draait hooguit één keer, bijgehouden in een optie.
 * Geen core beschikbaar met een eigen migratiesysteem, dus dit is voorlopig van de plugin zelf.
 */
final class Migrator {

	private const OPTION = 'reader_experience_migrations';

	/** @param MigrationInterface[] $migrations */
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
