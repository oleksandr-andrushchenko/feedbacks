<?php

declare(strict_types=1);

namespace App\Tests\Traits\Feedback;

use App\Repository\Feedback\SearchTermRepository;

trait SearchTermRepositoryProviderTrait
{
    public function getSearchTermRepository(): SearchTermRepository
    {
        return static::getContainer()->get('app.search_term_repository');
    }
}