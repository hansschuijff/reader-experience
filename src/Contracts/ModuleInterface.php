<?php
/**
 * Contract for a plugin module.
 *
 * @package ReaderExperience\Contracts
 * @author  Hans Schuijff <@hansschuijff>
 * @license GPL-2.0-only
 * @since   0.1.0
 */

declare( strict_types=1 );

namespace ReaderExperience\Contracts;

/**
 * A module bundles hooks, REST routes, blocks and migrations of one feature.
 */
interface ModuleInterface {

	/**
	 * Unique, stable key, for example "reading".
	 *
	 * @since 0.1.0
	 * @return string
	 */
	public function id(): string;

	/**
	 * Attaches hooks, REST routes and the like. Blocks are registered separately.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register(): void;

	/**
	 * Slugs of the blocks of this module, equal to the folder name in blocks/.
	 *
	 * @since 0.1.0
	 * @return string[]
	 */
	public function blocks(): array;

	/**
	 * Migration objects of this module.
	 *
	 * @since 0.1.0
	 * @return MigrationInterface[]
	 */
	public function migrations(): array;
}
