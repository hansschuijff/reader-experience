<?php

declare(strict_types=1);

namespace ReaderExperience\Modules\Rating;

use ReaderExperience\Modules\AbstractModule;
use ReaderExperience\Modules\Rating\Admin\DashboardWidget;
use ReaderExperience\Modules\Rating\Admin\FeedbackPage;
use ReaderExperience\Modules\Rating\Admin\PostColumn;
use ReaderExperience\Modules\Rating\Migrations\AddCommentFieldsToFeedbackTable;
use ReaderExperience\Modules\Rating\Notifier;
use ReaderExperience\Modules\Rating\Migrations\CreateFeedbackTable;

final class RatingModule extends AbstractModule {

	public function id(): string {
		return 'rating';
	}

	public function register(): void {
		( new RatingController() )->register();
		( new Notifier() )->register(); // Moet ook op de voorkant draaien: de REST-aanroep komt van een bezoeker.

		if ( is_admin() ) {
			( new PostColumn() )->register();
			( new DashboardWidget() )->register();
			( new FeedbackPage() )->register();
		}
	}

	public function blocks(): array {
		return array( 'rating' );
	}

	public function migrations(): array {
		return array( new CreateFeedbackTable(), new AddCommentFieldsToFeedbackTable() );
	}
}
