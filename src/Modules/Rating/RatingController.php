<?php
/**
 * REST controller for ratings.
 *
 * @package ReaderExperience\Modules\Rating
 * @author  Hans Schuijff <@hansschuijff>
 * @license GPL-2.0-only
 * @since   0.1.0
 */

declare( strict_types=1 );

namespace ReaderExperience\Modules\Rating;

use ReaderExperience\Rest\AbstractController;
use ReaderExperience\Support\RateLimiter;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

use function __;
use function bin2hex;
use function get_post_status;
use function in_array;
use function is_numeric;
use function is_string;
use function random_bytes;
use function register_rest_route;
use function strlen;
use function trim;

/**
 * Routes under dewitteprins/reader-experience/v1 for the rating flow: vote, optional comment, optional follow-up.
 */
final class RatingController extends AbstractController {

	/**
	 * Rating business logic.
	 *
	 * @var RatingService
	 */
	private RatingService $service;

	/**
	 * Rate limiter, used here only for the anonymous fingerprint.
	 *
	 * @var RateLimiter
	 */
	private RateLimiter $rate_limiter;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->service      = new RatingService();
		$this->rate_limiter = new RateLimiter();
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 0.1.0
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/rating',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'create' ),
				'permission_callback' => $this->public_permission(),
				'args'                => array(
					'post_id' => array(
						'required'          => true,
						'validate_callback' => static fn ( $value ): bool => is_numeric( $value ) && (int) $value > 0,
					),
					'value'   => array(
						'required'          => true,
						'validate_callback' => fn ( $value ): bool => is_string( $value ) && $this->service->is_valid_value( $value ),
					),
					'website' => array( 'required' => false ), // Honeypot: should always be empty.
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/rating/(?P<id>\d+)/text',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'attach_text' ),
				'permission_callback' => $this->public_permission(),
				'args'                => array(
					'id'      => array( 'validate_callback' => static fn ( $value ): bool => is_numeric( $value ) && (int) $value > 0 ),
					'token'   => array(
						'required'          => true,
						'validate_callback' => static fn ( $value ): bool => is_string( $value ) && strlen( $value ) === 32,
					),
					'text'    => array(
						'required'          => true,
						'validate_callback' => static fn ( $value ): bool => is_string( $value ) && '' !== trim( $value ),
					),
					'website' => array( 'required' => false ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/rating/(?P<id>\d+)/follow-up',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'resolve_follow_up' ),
				'permission_callback' => $this->public_permission(),
				'args'                => array(
					'id'      => array( 'validate_callback' => static fn ( $value ): bool => is_numeric( $value ) && (int) $value > 0 ),
					'token'   => array(
						'required'          => true,
						'validate_callback' => static fn ( $value ): bool => is_string( $value ) && strlen( $value ) === 32,
					),
					'action'  => array(
						'required'          => true,
						'validate_callback' => static fn ( $value ): bool => in_array( $value, array( 'publish', 'contact', 'skip' ), true ),
					),
					'name'    => array( 'required' => false ),
					'email'   => array( 'required' => false ),
					'website' => array( 'required' => false ),
				),
			)
		);
	}

	/**
	 * Step 1: stores a vote and returns the id and the single-use flow token.
	 *
	 * @since 0.1.0
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create( WP_REST_Request $request ) {
		$post_id = (int) $request->get_param( 'post_id' );

		if ( 'publish' !== get_post_status( $post_id ) ) {
			return new WP_Error( 'rx_invalid_post', __( 'Unknown article.', 'reader-experience' ), array( 'status' => 404 ) );
		}

		$result = $this->service->submit(
			$post_id,
			(string) $request->get_param( 'value' ),
			(string) $request->get_param( 'website' ),
			$this->rate_limiter->fingerprint()
		);

		if ( null === $result ) {
			// Spam or too many attempts: pretend it worked, without creating a real row.
			return new WP_REST_Response(
				array(
					'id'    => 0,
					'token' => bin2hex( random_bytes( 16 ) ),
				),
				201
			);
		}

		return new WP_REST_Response( $result, 201 );
	}

	/**
	 * Step 2: attaches the optional comment to the vote.
	 *
	 * @since 0.1.0
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response
	 */
	public function attach_text( WP_REST_Request $request ) {
		$this->service->attach_text(
			(int) $request->get_param( 'id' ),
			(string) $request->get_param( 'token' ),
			(string) $request->get_param( 'text' ),
			(string) $request->get_param( 'website' ),
			$this->rate_limiter->fingerprint()
		);

		return new WP_REST_Response( null, 204 );
	}

	/**
	 * Step 3: publish as a comment, request contact, or skip.
	 *
	 * @since 0.1.0
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response
	 */
	public function resolve_follow_up( WP_REST_Request $request ) {
		$this->service->resolve_follow_up(
			(int) $request->get_param( 'id' ),
			(string) $request->get_param( 'token' ),
			(string) $request->get_param( 'action' ),
			(string) ( $request->get_param( 'name' ) ?? '' ),
			(string) ( $request->get_param( 'email' ) ?? '' ),
			(string) $request->get_param( 'website' ),
			$this->rate_limiter->fingerprint()
		);

		return new WP_REST_Response( null, 204 );
	}
}
