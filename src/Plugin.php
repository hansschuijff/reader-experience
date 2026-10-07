<?php

declare(strict_types=1);

namespace ReaderExperience;

use ReaderExperience\Blocks\BlockRegistrar;
use ReaderExperience\Contracts\ModuleInterface;
use ReaderExperience\Modules\Bar\BarModule;
use ReaderExperience\Modules\Rating\RatingModule;
use ReaderExperience\Modules\Reading\ReadingModule;
use ReaderExperience\Modules\Sharing\SharingModule;
use ReaderExperience\Support\Migrator;

final class Plugin {

	private static ?self $instance = null;

	/** @var ModuleInterface[] */
	private array $modules = array();

	private function __construct( private readonly string $file ) {}

	public static function boot( string $file ): void {
		if ( null !== self::$instance ) {
			return;
		}

		// Geen afhankelijkheid van een core (nog) nodig: alle huidige modules zijn zelfstandig.
		// Komt er een echte koppeling (bijvoorbeeld een gedeelde migratierunner of instellingen-API),
		// voeg die dan hier gericht toe in plaats van een generieke "wacht op de core"-constructie.
		self::$instance = new self( $file );
		self::$instance->start();
	}

	public function start(): void {
		$this->modules = $this->buildModules();

		foreach ( $this->modules as $module ) {
			$module->register();
		}

		// Migraties zijn goedkoop (één option-check), maar horen niet op elke front-end
		// request te draaien. Ze lopen bij activatie en verder alleen in de admin, zodat
		// een nieuwe migratie na een update alsnog snel wordt opgepikt.
		if ( is_admin() ) {
			$this->runMigrations();
		}

		$this->onInit(
			function (): void {
				load_plugin_textdomain(
					'reader-experience',
					false,
					dirname( plugin_basename( $this->file ) ) . '/languages'
				);

				( new BlockRegistrar( $this->path( 'build/blocks' ) ) )->register( $this->blockSlugs() );
			}
		);
	}

	/** @return ModuleInterface[] */
	private function buildModules(): array {
		$modules = array(
			new ReadingModule(),
			new BarModule(),
			new SharingModule(),
			new RatingModule(),
		);

		/**
		 * Laat modules toevoegen of weghalen, bijvoorbeeld per site.
		 *
		 * @param ModuleInterface[] $modules
		 */
		$modules = apply_filters( 'reader_experience/modules', $modules );

		return array_values(
			array_filter( $modules, static fn ( $m ): bool => $m instanceof ModuleInterface )
		);
	}

	/** @return string[] */
	private function blockSlugs(): array {
		$slugs = array();
		foreach ( $this->modules as $module ) {
			$slugs = array_merge( $slugs, $module->blocks() );
		}
		return array_values( array_unique( $slugs ) );
	}

	private function runMigrations(): void {
		$migrations = array();
		foreach ( $this->modules as $module ) {
			$migrations = array_merge( $migrations, $module->migrations() );
		}
		( new Migrator() )->run( $migrations );
	}

	/** Vanuit register_activation_hook, zodat de tabellen er bij het inschakelen direct staan. */
	public static function onActivate( string $file ): void {
		$instance = self::$instance ?? new self( $file );
		$instance->modules = $instance->modules ?: $instance->buildModules();
		$instance->runMigrations();
	}

	private function path( string $relative ): string {
		return plugin_dir_path( $this->file ) . ltrim( $relative, '/' );
	}

	private function onInit( callable $callback ): void {
		if ( did_action( 'init' ) > 0 ) {
			$callback();
			return;
		}
		add_action( 'init', $callback );
	}
}
