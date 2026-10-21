<?php
/**
 * Rating module.
 *
 * @package ReaderExperience\Modules\Rating
 * @author  Hans Schuijff <@hansschuijff>
 * @license GPL-2.0-only
 * @since   0.1.0
 */

declare( strict_types=1 );

namespace ReaderExperience\Modules\Rating;

use ReaderExperience\Modules\AbstractModule;
use ReaderExperience\Modules\Rating\Admin\DashboardWidget;
use ReaderExperience\Modules\Rating\Admin\FeedbackPage;
use ReaderExperience\Modules\Rating\Admin\PostColumn;
use ReaderExperience\Modules\Rating\Migrations\AddCommentFieldsToFeedbackTable;
use ReaderExperience\Modules\Rating\Migrations\CreateFeedbackTable;

use function is_admin;

/**
 * Thumbs up/down with an optional comment, stored for the administrator.
 */
final class RatingModule extends AbstractModule {

	/**
	 * {@inheritDoc}
	 *
	 * @since 0.1.0
	 */
	public function id(): string {
		return 'rating';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 0.1.0
	 */
	public function register(): void {
		( new RatingController() )->register();
		( new Notifier() )->register(); // Must run on the front end too: the REST call comes from a visitor.

		if ( is_admin() ) {
			( new PostColumn() )->register();
			( new DashboardWidget() )->register();
			( new FeedbackPage() )->register();
		}
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 0.1.0
	 */
	public function blocks(): array {
		return array( 'rating' );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 0.1.0
	 */
	public function migrations(): array {
		return array( new CreateFeedbackTable(), new AddCommentFieldsToFeedbackTable() );
	}
}
