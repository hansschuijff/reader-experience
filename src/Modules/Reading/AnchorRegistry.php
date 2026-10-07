<?php

declare(strict_types=1);

namespace ReaderExperience\Modules\Reading;

/**
 * Deelt unieke ankers uit, in volgorde. Dezelfde volgorde en dezelfde regels worden
 * gebruikt bij het renderen van koppen en bij het opbouwen van de inhoudsopgave,
 * zodat beide op dezelfde id's uitkomen.
 */
final class AnchorRegistry {

	/** @var array<string,true> */
	private array $used = array();

	public function reset(): void {
		$this->used = array();
	}

	public function claim( ?string $existing, string $text ): string {
		if ( is_string( $existing ) && '' !== $existing ) {
			$this->used[ $existing ] = true;
			return $existing;
		}

		$base = sanitize_title( $text );
		if ( '' === $base ) {
			$base = 'sectie';
		}

		$slug = $base;
		$n    = 1;
		while ( isset( $this->used[ $slug ] ) ) {
			++$n;
			$slug = $base . '-' . $n;
		}

		$this->used[ $slug ] = true;
		return $slug;
	}
}
