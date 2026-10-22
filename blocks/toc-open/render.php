<?php
/**
 * Render template of the table of contents button and its panel.
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

use ReaderExperience\Modules\Reading\ReadingTime;
use ReaderExperience\Support\Icons;

if ( ! is_singular() ) {
	return;
}

$side      = ( 'right' === ( $attributes['side'] ?? 'left' ) ) ? 'right' : 'left';
$max_level = max( 2, min( 4, (int) ( $attributes['maxLevel'] ?? 3 ) ) );
$min_items = max( 1, (int) ( $attributes['minItems'] ?? 3 ) );
$collapse  = ! empty( $attributes['collapseNested'] );
$title     = isset( $attributes['title'] ) && '' !== trim( (string) $attributes['title'] )
	? (string) $attributes['title']
	: __( 'Contents', 'reader-experience' );
$push      = ( 'never' === ( $attributes['push'] ?? 'auto' ) ) ? 'never' : 'auto';
$stats     = ! empty( $attributes['showStats'] );
$panel_id  = wp_unique_id( 'dwp-rx-toc-drawer-' );

/*
 * The panel is built by view.js from the headings on the page. The button stays hidden until the
 * script has found enough of them, so it never opens an empty panel.
 */
?>
<button
	type="button"
	<?php echo get_block_wrapper_attributes( array( 'class' => 'dwp-rx-reset-button dwp-rx-bar__button' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by WordPress. ?>
	data-dwp-rx-toc-open
	hidden
	aria-haspopup="dialog"
	aria-expanded="false"
	aria-controls="<?php echo esc_attr( $panel_id ); ?>"
	title="<?php echo esc_attr( $title ); ?>"
>
	<span class="dwp-rx-bar__icon" aria-hidden="true"><?php echo Icons::get( 'toc' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
	<span class="dwp-rx-bar__label"><?php echo esc_html( $title ); ?></span>
</button>
<dialog
	id="<?php echo esc_attr( $panel_id ); ?>"
	class="dwp-rx-toc-drawer dwp-rx-toc-drawer--<?php echo esc_attr( $side ); ?>"
	data-dwp-rx-toc-drawer
	data-dwp-rx-max-level="<?php echo (int) $max_level; ?>"
	data-dwp-rx-min-items="<?php echo (int) $min_items; ?>"
	data-dwp-rx-push="<?php echo esc_attr( $push ); ?>"
	data-dwp-rx-collapse="<?php echo $collapse ? '1' : '0'; ?>"
	aria-label="<?php echo esc_attr( $title ); ?>"
>
	<div class="dwp-rx-toc-drawer__header">
		<span class="dwp-rx-toc-drawer__title"><?php echo esc_html( $title ); ?></span>
		<button type="button" class="dwp-rx-reset-button dwp-rx-toc-drawer__close" data-dwp-rx-toc-close aria-label="<?php esc_attr_e( 'Close', 'reader-experience' ); ?>">
			<?php echo Icons::get( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</button>
	</div>
	<?php
	if ( $stats ) :
		$reading = new ReadingTime();
		$text    = (string) get_post_field( 'post_content', get_queried_object_id() );
		/* translators: %s: number of minutes */
		$minutes = sprintf( __( '%s min', 'reader-experience' ), number_format_i18n( $reading->minutes( $text ) ) );
		?>
	<dl class="dwp-rx-toc-drawer__stats">
		<dt><?php esc_html_e( 'Characters', 'reader-experience' ); ?></dt>
		<dd><?php echo esc_html( number_format_i18n( $reading->character_count( $text ) ) ); ?></dd>
		<dt><?php esc_html_e( 'Words', 'reader-experience' ); ?></dt>
		<dd><?php echo esc_html( number_format_i18n( $reading->word_count( $text ) ) ); ?></dd>
		<dt><?php esc_html_e( 'Reading time', 'reader-experience' ); ?></dt>
		<dd><?php echo esc_html( $minutes ); ?></dd>
	</dl>
	<?php endif; ?>
	<nav class="dwp-rx-toc dwp-rx-toc--standard dwp-rx-toc-drawer__body" aria-label="<?php echo esc_attr( $title ); ?>">
		<ol class="dwp-rx-toc__list"></ol>
	</nav>
</dialog>
