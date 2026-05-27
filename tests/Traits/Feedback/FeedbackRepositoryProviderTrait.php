<?php
declare(strict_types=1);

namespace App\Tests\Traits\Feedback;

use App\Repository\Feedback\FeedbackRepository;

trait FeedbackRepositoryProviderTrait
{
    public function getFeedbackRepository(): FeedbackRepository
    {
        return static::getContainer()->get('app.feedback_repository');
    }
}