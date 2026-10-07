<?php

declare(strict_types=1);

namespace ReaderExperience\Modules\Sharing;

use ReaderExperience\Modules\AbstractModule;
use ReaderExperience\Modules\Sharing\Migrations\CreateShareDailyTable;

final class SharingModule extends AbstractModule {

	public function id(): string {
		return 'sharing';
	}

	public function register(): void {
		( new ShareController() )->register();
	}

	public function blocks(): array {
		return array( 'share' );
	}

	public function migrations(): array {
		return array( new CreateShareDailyTable() );
	}
}
