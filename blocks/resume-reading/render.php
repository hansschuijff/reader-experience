<?php
/**
 * Render template of the resume reading block.
 *
 * Prints a hidden notice. The script shows it on a return visit, when the browser remembers that
 * the visitor stopped somewhere in this article; the position itself never leaves the browser.
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

if ( ! is_singular() ) {
	return;
}

$post_id = (int) ( $block->context['postId'] ?? get_the_ID() );
if ( ! $post_id || post_password_required( $post_id ) ) {
	return;
}

// Short articles are read in one go: no notice, and no markup for the script to look at.
$min_minutes = max( 1, (int) ( $attributes['minMinutes'] ?? 5 ) );
$minutes     = ( new ReadingTime() )->minutes( (string) get_post_field( 'post_content', $post_id ) );
if ( $minutes < $min_minutes ) {
	return;
}

$target = isset( $attributes['target'] ) && '' !== trim( (string) $attributes['target'] )
	? (string) $attributes['target']
	: '.wp-block-post-content';

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class'                     => 'dwp-rx-resume',
		'role'                      => 'status',
		'hidden'                    => true,
		'data-dwp-rx-post'          => (string) $post_id,
		'data-dwp-rx-target'        => $target,
		'data-dwp-rx-seconds'       => (string) max( 0, (int) ( $attributes['displaySeconds'] ?? 20 ) ),
		'data-dwp-rx-label-heading' => __( 'Continue reading at:', 'reader-experience' ),
		'data-dwp-rx-label-plain'   => __( 'Continue where you left off?', 'reader-experience' ),
	)
);
?>
<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by WordPress. ?>>
	<p class="dwp-rx-resume__text"><span class="dwp-rx-resume__label"></span> <strong class="dwp-rx-resume__heading"></strong></p>
	<div class="dwp-rx-resume__actions">
		<button type="button" class="dwp-rx-resume__continue wp-element-button"><?php esc_html_e( 'Continue', 'reader-experience' ); ?></button>
		<button type="button" class="dwp-rx-resume__restart"><?php esc_html_e( 'Start over', 'reader-experience' ); ?></button>
	</div>
</div>
