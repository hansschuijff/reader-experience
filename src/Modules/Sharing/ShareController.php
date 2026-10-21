<?php
/**
 * REST controller for share clicks.
 *
 * @package ReaderExperience\Modules\Sharing
 * @author  Hans Schuijff <@hansschuijff>
 * @license GPL-2.0-only
 * @since   0.1.0
 */

declare( strict_types=1 );

namespace ReaderExperience\Modules\Sharing;

use ReaderExperience\Rest\AbstractController;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

use function __;
use function get_post_status;
use function get_transient;
use function is_numeric;
use function is_string;
use function md5;
use function register_rest_route;
use function set_transient;

/**
 * Counts clicks, not successful shares. Sent with sendBeacon, so no response body is needed.
 */
final class ShareController extends AbstractController {

	/**
	 * Share channels.
	 *
	 * @var ChannelRegistry
	 */
	private ChannelRegistry $channels;

	/**
	 * Database access.
	 *
	 * @var ShareRepository
	 */
	private ShareRepository $repository;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->channels   = new ChannelRegistry();
		$this->repository = new ShareRepository();
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 0.1.0
	 */
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
						'validate_callback' => static fn ( $value ): bool => is_numeric( $value ) && (int) $value > 0,
					),
					'channel' => array(
						'required'          => true,
						'type'              => 'string',
						'validate_callback' => fn ( $value ): bool => is_string( $value ) && $this->channels->is_valid( $value ),
					),
				),
			)
		);
	}

	/**
	 * Counts one click on a share channel.
	 *
	 * @since 0.1.0
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle( WP_REST_Request $request ) {
		$post_id = (int) $request->get_param( 'post_id' );
		$channel = (string) $request->get_param( 'channel' );

		if ( 'publish' !== get_post_status( $post_id ) ) {
			return new WP_Error( 'rx_invalid_post', __( 'Unknown article.', 'reader-experience' ), array( 'status' => 404 ) );
		}

		if ( $this->already_counted_recently( $post_id, $channel ) ) {
			return new WP_REST_Response( null, 204 );
		}

		$this->repository->increment( $post_id, $channel );
		return new WP_REST_Response( null, 204 );
	}

	/**
	 * Light, short deduplication against double clicks and refreshes, without storing IP
	 * addresses: the key is hashed and the transient expires by itself.
	 *
	 * @param int    $post_id Post id.
	 * @param string $channel Channel slug.
	 * @return bool True when this visitor already clicked this channel on this post recently.
	 */
	private function already_counted_recently( int $post_id, string $channel ): bool {
		$fingerprint = (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) . (string) ( $_SERVER['HTTP_USER_AGENT'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$key         = 'rx_share_' . md5( $post_id . '|' . $channel . '|' . $fingerprint );

		if ( get_transient( $key ) ) {
			return true;
		}

		set_transient( $key, 1, MINUTE_IN_SECONDS * 10 );
		return false;
	}
}
