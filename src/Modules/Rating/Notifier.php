<?php

declare(strict_types=1);

namespace ReaderExperience\Modules\Rating;

/**
 * Een korte e-mail aan de beheerder bij nieuwe feedback die de moeite waard is
 * om te lezen (een toelichting, of een contactverzoek). Puur-stem-zonder-tekst
 * genereert bewust geen mail: dat zou te veel worden en voegt zonder context
 * weinig toe (die aantallen staan al in de kolom bij de artikelen).
 *
 * Een geplaatste reactie (de "publiceren"-afslag) heeft hier geen eigen mail
 * nodig: wp_new_comment() loopt door WordPress' eigen reactie-pijplijn, die al
 * een "nieuwe reactie ter moderatie"-mail stuurt als de site daarvoor is ingesteld.
 */
final class Notifier {

	public function register(): void {
		add_action( 'reader_experience/feedback_text_added', array( $this, 'notifyText' ), 10, 3 );
		add_action( 'reader_experience/feedback_contact_requested', array( $this, 'notifyContact' ), 10, 2 );
	}

	public function notifyText( int $id, int $postId, string $text ): void {
		if ( ! $this->shouldNotify() ) {
			return;
		}

		$this->send(
			__( 'Nieuwe feedback met toelichting', 'reader-experience' ),
			sprintf(
				/* translators: 1: artikeltitel, 2: toelichting, 3: link naar het overzicht */
				__( "Bij \"%1\$s\":\n\n%2\$s\n\nBekijk alle feedback: %3\$s", 'reader-experience' ),
				get_the_title( $postId ),
				$text,
				admin_url( 'tools.php?page=rx-feedback' )
			)
		);
	}

	public function notifyContact( int $id, int $postId ): void {
		if ( ! $this->shouldNotify() ) {
			return;
		}

		$this->send(
			__( 'Iemand wil contact na feedback', 'reader-experience' ),
			sprintf(
				/* translators: 1: artikeltitel, 2: link naar het overzicht */
				__( "Bij \"%1\$s\" is een contactverzoek binnengekomen.\n\nBekijk de gegevens: %2\$s", 'reader-experience' ),
				get_the_title( $postId ),
				admin_url( 'tools.php?page=rx-feedback' )
			)
		);
	}

	private function shouldNotify(): bool {
		return (bool) apply_filters( 'reader_experience/notify_on_feedback', true );
	}

	private function send( string $subject, string $message ): void {
		wp_mail( get_option( 'admin_email' ), '[' . get_bloginfo( 'name' ) . '] ' . $subject, $message );
	}
}
