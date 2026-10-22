<?php
/**
 * Render template of the reading progress block.
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

use ReaderExperience\Support\CssValue;

if ( ! is_singular() ) {
	return;
}

$target = isset( $attributes['target'] ) && '' !== trim( (string) $attributes['target'] )
	? (string) $attributes['target']
	: '.wp-block-post-content';

$color        = CssValue::color( (string) ( $attributes['color'] ?? '' ) );
$inline_style = '' !== $color ? sprintf( '--rx-progress-color:%s;', esc_attr( $color ) ) : '';

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class'          => 'rx-progress',
		'aria-hidden'    => 'true',
		'data-rx-target' => $target,
		'style'          => $inline_style,
	)
);
?>
<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by WordPress. ?>><span class="rx-progress__bar"></span></div>
