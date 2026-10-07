<?php

declare(strict_types=1);

namespace ReaderExperience\Modules\Bar;

use ReaderExperience\Modules\AbstractModule;

final class BarModule extends AbstractModule {

	public function id(): string {
		return 'bar';
	}

	public function blocks(): array {
		return array( 'interaction-bar', 'comments-link', 'toc-open' );
	}
}
