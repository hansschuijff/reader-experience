<?php
/**
 * Feedback overview page in the admin.
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
use function _n;
use function absint;
use function add_action;
use function add_comments_page;
use function add_filter;
use function add_query_arg;
use function admin_url;
use function array_map;
use function ceil;
use function check_admin_referer;
use function current_user_can;
use function esc_attr;
use function esc_attr__;
use function esc_html;
use function esc_html_e;
use function esc_url;
use function get_edit_post_link;
use function max;
use function mysql2date;
use function paginate_links;
use function printf;
use function remove_query_arg;
use function selected;
use function sprintf;
use function strtoupper;
use function wp_get_referer;
use function wp_nonce_field;
use function wp_safe_redirect;
use function wp_unslash;

/**
 * Simple overview under Comments. No WP_List_Table (bulk actions, column visibility): that can come
 * later if there is a need. Shows only feedback with content (a comment, a posted comment or a
 * contact request); a bare thumb without context is not listed here, it already counts in the
 * column next to the posts.
 */
final class FeedbackPage {

	/**
	 * Rows per page.
	 *
	 * @var int
	 */
	private const PER_PAGE = 20;

	/**
	 * Nonce action for deleting a row.
	 *
	 * @var string
	 */
	private const NONCE_ACTION = 'rx_feedback_bulk_delete';

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
	 * Attaches the hooks.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_page' ) );
		add_filter( 'removable_query_args', array( $this, 'removable_query_args' ) );
	}

	/**
	 * Lets WordPress strip the deleted-count from the address bar after the page loads, as it does
	 * for its own notices, so a refresh does not show the message again.
	 *
	 * @since 0.2.0
	 * @param string[] $args Query arguments WordPress removes from the URL.
	 * @return string[]
	 */
	public function removable_query_args( $args ) {
		$args   = (array) $args;
		$args[] = 'rx_deleted';
		return $args;
	}

	/**
	 * Adds the page under Comments.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function add_page(): void {
		$hook = add_comments_page(
			__( 'Reader feedback', 'reader-experience' ),
			__( 'Reader feedback', 'reader-experience' ),
			'moderate_comments',
			'rx-feedback',
			array( $this, 'render' )
		);

		add_action( "admin_head-{$hook}", array( $this, 'print_style' ) );
	}

	/**
	 * Prints the small stylesheet for the icons on the page.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function print_style(): void {
		echo '<style>.rx-admin-icon svg{width:16px;height:16px;vertical-align:text-bottom;}</style>';
	}

	/**
	 * Prints the page.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( 'moderate_comments' ) ) {
			return;
		}

		$this->maybe_handle_bulk_delete();
		$deleted = (int) ( $_GET['rx_deleted'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification -- Only a number for a notice.

		$page    = max( 1, (int) ( $_GET['paged'] ?? 1 ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$post_id = (int) ( $_GET['rx_post'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification
		$orderby = ( 'post_title' === ( $_GET['orderby'] ?? '' ) ) ? 'post_title' : 'created'; // phpcs:ignore WordPress.Security.NonceVerification
		$order   = ( 'ASC' === strtoupper( (string) ( $_GET['order'] ?? '' ) ) ) ? 'ASC' : 'DESC'; // phpcs:ignore WordPress.Security.NonceVerification

		$result      = $this->repository->paginated(
			$page,
			self::PER_PAGE,
			array(
				'post_id' => $post_id,
				'orderby' => $orderby,
				'order'   => $order,
			)
		);
		$total_pages = (int) max( 1, ceil( $result['total'] / self::PER_PAGE ) );
		$posts       = $this->repository->posts_with_content();
		$icons       = array(
			'like'    => Icons::get( 'thumb_up' ),
			'dislike' => Icons::get( 'thumb_down' ),
		);

		$sort_link = static function ( string $column, string $label ) use ( $orderby, $order, $post_id ) {
			$next_order = ( $column === $orderby && 'DESC' === $order ) ? 'ASC' : 'DESC';
			$url        = add_query_arg(
				array(
					'orderby' => $column,
					'order'   => $next_order,
					'rx_post' => $post_id ? $post_id : null,
				),
				admin_url( 'edit-comments.php?page=rx-feedback' )
			);
			$arrow      = $column === $orderby ? ( 'ASC' === $order ? ' ↑' : ' ↓' ) : '';
			printf( '<a href="%s">%s%s</a>', esc_url( $url ), esc_html( $label ), esc_html( $arrow ) );
		};
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Reader feedback', 'reader-experience' ); ?></h1>
			<hr class="wp-header-end">

			<?php if ( $deleted > 0 ) : ?>
				<div class="notice notice-success is-dismissible"><p>
					<?php
					echo esc_html(
						sprintf(
							/* translators: %d: number of deleted feedback items */
							_n( '%d feedback item deleted.', '%d feedback items deleted.', $deleted, 'reader-experience' ),
							$deleted
						)
					);
					?>
				</p></div>
			<?php endif; ?>

			<div style="display: flex; flex-wrap: wrap; align-items: center; gap: 12px; margin: 1em 0;">
			<form method="get" style="margin: 0;">
				<input type="hidden" name="page" value="rx-feedback">
				<label for="rx-post-filter"><?php esc_html_e( 'Article:', 'reader-experience' ); ?></label>
				<select name="rx_post" id="rx-post-filter" onchange="this.form.submit()">
					<option value="0"><?php esc_html_e( 'All articles', 'reader-experience' ); ?></option>
					<?php foreach ( $posts as $post_item ) : ?>
						<option value="<?php echo (int) $post_item['post_id']; ?>" <?php selected( $post_id, $post_item['post_id'] ); ?>>
							<?php echo esc_html( $post_item['post_title'] ? $post_item['post_title'] : __( '(untitled)', 'reader-experience' ) ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<noscript><button type="submit"><?php esc_html_e( 'Filter', 'reader-experience' ); ?></button></noscript>
			</form>

			<?php // Same row as the filter; the form attribute ties the button to the table form below. ?>
			<button
				type="submit"
				form="rx-feedback-form"
				name="rx_bulk_delete"
				value="1"
				class="button action"
				id="rx-bulk-delete"
				data-rx-confirm="<?php echo esc_attr__( 'Delete the selected feedback?', 'reader-experience' ); ?>"
				disabled
			><?php esc_html_e( 'Delete selected', 'reader-experience' ); ?></button>
			</div>

			<form method="post" id="rx-feedback-form">
				<?php wp_nonce_field( self::NONCE_ACTION ); ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<td class="manage-column column-cb check-column">
								<input type="checkbox" id="rx-select-all" aria-label="<?php esc_attr_e( 'Select all', 'reader-experience' ); ?>">
							</td>
							<th><?php $sort_link( 'post_title', __( 'Article', 'reader-experience' ) ); ?></th>
							<th style="width:9em"><?php esc_html_e( 'Rating', 'reader-experience' ); ?></th>
							<th style="width:35%"><?php esc_html_e( 'Reaction', 'reader-experience' ); ?></th>
							<th><?php esc_html_e( 'Sender', 'reader-experience' ); ?></th>
							<th style="width:10em"><?php $sort_link( 'created', __( 'Date', 'reader-experience' ) ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( ! $result['rows'] ) : ?>
							<tr><td colspan="6"><?php esc_html_e( 'No feedback yet.', 'reader-experience' ); ?></td></tr>
						<?php endif; ?>
						<?php foreach ( $result['rows'] as $row ) : ?>
							<tr>
								<th scope="row" class="check-column">
									<input type="checkbox" name="rx_ids[]" value="<?php echo (int) $row['id']; ?>" aria-label="<?php esc_attr_e( 'Select this feedback', 'reader-experience' ); ?>">
								</th>
								<td>
									<a href="<?php echo esc_url( (string) get_edit_post_link( (int) $row['post_id'] ) ); ?>">
										<?php echo esc_html( (string) ( $row['post_title'] ? $row['post_title'] : __( '(untitled)', 'reader-experience' ) ) ); ?>
									</a>
								</td>
								<td><span class="rx-admin-icon"><?php echo $icons[ $row['value'] ] ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span></td>
								<td><?php echo esc_html( (string) ( $row['text'] ?? '' ) ); ?></td>
								<td>
									<?php if ( ! empty( $row['comment_id'] ) ) : ?>
										<a href="<?php echo esc_url( admin_url( 'comment.php?action=editcomment&c=' . (int) $row['comment_id'] ) ); ?>"><?php esc_html_e( 'Comment posted', 'reader-experience' ); ?></a>
									<?php elseif ( ! empty( $row['wants_contact'] ) ) : ?>
										<?php echo esc_html( (string) ( $row['contact_name'] ?? '' ) ); ?>
										<?php if ( ! empty( $row['contact_email'] ) ) : ?>
											&nbsp;&lt;<a href="<?php echo esc_url( 'mailto:' . $row['contact_email'] ); ?>"><?php echo esc_html( $row['contact_email'] ); ?></a>&gt;
										<?php endif; ?>
									<?php else : ?>
										—
									<?php endif; ?>
								</td>
								<td><?php echo esc_html( mysql2date( 'j M Y H:i', (string) $row['created'] ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</form>
			<script>
				( function () {
					var form = document.getElementById( 'rx-feedback-form' );
					var all = document.getElementById( 'rx-select-all' );
					var button = document.getElementById( 'rx-bulk-delete' );
					if ( ! form || ! all || ! button ) { return; }
					var boxes = function () { return Array.prototype.slice.call( form.querySelectorAll( 'input[name="rx_ids[]"]' ) ); };
					var update = function () { button.disabled = ! boxes().some( function ( box ) { return box.checked; } ); };
					all.addEventListener( 'change', function () { boxes().forEach( function ( box ) { box.checked = all.checked; } ); update(); } );
					form.addEventListener( 'change', update );
					form.addEventListener( 'submit', function ( event ) {
						if ( ! window.confirm( button.getAttribute( 'data-rx-confirm' ) ) ) { event.preventDefault(); }
					} );
				}() );
			</script>

			<?php if ( $total_pages > 1 ) : ?>
				<p class="tablenav">
					<span class="pagination-links">
						<?php
						echo paginate_links( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core escapes the links.
							array(
								'base'     => add_query_arg( 'paged', '%#%' ),
								'format'   => '',
								'current'  => $page,
								'total'    => $total_pages,
								'add_args' => array(
									'page'    => 'rx-feedback',
									'rx_post' => $post_id ? $post_id : null,
									'orderby' => $orderby,
									'order'   => $order,
								),
							)
						);
						?>
					</span>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Deletes the selected rows when the form was submitted (with a valid nonce), then redirects
	 * back with the number of deleted rows, so a refresh does not submit the form again.
	 *
	 * @return void
	 */
	private function maybe_handle_bulk_delete(): void {
		if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || empty( $_POST['rx_bulk_delete'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- Checked below.
			return;
		}

		check_admin_referer( self::NONCE_ACTION );

		$ids     = array_map( 'absint', (array) wp_unslash( $_POST['rx_ids'] ?? array() ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Cast to integers.
		$deleted = $this->repository->delete_many( $ids );
		$back    = wp_get_referer() ? (string) wp_get_referer() : admin_url( 'edit-comments.php?page=rx-feedback' );

		wp_safe_redirect( add_query_arg( 'rx_deleted', $deleted, remove_query_arg( 'rx_deleted', $back ) ) );
		exit;
	}
}
