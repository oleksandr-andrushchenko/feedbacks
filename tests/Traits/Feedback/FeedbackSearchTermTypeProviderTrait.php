<?php
declare(strict_types=1);

namespace App\Tests\Traits\Feedback;

use App\Service\Feedback\SearchTerm\SearchTermTypeProvider;

trait FeedbackSearchTermTypeProviderTrait
{
    public function getFeedbackSearchTermTypeProvider(): SearchTermTypeProvider
    {
        return static::getContainer()->get('app.feedback_search_term_type_provider');
    }
}