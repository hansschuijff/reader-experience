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
use function add_action;
use function add_comments_page;
use function add_query_arg;
use function admin_url;
use function ceil;
use function check_admin_referer;
use function current_user_can;
use function esc_attr;
use function esc_html;
use function esc_html_e;
use function esc_js;
use function esc_url;
use function get_edit_post_link;
use function max;
use function mysql2date;
use function paginate_links;
use function printf;
use function remove_query_arg;
use function selected;
use function strtoupper;
use function wp_nonce_url;
use function wp_safe_redirect;

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
	private const NONCE_ACTION = 'rx_feedback_delete';

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
	}

	/**
	 * Adds the page under Comments.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function add_page(): void {
		$hook = add_comments_page(
			__( 'Reader Experience feedback', 'reader-experience' ),
			__( 'Feedback', 'reader-experience' ),
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

		$this->maybe_handle_delete();

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
			<h1><?php esc_html_e( 'Feedback', 'reader-experience' ); ?></h1>

			<form method="get" style="margin: 1em 0;">
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

			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php $sort_link( 'post_title', __( 'Article', 'reader-experience' ) ); ?></th>
						<th><?php esc_html_e( 'Rating', 'reader-experience' ); ?></th>
						<th><?php esc_html_e( 'Explanation', 'reader-experience' ); ?></th>
						<th><?php esc_html_e( 'Follow-up', 'reader-experience' ); ?></th>
						<th><?php $sort_link( 'created', __( 'Date', 'reader-experience' ) ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! $result['rows'] ) : ?>
						<tr><td colspan="6"><?php esc_html_e( 'No feedback yet.', 'reader-experience' ); ?></td></tr>
					<?php endif; ?>
					<?php foreach ( $result['rows'] as $row ) : ?>
						<tr>
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
							<td>
								<a
									href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'rx_delete' => (int) $row['id'] ) ), self::NONCE_ACTION ) ); ?>"
									onclick="return confirm('<?php echo esc_js( __( 'Delete this feedback?', 'reader-experience' ) ); ?>')"
								><?php esc_html_e( 'Delete', 'reader-experience' ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

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
	 * Deletes a row when the request asks for it (with a valid nonce) and redirects back.
	 *
	 * @return void
	 */
	private function maybe_handle_delete(): void {
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
