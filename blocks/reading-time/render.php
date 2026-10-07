<?php
/**
 * Serverside weergave van het leestijdblok.
 *
 * @var array    $attributes
 * @var string   $content
 * @var WP_Block $block
 */

declare(strict_types=1);

use ReaderExperience\Modules\Reading\ReadingTime;

$post_id = (int) ( $block->context['postId'] ?? get_the_ID() );
if ( ! $post_id ) {
	return;
}

$minutes = ( new ReadingTime() )->minutes( (string) get_post_field( 'post_content', $post_id ) );

$extra = array();
if ( ! empty( $attributes['remaining'] ) ) {
	$extra = array(
		'data-rx-remaining'       => '1',
		'data-rx-minutes'         => (string) $minutes,
		'data-rx-target'          => (string) ( $attributes['target'] ?? '.wp-block-post-content' ),
		/* translators: %d: aantal resterende minuten */
		'data-rx-label-remaining' => __( 'nog ~%d min', 'reader-experience' ),
		'data-rx-label-done'      => __( 'Uitgelezen', 'reader-experience' ),
	);
}
?>
<p <?php echo get_block_wrapper_attributes( $extra ); ?>><span class="rx-rt__label"><?php
	printf(
		/* translators: %d: aantal minuten */
		esc_html( _n( '%d min leestijd', '%d min leestijd', $minutes, 'reader-experience' ) ),
		(int) $minutes
	);
?></span></p>
