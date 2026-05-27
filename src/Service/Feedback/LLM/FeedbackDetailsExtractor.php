<?php
declare(strict_types=1);

namespace App\Service\Feedback\LLM;

use App\Enum\Feedback\Rating;
use App\Enum\Feedback\SearchTermType;
use App\Transfer\Feedback\SearchTermsTransfer;
use App\Transfer\Feedback\SearchTermTransfer;

readonly class FeedbackDetailsExtractor
{
    public function __construct(
        private LlmClientInterface $llmClient,
    )
    {
    }

    /**
     * @param string $feedback
     * @return array{search_terms: SearchTermsTransfer, rating: Rating}
     */
    public function extract(string $feedback): array
    {
        $payload = $this->llmClient->requestJson('feedback_details', [
            [
                'role' => 'system',
                'content' => $this->getPrompt(),
            ],
            [
                'role' => 'user',
                'content' => $feedback,
            ],
        ], $this->getResponseSchema());

        return [
            'search_terms' => $this->extractSearchTerms($payload),
            'rating' => $this->extractRating($payload),
        ];
    }

    private function getPrompt(): string
    {
        $types = implode(', ', array_map(
            static fn (SearchTermType $type): string => $type->name,
            SearchTermType::cases()
        ));
        $ratings = implode(', ', array_map(
            static fn (Rating $rating): string => $rating->name,
            Rating::cases()
        ));

        return <<<PROMPT
Extract structured feedback details from the user text.

Rules:
- search_terms are identifiers for who or what the feedback is about.
- Include every useful target identifier: names, usernames, profile URLs, phone numbers, emails, car numbers, tax numbers, organization names, place names, and generic URLs.
- Use only these search term types: {$types}.
- Use only these ratings: {$ratings}.
- rating=satisfied for positive feedback, unsatisfied for negative feedback, neutral when mixed or unclear.
- If a term type is unclear, use unknown.
- Do not rewrite the original feedback text.
PROMPT;
    }

    private function getResponseSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['search_terms', 'rating'],
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
                'rating' => [
                    'type' => 'string',
                    'enum' => array_map(
                        static fn (Rating $rating): string => $rating->name,
                        Rating::cases()
                    ),
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

    private function extractRating(array $payload): Rating
    {
        $rating = $payload['rating'] ?? null;

        if (is_string($rating)) {
            foreach (Rating::cases() as $case) {
                if ($case->name === $rating) {
                    return $case;
                }
            }
        }

        return Rating::neutral;
    }
}
