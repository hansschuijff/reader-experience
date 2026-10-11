<?php

declare(strict_types=1);

namespace ReaderExperience\Modules\Reading;

use ReaderExperience\Modules\AbstractModule;

final class ReadingModule extends AbstractModule {

	public function id(): string {
		return 'reading';
	}

	public function register(): void {
		// Eén beleidsobject voor beide: de id's en de assets moeten over hetzelfde oordelen.
		$anchors = new HeadingAnchors();

		( new HeadingIndexer( $anchors ) )->register();
		( new HeadingLinkAssets( $anchors ) )->register();
	}

	public function blocks(): array {
		return array( 'reading-time', 'reading-progress', 'toc' );
	}
}
