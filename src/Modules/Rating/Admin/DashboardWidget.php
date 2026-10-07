<?php

declare(strict_types=1);

namespace ReaderExperience\Modules\Rating\Admin;

use ReaderExperience\Modules\Rating\FeedbackRepository;
use ReaderExperience\Support\Icons;

final class DashboardWidget {

	private FeedbackRepository $repository;

	public function __construct() {
		$this->repository = new FeedbackRepository();
	}

	public function register(): void {
		add_action( 'wp_dashboard_setup', array( $this, 'add' ) );
	}

	public function add(): void {
		wp_add_dashboard_widget(
			'rx_feedback_widget',
			__( 'Recente feedback', 'reader-experience' ),
			array( $this, 'render' )
		);
	}

	public function render(): void {
		$items = $this->repository->recentWithText( 10 );

		if ( ! $items ) {
			echo '<p>' . esc_html__( 'Nog geen feedback met een toelichting.', 'reader-experience' ) . '</p>';
			return;
		}

		echo '<style>.rx-feedback-widget svg{width:14px;height:14px;vertical-align:text-bottom;}</style>';
		echo '<ul class="rx-feedback-widget">';
		foreach ( $items as $item ) {
			$icon   = Icons::get( 'ja' === $item['value'] ? 'thumb_up' : 'thumb_down' );
			$extra  = array();
			if ( ! empty( $item['comment_id'] ) ) {
				$extra[] = sprintf(
					'<a href="%s">%s</a>',
					esc_url( admin_url( 'comment.php?action=editcomment&c=' . (int) $item['comment_id'] ) ),
					esc_html__( 'geplaatst als reactie', 'reader-experience' )
				);
			}
			if ( ! empty( $item['wants_contact'] ) ) {
				$extra[] = esc_html__( 'wil contact', 'reader-experience' ) . ( $item['contact_email'] ? ' (' . esc_html( $item['contact_email'] ) . ')' : '' );
			}

			printf(
				'<li style="margin-bottom:0.75em;"><strong>%s</strong> <a href="%s">%s</a>%s<br><span>%s</span></li>',
				$icon, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- vaste SVG uit de plugin
				esc_url( (string) get_edit_post_link( (int) $item['post_id'] ) ),
				esc_html( (string) ( $item['post_title'] ?: __( '(zonder titel)', 'reader-experience' ) ) ),
				$extra ? ' — ' . implode( ', ', $extra ) : '', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hierboven al geëscaped
				esc_html( (string) $item['text'] )
			);
		}
		echo '</ul>';

		printf(
			'<p><a href="%s">%s</a></p>',
			esc_url( admin_url( 'tools.php?page=rx-feedback' ) ),
			esc_html__( 'Alle feedback bekijken', 'reader-experience' )
		);
	}
}
