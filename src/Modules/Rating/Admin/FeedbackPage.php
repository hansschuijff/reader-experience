<?php

declare(strict_types=1);

namespace ReaderExperience\Modules\Rating\Admin;

use ReaderExperience\Modules\Rating\FeedbackRepository;
use ReaderExperience\Support\Icons;

/**
 * Eenvoudig overzicht onder Gereedschap. Geen WP_List_Table (bulkacties, kolom-
 * zichtbaarheid): dat kan er later bij als er behoefte aan blijkt. Toont alleen
 * feedback met inhoud (toelichting, geplaatste reactie of contactverzoek) — een
 * kaal duimpje zonder context staat hier niet tussen, dat telt al mee in de
 * kolom bij de artikelen.
 */
final class FeedbackPage {

	private const PER_PAGE = 20;
	private const NONCE_ACTION = 'rx_feedback_delete';

	private FeedbackRepository $repository;

	public function __construct() {
		$this->repository = new FeedbackRepository();
	}

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'addPage' ) );
	}

	public function addPage(): void {
		$hook = add_management_page(
			__( 'Reader Experience feedback', 'reader-experience' ),
			__( 'Feedback', 'reader-experience' ),
			'edit_others_posts',
			'rx-feedback',
			array( $this, 'render' )
		);

		add_action( "admin_head-{$hook}", array( $this, 'printStyle' ) );
	}

	public function printStyle(): void {
		echo '<style>.rx-admin-icon svg{width:16px;height:16px;vertical-align:text-bottom;}</style>';
	}

	public function render(): void {
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			return;
		}

		$this->maybeHandleDelete();

		$page    = max( 1, (int) ( $_GET['paged'] ?? 1 ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$postId  = (int) ( $_GET['rx_post'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification
		$orderby = ( 'post_title' === ( $_GET['orderby'] ?? '' ) ) ? 'post_title' : 'created'; // phpcs:ignore WordPress.Security.NonceVerification
		$order   = ( 'ASC' === strtoupper( (string) ( $_GET['order'] ?? '' ) ) ) ? 'ASC' : 'DESC'; // phpcs:ignore WordPress.Security.NonceVerification

		$result     = $this->repository->paginated(
			$page,
			self::PER_PAGE,
			array( 'post_id' => $postId, 'orderby' => $orderby, 'order' => $order )
		);
		$totalPages = (int) max( 1, ceil( $result['total'] / self::PER_PAGE ) );
		$posts      = $this->repository->postsWithContent();
		$icons      = array( 'ja' => Icons::get( 'thumb_up' ), 'nee' => Icons::get( 'thumb_down' ) );

		$sortLink = static function ( string $column, string $label ) use ( $orderby, $order, $postId ) {
			$nextOrder = ( $column === $orderby && 'DESC' === $order ) ? 'ASC' : 'DESC';
			$url       = add_query_arg(
				array( 'orderby' => $column, 'order' => $nextOrder, 'rx_post' => $postId ?: null ),
				admin_url( 'tools.php?page=rx-feedback' )
			);
			$arrow = $column === $orderby ? ( 'ASC' === $order ? ' ↑' : ' ↓' ) : '';
			printf( '<a href="%s">%s%s</a>', esc_url( $url ), esc_html( $label ), esc_html( $arrow ) );
		};
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Feedback', 'reader-experience' ); ?></h1>

			<form method="get" style="margin: 1em 0;">
				<input type="hidden" name="page" value="rx-feedback">
				<label for="rx-post-filter"><?php esc_html_e( 'Artikel:', 'reader-experience' ); ?></label>
				<select name="rx_post" id="rx-post-filter" onchange="this.form.submit()">
					<option value="0"><?php esc_html_e( 'Alle artikelen', 'reader-experience' ); ?></option>
					<?php foreach ( $posts as $post ) : ?>
						<option value="<?php echo (int) $post['post_id']; ?>" <?php selected( $postId, $post['post_id'] ); ?>>
							<?php echo esc_html( $post['post_title'] ?: __( '(zonder titel)', 'reader-experience' ) ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<noscript><button type="submit"><?php esc_html_e( 'Filteren', 'reader-experience' ); ?></button></noscript>
			</form>

			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php $sortLink( 'post_title', __( 'Artikel', 'reader-experience' ) ); ?></th>
						<th><?php esc_html_e( 'Waardering', 'reader-experience' ); ?></th>
						<th><?php esc_html_e( 'Toelichting', 'reader-experience' ); ?></th>
						<th><?php esc_html_e( 'Vervolg', 'reader-experience' ); ?></th>
						<th><?php $sortLink( 'created', __( 'Datum', 'reader-experience' ) ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! $result['rows'] ) : ?>
						<tr><td colspan="6"><?php esc_html_e( 'Nog geen feedback.', 'reader-experience' ); ?></td></tr>
					<?php endif; ?>
					<?php foreach ( $result['rows'] as $row ) : ?>
						<tr>
							<td>
								<a href="<?php echo esc_url( (string) get_edit_post_link( (int) $row['post_id'] ) ); ?>">
									<?php echo esc_html( (string) ( $row['post_title'] ?: __( '(zonder titel)', 'reader-experience' ) ) ); ?>
								</a>
							</td>
							<td><span class="rx-admin-icon"><?php echo $icons[ $row['value'] ] ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span></td>
							<td><?php echo esc_html( (string) ( $row['text'] ?? '' ) ); ?></td>
							<td>
								<?php if ( ! empty( $row['comment_id'] ) ) : ?>
									<a href="<?php echo esc_url( admin_url( 'comment.php?action=editcomment&c=' . (int) $row['comment_id'] ) ); ?>"><?php esc_html_e( 'Reactie geplaatst', 'reader-experience' ); ?></a>
								<?php elseif ( ! empty( $row['wants_contact'] ) ) : ?>
									<?php echo esc_html( (string) ( $row['contact_name'] ?? '' ) ); ?>
									<?php if ( ! empty( $row['contact_email'] ) ) : ?>
										&nbsp;&lt;<a href="mailto:<?php echo esc_attr( $row['contact_email'] ); ?>"><?php echo esc_html( $row['contact_email'] ); ?></a>&gt;
									<?php endif; ?>
								<?php else : ?>
									—
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( mysql2date( 'j M Y H:i', (string) $row['created'] ) ); ?></td>
							<td>
								<a
									href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'rx_delete' => (int) $row['id'] ) ), self::NONCE_ACTION ) ); ?>"
									onclick="return confirm('<?php echo esc_js( __( 'Deze feedback verwijderen?', 'reader-experience' ) ); ?>')"
								><?php esc_html_e( 'Verwijderen', 'reader-experience' ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php if ( $totalPages > 1 ) : ?>
				<p class="tablenav">
					<span class="pagination-links">
						<?php
						echo paginate_links(
							array(
								'base'     => add_query_arg( 'paged', '%#%' ),
								'format'   => '',
								'current'  => $page,
								'total'    => $totalPages,
								'add_args' => array( 'page' => 'rx-feedback', 'rx_post' => $postId ?: null, 'orderby' => $orderby, 'order' => $order ),
							)
						);
						?>
					</span>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}

	private function maybeHandleDelete(): void {
		$id = (int) ( $_GET['rx_delete'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( $id <= 0 ) {
			return;
		}

		check_admin_referer( self::NONCE_ACTION );
		$this->repository->delete( $id );

		wp_safe_redirect( remove_query_arg( array( 'rx_delete', '_wpnonce' ) ) );
		exit;
	}
}
