<?php
/**
 * CSS en JS voor het "kopieer link"-icoontje bij koppen.
 *
 * @package ReaderExperience\Modules\Reading
 */

declare(strict_types=1);

namespace ReaderExperience\Modules\Reading;

use WP_Post;
use const ReaderExperience\PLUGIN_FILE;

/**
 * Registreert het losse (ongebundelde) "kopieer link"-icoontje bij koppen.
 *
 * Laadt alleen op een enkele weergave waarvoor HeadingAnchors de functionaliteit aan heeft
 * gezet. CSS en JS zijn los uit te schakelen, zodat een site er een eigen versie voor in de
 * plaats kan zetten (zie HeadingAnchors voor de filters).
 */
final class HeadingLinkAssets {

	private const HANDLE = 'rx-heading-anchor-links';

	private HeadingAnchors $anchors;

	/**
	 * @param HeadingAnchors $anchors Beleid.
	 */
	public function __construct( HeadingAnchors $anchors ) {
		$this->anchors = $anchors;
	}

	/**
	 * Koppelt aan de scripts-hook.
	 */
	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Laadt CSS en JS, voor zover het beleid dat toestaat.
	 */
	public function enqueue(): void {
		if ( ! is_singular() || ! defined( 'ReaderExperience\PLUGIN_FILE' ) ) {
			return;
		}

		$post = get_queried_object();
		if ( ! $post instanceof WP_Post || ! $this->anchors->is_enabled_for( $post ) ) {
			return;
		}

		$uri = plugin_dir_url( PLUGIN_FILE );
		$dir = plugin_dir_path( PLUGIN_FILE );

		if ( $this->anchors->load_css( $post ) ) {
			wp_enqueue_style(
				self::HANDLE,
				$uri . 'assets/css/heading-anchor-links.css',
				array(),
				(string) filemtime( $dir . 'assets/css/heading-anchor-links.css' )
			);
		}

		if ( $this->anchors->load_js( $post ) ) {
			wp_enqueue_script(
				self::HANDLE,
				$uri . 'assets/js/heading-anchor-links.js',
				array(),
				(string) filemtime( $dir . 'assets/js/heading-anchor-links.js' ),
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);

			// De opties horen bij onze JS: wordt die vervangen, dan regelt de site de eigen opties zelf.
			wp_add_inline_script(
				self::HANDLE,
				'window.rxHeadingAnchors=' . wp_json_encode( $this->anchors->config( $post ) ) . ';',
				'before'
			);
		}
	}
}
