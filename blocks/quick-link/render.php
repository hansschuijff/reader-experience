<?php
/**
 * Render template of the quick link block.
 *
 * @package ReaderExperience
 * @author  Hans Schuijff <@hansschuijff>
 * @license GPL-2.0-only
 * @since   0.3.0
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

declare( strict_types=1 );

use ReaderExperience\Support\Icons;

/*
 * Only web, mail and phone links, or a relative address or #anchor. esc_url() returns an empty
 * string for anything else (javascript:, data:, ...), and then the block renders nothing.
 */
$protocols = array( 'http', 'https', 'mailto', 'tel' );
$url       = esc_url_raw( trim( (string) ( $attributes['url'] ?? '' ) ), $protocols );
if ( '' === $url ) {
	return;
}

$icons = array( 'help', 'info', 'home', 'mail', 'phone' );
$icon  = in_array( $attributes['icon'] ?? 'help', $icons, true ) ? $attributes['icon'] : 'help';
$label = isset( $attributes['label'] ) && '' !== trim( (string) $attributes['label'] )
	? trim( (string) $attributes['label'] )
	: __( 'Link', 'reader-experience' );
$blank = ! empty( $attributes['newTab'] );
?>
<a <?php echo get_block_wrapper_attributes( array( 'class' => 'dwp-rx-reset-button dwp-rx-bar__button' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by WordPress. ?> href="<?php echo esc_url( $url, $protocols ); ?>" title="<?php echo esc_attr( $label ); ?>"<?php echo $blank ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>>
	<span class="dwp-rx-bar__icon" aria-hidden="true"><?php echo Icons::get( $icon ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
	<span class="dwp-rx-bar__label"><?php echo esc_html( $label ); ?></span>
</a>
