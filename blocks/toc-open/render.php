<?php
/**
 * @var array    $attributes
 * @var string   $content
 * @var WP_Block $block
 */

declare(strict_types=1);

use ReaderExperience\Support\Icons;

$post_id = (int) ( $block->context['postId'] ?? get_the_ID() );
if ( ! $post_id ) {
	return;
}

$post = get_post( $post_id );
if ( ! $post instanceof WP_Post || ! has_block( 'reader-experience/toc', $post ) ) {
	// Geen inhoudsopgave in dit artikel: de knop heeft dan niets om naartoe te springen.
	return;
}
?>
<button type="button" <?php echo get_block_wrapper_attributes( array( 'class' => 'rx-reset-button rx-bar__button' ) ); ?> data-rx-toc-open data-rx-toc-target="#rx-toc" title="<?php esc_attr_e( 'Inhoud', 'reader-experience' ); ?>">
	<span class="rx-bar__icon" aria-hidden="true"><?php echo Icons::get( 'toc' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
	<span class="rx-bar__label"><?php esc_html_e( 'Inhoud', 'reader-experience' ); ?></span>
</button>
