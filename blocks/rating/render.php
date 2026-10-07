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

$variant = ( 'bar' === ( $attributes['variant'] ?? 'inline' ) ) ? 'bar' : 'inline';
$unique  = wp_unique_id( 'rx-rating-' );

$rest = array(
	'create'  => esc_url_raw( rest_url( 'rx/v1/rating' ) ),
	'text'    => esc_url_raw( rest_url( 'rx/v1/rating/' ) ),   // + {id}/tekst
	'followup' => esc_url_raw( rest_url( 'rx/v1/rating/' ) ),  // + {id}/vervolg
);
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'rx-rating rx-rating--' . esc_attr( $variant ) ) ); ?>
	data-rx-rating
	data-rx-post="<?php echo (int) $post_id; ?>"
	data-rx-rest-create="<?php echo esc_url( $rest['create'] ); ?>"
	data-rx-rest-text-base="<?php echo esc_url( $rest['text'] ); ?>"
	data-rx-rest-followup-base="<?php echo esc_url( $rest['followup'] ); ?>"
>
	<div class="rx-rating__thumbs" data-rx-step="thumbs">
		<button type="button" class="rx-reset-button rx-rating__thumb" data-rx-value="ja" aria-pressed="false" aria-label="<?php esc_attr_e( 'Nuttig', 'reader-experience' ); ?>" title="<?php esc_attr_e( 'Nuttig', 'reader-experience' ); ?>">
			<?php echo Icons::get( 'thumb_up' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- vaste, vertrouwde SVG uit de plugin zelf ?>
			<span class="rx-rating__thumb-label"><?php esc_html_e( 'Nuttig', 'reader-experience' ); ?></span>
		</button>
		<button type="button" class="rx-reset-button rx-rating__thumb" data-rx-value="nee" aria-pressed="false" aria-label="<?php esc_attr_e( 'Niet nuttig', 'reader-experience' ); ?>" title="<?php esc_attr_e( 'Niet nuttig', 'reader-experience' ); ?>">
			<?php echo Icons::get( 'thumb_down' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span class="rx-rating__thumb-label"><?php esc_html_e( 'Niet nuttig', 'reader-experience' ); ?></span>
		</button>
	</div>

	<div class="rx-rating__panel" data-rx-panel hidden>
		<div class="rx-rating__step" data-rx-step="tekst">
			<p
				class="rx-rating__question"
				data-rx-step-question
				data-rx-label-ja="<?php esc_attr_e( 'Bedankt voor je duim. Wat vond je goed aan deze pagina?', 'reader-experience' ); ?>"
				data-rx-label-nee="<?php esc_attr_e( 'Bedankt voor je duim. Wat kunnen we verbeteren aan deze pagina?', 'reader-experience' ); ?>"
			></p>
			<label class="screen-reader-text" for="<?php echo esc_attr( $unique ); ?>-text"><?php esc_html_e( 'Toelichting (optioneel)', 'reader-experience' ); ?></label>
			<textarea id="<?php echo esc_attr( $unique ); ?>-text" class="rx-rating__text" rows="3" maxlength="1000" placeholder="<?php esc_attr_e( 'Optioneel', 'reader-experience' ); ?>"></textarea>
			<div class="rx-rating__hp" aria-hidden="true">
				<label for="<?php echo esc_attr( $unique ); ?>-website"><?php esc_html_e( 'Laat dit veld leeg', 'reader-experience' ); ?></label>
				<input type="text" id="<?php echo esc_attr( $unique ); ?>-website" name="website" tabindex="-1" autocomplete="off">
			</div>
			<p class="rx-rating__actions">
				<button type="button" class="rx-reset-button rx-rating__skip"><?php esc_html_e( 'Overslaan', 'reader-experience' ); ?></button>
				<button type="button" class="rx-rating__send"><?php esc_html_e( 'Versturen', 'reader-experience' ); ?></button>
			</p>
		</div>

		<div class="rx-rating__step" data-rx-step="vervolg" hidden>
			<p
				class="rx-rating__question"
				data-rx-vervolg-vraag
				data-rx-label-publiceren="<?php esc_attr_e( 'Mag je reactie onder het artikel geplaatst worden?', 'reader-experience' ); ?>"
				data-rx-label-contact="<?php esc_attr_e( 'Hoe mogen we je benaderen als we nog vragen hebben?', 'reader-experience' ); ?>"
			></p>
			<div class="rx-rating__fields" data-rx-vervolg-velden hidden>
				<label class="screen-reader-text" for="<?php echo esc_attr( $unique ); ?>-naam"><?php esc_html_e( 'Naam', 'reader-experience' ); ?></label>
				<input type="text" id="<?php echo esc_attr( $unique ); ?>-naam" class="rx-rating__naam" placeholder="<?php esc_attr_e( 'Naam', 'reader-experience' ); ?>" autocomplete="name" required>
				<label class="screen-reader-text" for="<?php echo esc_attr( $unique ); ?>-email"><?php esc_html_e( 'E-mailadres', 'reader-experience' ); ?></label>
				<input type="email" id="<?php echo esc_attr( $unique ); ?>-email" class="rx-rating__email" placeholder="<?php esc_attr_e( 'E-mailadres', 'reader-experience' ); ?>" autocomplete="email" required>
				<span
					class="rx-rating__field-error"
					role="alert"
					data-rx-vervolg-error
					data-rx-error="<?php esc_attr_e( 'Vul je naam en een geldig e-mailadres in.', 'reader-experience' ); ?>"
				></span>
			</div>
			<p class="rx-rating__actions">
				<button type="button" class="rx-reset-button rx-rating__vervolg-nee"><?php esc_html_e( 'Nee, dank je', 'reader-experience' ); ?></button>
				<button
					type="button"
					class="rx-rating__vervolg-ja"
					data-rx-vervolg-ja-label
					data-rx-label-publiceren="<?php esc_attr_e( 'Ja, plaats het', 'reader-experience' ); ?>"
					data-rx-label-contact="<?php esc_attr_e( 'Ja, dat mag', 'reader-experience' ); ?>"
				></button>
			</p>
		</div>

		<div class="rx-rating__step" data-rx-step="klaar" hidden>
			<p class="rx-rating__done">✓ <?php esc_html_e( 'Bedankt voor je reactie', 'reader-experience' ); ?></p>
		</div>
	</div>

	<noscript><p><?php esc_html_e( 'Schakel JavaScript in om te kunnen reageren.', 'reader-experience' ); ?></p></noscript>
</div>
