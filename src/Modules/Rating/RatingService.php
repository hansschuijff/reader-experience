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
		private readonly RateLimiter $rateLimiter = new RateLimiter(),
		private readonly CommentPublisher $commentPublisher = new CommentPublisher()
	) {}

	public function isValidValue( string $value ): bool {
		return in_array( $value, FeedbackRepository::VALUES, true );
	}

	/**
	 * @return array{id:int,token:string}|null Null bij spam (honeypot) of te veel pogingen;
	 *                                          de aanroeper doet dan net alsof het gelukt is.
	 */
	public function submit( int $postId, string $value, string $honeypot, string $fingerprint ): ?array {
		if ( ! $this->passesSpamChecks( $honeypot, "rating:{$postId}:{$fingerprint}", 5 ) ) {
			return null;
		}

		$token     = $this->generateToken();
		$tokenHash = $this->hashToken( $token );
		$id        = $this->repository->create( $postId, $value, $tokenHash, time() + self::TOKEN_TTL_SECONDS );

		return array(
			'id'    => $id,
			'token' => $token,
		);
	}

	/**
	 * Altijd true, ook bij spam of een ongeldig token: er wordt nooit weggegeven
	 * waarom iets niet echt is verwerkt.
	 */
	public function attachText( int $id, string $token, string $text, string $honeypot, string $fingerprint ): bool {
		if ( ! $this->passesSpamChecks( $honeypot, "rating-tekst:{$fingerprint}", 20 ) ) {
			return true;
		}

		$row = $this->validRow( $id, $token );
		if ( null === $row ) {
			return true;
		}

		$clean = $this->sanitizeText( $text );
		$this->repository->setTextIfEmpty( $id, $clean );

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
	public function resolveFollowUp(
		int $id,
		string $token,
		string $action,
		string $name,
		string $email,
		string $honeypot,
		string $fingerprint
	): bool {
		if ( ! $this->passesSpamChecks( $honeypot, "rating-vervolg:{$fingerprint}", 20 ) ) {
			return true;
		}

		$row = $this->validRow( $id, $token );
		if ( null === $row ) {
			return true;
		}

		if ( $row['comment_id'] > 0 || $row['wants_contact'] ) {
			$this->repository->invalidateToken( $id ); // Deze stap is al eens gedaan.
			return true;
		}

		$name  = sanitize_text_field( $name );
		$email = sanitize_email( $email );
		$text  = trim( (string) $row['text'] );

		if ( 'publiceren' === $action && 'ja' === $row['value'] && '' !== $text ) {
			$commentId = $this->commentPublisher->publish( $row['post_id'], $text, $name, $email );
			if ( false !== $commentId ) {
				$this->repository->setComment( $id, $commentId );
			}
		} elseif ( 'contact' === $action && 'nee' === $row['value'] && ( '' !== $name || '' !== $email ) ) {
			$this->repository->setContact( $id, $name, $email );
			do_action( 'reader_experience/feedback_contact_requested', $id, $row['post_id'] );
		}

		$this->repository->invalidateToken( $id );
		return true;
	}

	private function passesSpamChecks( string $honeypot, string $bucket, int $limit ): bool {
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

		return ! $this->rateLimiter->tooMany( $bucket, $limit, HOUR_IN_SECONDS );
	}

	/**
	 * @return array{post_id:int,value:string,text:?string,comment_id:int,wants_contact:bool}|null
	 */
	private function validRow( int $id, string $token ): ?array {
		$row = $this->repository->find( $id );
		if ( null === $row || null === $row['token_hash'] ) {
			return null;
		}

		if ( ! hash_equals( $row['token_hash'], $this->hashToken( $token ) ) ) {
			return null;
		}

		$expires = $row['token_expires'] ? strtotime( $row['token_expires'] . ' UTC' ) : 0;
		if ( ! $expires || $expires < time() ) {
			return null;
		}

		return $row;
	}

	private function sanitizeText( string $text ): string {
		$clean = trim( wp_strip_all_tags( $text ) );
		if ( function_exists( 'mb_substr' ) ) {
			return mb_substr( $clean, 0, self::MAX_TEXT_LENGTH );
		}
		return substr( $clean, 0, self::MAX_TEXT_LENGTH );
	}

	private function generateToken(): string {
		return bin2hex( random_bytes( 16 ) );
	}

	private function hashToken( string $token ): string {
		return hash( 'sha256', $token );
	}
}
