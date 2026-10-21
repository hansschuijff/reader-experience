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
$href  = comments_open( $post_id ) || $count > 0
	? get_comments_link( $post_id )
	: '';

if ( '' === $href ) {
	return;
}
?>
<a <?php echo get_block_wrapper_attributes( array( 'class' => 'rx-reset-button rx-bar__button' ) ); ?> href="<?php echo esc_url( $href ); ?>" title="<?php esc_attr_e( 'Comments', 'reader-experience' ); ?>">
	<span class="rx-bar__icon" aria-hidden="true"><?php echo Icons::get( 'comments' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
	<span class="rx-bar__label">
		<?php
		printf(
			/* translators: %d: number of comments */
			esc_html( _n( '%d comment', '%d comments', $count, 'reader-experience' ) ),
			$count
		);
		?>
	</span>
</a>
