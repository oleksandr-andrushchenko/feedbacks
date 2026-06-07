<?php
declare(strict_types=1);

namespace App\Service\Feedback\LLM;

use App\Enum\Feedback\SearchTermType;
use App\Service\LLM\LlmClientInterface;
use App\Transfer\Feedback\SearchTermsTransfer;
use App\Transfer\Feedback\SearchTermTransfer;

readonly class FeedbackSearchTermsExtractor
{
    public function __construct(
        private LlmClientInterface $llmClient,
    )
    {
    }

    public function extract(string $text): SearchTermsTransfer
    {
        $payload = $this->llmClient->requestJson('search_terms', [
            [
                'role' => 'system',
                'content' => $this->getPrompt(),
            ],
            [
                'role' => 'user',
                'content' => $text,
            ],
        ], $this->getResponseSchema());

        return $this->extractSearchTerms($payload);
    }

    private function getPrompt(): string
    {
        $types = implode(', ', array_map(
            static fn (SearchTermType $type): string => $type->name,
            SearchTermType::cases()
        ));

        return <<<PROMPT
Extract structured search terms from text describing a target the user wants to search feedback about.

Rules:
- search_terms are identifiers for who or what the target is.
- Include every useful target identifier: names, usernames, profile URLs, phone numbers, emails, car numbers, tax numbers, organization names, place names, and generic URLs.
- Use only these search term types: {$types}.
- If a term type is unclear, use unknown.
- Do not invent identifiers that are not present in the text.
PROMPT;
    }

    private function getResponseSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['search_terms'],
            'properties' => [
                'search_terms' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['text', 'type'],
                        'properties' => [
                            'text' => ['type' => 'string'],
                            'type' => [
                                'type' => 'string',
                                'enum' => array_map(
                                    static fn (SearchTermType $type): string => $type->name,
                                    SearchTermType::cases()
                                ),
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function extractSearchTerms(array $payload): SearchTermsTransfer
    {
        $searchTerms = new SearchTermsTransfer();
        $items = $payload['search_terms'] ?? [];

        if (!is_array($items)) {
            return $searchTerms;
        }

        foreach ($items as $item) {
            if (!is_array($item) || !isset($item['text']) || !is_string($item['text'])) {
                continue;
            }

            $text = trim($item['text']);

            if ($text === '') {
                continue;
            }

            $type = null;

            if (isset($item['type']) && is_string($item['type'])) {
                $type = SearchTermType::fromName($item['type']);
            }

            $searchTerms->addItem(new SearchTermTransfer($text, type: $type ?? SearchTermType::unknown));
        }

        return $searchTerms;
    }
}
