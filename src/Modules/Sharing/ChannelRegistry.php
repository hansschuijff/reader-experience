<?php

declare(strict_types=1);

namespace ReaderExperience\Modules\Sharing;

/**
 * Kanalen als URL-template. Geen counts van derden, alleen linken naar hun deelscherm.
 */
/**
 * Kanalen als URL-template, filterbaar. Zelf een kanaal toevoegen:
 *
 *   add_filter( 'reader_experience/share_channels', function ( $channels ) {
 *       $channels['mastodon'] = array(
 *           'label' => 'Mastodon',
 *           'icon'  => 'share', // of registreer een eigen icoon via 'reader_experience/icons'
 *           'color' => '#6364FF',
 *           'url'   => fn( $url, $title ) => 'https://jouw.instantie/share?text=' . rawurlencode( $title . ' ' . $url ),
 *       );
 *       return $channels;
 *   } );
 */
final class ChannelRegistry {

	/**
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
				'label' => __( 'E-mail', 'reader-experience' ),
				'icon'  => 'email',
				'color' => '', // Neutraal: geen platform, geen merkkleur.
				'url'   => static fn ( string $url, string $title ): string =>
					'mailto:?subject=' . rawurlencode( $title ) . '&body=' . rawurlencode( $url ),
			),
		);

		/*
		 * De kleuren hierboven zijn benaderingen van elk platform se eigen merkkleur,
		 * gebruikt als gekleurde achtergrond achter een eigen, simpel lijnicoon — niet
		 * de officiële logo's zelf. Merkrichtlijnen (toegestaan gebruik, exacte kleur-
		 * en vormvereisten) verschillen per platform en kunnen wijzigen; dit is bewust
		 * een veiliger, herkenbare benadering in plaats van een exacte reproductie.
		 */

		/**
		 * Kanalen toevoegen, verwijderen of aanpassen. Een "icon"-sleutel zonder
		 * overeenkomende definitie in Icons::get() toont geen icoon; "color" is
		 * optioneel (leeg = neutrale achtergrond).
		 *
		 * @param array<string,array{label:string,icon:string,color:string,url:callable}> $channels
		 */
		return apply_filters( 'reader_experience/share_channels', $channels );
	}

	public function isValid( string $channel ): bool {
		return 'copy' === $channel || isset( $this->channels()[ $channel ] );
	}
}
