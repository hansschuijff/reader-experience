<?php
/**
 * Rating business logic.
 *
 * @package ReaderExperience\Modules\Rating
 * @author  Hans Schuijff <@hansschuijff>
 * @license GPL-2.0-only
 * @since   0.1.0
 */

declare( strict_types=1 );

namespace ReaderExperience\Modules\Rating;

use ReaderExperience\Support\RateLimiter;

use function apply_filters;
use function bin2hex;
use function do_action;
use function function_exists;
use function hash;
use function hash_equals;
use function in_array;
use function mb_substr;
use function random_bytes;
use function sanitize_email;
use function sanitize_text_field;
use function strtotime;
use function substr;
use function time;
use function trim;
use function wp_strip_all_tags;

/**
 * Validation, token handling and spam protection around a rating. The database itself stays in
 * FeedbackRepository, and posting as a comment in CommentPublisher, so this class can be tested
 * without wpdb or WordPress' comment pipeline.
 *
 * One token covers the whole short flow: vote, optional comment, and then optionally "post as
 * comment" (after a positive vote) or "contact me" (after a negative vote). The token is only
 * invalidated at the end, not after the comment, because the last step could not happen otherwise.
 *
 * The stored vote values ("ja", "nee") and the follow-up actions ("publiceren", "contact",
 * "overslaan") are data and wire formats shared with the database and the JavaScript. They are
 * deliberately not translated or renamed.
 */
final class RatingService {

	/**
	 * Time to finish the whole flow: 30 minutes.
	 *
	 * @var int
	 */
	private const TOKEN_TTL_SECONDS = 1800;

	/**
	 * Maximum length of the comment, in characters.
	 *
	 * @var int
	 */
	private const MAX_TEXT_LENGTH = 1000;

	/**
	 * Constructor.
	 *
	 * @param FeedbackRepository $repository        Database access.
	 * @param RateLimiter        $rate_limiter       Rate limiter.
	 * @param CommentPublisher   $comment_publisher  Publisher of comments.
	 */
	public function __construct(
		private readonly FeedbackRepository $repository = new FeedbackRepository(),
		private readonly RateLimiter $rate_limiter = new RateLimiter(),
		private readonly CommentPublisher $comment_publisher = new CommentPublisher()
	) {}

	/**
	 * Is this an allowed vote value?
	 *
	 * @since 0.1.0
	 * @param string $value Vote value.
	 * @return bool
	 */
	public function is_valid_value( string $value ): bool {
		return in_array( $value, FeedbackRepository::VALUES, true );
	}

	/**
	 * Stores a vote and hands out the flow token.
	 *
	 * @since 0.1.0
	 * @param int    $post_id     Post id.
	 * @param string $value       Vote value.
	 * @param string $honeypot    Value of the honeypot field; must be empty.
	 * @param string $fingerprint Anonymous fingerprint of the visitor, for rate limiting.
	 * @return array{id:int,token:string}|null Null on spam (honeypot) or too many attempts;
	 *                                         the caller then pretends it worked.
	 */
	public function submit( int $post_id, string $value, string $honeypot, string $fingerprint ): ?array {
		if ( ! $this->passes_spam_checks( $honeypot, "rating:{$post_id}:{$fingerprint}", 5 ) ) {
			return null;
		}

		$token      = $this->generate_token();
		$token_hash = $this->hash_token( $token );
		$id         = $this->repository->create( $post_id, $value, $token_hash, time() + self::TOKEN_TTL_SECONDS );

		return array(
			'id'    => $id,
			'token' => $token,
		);
	}

	/**
	 * Attaches the optional comment to a vote.
	 *
	 * Always returns true, also on spam or an invalid token: it is never revealed why something
	 * was not really processed.
	 *
	 * @since 0.1.0
	 * @param int    $id          Feedback id.
	 * @param string $token       Flow token.
	 * @param string $text        The comment.
	 * @param string $honeypot    Value of the honeypot field; must be empty.
	 * @param string $fingerprint Anonymous fingerprint of the visitor, for rate limiting.
	 * @return bool
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
			/**
			 * Fires when a reader added a comment to a rating.
			 *
			 * @since 0.1.0
			 *
			 * @param int    $id      Feedback id.
			 * @param int    $post_id Post id.
			 * @param string $text    The sanitized comment.
			 */
			do_action( 'reader_experience/feedback_text_added', $id, $row['post_id'], $clean );
		}

		return true;
	}

	/**
	 * The third, optional step: after a positive vote "post as a comment", after a negative vote
	 * "contact me". Always invalidates the token afterwards, also on "skip": the short session
	 * is then finished.
	 *
	 * @since 0.1.0
	 * @param int    $id          Feedback id.
	 * @param string $token       Flow token.
	 * @param string $action      One of "publiceren", "contact" or "overslaan".
	 * @param string $name        Name of the reader.
	 * @param string $email       Email address of the reader.
	 * @param string $honeypot    Value of the honeypot field; must be empty.
	 * @param string $fingerprint Anonymous fingerprint of the visitor, for rate limiting.
	 * @return bool Always true, like attach_text().
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
			$this->repository->invalidate_token( $id ); // This step was already done once.
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

			/**
			 * Fires when a reader asked to be contacted after a negative vote.
			 *
			 * @since 0.1.0
			 *
			 * @param int $id      Feedback id.
			 * @param int $post_id Post id.
			 */
			do_action( 'reader_experience/feedback_contact_requested', $id, $row['post_id'] );
		}

		$this->repository->invalidate_token( $id );
		return true;
	}

	/**
	 * Honeypot, extra spam filter and rate limit, in that order.
	 *
	 * @param string $honeypot Value of the honeypot field.
	 * @param string $bucket   Rate limit bucket name.
	 * @param int    $limit    Maximum attempts per hour in this bucket.
	 * @return bool True when the request may continue.
	 */
	private function passes_spam_checks( string $honeypot, string $bucket, int $limit ): bool {
		if ( '' !== trim( $honeypot ) ) {
			return false;
		}

		/**
		 * Filters whether a rating request passes an extra spam check, for example reCAPTCHA or
		 * hCaptcha. Return false to reject the request silently.
		 *
		 * @since 0.1.0
		 *
		 * @param bool $passes Whether the request passes. Default true.
		 */
		if ( ! apply_filters( 'reader_experience/rating_spam_check', true ) ) {
			return false;
		}

		return ! $this->rate_limiter->too_many( $bucket, $limit, HOUR_IN_SECONDS );
	}

	/**
	 * The stored row, if the token is correct and not expired.
	 *
	 * @param int    $id    Feedback id.
	 * @param string $token Flow token.
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

	/**
	 * Strips tags and cuts the comment to the maximum length.
	 *
	 * @param string $text Raw comment.
	 * @return string
	 */
	private function sanitize_text( string $text ): string {
		$clean = trim( wp_strip_all_tags( $text ) );
		if ( function_exists( 'mb_substr' ) ) {
			return mb_substr( $clean, 0, self::MAX_TEXT_LENGTH );
		}
		return substr( $clean, 0, self::MAX_TEXT_LENGTH );
	}

	/**
	 * A random single-use token.
	 *
	 * @return string 32 hexadecimal characters.
	 */
	private function generate_token(): string {
		return bin2hex( random_bytes( 16 ) );
	}

	/**
	 * Hash of a token; only the hash is stored.
	 *
	 * @param string $token Token.
	 * @return string
	 */
	private function hash_token( string $token ): string {
		return hash( 'sha256', $token );
	}
}
