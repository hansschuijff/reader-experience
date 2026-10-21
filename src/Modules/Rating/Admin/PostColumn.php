<?php
/**
 * Rating column in the posts list.
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
use function add_filter;
use function array_sum;
use function printf;

/**
 * Adds a column with the number of thumbs up and down to the posts list.
 */
final class PostColumn {

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
		add_filter( 'manage_post_posts_columns', array( $this, 'add_column' ) );
		add_action( 'manage_post_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
		add_action( 'admin_head-edit.php', array( $this, 'print_style' ) );
	}

	/**
	 * Prints the small stylesheet for the icons in the column.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function print_style(): void {
		echo '<style>.rx-admin-icon svg{width:14px;height:14px;vertical-align:text-bottom;margin-inline-end:2px;}</style>';
	}

	/**
	 * Adds the column.
	 *
	 * @since 0.1.0
	 * @param array<string,string> $columns Columns of the posts list.
	 * @return array<string,string>
	 */
	public function add_column( array $columns ): array {
		$columns['rx_feedback'] = __( 'Rating', 'reader-experience' );
		return $columns;
	}

	/**
	 * Prints the cell of the column.
	 *
	 * @since 0.1.0
	 * @param string $column  Column name.
	 * @param int    $post_id Post id.
	 * @return void
	 */
	public function render_column( string $column, int $post_id ): void {
		if ( 'rx_feedback' !== $column ) {
			return;
		}

		$totals = $this->repository->totals_for_post( $post_id );
		$total  = array_sum( $totals );

		if ( 0 === $total ) {
			echo '<span aria-hidden="true">—</span>';
			return;
		}

		printf(
			'<span class="rx-admin-icon">%s %d</span> &nbsp; <span class="rx-admin-icon">%s %d</span>',
			Icons::get( 'thumb_up' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed SVG from the plugin.
			(int) $totals['ja'],
			Icons::get( 'thumb_down' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed SVG from the plugin.
			(int) $totals['nee']
		);
	}
}
