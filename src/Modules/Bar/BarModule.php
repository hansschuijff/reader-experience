<?php
/**
 * Interaction bar module.
 *
 * @package ReaderExperience\Modules\Bar
 * @author  Hans Schuijff <@hansschuijff>
 * @license GPL-2.0-only
 * @since   0.1.0
 */

declare( strict_types=1 );

namespace ReaderExperience\Modules\Bar;

use ReaderExperience\Modules\AbstractModule;

/**
 * The interaction bar and the buttons that live in it. Only blocks, no PHP behaviour.
 */
final class BarModule extends AbstractModule {

	/**
	 * {@inheritDoc}
	 *
	 * @since 0.1.0
	 */
	public function id(): string {
		return 'bar';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 0.1.0
	 */
	public function blocks(): array {
		return array( 'interaction-bar', 'comments-link', 'toc-open', 'quick-link' );
	}
}
