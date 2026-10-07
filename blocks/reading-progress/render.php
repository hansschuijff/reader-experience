<?php
/**
 * @var array    $attributes
 * @var string   $content
 * @var WP_Block $block
 */

declare(strict_types=1);

if ( ! is_singular() ) {
	return;
}

$target = isset( $attributes['target'] ) && '' !== trim( (string) $attributes['target'] )
	? (string) $attributes['target']
	: '.wp-block-post-content';

$color       = isset( $attributes['color'] ) ? (string) $attributes['color'] : '';
$inlineStyle = '' !== $color ? sprintf( '--rx-progress-color:%s;', esc_attr( $color ) ) : '';
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'rx-progress', 'aria-hidden' => 'true', 'data-rx-target' => $target, 'style' => $inlineStyle ) ); ?>><span class="rx-progress__bar"></span></div>
