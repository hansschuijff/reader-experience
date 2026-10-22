<?php
/**
 * Render template of the comments link block.
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

$count = (int) get_comments_number( $post_id );
$open  = comments_open( $post_id );

// Nothing to show or to write: no link.
if ( ! $open && 0 === $count ) {
	return;
}

/*
 * Where the link goes. "auto" takes the reader to the comments when there are some and to the form
 * when there are none; the other values pick one of them, or an id of the theme's own.
 */
$target = (string) ( $attributes['target'] ?? 'auto' );
if ( 'auto' === $target ) {
	$target = ( $count > 0 || ! $open ) ? 'comments' : 'respond';
}

$anchor = 'comments';
// With comments closed there is no form to jump to.
if ( 'respond' === $target && $open ) {
	$anchor = 'respond';
} elseif ( 'custom' === $target ) {
	// An id: letters, digits, hyphens and underscores only. Without a usable id, fall back to the comments.
	$custom = preg_replace( '/[^A-Za-z0-9_\-]/', '', ltrim( trim( (string) ( $attributes['anchor'] ?? '' ) ), '#' ) );
	$anchor = '' !== $custom ? (string) $custom : 'comments';
}

$href = get_permalink( $post_id ) . '#' . $anchor;
?>
<a <?php echo get_block_wrapper_attributes( array( 'class' => 'dwp-rx-reset-button dwp-rx-bar__button' ) ); ?> href="<?php echo esc_url( $href ); ?>" title="<?php esc_attr_e( 'Comments', 'reader-experience' ); ?>">
	<span class="dwp-rx-bar__icon" aria-hidden="true"><?php echo Icons::get( 'comments' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
	<span class="dwp-rx-bar__label">
		<?php
		printf(
			/* translators: %d: number of comments */
			esc_html( _n( '%d comment', '%d comments', $count, 'reader-experience' ) ),
			$count
		);
		?>
	</span>
</a>
