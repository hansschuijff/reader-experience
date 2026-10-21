<?php
/**
 * Base class for REST controllers.
 *
 * @package ReaderExperience\Rest
 * @author  Hans Schuijff <@hansschuijff>
 * @license GPL-2.0-only
 * @since   0.1.0
 */

declare( strict_types=1 );

namespace ReaderExperience\Rest;

/**
 * Shared plumbing for the REST controllers of the plugin (namespace rx/v1).
 */
abstract class AbstractController {

	/**
	 * REST namespace of all routes of this plugin.
	 *
	 * @var string
	 */
	protected const NAMESPACE = 'rx/v1';

	/**
	 * Hooks the route registration into rest_api_init.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Registers the routes of the controller.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	abstract public function register_routes(): void;

	/**
	 * Permission callback for anonymous write endpoints: no nonce (pages can be cached), so
	 * explicitly open to everyone. The endpoints themselves must stay narrow and well validated.
	 *
	 * @since 0.1.0
	 * @return callable
	 */
	protected function public_permission(): callable {
		return static fn (): bool => true;
	}
}
