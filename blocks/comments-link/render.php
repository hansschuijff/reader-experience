<?php
/**
 * @var array    $attributes
 * @var string   $content
 * @var WP_Block $block
 */

declare(strict_types=1);

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
<a <?php echo get_block_wrapper_attributes( array( 'class' => 'rx-reset-button rx-bar__button' ) ); ?> href="<?php echo esc_url( $href ); ?>" title="<?php esc_attr_e( 'Reacties', 'reader-experience' ); ?>">
	<span class="rx-bar__icon" aria-hidden="true"><?php echo Icons::get( 'comments' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
	<span class="rx-bar__label">
		<?php
		printf(
			/* translators: %d: aantal reacties */
			esc_html( _n( '%d reactie', '%d reacties', $count, 'reader-experience' ) ),
			$count
		);
		?>
	</span>
</a>
