<?php

declare(strict_types=1);

namespace ReaderExperience\Modules\Sharing;

use ReaderExperience\Rest\AbstractController;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Telt klikken, geen gelukte shares. Verstuurd met sendBeacon, dus geen antwoordinhoud nodig.
 */
final class ShareController extends AbstractController {

	private ChannelRegistry $channels;
	private ShareRepository $repository;

	public function __construct() {
		$this->channels   = new ChannelRegistry();
		$this->repository = new ShareRepository();
	}

	public function registerRoutes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/share',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle' ),
				'permission_callback' => $this->publicPermission(),
				'args'                => array(
					'post_id' => array(
						'required'          => true,
						'type'              => 'integer',
						'validate_callback' => static fn ( $v ): bool => is_numeric( $v ) && (int) $v > 0,
					),
					'channel' => array(
						'required'          => true,
						'type'              => 'string',
						'validate_callback' => fn ( $v ): bool => is_string( $v ) && $this->channels->isValid( $v ),
					),
				),
			)
		);
	}

	public function handle( WP_REST_Request $request ) {
		$postId  = (int) $request->get_param( 'post_id' );
		$channel = (string) $request->get_param( 'channel' );

		if ( 'publish' !== get_post_status( $postId ) ) {
			return new WP_Error( 'rx_invalid_post', __( 'Onbekend artikel.', 'reader-experience' ), array( 'status' => 404 ) );
		}

		if ( $this->alreadyCountedRecently( $postId, $channel ) ) {
			return new WP_REST_Response( null, 204 );
		}

		$this->repository->increment( $postId, $channel );
		return new WP_REST_Response( null, 204 );
	}

	/**
	 * Lichte, korte deduplicatie tegen dubbelklikken en verversen, zonder IP-adressen op te slaan:
	 * de sleutel wordt gehasht en de transient vervalt vanzelf.
	 */
	private function alreadyCountedRecently( int $postId, string $channel ): bool {
		$fingerprint = (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) . (string) ( $_SERVER['HTTP_USER_AGENT'] ?? '' );
		$key         = 'rx_share_' . md5( $postId . '|' . $channel . '|' . $fingerprint );

		if ( get_transient( $key ) ) {
			return true;
		}

		set_transient( $key, 1, MINUTE_IN_SECONDS * 10 );
		return false;
	}
}
