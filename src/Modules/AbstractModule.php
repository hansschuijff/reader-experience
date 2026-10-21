<?php
/**
 * Base class for modules.
 *
 * @package ReaderExperience\Modules
 * @author  Hans Schuijff <@hansschuijff>
 * @license GPL-2.0-only
 * @since   0.1.0
 */

declare( strict_types=1 );

namespace ReaderExperience\Modules;

use ReaderExperience\Contracts\ModuleInterface;

/**
 * Defaults for a module: no hooks, no blocks, no migrations.
 */
abstract class AbstractModule implements ModuleInterface {

	/**
	 * {@inheritDoc}
	 *
	 * @since 0.1.0
	 */
	public function register(): void {}

	/**
	 * {@inheritDoc}
	 *
	 * @since 0.1.0
	 */
	public function blocks(): array {
		return array();
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 0.1.0
	 */
	public function migrations(): array {
		return array();
	}
}
