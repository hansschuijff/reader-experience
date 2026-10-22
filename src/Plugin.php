<?php
/**
 * Plugin bootstrap.
 *
 * @package ReaderExperience
 * @author  Hans Schuijff <@hansschuijff>
 * @license GPL-2.0-only
 * @since   0.1.0
 */

declare( strict_types=1 );

namespace ReaderExperience;

use ReaderExperience\Blocks\BlockRegistrar;
use ReaderExperience\Contracts\ModuleInterface;
use ReaderExperience\Modules\Bar\BarModule;
use ReaderExperience\Modules\Rating\RatingModule;
use ReaderExperience\Modules\Reading\ReadingModule;
use ReaderExperience\Modules\Sharing\SharingModule;
use ReaderExperience\Support\LocaleFallback;
use ReaderExperience\Support\Migrator;

use function add_action;
use function apply_filters;
use function array_filter;
use function array_merge;
use function array_unique;
use function array_values;
use function defined;
use function determine_locale;
use function did_action;
use function dirname;
use function error_log;
use function is_admin;
use function is_textdomain_loaded;
use function load_plugin_textdomain;
use function load_textdomain;
use function ltrim;
use function plugin_basename;
use function plugin_dir_path;
use function sprintf;

/**
 * Builds the modules, runs the migrations and registers the blocks.
 */
final class Plugin {

	/**
	 * The single instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Active modules.
	 *
	 * @var ModuleInterface[]
	 */
	private array $modules = array();

	/**
	 * Constructor.
	 *
	 * @param string $file Main plugin file.
	 */
	private function __construct( private readonly string $file ) {}

	/**
	 * Starts the plugin once.
	 *
	 * There is no dependency on a core plugin: all current modules are self-contained. If a real
	 * coupling comes (a shared migration runner or settings API, say), add it here in a targeted
	 * way instead of a generic "wait for the core" construction.
	 *
	 * @since 0.1.0
	 * @param string $file Main plugin file.
	 * @return void
	 */
	public static function boot( string $file ): void {
		if ( null !== self::$instance ) {
			return;
		}

		self::$instance = new self( $file );
		self::$instance->start();
	}

	/**
	 * Registers the modules and schedules block registration and text domain loading.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function start(): void {
		$this->modules = $this->build_modules();

		foreach ( $this->modules as $module ) {
			$module->register();
		}

		// Migrations are cheap (one option check) but do not belong on every front-end request.
		// They run on activation and otherwise only in the admin, so a new migration is still
		// picked up quickly after an update.
		if ( is_admin() ) {
			$this->run_migrations();
		}

		( new LocaleFallback( 'reader-experience', $this->path( 'languages' ) ) )->register();

		$this->on_init(
			function (): void {
				load_plugin_textdomain(
					'reader-experience',
					false,
					dirname( plugin_basename( $this->file ) ) . '/languages'
				);

				// Fallback with an absolute path, for installs where plugin_basename() cannot
				// resolve the folder (for example a symlinked development copy).
				if ( ! is_textdomain_loaded( 'reader-experience' ) ) {
					$locale = apply_filters( 'plugin_locale', determine_locale(), 'reader-experience' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core hook.
					load_textdomain( 'reader-experience', $this->path( "languages/reader-experience-{$locale}.mo" ), $locale );
				}

				if ( defined( 'WP_DEBUG' ) && WP_DEBUG && ! is_textdomain_loaded( 'reader-experience' ) && 'en_US' !== determine_locale() ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( sprintf( 'Reader Experience: no translations loaded for locale %s (looked in %s).', determine_locale(), $this->path( 'languages' ) ) );
				}

				( new BlockRegistrar( $this->path( 'build/blocks' ) ) )->register( $this->block_slugs() );
			}
		);
	}

	/**
	 * Called from register_activation_hook, so the tables exist right after activation.
	 *
	 * @since 0.1.0
	 * @param string $file Main plugin file.
	 * @return void
	 */
	public static function on_activate( string $file ): void {
		$instance          = self::$instance ?? new self( $file );
		$instance->modules = $instance->modules ? $instance->modules : $instance->build_modules();
		$instance->run_migrations();
	}

	/**
	 * Builds the list of modules.
	 *
	 * @return ModuleInterface[]
	 */
	private function build_modules(): array {
		$modules = array(
			new ReadingModule(),
			new BarModule(),
			new SharingModule(),
			new RatingModule(),
		);

		/**
		 * Filters the plugin modules, to add or remove modules (per site, for example).
		 *
		 * @since 0.1.0
		 *
		 * @param ModuleInterface[] $modules Module objects. Anything that is not a ModuleInterface is dropped.
		 */
		$modules = apply_filters( 'reader_experience/modules', $modules );

		return array_values(
			array_filter( $modules, static fn ( $module ): bool => $module instanceof ModuleInterface )
		);
	}

	/**
	 * Slugs of all blocks of the active modules.
	 *
	 * @return string[]
	 */
	private function block_slugs(): array {
		$slugs = array();
		foreach ( $this->modules as $module ) {
			$slugs = array_merge( $slugs, $module->blocks() );
		}
		return array_values( array_unique( $slugs ) );
	}

	/**
	 * Runs the migrations of all active modules.
	 *
	 * @return void
	 */
	private function run_migrations(): void {
		$migrations = array();
		foreach ( $this->modules as $module ) {
			$migrations = array_merge( $migrations, $module->migrations() );
		}
		( new Migrator() )->run( $migrations );
	}

	/**
	 * Absolute path of a file inside the plugin.
	 *
	 * @param string $relative Path relative to the plugin directory.
	 * @return string
	 */
	private function path( string $relative ): string {
		return plugin_dir_path( $this->file ) . ltrim( $relative, '/' );
	}

	/**
	 * Runs a callback on init, immediately if init has already fired.
	 *
	 * @param callable $callback Callback.
	 * @return void
	 */
	private function on_init( callable $callback ): void {
		if ( did_action( 'init' ) > 0 ) {
			$callback();
			return;
		}
		add_action( 'init', $callback );
	}
}
