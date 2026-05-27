<?php
declare(strict_types=1);

namespace App\Tests\Traits\Feedback;

use App\Serializer\Feedback\SearchTermTransferNormalizer;

trait SearchTermTransferNormalizerProviderTrait
{
    public function getSearchTermTransferNormalizer(): SearchTermTransferNormalizer
    {
        return static::getContainer()->get('app.feedback_search_term_transfer_normalizer');
    }
}