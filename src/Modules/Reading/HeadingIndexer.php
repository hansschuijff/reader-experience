<?php

declare(strict_types=1);

namespace ReaderExperience\Modules\Reading;

/**
 * Geeft koppen (h2 t/m h4) een anker en bouwt de lijst voor de inhoudsopgave.
 *
 * - Ankers worden toegevoegd terwijl de core/heading-blokken van de berichttekst renderen.
 * - De lijst komt uit parse_blocks() op de berichttekst, inclusief gesynchroniseerde patronen.
 *
 * Beperking: koppen die pas ontstaan door andere dynamische blokken (bijvoorbeeld een query loop
 * binnen het bericht) en klassieke, niet-blokgebaseerde koppen worden niet meegenomen.
 */
final class HeadingIndexer {

	public const MIN_LEVEL = 2;
	public const MAX_LEVEL = 4;

	private int $depth = 0;

	private AnchorRegistry $registry;

	/** @var array<int,array<int,array{id:string,text:string,level:int}>> */
	private static array $cache = array();

	public function __construct() {
		$this->registry = new AnchorRegistry();
	}

	public function register(): void {
		add_filter( 'the_content', array( $this, 'start' ), 1 );
		add_filter( 'the_content', array( $this, 'end' ), PHP_INT_MAX );
		add_filter( 'render_block_core/heading', array( $this, 'addAnchor' ), 10, 2 );
	}

	public function start( $content ) {
		if ( 0 === $this->depth ) {
			$this->registry->reset();
		}
		++$this->depth;
		return $content;
	}

	public function end( $content ) {
		$this->depth = max( 0, $this->depth - 1 );
		return $content;
	}

	/**
	 * @param mixed                $html
	 * @param array<string,mixed>  $block
	 * @return mixed
	 */
	public function addAnchor( $html, $block = array() ) {
		// Alleen koppen binnen de berichttekst tellen mee, niet die uit templates.
		if ( 0 === $this->depth || ! is_string( $html ) || '' === $html ) {
			return $html;
		}

		$processor = new \WP_HTML_Tag_Processor( $html );
		if ( ! $processor->next_tag() ) {
			return $html;
		}

		if ( ! preg_match( '/^H([1-6])$/', (string) $processor->get_tag(), $m ) ) {
			return $html;
		}

		$level = (int) $m[1];
		if ( $level < self::MIN_LEVEL || $level > self::MAX_LEVEL ) {
			return $html;
		}

		$existing = $processor->get_attribute( 'id' );
		$existing = is_string( $existing ) && '' !== $existing ? $existing : null;

		$id = $this->registry->claim( $existing, self::plainText( $html ) );

		if ( null !== $existing ) {
			return $html;
		}

		$processor->set_attribute( 'id', $id );
		return $processor->get_updated_html();
	}

	/**
	 * @return array<int,array{id:string,text:string,level:int}>
	 */
	public static function headingsForPost( int $postId, int $maxLevel = 3 ): array {
		if ( ! isset( self::$cache[ $postId ] ) ) {
			$post = get_post( $postId );
			$out  = array();

			if ( $post instanceof \WP_Post ) {
				self::walk( parse_blocks( $post->post_content ), new AnchorRegistry(), $out, 0 );
			}

			self::$cache[ $postId ] = $out;
		}

		return array_values(
			array_filter(
				self::$cache[ $postId ],
				static fn ( array $item ): bool => $item['level'] <= $maxLevel
			)
		);
	}

	/**
	 * @param array<int,array<string,mixed>>                 $blocks
	 * @param array<int,array{id:string,text:string,level:int}> $out
	 */
	private static function walk( array $blocks, AnchorRegistry $registry, array &$out, int $guard ): void {
		foreach ( $blocks as $block ) {
			$name = $block['blockName'] ?? null;

			if ( 'core/heading' === $name ) {
				$level = (int) ( $block['attrs']['level'] ?? 2 );

				if ( $level >= self::MIN_LEVEL && $level <= self::MAX_LEVEL ) {
					$text     = self::plainText( (string) ( $block['innerHTML'] ?? '' ) );
					$existing = $block['attrs']['anchor'] ?? null;
					$id       = $registry->claim( is_string( $existing ) ? $existing : null, $text );

					if ( '' !== $text ) {
						$out[] = array(
							'id'    => $id,
							'text'  => $text,
							'level' => $level,
						);
					}
				}
			} elseif ( 'core/block' === $name && $guard < 3 && ! empty( $block['attrs']['ref'] ) ) {
				$ref = get_post( (int) $block['attrs']['ref'] );
				if ( $ref instanceof \WP_Post && 'wp_block' === $ref->post_type ) {
					self::walk( parse_blocks( $ref->post_content ), $registry, $out, $guard + 1 );
				}
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				self::walk( $block['innerBlocks'], $registry, $out, $guard );
			}
		}
	}


	/**
	 * Groepeert een platte koppenlijst per hoofdniveau (het laagste niveau in de lijst,
	 * doorgaans h2): alles daaronder wordt een kind van de meest recente hoofdkop.
	 * Koppen die zelf al voor de eerste hoofdkop staan (zeldzaam) krijgen een eigen,
	 * kindloze groep.
	 *
	 * @param array<int,array{id:string,text:string,level:int}> $items
	 * @return array<int,array{item:array{id:string,text:string,level:int},children:array<int,array{id:string,text:string,level:int}>}>
	 */
	public static function groupByTopLevel( array $items ): array {
		if ( ! $items ) {
			return array();
		}

		$topLevel = min( array_column( $items, 'level' ) );
		$groups   = array();
		$current  = null;

		foreach ( $items as $item ) {
			if ( $item['level'] === $topLevel ) {
				$current  = array( 'item' => $item, 'children' => array() );
				$groups[] = $current;
			} elseif ( null !== $current ) {
				$groups[ count( $groups ) - 1 ]['children'][] = $item;
			} else {
				$groups[] = array( 'item' => $item, 'children' => array() );
			}
		}

		return $groups;
	}

	private static function plainText( string $html ): string {
		return trim( html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	}
}
