<?php
declare(strict_types=1);

namespace App\Service\Search\Provider;

abstract class SearchProvider implements SearchProviderInterface
{
    public function __construct(
        protected readonly SearchProviderCompose $searchProviderCompose,
    )
    {
    }
}