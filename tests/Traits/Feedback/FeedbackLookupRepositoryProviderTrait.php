<?php
declare(strict_types=1);

namespace App\Tests\Traits\Feedback;

use App\Repository\Feedback\FeedbackLookupRepository;

trait FeedbackLookupRepositoryProviderTrait
{
    public function getFeedbackLookupRepository(): FeedbackLookupRepository
    {
        return static::getContainer()->get('app.feedback_lookup_repository');
    }
}