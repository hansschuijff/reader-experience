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

	/** @return int|false Het nieuwe reactie-id, of false als plaatsen niet lukte. */
	public function publish( int $postId, string $text, string $name, string $email ) {
		if ( ! function_exists( 'wp_new_comment' ) ) {
			return false; // Alleen relevant in geïsoleerde tests zonder WordPress.
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
