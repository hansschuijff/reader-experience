<?php

declare(strict_types=1);

namespace ReaderExperience\Modules\Reading;

use ReaderExperience\Modules\AbstractModule;

final class ReadingModule extends AbstractModule {

	public function id(): string {
		return 'reading';
	}

	public function register(): void {
		( new HeadingIndexer() )->register();
		( new HeadingLinkAssets() )->register();
	}

	public function blocks(): array {
		return array( 'reading-time', 'reading-progress', 'toc' );
	}
}
