<?php
/**
 * Reading module.
 *
 * @package ReaderExperience\Modules\Reading
 * @author  Hans Schuijff <@hansschuijff>
 * @license GPL-2.0-only
 * @since   0.1.0
 */

declare( strict_types=1 );

namespace ReaderExperience\Modules\Reading;

use ReaderExperience\Modules\AbstractModule;

/**
 * Reading time, reading progress, table of contents and heading anchors.
 */
final class ReadingModule extends AbstractModule {

	/**
	 * {@inheritDoc}
	 *
	 * @since 0.1.0
	 */
	public function id(): string {
		return 'reading';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 0.1.0
	 */
	public function register(): void {
		// One policy object for both: the ids and the assets must reach the same verdict.
		$anchors = new HeadingAnchors();

		( new HeadingIndexer( $anchors ) )->register();
		( new HeadingLinkAssets( $anchors ) )->register();
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 0.1.0
	 */
	public function blocks(): array {
		return array( 'reading-time', 'reading-progress', 'toc' );
	}
}
