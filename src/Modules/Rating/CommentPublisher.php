<?php
/**
 * Publishing a rating comment as a real WordPress comment.
 *
 * @package ReaderExperience\Modules\Rating
 * @author  Hans Schuijff <@hansschuijff>
 * @license GPL-2.0-only
 * @since   0.1.0
 */

declare( strict_types=1 );

namespace ReaderExperience\Modules\Rating;

/**
 * Posts a rating with a comment as a real comment, through the normal WordPress comment pipeline
 * (wp_new_comment). That means: normal moderation handling (including automatic approval if the
 * site is set up that way) and, if the site has Akismet, automatic spam checking. No separate
 * forms system needed.
 */
final class CommentPublisher {

	/**
	 * May a comment be posted on this post?
	 *
	 * The function wp_new_comment() does NOT check this itself: the checks for closed comments, a password and
	 * "comment only with an account" live in wp-comments-post.php and in the REST API for
	 * comments, and we bypass both. Without this check a reader could be invited to post a
	 * comment on a page where comments are closed.
	 *
	 * @since 0.2.0
	 * @param int $post_id Post id.
	 * @return bool
	 */
	public function can_publish( int $post_id ): bool {
		if ( ! function_exists( 'comments_open' ) ) {
			return false; // Only relevant in isolated tests without WordPress.
		}

		$allowed = comments_open( $post_id )
			&& ! post_password_required( $post_id )
			&& ! get_option( 'comment_registration' );

		/**
		 * Filters whether a rating comment may be posted as a WordPress comment on a post.
		 *
		 * By default this is true when comments are open, the post is not password protected and
		 * commenting does not require an account.
		 *
		 * @since 0.2.0
		 *
		 * @param bool $allowed Whether posting is allowed.
		 * @param int  $post_id Post id.
		 */
		return (bool) apply_filters( 'reader_experience/can_publish_as_comment', $allowed, $post_id );
	}

	/**
	 * Posts the comment.
	 *
	 * @since 0.1.0
	 * @param int    $post_id Post id.
	 * @param string $text    Comment text.
	 * @param string $name    Author name. Empty becomes "Anonymous".
	 * @param string $email   Author email.
	 * @return int|false The new comment id, or false when posting failed or is not allowed.
	 */
	public function publish( int $post_id, string $text, string $name, string $email ) {
		if ( ! function_exists( 'wp_new_comment' ) || ! $this->can_publish( $post_id ) ) {
			return false;
		}

		$comment_data = array(
			'comment_post_ID'      => $post_id,
			'comment_content'      => $text,
			'comment_author'       => '' !== trim( $name ) ? $name : __( 'Anonymous', 'reader-experience' ),
			'comment_author_email' => $email,
			'comment_author_url'   => '',
			'comment_type'         => '',
		);

		// Second argument true: return a WP_Error on a problem instead of aborting the request
		// with wp_die(), which does not fit a REST context.
		$result = wp_new_comment( $comment_data, true );

		if ( is_wp_error( $result ) ) {
			return false;
		}

		return (int) $result;
	}
}
