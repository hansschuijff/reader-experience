<?php
/**
 * Render template of the share block.
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

use ReaderExperience\Modules\Sharing\ChannelRegistry;
use ReaderExperience\Support\Icons;

$post_id = (int) ( $block->context['postId'] ?? get_the_ID() );
if ( ! $post_id ) {
	return;
}

$url   = (string) get_permalink( $post_id );
$title = (string) get_the_title( $post_id );

$popover_id = 'dwp-rx-share-' . $post_id . '-' . wp_unique_id();
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'dwp-rx-share' ) ); ?> data-dwp-rx-share data-dwp-rx-url="<?php echo esc_url( $url ); ?>" data-dwp-rx-title="<?php echo esc_attr( $title ); ?>" data-dwp-rx-rest="<?php echo esc_url( rest_url( 'dewitteprins/reader-experience/v1/share' ) ); ?>" data-dwp-rx-post="<?php echo (int) $post_id; ?>">
	<button type="button" class="dwp-rx-reset-button dwp-rx-bar__button dwp-rx-share__trigger" aria-haspopup="dialog" aria-expanded="false" aria-controls="<?php echo esc_attr( $popover_id ); ?>" title="<?php esc_attr_e( 'Share', 'reader-experience' ); ?>">
		<span class="dwp-rx-bar__icon" aria-hidden="true"><?php echo Icons::get( 'share' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		<span class="dwp-rx-bar__label"><?php esc_html_e( 'Share', 'reader-experience' ); ?></span>
	</button>

	<div id="<?php echo esc_attr( $popover_id ); ?>" class="dwp-rx-share__popover" data-dwp-rx-panel role="dialog" aria-label="<?php esc_attr_e( 'Share', 'reader-experience' ); ?>" hidden>
		<div class="dwp-rx-share__channels">
			<?php foreach ( ( new ChannelRegistry() )->channels() as $slug => $channel ) : ?>
				<?php $channel_style = ! empty( $channel['color'] ) ? 'background:' . $channel['color'] : ''; ?>
				<a class="dwp-rx-share__channel" data-dwp-rx-channel="<?php echo esc_attr( $slug ); ?>" href="<?php echo esc_url( ( $channel['url'] )( $url, $title ) ); ?>" target="_blank" rel="noopener noreferrer">
					<span
						class="dwp-rx-share__channel-icon"
						aria-hidden="true"
						<?php echo '' !== $channel_style ? 'style="' . esc_attr( $channel_style ) . '"' : ''; ?>
					><?php echo Icons::get( $channel['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<span class="dwp-rx-share__channel-label"><?php echo esc_html( $channel['label'] ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
		<div class="dwp-rx-share__copyrow">
			<input type="text" class="dwp-rx-share__url" value="<?php echo esc_url( $url ); ?>" readonly aria-label="<?php esc_attr_e( 'Link to this article', 'reader-experience' ); ?>">
			<button type="button" class="wp-element-button dwp-rx-share__copy" data-dwp-rx-copy data-dwp-rx-copied-label="<?php esc_attr_e( 'Copied', 'reader-experience' ); ?>">
				<span class="dwp-rx-share__copy-icon" aria-hidden="true"><?php echo Icons::get( 'copy' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<span class="dwp-rx-share__copy-label" data-dwp-rx-copy-label><?php esc_html_e( 'Copy', 'reader-experience' ); ?></span>
			</button>
		</div>
	</div>
</div>
