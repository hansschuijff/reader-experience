<?php

declare(strict_types=1);

namespace ReaderExperience\Modules\Rating\Admin;

use ReaderExperience\Modules\Rating\FeedbackRepository;
use ReaderExperience\Support\Icons;

final class PostColumn {

	private FeedbackRepository $repository;

	public function __construct() {
		$this->repository = new FeedbackRepository();
	}

	public function register(): void {
		add_filter( 'manage_post_posts_columns', array( $this, 'addColumn' ) );
		add_action( 'manage_post_posts_custom_column', array( $this, 'renderColumn' ), 10, 2 );
		add_action( 'admin_head-edit.php', array( $this, 'printStyle' ) );
	}

	public function printStyle(): void {
		echo '<style>.rx-admin-icon svg{width:14px;height:14px;vertical-align:text-bottom;margin-inline-end:2px;}</style>';
	}

	/** @param array<string,string> $columns */
	public function addColumn( array $columns ): array {
		$columns['rx_feedback'] = __( 'Waardering', 'reader-experience' );
		return $columns;
	}

	public function renderColumn( string $column, int $postId ): void {
		if ( 'rx_feedback' !== $column ) {
			return;
		}

		$totals = $this->repository->totalsForPost( $postId );
		$total  = array_sum( $totals );

		if ( 0 === $total ) {
			echo '<span aria-hidden="true">—</span>';
			return;
		}

		printf(
			'<span class="rx-admin-icon">%s %d</span> &nbsp; <span class="rx-admin-icon">%s %d</span>',
			Icons::get( 'thumb_up' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- vaste SVG uit de plugin
			(int) $totals['ja'],
			Icons::get( 'thumb_down' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			(int) $totals['nee']
		);
	}
}
