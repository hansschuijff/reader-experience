<?php

declare(strict_types=1);

namespace ReaderExperience\Modules\Rating;

use ReaderExperience\Rest\AbstractController;
use ReaderExperience\Support\RateLimiter;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class RatingController extends AbstractController {

	private RatingService $service;
	private RateLimiter $rateLimiter;

	public function __construct() {
		$this->service     = new RatingService();
		$this->rateLimiter = new RateLimiter();
	}

	public function registerRoutes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/rating',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'create' ),
				'permission_callback' => $this->publicPermission(),
				'args'                => array(
					'post_id' => array(
						'required'          => true,
						'validate_callback' => static fn ( $v ): bool => is_numeric( $v ) && (int) $v > 0,
					),
					'value'   => array(
						'required'          => true,
						'validate_callback' => fn ( $v ): bool => is_string( $v ) && $this->service->isValidValue( $v ),
					),
					'website' => array( 'required' => false ), // honeypot: hoort altijd leeg te zijn
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/rating/(?P<id>\d+)/tekst',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'attachText' ),
				'permission_callback' => $this->publicPermission(),
				'args'                => array(
					'id'      => array( 'validate_callback' => static fn ( $v ): bool => is_numeric( $v ) && (int) $v > 0 ),
					'token'   => array(
						'required'          => true,
						'validate_callback' => static fn ( $v ): bool => is_string( $v ) && strlen( $v ) === 32,
					),
					'tekst'   => array(
						'required'          => true,
						'validate_callback' => static fn ( $v ): bool => is_string( $v ) && '' !== trim( $v ),
					),
					'website' => array( 'required' => false ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/rating/(?P<id>\d+)/vervolg',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'resolveFollowUp' ),
				'permission_callback' => $this->publicPermission(),
				'args'                => array(
					'id'      => array( 'validate_callback' => static fn ( $v ): bool => is_numeric( $v ) && (int) $v > 0 ),
					'token'   => array(
						'required'          => true,
						'validate_callback' => static fn ( $v ): bool => is_string( $v ) && strlen( $v ) === 32,
					),
					'actie'   => array(
						'required'          => true,
						'validate_callback' => static fn ( $v ): bool => in_array( $v, array( 'publiceren', 'contact', 'overslaan' ), true ),
					),
					'naam'    => array( 'required' => false ),
					'email'   => array( 'required' => false ),
					'website' => array( 'required' => false ),
				),
			)
		);
	}

	public function create( WP_REST_Request $request ) {
		$postId = (int) $request->get_param( 'post_id' );

		if ( 'publish' !== get_post_status( $postId ) ) {
			return new WP_Error( 'rx_invalid_post', __( 'Onbekend artikel.', 'reader-experience' ), array( 'status' => 404 ) );
		}

		$result = $this->service->submit(
			$postId,
			(string) $request->get_param( 'value' ),
			(string) $request->get_param( 'website' ),
			$this->rateLimiter->fingerprint()
		);

		if ( null === $result ) {
			// Spam of te veel pogingen: doen alsof het gelukt is, zonder een echte rij aan te maken.
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

	public function attachText( WP_REST_Request $request ) {
		$this->service->attachText(
			(int) $request->get_param( 'id' ),
			(string) $request->get_param( 'token' ),
			(string) $request->get_param( 'tekst' ),
			(string) $request->get_param( 'website' ),
			$this->rateLimiter->fingerprint()
		);

		return new WP_REST_Response( null, 204 );
	}

	public function resolveFollowUp( WP_REST_Request $request ) {
		$this->service->resolveFollowUp(
			(int) $request->get_param( 'id' ),
			(string) $request->get_param( 'token' ),
			(string) $request->get_param( 'actie' ),
			(string) ( $request->get_param( 'naam' ) ?? '' ),
			(string) ( $request->get_param( 'email' ) ?? '' ),
			(string) $request->get_param( 'website' ),
			$this->rateLimiter->fingerprint()
		);

		return new WP_REST_Response( null, 204 );
	}
}
