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

	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/share',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle' ),
				'permission_callback' => $this->public_permission(),
				'args'                => array(
					'post_id' => array(
						'required'          => true,
						'type'              => 'integer',
						'validate_callback' => static fn ( $v ): bool => is_numeric( $v ) && (int) $v > 0,
					),
					'channel' => array(
						'required'          => true,
						'type'              => 'string',
						'validate_callback' => fn ( $v ): bool => is_string( $v ) && $this->channels->is_valid( $v ),
					),
				),
			)
		);
	}

	public function handle( WP_REST_Request $request ) {
		$post_id  = (int) $request->get_param( 'post_id' );
		$channel = (string) $request->get_param( 'channel' );

		if ( 'publish' !== get_post_status( $post_id ) ) {
			return new WP_Error( 'rx_invalid_post', __( 'Onbekend artikel.', 'reader-experience' ), array( 'status' => 404 ) );
		}

		if ( $this->already_counted_recently( $post_id, $channel ) ) {
			return new WP_REST_Response( null, 204 );
		}

		$this->repository->increment( $post_id, $channel );
		return new WP_REST_Response( null, 204 );
	}

	/**
	 * Lichte, korte deduplicatie tegen dubbelklikken en verversen, zonder IP-adressen op te slaan:
	 * de sleutel wordt gehasht en de transient vervalt vanzelf.
	 */
	private function already_counted_recently( int $post_id, string $channel ): bool {
		$fingerprint = (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) . (string) ( $_SERVER['HTTP_USER_AGENT'] ?? '' );
		$key         = 'rx_share_' . md5( $post_id . '|' . $channel . '|' . $fingerprint );

		if ( get_transient( $key ) ) {
			return true;
		}

		set_transient( $key, 1, MINUTE_IN_SECONDS * 10 );
		return false;
	}
}
