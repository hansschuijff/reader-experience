<?php

declare(strict_types=1);

namespace ReaderExperience\Rest;

abstract class AbstractController {

	protected const NAMESPACE = 'rx/v1';

	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'registerRoutes' ) );
	}

	abstract public function registerRoutes(): void;

	/**
	 * Anonieme write-endpoints: geen nonce (pagina's kunnen gecacht zijn), dus expliciet
	 * toegankelijk voor iedereen. De endpoints zelf moeten smal en goed gevalideerd blijven.
	 */
	protected function publicPermission(): callable {
		return static fn (): bool => true;
	}
}
