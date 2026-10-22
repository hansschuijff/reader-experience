<?php
/**
 * Sharing module.
 *
 * @package ReaderExperience\Modules\Sharing
 * @author  Hans Schuijff <@hansschuijff>
 * @license GPL-2.0-only
 * @since   0.1.0
 */

declare( strict_types=1 );

namespace ReaderExperience\Modules\Sharing;

use ReaderExperience\Modules\AbstractModule;
use ReaderExperience\Modules\Sharing\Migrations\CreateShareDailyTable;
use ReaderExperience\Support\Migrations\RenameLegacyTable;

/**
 * The share button and popover, and the click counter behind it.
 */
final class SharingModule extends AbstractModule {

	/**
	 * {@inheritDoc}
	 *
	 * @since 0.1.0
	 */
	public function id(): string {
		return 'sharing';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 0.1.0
	 */
	public function register(): void {
		( new ShareController() )->register();
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 0.1.0
	 */
	public function blocks(): array {
		return array( 'share' );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 0.1.0
	 */
	public function migrations(): array {
		return array(
			new CreateShareDailyTable(),
			new RenameLegacyTable( '2026_10_22_rename_share_daily_table', 'rx_share_daily', ( new ShareRepository() )->table() ),
		);
	}
}
