<?php
/**
 * Render template of the table of contents block.
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

$max_level       = max( 2, min( 4, (int) ( $attributes['maxLevel'] ?? 3 ) ) );
$min_items       = max( 1, (int) ( $attributes['minItems'] ?? 3 ) );
$style           = ( 'compact' === ( $attributes['style'] ?? 'standard' ) ) ? 'compact' : 'standard';
$collapse_nested = ! empty( $attributes['collapseNested'] );

$title = isset( $attributes['title'] ) && '' !== trim( (string) $attributes['title'] )
	? (string) $attributes['title']
	: __( 'Contents', 'reader-experience' );

/*
 * Colour and font size deliberately do not use WordPress' built-in block supports: those
 * turned out to behave differently for linked text (the headings are <a> tags) than for plain
 * text, and differently for a theme colour than for a custom colour. With our own attributes,
 * applied as CSS variables on the wrapper element, the editor behaves exactly like the front end.
 */
$style_props      = array();
$text_color       = CssValue::color( (string) ( $attributes['textColor'] ?? '' ) );
$background_color = CssValue::color( (string) ( $attributes['backgroundColor'] ?? '' ) );
if ( '' !== $text_color ) {
	$style_props[] = '--rx-toc-text:' . esc_attr( $text_color );
}
if ( '' !== $background_color ) {
	$style_props[] = '--rx-toc-bg:' . esc_attr( $background_color );
}
if ( ! empty( $attributes['fontSize'] ) ) {
	$style_props[] = '--rx-toc-font-size:' . (int) $attributes['fontSize'] . 'px';
}
$inline_style = implode( ';', $style_props );

/*
 * The list is built in the browser (view.js) from the headings that are on the page, so it always
 * matches what the visitor sees and does not depend on heading ids. The block stays hidden until
 * the script has found enough headings.
 */
$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class'             => 'rx-toc rx-toc--' . $style,
		'open'              => '',
		'id'                => 'rx-toc',
		'style'             => $inline_style,
		'hidden'            => '',
		'data-rx-toc'       => '',
		'data-rx-max-level' => (string) $max_level,
		'data-rx-min-items' => (string) $min_items,
		'data-rx-collapse'  => $collapse_nested ? '1' : '0',
	)
);
?>
<details <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by WordPress. ?>>
	<summary class="rx-toc__summary"><?php echo esc_html( $title ); ?></summary>
	<ol class="rx-toc__list"></ol>
</details>
