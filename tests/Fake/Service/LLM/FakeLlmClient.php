<?php
declare(strict_types=1);

namespace App\Tests\Fake\Service\LLM;

use App\Enum\Feedback\Rating;
use App\Enum\Feedback\SearchTermType;
use App\Service\LLM\LlmClientInterface;
use RuntimeException;

class FakeLlmClient implements LlmClientInterface
{
    public function requestJson(string $name, array $messages, array $schema): array
    {
        $content = (string) ($messages[array_key_last($messages)]['content'] ?? '');

        if (str_contains($content, 'extract_fail')) {
            throw new RuntimeException('Extraction failed');
        }

        $searchTerms = str_contains($content, 'no_terms') ? [] : [
            [
                'text' => 'instasd',
                'type' => SearchTermType::instagram_username->name,
            ],
        ];

        if ($name === 'feedback_details') {
            return [
                'search_terms' => $searchTerms,
                'rating' => Rating::satisfied->name,
            ];
        }

        if ($name === 'search_terms') {
            return [
                'search_terms' => $searchTerms,
            ];
        }

        return [];
    }
}
