<?php

declare(strict_types=1);

namespace ReaderExperience\Contracts;

interface MigrationInterface {

	/** Stabiele, oplopende sleutel, bijvoorbeeld "2026_09_29_share_daily". Wijzig nooit een bestaande. */
	public function id(): string;

	public function up( \wpdb $db ): void;
}
