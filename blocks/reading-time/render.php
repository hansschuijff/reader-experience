<?php
/**
 * Render template of the reading time block.
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

use ReaderExperience\Modules\Reading\ReadingTime;

$post_id = (int) ( $block->context['postId'] ?? get_the_ID() );
if ( ! $post_id ) {
	return;
}

$minutes = ( new ReadingTime() )->minutes( (string) get_post_field( 'post_content', $post_id ) );

$extra = array();
if ( ! empty( $attributes['remaining'] ) ) {
	$extra = array(
		'data-dwp-rx-remaining'       => '1',
		'data-dwp-rx-minutes'         => (string) $minutes,
		'data-dwp-rx-target'          => (string) ( $attributes['target'] ?? '.wp-block-post-content' ),
		/* translators: %d: number of minutes left */
		'data-dwp-rx-label-remaining' => __( '~%d min left', 'reader-experience' ),
		'data-dwp-rx-label-done'      => __( 'Finished', 'reader-experience' ),
	);
}

$label = sprintf(
	/* translators: %d: number of minutes */
	_n( '%d min read', '%d min read', $minutes, 'reader-experience' ),
	$minutes
);
?>
<p <?php echo get_block_wrapper_attributes( $extra ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by WordPress. ?>><span class="dwp-rx-rt__label"><?php echo esc_html( $label ); ?></span></p>
