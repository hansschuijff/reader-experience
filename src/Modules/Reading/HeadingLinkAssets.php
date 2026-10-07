<?php

declare(strict_types=1);

namespace ReaderExperience\Modules\Reading;

/**
 * Registreert het losse (ongebundelde) "kopieer link"-icoontje bij koppen.
 * Draait alleen op singular content, waar HeadingIndexer ook daadwerkelijk
 * id's op de koppen zet.
 */
final class HeadingLinkAssets {

	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	public function enqueue(): void {
		if ( ! is_singular() || ! defined( 'ReaderExperience\PLUGIN_FILE' ) ) {
			return;
		}

		$uri = plugin_dir_url( \ReaderExperience\PLUGIN_FILE );
		$dir = plugin_dir_path( \ReaderExperience\PLUGIN_FILE );

		wp_enqueue_style(
			'rx-heading-anchor-links',
			$uri . 'assets/css/heading-anchor-links.css',
			array(),
			(string) filemtime( $dir . 'assets/css/heading-anchor-links.css' )
		);

		wp_enqueue_script(
			'rx-heading-anchor-links',
			$uri . 'assets/js/heading-anchor-links.js',
			array(),
			(string) filemtime( $dir . 'assets/js/heading-anchor-links.js' ),
			array( 'in_footer' => true, 'strategy' => 'defer' )
		);
	}
}
