<?php

declare(strict_types=1);

namespace App\Service\Lookup\Viewer;

use App\Entity\Feedback\Feedback;
use App\Entity\Feedback\FeedbackSearchTerm;
use App\Service\Feedback\SearchTerm\SearchTermProvider;
use App\Service\Feedback\Telegram\Bot\View\FeedbackTelegramViewProvider;
use App\Service\Feedback\Telegram\View\SearchTermTelegramViewProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

class FeedbackTelegramLookupViewer implements LookupViewerInterface
{
    public function __construct(
        private readonly SearchTermTelegramViewProvider $searchTermTelegramViewProvider,
        private readonly FeedbackTelegramViewProvider $feedbackTelegramViewProvider,
        private readonly SearchTermProvider $searchTermProvider,
        private readonly TranslatorInterface $translator,
    )
    {
    }

    public function getOnSearchTitle(FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        $parameters = $this->getParameters($searchTerm);

        return $this->translator->trans('on_search', parameters: $parameters, domain: 'lookups.tg.feedback');
    }

    public function getEmptyResultTitle(FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        $parameters = $this->getParameters($searchTerm);

        $message = '😐 ';
        $message .= $this->translator->trans('empty_result', parameters: $parameters, domain: 'lookups.tg.feedback');

        return $message;
    }

    public function getResultTitle(FeedbackSearchTerm $searchTerm, int $count, array $context = []): string
    {
        $parameters = $this->getParameters($searchTerm);
        $parameters['count'] = $count;

        return $this->translator->trans('result', parameters: $parameters, domain: 'lookups.tg.feedback');
    }

    /**
     * @param Feedback $record
     * @param array $context
     * @return string
     */
    public function getResultRecord($record, array $context = []): string
    {
        return $this->feedbackTelegramViewProvider->getFeedbackTelegramView(
            $context['bot'] ?? $record->getTelegramBot(),
            $record,
            numberToAdd: ($context['index'] ?? 0) + 1,
            addSecrets: $context['addSecrets'] ?? false,
            addSign: $context['addSign'] ?? false,
            addTime: $context['addTime'] ?? false,
            addCountry: $context['addCountry'] ?? false,
        );
    }

    private function getParameters(FeedbackSearchTerm $searchTerm): array
    {
        $searchTermTransfer = $this->searchTermProvider->getFeedbackSearchTermTransfer($searchTerm);

        return [
            'search_term' => $this->searchTermTelegramViewProvider->getSearchTermTelegramView($searchTermTransfer),
        ];
    }
}
