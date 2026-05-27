<?php
declare(strict_types=1);

namespace App\Tests\Traits\Feedback;

use App\Service\Feedback\Rating\FeedbackRatingProvider;

trait FeedbackRatingProviderTrait
{
    public function getFeedbackRatingProvider(): FeedbackRatingProvider
    {
        return static::getContainer()->get('app.feedback_rating_provider');
    }
}