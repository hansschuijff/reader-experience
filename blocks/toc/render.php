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

use ReaderExperience\Modules\Reading\HeadingIndexer;

$post_id = (int) ( $block->context['postId'] ?? get_the_ID() );
if ( ! $post_id ) {
	return;
}

$max_level       = max( 2, min( 4, (int) ( $attributes['maxLevel'] ?? 3 ) ) );
$min_items       = max( 1, (int) ( $attributes['minItems'] ?? 3 ) );
$style           = ( 'compact' === ( $attributes['style'] ?? 'standard' ) ) ? 'compact' : 'standard';
$collapse_nested = ! empty( $attributes['collapseNested'] );
$items           = HeadingIndexer::headings_for_post( $post_id, $max_level );

if ( count( $items ) < $min_items ) {
	return;
}

$title = isset( $attributes['title'] ) && '' !== trim( (string) $attributes['title'] )
	? (string) $attributes['title']
	: __( 'Contents', 'reader-experience' );

/*
 * Colour and font size deliberately do not use WordPress' built-in block supports: those
 * turned out to behave differently for linked text (the headings are <a> tags) than for plain
 * text, and differently for a theme colour than for a custom colour. With our own attributes,
 * applied as CSS variables on the wrapper element, the editor behaves exactly like the front end.
 */
$style_props = array();
if ( ! empty( $attributes['textColor'] ) ) {
	$style_props[] = '--rx-toc-text:' . esc_attr( (string) $attributes['textColor'] );
}
if ( ! empty( $attributes['backgroundColor'] ) ) {
	$style_props[] = '--rx-toc-bg:' . esc_attr( (string) $attributes['backgroundColor'] );
}
if ( ! empty( $attributes['fontSize'] ) ) {
	$style_props[] = '--rx-toc-font-size:' . (int) $attributes['fontSize'] . 'px';
}
$inline_style = implode( ';', $style_props );

/**
 * Prints the link of one table of contents entry.
 *
 * @param array{id:string,text:string,level:int} $item Heading.
 */
$render_link = static function ( array $item ): void {
	printf(
		'<a href="#%s">%s</a>',
		esc_attr( $item['id'] ),
		esc_html( $item['text'] )
	);
};

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'rx-toc rx-toc--' . $style,
		'open'  => '',
		'id'    => 'rx-toc',
		'style' => $inline_style,
	)
);
?>
<details <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by WordPress. ?>>
	<summary class="rx-toc__summary"><?php echo esc_html( $title ); ?></summary>

	<?php if ( $collapse_nested ) : ?>
		<ul class="rx-toc__list rx-toc__list--grouped">
			<?php foreach ( HeadingIndexer::group_by_top_level( $items ) as $group ) : ?>
				<?php if ( $group['children'] ) : ?>
					<li class="rx-toc__group">
						<details class="rx-toc__group-toggle">
							<summary class="rx-toc__item rx-toc__item--l<?php echo (int) $group['item']['level']; ?>"><?php $render_link( $group['item'] ); ?></summary>
							<ul class="rx-toc__sublist">
								<?php foreach ( $group['children'] as $child ) : ?>
									<li class="rx-toc__item rx-toc__item--l<?php echo (int) $child['level']; ?>"><?php $render_link( $child ); ?></li>
								<?php endforeach; ?>
							</ul>
						</details>
					</li>
				<?php else : ?>
					<li class="rx-toc__item rx-toc__item--l<?php echo (int) $group['item']['level']; ?>"><?php $render_link( $group['item'] ); ?></li>
				<?php endif; ?>
			<?php endforeach; ?>
		</ul>
	<?php else : ?>
		<ol class="rx-toc__list">
			<?php foreach ( $items as $item ) : ?>
				<li class="rx-toc__item rx-toc__item--l<?php echo (int) $item['level']; ?>"><?php $render_link( $item ); ?></li>
			<?php endforeach; ?>
		</ol>
	<?php endif; ?>
</details>
