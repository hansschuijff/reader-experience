<?php
/**
 * Email notifications for new feedback.
 *
 * @package ReaderExperience\Modules\Rating
 * @author  Hans Schuijff <@hansschuijff>
 * @license GPL-2.0-only
 * @since   0.1.0
 */

declare( strict_types=1 );

namespace ReaderExperience\Modules\Rating;

use function __;
use function add_action;
use function admin_url;
use function apply_filters;
use function get_bloginfo;
use function get_option;
use function get_the_title;
use function sprintf;
use function wp_mail;

/**
 * A short email to the site administrator for new feedback worth reading (a comment, or a
 * contact request). A bare thumb without text deliberately sends no email: that would be too
 * much, and adds little without context (those totals are already in the posts list column).
 *
 * A posted comment (the "publish" branch) needs no email of its own here: wp_new_comment() goes
 * through WordPress' own comment pipeline, which already sends a "new comment awaiting
 * moderation" email when the site is set up for that.
 */
final class Notifier {

	/**
	 * Attaches the actions.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register(): void {
		add_action( 'reader_experience/feedback_text_added', array( $this, 'notify_text' ), 10, 3 );
		add_action( 'reader_experience/feedback_contact_requested', array( $this, 'notify_contact' ), 10, 2 );
	}

	/**
	 * Emails the administrator about feedback that came with a comment.
	 *
	 * @since 0.1.0
	 * @param int    $id      Feedback id.
	 * @param int    $post_id Post id.
	 * @param string $text    The comment.
	 * @return void
	 */
	public function notify_text( int $id, int $post_id, string $text ): void {
		if ( ! $this->should_notify() ) {
			return;
		}

		$this->send(
			__( 'New feedback with a comment', 'reader-experience' ),
			sprintf(
				/* translators: 1: post title, 2: the reader's comment, 3: link to the feedback overview */
				__( "On \"%1\$s\":\n\n%2\$s\n\nView all feedback: %3\$s", 'reader-experience' ),
				get_the_title( $post_id ),
				$text,
				admin_url( 'tools.php?page=rx-feedback' )
			)
		);
	}

	/**
	 * Emails the administrator about a contact request.
	 *
	 * @since 0.1.0
	 * @param int $id      Feedback id.
	 * @param int $post_id Post id.
	 * @return void
	 */
	public function notify_contact( int $id, int $post_id ): void {
		if ( ! $this->should_notify() ) {
			return;
		}

		$this->send(
			__( 'Someone wants to be contacted after giving feedback', 'reader-experience' ),
			sprintf(
				/* translators: 1: post title, 2: link to the feedback overview */
				__( "On \"%1\$s\" a contact request came in.\n\nView the details: %2\$s", 'reader-experience' ),
				get_the_title( $post_id ),
				admin_url( 'tools.php?page=rx-feedback' )
			)
		);
	}

	/**
	 * Should the administrator be emailed?
	 *
	 * @return bool
	 */
	private function should_notify(): bool {
		/**
		 * Filters whether the administrator gets an email for new feedback with a comment or a
		 * contact request.
		 *
		 * @since 0.1.0
		 *
		 * @param bool $notify Whether to send the email. Default true.
		 */
		return (bool) apply_filters( 'reader_experience/notify_on_feedback', true );
	}

	/**
	 * Sends the email to the administrator.
	 *
	 * @param string $subject Subject, without the site name prefix.
	 * @param string $message Message.
	 * @return void
	 */
	private function send( string $subject, string $message ): void {
		wp_mail( get_option( 'admin_email' ), '[' . get_bloginfo( 'name' ) . '] ' . $subject, $message );
	}
}
