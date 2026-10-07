<?php

declare(strict_types=1);

namespace ReaderExperience\Modules;

use ReaderExperience\Contracts\ModuleInterface;

abstract class AbstractModule implements ModuleInterface {

	public function register(): void {}

	public function blocks(): array {
		return array();
	}

	public function migrations(): array {
		return array();
	}
}
