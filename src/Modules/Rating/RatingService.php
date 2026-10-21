<?php

declare(strict_types=1);

namespace ReaderExperience\Modules\Rating;

use ReaderExperience\Support\RateLimiter;

/**
 * Validatie, tokenbeheer en spamweringen rond een waardering. De database zelf
 * blijft in FeedbackRepository, en het plaatsen als reactie in CommentPublisher,
 * zodat deze klasse te testen is zonder wpdb of WordPress' comment-pijplijn.
 *
 * Eén token dekt de hele korte flow: stem, optionele toelichting, en daarna
 * optioneel "plaats als reactie" (bij een positieve stem) of "neem contact op"
 * (bij een negatieve stem). Het token wordt pas aan het eind ongeldig gemaakt,
 * niet al na de toelichting, want anders zou de laatste stap niet meer kunnen.
 */
final class RatingService {

	private const TOKEN_TTL_SECONDS = 1800; // 30 minuten om de hele flow af te maken.
	private const MAX_TEXT_LENGTH   = 1000;

	public function __construct(
		private readonly FeedbackRepository $repository = new FeedbackRepository(),
		private readonly RateLimiter $rate_limiter = new RateLimiter(),
		private readonly CommentPublisher $comment_publisher = new CommentPublisher()
	) {}

	public function is_valid_value( string $value ): bool {
		return in_array( $value, FeedbackRepository::VALUES, true );
	}

	/**
	 * @return array{id:int,token:string}|null Null bij spam (honeypot) of te veel pogingen;
	 *                                          de aanroeper doet dan net alsof het gelukt is.
	 */
	public function submit( int $post_id, string $value, string $honeypot, string $fingerprint ): ?array {
		if ( ! $this->passes_spam_checks( $honeypot, "rating:{$post_id}:{$fingerprint}", 5 ) ) {
			return null;
		}

		$token     = $this->generate_token();
		$token_hash = $this->hash_token( $token );
		$id        = $this->repository->create( $post_id, $value, $token_hash, time() + self::TOKEN_TTL_SECONDS );

		return array(
			'id'    => $id,
			'token' => $token,
		);
	}

	/**
	 * Altijd true, ook bij spam of een ongeldig token: er wordt nooit weggegeven
	 * waarom iets niet echt is verwerkt.
	 */
	public function attach_text( int $id, string $token, string $text, string $honeypot, string $fingerprint ): bool {
		if ( ! $this->passes_spam_checks( $honeypot, "rating-tekst:{$fingerprint}", 20 ) ) {
			return true;
		}

		$row = $this->valid_row( $id, $token );
		if ( null === $row ) {
			return true;
		}

		$clean = $this->sanitize_text( $text );
		$this->repository->set_text_if_empty( $id, $clean );

		if ( '' !== $clean ) {
			do_action( 'reader_experience/feedback_text_added', $id, $row['post_id'], $clean );
		}

		return true;
	}

	/**
	 * De derde, optionele stap: bij een positieve stem "plaats als reactie", bij een
	 * negatieve stem "neem contact op". Maakt hierna altijd het token ongeldig, ook
	 * bij "overslaan": de korte sessie is dan afgerond.
	 */
	public function resolve_follow_up(
		int $id,
		string $token,
		string $action,
		string $name,
		string $email,
		string $honeypot,
		string $fingerprint
	): bool {
		if ( ! $this->passes_spam_checks( $honeypot, "rating-vervolg:{$fingerprint}", 20 ) ) {
			return true;
		}

		$row = $this->valid_row( $id, $token );
		if ( null === $row ) {
			return true;
		}

		if ( $row['comment_id'] > 0 || $row['wants_contact'] ) {
			$this->repository->invalidate_token( $id ); // Deze stap is al eens gedaan.
			return true;
		}

		$name  = sanitize_text_field( $name );
		$email = sanitize_email( $email );
		$text  = trim( (string) $row['text'] );

		if ( 'publiceren' === $action && 'ja' === $row['value'] && '' !== $text && $this->comment_publisher->can_publish( $row['post_id'] ) ) {
			$comment_id = $this->comment_publisher->publish( $row['post_id'], $text, $name, $email );
			if ( false !== $comment_id ) {
				$this->repository->set_comment( $id, $comment_id );
			}
		} elseif ( 'contact' === $action && 'nee' === $row['value'] && ( '' !== $name || '' !== $email ) ) {
			$this->repository->set_contact( $id, $name, $email );
			do_action( 'reader_experience/feedback_contact_requested', $id, $row['post_id'] );
		}

		$this->repository->invalidate_token( $id );
		return true;
	}

	private function passes_spam_checks( string $honeypot, string $bucket, int $limit ): bool {
		if ( '' !== trim( $honeypot ) ) {
			return false;
		}

		/**
		 * Extra spamcontrole, bijvoorbeeld reCAPTCHA of hCaptcha. Geeft standaard true
		 * (doorlaten); een site kan hier zelf op inhaken.
		 */
		if ( ! apply_filters( 'reader_experience/rating_spam_check', true ) ) {
			return false;
		}

		return ! $this->rate_limiter->too_many( $bucket, $limit, HOUR_IN_SECONDS );
	}

	/**
	 * @return array{post_id:int,value:string,text:?string,comment_id:int,wants_contact:bool}|null
	 */
	private function valid_row( int $id, string $token ): ?array {
		$row = $this->repository->find( $id );
		if ( null === $row || null === $row['token_hash'] ) {
			return null;
		}

		if ( ! hash_equals( $row['token_hash'], $this->hash_token( $token ) ) ) {
			return null;
		}

		$expires = $row['token_expires'] ? strtotime( $row['token_expires'] . ' UTC' ) : 0;
		if ( ! $expires || $expires < time() ) {
			return null;
		}

		return $row;
	}

	private function sanitize_text( string $text ): string {
		$clean = trim( wp_strip_all_tags( $text ) );
		if ( function_exists( 'mb_substr' ) ) {
			return mb_substr( $clean, 0, self::MAX_TEXT_LENGTH );
		}
		return substr( $clean, 0, self::MAX_TEXT_LENGTH );
	}

	private function generate_token(): string {
		return bin2hex( random_bytes( 16 ) );
	}

	private function hash_token( string $token ): string {
		return hash( 'sha256', $token );
	}
}
