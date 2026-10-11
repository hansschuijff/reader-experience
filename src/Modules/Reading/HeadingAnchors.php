<?php
/**
 * Beleid voor kop-ankers: waar ze actief zijn en met welke opties.
 *
 * @package ReaderExperience\Modules\Reading
 */

declare(strict_types=1);

namespace ReaderExperience\Modules\Reading;

/**
 * Beslist, via filters, waar de ankerfunctionaliteit actief is.
 *
 * Alles staat standaard UIT: zonder filter gebeurt er niets (geen id's op koppen, geen
 * knop, geen CSS of JS). Een site zet het aan per post type en kan daarna per bericht
 * afwijken of de meegeleverde CSS/JS vervangen.
 *
 * Filters (alle vooraf gegaan door "reader_experience/"):
 *
 * - heading_anchors_post_types  array   Post types waarvoor het aan staat. Standaard: array().
 * - heading_anchors_enabled     bool    Eindoordeel per bericht. Args: standaardwaarde, WP_Post.
 * - heading_anchors_load_css    bool    Meegeleverde CSS laden. Standaard: true. Args: bool, WP_Post.
 * - heading_anchors_load_js     bool    Meegeleverde JS laden. Standaard: true. Args: bool, WP_Post.
 * - heading_anchors_config      array   Opties voor de JS: position ('before'|'after'),
 *                                       container (selectors), labels (copy, copied). Args: array, WP_Post.
 *
 * Uitzondering: de koppen hebben id's nodig zodra het bericht een inhoudsopgave-blok bevat,
 * ook als het post type niet is aangezet; anders zou de inhoudsopgave naar niets verwijzen.
 * Alleen de id's dus: de knop en de assets blijven uit.
 */
final class HeadingAnchors {

	public const POSITION_BEFORE = 'before';
	public const POSITION_AFTER  = 'after';

	private const TOC_BLOCK = 'reader-experience/toc';

	/**
	 * Post types waarvoor het aan staat, eenmaal per request opgehaald.
	 *
	 * @var string[]|null
	 */
	private ?array $postTypes = null;

	/**
	 * Uitkomst van isEnabledFor() per bericht, zodat de filters niet per kop opnieuw lopen.
	 *
	 * @var array<int,bool>
	 */
	private array $enabled = array();

	/**
	 * Uitkomst van needsIdsFor() per bericht.
	 *
	 * @var array<int,bool>
	 */
	private array $needsIds = array();

	/**
	 * Post types waarvoor de functionaliteit aan staat.
	 *
	 * @return string[]
	 */
	public function postTypes(): array {
		if ( null === $this->postTypes ) {
			$types = apply_filters( 'reader_experience/heading_anchors_post_types', array() );

			$this->postTypes = is_array( $types )
				? array_values( array_unique( array_filter( $types, 'is_string' ) ) )
				: array();
		}

		return $this->postTypes;
	}

	/**
	 * Staat de volledige functionaliteit (id's, knop, assets) aan voor dit bericht?
	 *
	 * @param \WP_Post|null $post Het bericht.
	 */
	public function isEnabledFor( ?\WP_Post $post ): bool {
		if ( null === $post ) {
			return false;
		}

		if ( ! isset( $this->enabled[ $post->ID ] ) ) {
			$default = in_array( $post->post_type, $this->postTypes(), true );

			$this->enabled[ $post->ID ] = (bool) apply_filters( 'reader_experience/heading_anchors_enabled', $default, $post );
		}

		return $this->enabled[ $post->ID ];
	}

	/**
	 * Moeten de koppen in dit bericht een id krijgen? Ja als de functionaliteit aan staat,
	 * of als het bericht een inhoudsopgave bevat die die id's nodig heeft.
	 *
	 * @param \WP_Post|null $post Het bericht.
	 */
	public function needsIdsFor( ?\WP_Post $post ): bool {
		if ( null === $post ) {
			return false;
		}

		if ( ! isset( $this->needsIds[ $post->ID ] ) ) {
			$this->needsIds[ $post->ID ] = $this->isEnabledFor( $post ) || has_block( self::TOC_BLOCK, $post );
		}

		return $this->needsIds[ $post->ID ];
	}

	/**
	 * Mag de meegeleverde CSS geladen worden?
	 *
	 * @param \WP_Post $post Het bericht.
	 */
	public function loadCss( \WP_Post $post ): bool {
		return (bool) apply_filters( 'reader_experience/heading_anchors_load_css', true, $post );
	}

	/**
	 * Mag de meegeleverde JS geladen worden?
	 *
	 * @param \WP_Post $post Het bericht.
	 */
	public function loadJs( \WP_Post $post ): bool {
		return (bool) apply_filters( 'reader_experience/heading_anchors_load_js', true, $post );
	}

	/**
	 * Opties voor de JS, na filteren opgeschoond tot geldige waarden.
	 *
	 * @param \WP_Post $post Het bericht.
	 * @return array{position:string,container:string[],labels:array{copy:string,copied:string}}
	 */
	public function config( \WP_Post $post ): array {
		$config = apply_filters(
			'reader_experience/heading_anchors_config',
			array(
				'position'  => self::POSITION_BEFORE,
				'container' => array( '.wp-block-post-content', '.entry-content', 'article' ),
				'labels'    => array(
					'copy'   => __( 'Kopieer link naar dit onderdeel', 'reader-experience' ),
					'copied' => __( 'Link gekopieerd', 'reader-experience' ),
				),
			),
			$post
		);

		$config    = is_array( $config ) ? $config : array();
		$labels    = is_array( $config['labels'] ?? null ) ? $config['labels'] : array();
		$container = $config['container'] ?? array();

		return array(
			'position'  => self::POSITION_AFTER === ( $config['position'] ?? '' ) ? self::POSITION_AFTER : self::POSITION_BEFORE,
			'container' => array_values( array_filter( (array) $container, static fn ( $s ): bool => is_string( $s ) && '' !== trim( $s ) ) ),
			'labels'    => array(
				'copy'   => (string) ( $labels['copy'] ?? '' ),
				'copied' => (string) ( $labels['copied'] ?? '' ),
			),
		);
	}
}
