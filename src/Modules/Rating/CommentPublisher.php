<?php

declare(strict_types=1);

namespace ReaderExperience\Modules\Rating;

/**
 * Plaatst een waardering met toelichting als een echte reactie, via de normale
 * WordPress-commentpijplijn (wp_new_comment). Dat betekent: gewone moderatie-
 * afhandeling (inclusief automatisch goedkeuren als dat zo is ingesteld) en,
 * als de site Akismet heeft, automatische spamcontrole. Geen los formulieren-
 * systeem nodig.
 */
final class CommentPublisher {

	/**
	 * Mag er op dit bericht een reactie geplaatst worden?
	 *
	 * wp_new_comment() zelf controleert dat NIET: de controle op gesloten reacties, een
	 * wachtwoord en "reageren alleen met account" zit in wp-comments-post.php en in de REST
	 * API voor reacties, en wij lopen daar langs. Zonder deze controle kon een lezer
	 * uitgenodigd worden een reactie te plaatsen op een pagina waar reacties dicht staan.
	 *
	 * Een site kan dit oordeel bijsturen met het filter reader_experience/can_publish_as_comment.
	 */
	public function canPublish( int $postId ): bool {
		if ( ! function_exists( 'comments_open' ) ) {
			return false; // Alleen relevant in geïsoleerde tests zonder WordPress.
		}

		$allowed = comments_open( $postId )
			&& ! post_password_required( $postId )
			&& ! get_option( 'comment_registration' );

		return (bool) apply_filters( 'reader_experience/can_publish_as_comment', $allowed, $postId );
	}

	/** @return int|false Het nieuwe reactie-id, of false als plaatsen niet lukte of niet mag. */
	public function publish( int $postId, string $text, string $name, string $email ) {
		if ( ! function_exists( 'wp_new_comment' ) || ! $this->canPublish( $postId ) ) {
			return false;
		}

		$commentData = array(
			'comment_post_ID'      => $postId,
			'comment_content'      => $text,
			'comment_author'       => '' !== trim( $name ) ? $name : __( 'Anoniem', 'reader-experience' ),
			'comment_author_email' => $email,
			'comment_author_url'   => '',
			'comment_type'         => '',
		);

		// Tweede argument true: geef een WP_Error terug bij een probleem in plaats van
		// de request af te breken met wp_die(), wat in een REST-context niet past.
		$result = wp_new_comment( $commentData, true );

		if ( is_wp_error( $result ) ) {
			return false;
		}

		return (int) $result;
	}
}
