<?php

declare(strict_types=1);

namespace ReaderExperience\Blocks;

/**
 * Registreert blokken uit de buildmap op basis van hun block.json.
 * Alleen blokken van actieve modules worden doorgegeven.
 */
final class BlockRegistrar {

	public function __construct( private readonly string $buildDir ) {}

	/** @param string[] $slugs */
	public function register( array $slugs ): void {
		foreach ( $slugs as $slug ) {
			$dir = rtrim( $this->buildDir, '/' ) . '/' . $slug;

			if ( ! is_readable( $dir . '/block.json' ) ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( sprintf( 'Reader Experience: block.json ontbreekt voor "%s". Is npm run build uitgevoerd?', $slug ) );
				}
				continue;
			}

			register_block_type( $dir );
		}
	}
}
