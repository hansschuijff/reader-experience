<?php
/**
 * @var array    $attributes
 * @var string   $content
 * @var WP_Block $block
 */

declare(strict_types=1);

use ReaderExperience\Modules\Sharing\ChannelRegistry;
use ReaderExperience\Support\Icons;

$post_id = (int) ( $block->context['postId'] ?? get_the_ID() );
if ( ! $post_id ) {
	return;
}

$url   = (string) get_permalink( $post_id );
$title = (string) get_the_title( $post_id );

$popover_id = 'rx-share-' . $post_id . '-' . wp_unique_id();
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'rx-share' ) ); ?> data-rx-share data-rx-url="<?php echo esc_url( $url ); ?>" data-rx-title="<?php echo esc_attr( $title ); ?>" data-rx-rest="<?php echo esc_url( rest_url( 'rx/v1/share' ) ); ?>" data-rx-post="<?php echo (int) $post_id; ?>">
	<button type="button" class="rx-reset-button rx-bar__button rx-share__trigger" aria-haspopup="dialog" aria-expanded="false" aria-controls="<?php echo esc_attr( $popover_id ); ?>" title="<?php esc_attr_e( 'Delen', 'reader-experience' ); ?>">
		<span class="rx-bar__icon" aria-hidden="true"><?php echo Icons::get( 'share' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		<span class="rx-bar__label"><?php esc_html_e( 'Delen', 'reader-experience' ); ?></span>
	</button>

	<div id="<?php echo esc_attr( $popover_id ); ?>" class="rx-share__popover" data-rx-panel role="dialog" aria-label="<?php esc_attr_e( 'Delen', 'reader-experience' ); ?>" hidden>
		<div class="rx-share__channels">
			<?php foreach ( ( new ChannelRegistry() )->channels() as $slug => $channel ) : ?>
				<a class="rx-share__channel" data-rx-channel="<?php echo esc_attr( $slug ); ?>" href="<?php echo esc_url( ( $channel['url'] )( $url, $title ) ); ?>" target="_blank" rel="noopener noreferrer">
					<span
						class="rx-share__channel-icon"
						aria-hidden="true"
						<?php if ( ! empty( $channel['color'] ) ) : ?>style="background:<?php echo esc_attr( $channel['color'] ); ?>"<?php endif; ?>
					><?php echo Icons::get( $channel['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<span class="rx-share__channel-label"><?php echo esc_html( $channel['label'] ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
		<div class="rx-share__copyrow">
			<span class="rx-share__url"><?php echo esc_html( preg_replace( '#^https?://#', '', $url ) ); ?></span>
			<button type="button" class="rx-reset-button rx-share__copy" data-rx-copy>
				<span aria-hidden="true"><?php echo Icons::get( 'copy' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<?php esc_html_e( 'Kopieer', 'reader-experience' ); ?>
			</button>
		</div>
	</div>
</div>
