<?php
declare(strict_types=1);

namespace App\Tests\Traits\Feedback;

use App\Service\Feedback\SearchTerm\SearchTermParserInterface;

trait SearchTermParserProviderTrait
{
    public function getSearchTermParser(): SearchTermParserInterface
    {
        return static::getContainer()->get('app.feedback_search_term_parser');
    }
}