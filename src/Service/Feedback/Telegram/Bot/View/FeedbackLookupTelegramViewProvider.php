<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\Bot\View;

use App\Entity\Feedback\FeedbackLookup;
use App\Entity\Telegram\TelegramBot;
use App\Entity\Telegram\TelegramChannel;
use App\Service\Feedback\FeedbackLookupService;
use App\Service\Feedback\SearchTerm\SearchTermProvider;
use App\Service\Feedback\Telegram\View\SearchTermTelegramViewProvider;
use App\Service\Intl\CountryProvider;
use App\Service\Intl\TimeProvider;
use App\Service\Util\String\MbUcFirster;
use Symfony\Contracts\Translation\TranslatorInterface;

class FeedbackLookupTelegramViewProvider
{
    public function __construct(
        private readonly SearchTermProvider $searchTermProvider,
        private readonly SearchTermTelegramViewProvider $searchTermTelegramViewProvider,
        private readonly CountryProvider $countryProvider,
        private readonly TimeProvider $timeProvider,
        private readonly TranslatorInterface $translator,
        private readonly FeedbackTelegramReplySignViewProvider $feedbackTelegramReplySignViewProvider,
        private readonly MbUcFirster $mbUcFirster,
        private readonly FeedbackLookupService $feedbackLookupService,
    )
    {
    }

    public function getFeedbackLookupTelegramView(
        TelegramBot $bot,
        FeedbackLookup $feedbackLookup,
        int $numberToAdd = null,
        bool $addSecrets = false,
        bool $addSign = false,
        bool $addTime = false,
        bool $addCountry = false,
        bool $addQuotes = false,
        string $localeCode = null,
        TelegramChannel $channel = null,
    ): string
    {
        $message = '';

        if ($addQuotes) {
            $message .= '<i>';
        }

        if ($numberToAdd !== null) {
            $message .= $this->translator->trans('icon.number', domain: 'feedbacks.tg', locale: $localeCode);
            $message .= $numberToAdd;
            $message .= "\n";
        }

        $message2 = '';

        if ($addTime) {
            $user = $this->feedbackLookupService->getUser($feedbackLookup);
            $message2 .= $this->timeProvider->formatAsDate($feedbackLookup->getCreatedAt(), timezone: $user->getTimezone(), locale: $localeCode);
            $message2 .= ', ';
        }

        $message2 .= $this->translator->trans('somebody', domain: 'feedbacks.tg.feedback_lookup', locale: $localeCode);
        $message2 .= ' ';

        $message .= $this->mbUcFirster->mbUcFirst($message2);

        if ($addCountry) {
            $message .= $this->translator->trans('from', domain: 'feedbacks.tg.feedback_lookup', locale: $localeCode);
            $message .= ' ';
            $message .= $this->countryProvider->getCountryComposeName($feedbackLookup->getCountryCode(), localeCode: $localeCode);
            $message .= ' ';
        }

        $message .= $this->translator->trans('searched_for', domain: 'feedbacks.tg.feedback_lookup', locale: $localeCode);
        $message .= ' ';
        $searchTerm = $this->feedbackLookupService->getSearchTerm($feedbackLookup);
        $message .= $this->searchTermTelegramViewProvider->getSearchTermTelegramView(
            $this->searchTermProvider->getFeedbackSearchTermTransfer($searchTerm),
            addSecrets: $addSecrets,
            localeCode: $localeCode
        );

        if ($addQuotes) {
            $message .= '</i>';
        }

        if ($addSign) {
            $message .= "\n\n";

            $message .= $this->feedbackTelegramReplySignViewProvider->getFeedbackTelegramReplySignView($bot, channel: $channel, localeCode: $localeCode);
        }

        return $message;
    }
}