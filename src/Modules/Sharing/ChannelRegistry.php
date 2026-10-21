<?php
/**
 * Share channels.
 *
 * @package ReaderExperience\Modules\Sharing
 * @author  Hans Schuijff <@hansschuijff>
 * @license GPL-2.0-only
 * @since   0.1.0
 */

declare( strict_types=1 );

namespace ReaderExperience\Modules\Sharing;

use function __;
use function apply_filters;
use function rawurlencode;

/**
 * Share channels as URL templates, filterable.
 *
 * No counts from third parties, only links to their share screens. Example of adding a channel:
 *
 *     add_filter( 'reader_experience/share_channels', function ( $channels ) {
 *         $channels['mastodon'] = array(
 *             'label' => 'Mastodon',
 *             'icon'  => 'share', // Or register your own icon through 'reader_experience/icons'.
 *             'color' => '#6364FF',
 *             'url'   => fn( $url, $title ) => 'https://your.instance/share?text=' . rawurlencode( $title . ' ' . $url ),
 *         );
 *         return $channels;
 *     } );
 */
final class ChannelRegistry {

	/**
	 * The share channels, after filtering.
	 *
	 * @since 0.1.0
	 * @return array<string,array{label:string,icon:string,color:string,url:callable}>
	 */
	public function channels(): array {
		$channels = array(
			'whatsapp' => array(
				'label' => __( 'WhatsApp', 'reader-experience' ),
				'icon'  => 'whatsapp',
				'color' => '#25D366',
				'url'   => static fn ( string $url, string $title ): string =>
					'https://wa.me/?text=' . rawurlencode( $title . ' ' . $url ),
			),
			'linkedin' => array(
				'label' => __( 'LinkedIn', 'reader-experience' ),
				'icon'  => 'linkedin',
				'color' => '#0A66C2',
				'url'   => static fn ( string $url, string $title ): string =>
					'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode( $url ),
			),
			'facebook' => array(
				'label' => __( 'Facebook', 'reader-experience' ),
				'icon'  => 'facebook',
				'color' => '#1877F2',
				'url'   => static fn ( string $url, string $title ): string =>
					'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $url ),
			),
			'email'    => array(
				'label' => __( 'Email', 'reader-experience' ),
				'icon'  => 'email',
				'color' => '', // Neutral: no platform, no brand colour.
				'url'   => static fn ( string $url, string $title ): string =>
					'mailto:?subject=' . rawurlencode( $title ) . '&body=' . rawurlencode( $url ),
			),
		);

		/*
		 * The colours above are approximations of each platform's own brand colour, used as a
		 * coloured background behind our own simple line icon, not the official logos. Brand
		 * guidelines (permitted use, exact colour and shape requirements) differ per platform and
		 * can change; this is deliberately a safer, recognisable approximation instead of an
		 * exact reproduction.
		 */

		/**
		 * Filters the share channels: add, remove or change channels.
		 *
		 * An "icon" key without a matching definition in Icons::get() shows no icon. "color" is
		 * optional (empty means a neutral background).
		 *
		 * @since 0.1.0
		 *
		 * @param array<string,array{label:string,icon:string,color:string,url:callable}> $channels {
		 *     Channels, keyed by slug.
		 *
		 *     @type string   $label Name shown to the reader.
		 *     @type string   $icon  Key of an icon (see the 'reader_experience/icons' filter).
		 *     @type string   $color Background colour of the icon, or an empty string.
		 *     @type callable $url   Receives the post URL and the title, returns the share URL.
		 * }
		 */
		return apply_filters( 'reader_experience/share_channels', $channels );
	}

	/**
	 * Is this a known channel? "copy" (copy the link) is always valid.
	 *
	 * @since 0.1.0
	 * @param string $channel Channel slug.
	 * @return bool
	 */
	public function is_valid( string $channel ): bool {
		return 'copy' === $channel || isset( $this->channels()[ $channel ] );
	}
}
