<?php
/**
 * Render template of the table of contents button block.
 *
 * @package ReaderExperience
 * @author  Hans Schuijff <@hansschuijff>
 * @license GPL-2.0-only
 * @since   0.1.0
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

declare( strict_types=1 );

use ReaderExperience\Support\Icons;

$post_id = (int) ( $block->context['postId'] ?? get_the_ID() );
if ( ! $post_id ) {
	return;
}

$post = get_post( $post_id );
if ( ! $post instanceof WP_Post || ! has_block( 'reader-experience/toc', $post ) ) {
	// No table of contents in this article: the button would have nothing to jump to.
	return;
}
?>
<button type="button" <?php echo get_block_wrapper_attributes( array( 'class' => 'rx-reset-button rx-bar__button' ) ); ?> data-rx-toc-open data-rx-toc-target="#rx-toc" title="<?php esc_attr_e( 'Contents', 'reader-experience' ); ?>">
	<span class="rx-bar__icon" aria-hidden="true"><?php echo Icons::get( 'toc' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
	<span class="rx-bar__label"><?php esc_html_e( 'Contents', 'reader-experience' ); ?></span>
</button>
