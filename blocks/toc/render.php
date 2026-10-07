<?php
/**
 * @var array    $attributes
 * @var string   $content
 * @var WP_Block $block
 */

declare(strict_types=1);

use ReaderExperience\Modules\Reading\HeadingIndexer;

$post_id = (int) ( $block->context['postId'] ?? get_the_ID() );
if ( ! $post_id ) {
	return;
}

$max_level      = max( 2, min( 4, (int) ( $attributes['maxLevel'] ?? 3 ) ) );
$min_items      = max( 1, (int) ( $attributes['minItems'] ?? 3 ) );
$style          = ( 'compact' === ( $attributes['style'] ?? 'standard' ) ) ? 'compact' : 'standard';
$collapseNested = ! empty( $attributes['collapseNested'] );
$items          = HeadingIndexer::headingsForPost( $post_id, $max_level );

if ( count( $items ) < $min_items ) {
	return;
}

$title = isset( $attributes['title'] ) && '' !== trim( (string) $attributes['title'] )
	? (string) $attributes['title']
	: __( 'Inhoud', 'reader-experience' );

/*
 * Kleur en tekstgrootte bewust niet via de ingebouwde block-supports van WordPress:
 * die bleken zich anders te gedragen voor gelinkte tekst (de koppen zijn <a>-tags)
 * dan voor gewone tekst, en anders voor een thema-kleur dan voor een custom kleur.
 * Met eigen attributen, toegepast via CSS-variabelen op het wrapper-element, gedraagt
 * de editor zich exact zoals de voorkant.
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

/** @param array{id:string,text:string,level:int} $item */
$render_link = static function ( array $item ): void {
	printf(
		'<a href="#%s">%s</a>',
		esc_attr( $item['id'] ),
		esc_html( $item['text'] )
	);
};
?>
<details <?php echo get_block_wrapper_attributes( array( 'class' => 'rx-toc rx-toc--' . $style, 'open' => '', 'id' => 'rx-toc', 'style' => $inline_style ) ); ?>>
	<summary class="rx-toc__summary"><?php echo esc_html( $title ); ?></summary>

	<?php if ( $collapseNested ) : ?>
		<ul class="rx-toc__list rx-toc__list--grouped">
			<?php foreach ( HeadingIndexer::groupByTopLevel( $items ) as $group ) : ?>
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
