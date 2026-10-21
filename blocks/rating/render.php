<?php
/**
 * Render template of the rating block.
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

use ReaderExperience\Modules\Rating\CommentPublisher;
use ReaderExperience\Support\Icons;

$post_id = (int) ( $block->context['postId'] ?? get_the_ID() );
if ( ! $post_id ) {
	return;
}

$variant = ( 'bar' === ( $attributes['variant'] ?? 'inline' ) ) ? 'bar' : 'inline';

// May a comment on a positive rating be posted as a comment? Not when comments on this post
// are closed (or require an account): then we do not offer it either.
$can_comment = ( new CommentPublisher() )->can_publish( $post_id );
$unique      = wp_unique_id( 'rx-rating-' );

$rest = array(
	'create'   => esc_url_raw( rest_url( 'rx/v1/rating' ) ),
	'text'     => esc_url_raw( rest_url( 'rx/v1/rating/' ) ), // Followed by {id}/text.
	'followup' => esc_url_raw( rest_url( 'rx/v1/rating/' ) ), // Followed by {id}/follow-up.
);
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'rx-rating rx-rating--' . esc_attr( $variant ) ) ); ?>
	data-rx-rating
	data-rx-post="<?php echo (int) $post_id; ?>"
	data-rx-can-comment="<?php echo $can_comment ? '1' : '0'; ?>"
	data-rx-rest-create="<?php echo esc_url( $rest['create'] ); ?>"
	data-rx-rest-text-base="<?php echo esc_url( $rest['text'] ); ?>"
	data-rx-rest-followup-base="<?php echo esc_url( $rest['followup'] ); ?>"
>
	<div class="rx-rating__thumbs" data-rx-step="thumbs">
		<button type="button" class="rx-reset-button rx-rating__thumb<?php echo 'bar' === $variant ? ' rx-bar__button' : ''; ?>" data-rx-value="like" aria-pressed="false" aria-label="<?php esc_attr_e( 'Useful', 'reader-experience' ); ?>" title="<?php esc_attr_e( 'Useful', 'reader-experience' ); ?>">
			<?php echo Icons::get( 'thumb_up' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed, trusted SVG from the plugin itself. ?>
			<span class="rx-rating__thumb-label"><?php esc_html_e( 'Useful', 'reader-experience' ); ?></span>
		</button>
		<button type="button" class="rx-reset-button rx-rating__thumb<?php echo 'bar' === $variant ? ' rx-bar__button' : ''; ?>" data-rx-value="dislike" aria-pressed="false" aria-label="<?php esc_attr_e( 'Not useful', 'reader-experience' ); ?>" title="<?php esc_attr_e( 'Not useful', 'reader-experience' ); ?>">
			<?php echo Icons::get( 'thumb_down' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span class="rx-rating__thumb-label"><?php esc_html_e( 'Not useful', 'reader-experience' ); ?></span>
		</button>
	</div>

	<div class="rx-rating__panel" data-rx-panel hidden>
		<div class="rx-rating__step" data-rx-step="text">
			<p
				class="rx-rating__question"
				data-rx-step-question
				data-rx-label-like="<?php esc_attr_e( 'Thanks for your thumb. What did you like about this page?', 'reader-experience' ); ?>"
				data-rx-label-dislike="<?php esc_attr_e( 'Thanks for your thumb. What could we improve about this page?', 'reader-experience' ); ?>"
			></p>
			<label class="screen-reader-text" for="<?php echo esc_attr( $unique ); ?>-text"><?php esc_html_e( 'Explanation (optional)', 'reader-experience' ); ?></label>
			<textarea id="<?php echo esc_attr( $unique ); ?>-text" class="rx-rating__text" rows="3" maxlength="1000" placeholder="<?php esc_attr_e( 'Optional', 'reader-experience' ); ?>"></textarea>
			<div class="rx-rating__hp" aria-hidden="true">
				<label for="<?php echo esc_attr( $unique ); ?>-website"><?php esc_html_e( 'Leave this field empty', 'reader-experience' ); ?></label>
				<input type="text" id="<?php echo esc_attr( $unique ); ?>-website" name="website" tabindex="-1" autocomplete="off">
			</div>
			<p class="rx-rating__actions">
				<button type="button" class="rx-reset-button rx-rating__skip"><?php esc_html_e( 'Skip', 'reader-experience' ); ?></button>
				<button type="button" class="rx-rating__send"><?php esc_html_e( 'Send', 'reader-experience' ); ?></button>
			</p>
		</div>

		<div class="rx-rating__step" data-rx-step="follow-up" hidden>
			<p
				class="rx-rating__question"
				data-rx-follow-up-question
				data-rx-label-publish="<?php esc_attr_e( 'May we post your comment below the article?', 'reader-experience' ); ?>"
				data-rx-label-contact="<?php esc_attr_e( 'How can we contact you if we have more questions?', 'reader-experience' ); ?>"
			></p>
			<div class="rx-rating__fields" data-rx-follow-up-fields hidden>
				<label class="screen-reader-text" for="<?php echo esc_attr( $unique ); ?>-name"><?php esc_html_e( 'Name', 'reader-experience' ); ?></label>
				<input type="text" id="<?php echo esc_attr( $unique ); ?>-name" class="rx-rating__name" placeholder="<?php esc_attr_e( 'Name', 'reader-experience' ); ?>" autocomplete="name" required>
				<label class="screen-reader-text" for="<?php echo esc_attr( $unique ); ?>-email"><?php esc_html_e( 'Email address', 'reader-experience' ); ?></label>
				<input type="email" id="<?php echo esc_attr( $unique ); ?>-email" class="rx-rating__email" placeholder="<?php esc_attr_e( 'Email address', 'reader-experience' ); ?>" autocomplete="email" required>
				<span
					class="rx-rating__field-error"
					role="alert"
					data-rx-follow-up-error
					data-rx-error="<?php esc_attr_e( 'Please enter your name and a valid email address.', 'reader-experience' ); ?>"
				></span>
			</div>
			<p class="rx-rating__actions">
				<button type="button" class="rx-reset-button rx-rating__follow-up-no"><?php esc_html_e( 'No, thanks', 'reader-experience' ); ?></button>
				<button
					type="button"
					class="rx-rating__follow-up-yes"
					data-rx-follow-up-yes-label
					data-rx-label-publish="<?php esc_attr_e( 'Yes, post it', 'reader-experience' ); ?>"
					data-rx-label-contact="<?php esc_attr_e( 'Yes, that is fine', 'reader-experience' ); ?>"
				></button>
			</p>
		</div>

		<div class="rx-rating__step" data-rx-step="klaar" hidden>
			<p class="rx-rating__done">✓ <?php esc_html_e( 'Thanks for your response', 'reader-experience' ); ?></p>
		</div>
	</div>

	<noscript><p><?php esc_html_e( 'Enable JavaScript to be able to respond.', 'reader-experience' ); ?></p></noscript>
</div>
