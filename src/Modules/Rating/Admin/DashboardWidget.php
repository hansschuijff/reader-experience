<?php
/**
 * Dashboard widget with recent feedback.
 *
 * @package ReaderExperience\Modules\Rating\Admin
 * @author  Hans Schuijff <@hansschuijff>
 * @license GPL-2.0-only
 * @since   0.1.0
 */

declare( strict_types=1 );

namespace ReaderExperience\Modules\Rating\Admin;

use ReaderExperience\Modules\Rating\FeedbackRepository;
use ReaderExperience\Support\Icons;

use function __;
use function add_action;
use function admin_url;
use function esc_html;
use function esc_html__;
use function esc_url;
use function get_edit_post_link;
use function implode;
use function printf;
use function sprintf;
use function wp_add_dashboard_widget;

/**
 * Shows the most recent feedback that has a comment on the WordPress dashboard.
 */
final class DashboardWidget {

	/**
	 * Database access.
	 *
	 * @var FeedbackRepository
	 */
	private FeedbackRepository $repository;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->repository = new FeedbackRepository();
	}

	/**
	 * Hooks the widget into the dashboard.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register(): void {
		add_action( 'wp_dashboard_setup', array( $this, 'add' ) );
	}

	/**
	 * Adds the widget.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function add(): void {
		wp_add_dashboard_widget(
			'rx_feedback_widget',
			__( 'Recent feedback', 'reader-experience' ),
			array( $this, 'render' )
		);
	}

	/**
	 * Prints the widget.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function render(): void {
		$items = $this->repository->recent_with_text( 10 );

		if ( ! $items ) {
			echo '<p>' . esc_html__( 'No feedback with a comment yet.', 'reader-experience' ) . '</p>';
			return;
		}

		echo '<style>.rx-feedback-widget svg{width:14px;height:14px;vertical-align:text-bottom;}</style>';
		echo '<ul class="rx-feedback-widget">';
		foreach ( $items as $item ) {
			$icon  = Icons::get( 'like' === $item['value'] ? 'thumb_up' : 'thumb_down' );
			$extra = array();
			if ( ! empty( $item['comment_id'] ) ) {
				$extra[] = sprintf(
					'<a href="%s">%s</a>',
					esc_url( admin_url( 'comment.php?action=editcomment&c=' . (int) $item['comment_id'] ) ),
					esc_html__( 'posted as a comment', 'reader-experience' )
				);
			}
			if ( ! empty( $item['wants_contact'] ) ) {
				$extra[] = esc_html__( 'wants contact', 'reader-experience' ) . ( $item['contact_email'] ? ' (' . esc_html( $item['contact_email'] ) . ')' : '' );
			}

			printf(
				'<li style="margin-bottom:0.75em;"><strong>%s</strong> <a href="%s">%s</a>%s<br><span>%s</span></li>',
				$icon, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed SVG from the plugin.
				esc_url( (string) get_edit_post_link( (int) $item['post_id'] ) ),
				esc_html( (string) ( $item['post_title'] ? $item['post_title'] : __( '(untitled)', 'reader-experience' ) ) ),
				$extra ? ' — ' . implode( ', ', $extra ) : '', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.
				esc_html( (string) $item['text'] )
			);
		}
		echo '</ul>';

		printf(
			'<p><a href="%s">%s</a></p>',
			esc_url( admin_url( 'edit-comments.php?page=rx-feedback' ) ),
			esc_html__( 'View all feedback', 'reader-experience' )
		);
	}
}
