<?php

declare(strict_types=1);

namespace ReaderExperience\Contracts;

interface ModuleInterface {

	/** Unieke, stabiele sleutel, bijvoorbeeld "reading". */
	public function id(): string;

	/** Hooks, REST-routes en dergelijke koppelen. Blokken worden apart geregistreerd. */
	public function register(): void;

	/**
	 * Slugs van de blokken van deze module, gelijk aan de mapnaam in blocks/.
	 *
	 * @return string[]
	 */
	public function blocks(): array;

	/**
	 * Migratieklassen van deze module. Wordt gebruikt vanaf de Rating-module.
	 *
	 * @return string[]
	 */
	public function migrations(): array;
}
