<?php
declare(strict_types=1);

namespace App\Tests\Traits\Feedback;

use App\Repository\Feedback\FeedbackSearchRepository;

trait FeedbackSearchRepositoryProviderTrait
{
    public function getFeedbackSearchRepository(): FeedbackSearchRepository
    {
        return static::getContainer()->get('app.feedback_search_repository');
    }
}