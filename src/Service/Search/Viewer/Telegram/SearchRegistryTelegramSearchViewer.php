<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer\Telegram;

use App\Entity\Feedback\FeedbackSearch;
use App\Entity\Feedback\SearchTerm;
use App\Entity\Telegram\TelegramBot;
use App\Entity\Telegram\TelegramChannel;
use App\Service\Feedback\FeedbackSearchService;
use App\Service\Feedback\SearchTerm\SearchTermProvider;
use App\Service\Feedback\Telegram\Bot\View\FeedbackTelegramReplySignViewProvider;
use App\Service\Feedback\Telegram\View\SearchTermTelegramViewProvider;
use App\Service\Intl\TimeProvider;
use App\Service\Modifier;
use App\Service\Search\Viewer\SearchViewer;
use App\Service\Search\Viewer\SearchViewerCompose;
use App\Service\Search\Viewer\SearchViewerInterface;

class SearchRegistryTelegramSearchViewer extends SearchViewer implements SearchViewerInterface
{
    public function __construct(
        SearchViewerCompose $searchViewerCompose,
        Modifier $modifier,
        private readonly SearchTermProvider $searchTermProvider,
        private readonly SearchTermTelegramViewProvider $searchTermTelegramViewProvider,
        private readonly FeedbackTelegramReplySignViewProvider $feedbackTelegramReplySignViewProvider,
        private readonly FeedbackSearchService $feedbackSearchService,
    )
    {
        parent::__construct($searchViewerCompose->withTransDomain('search'), $modifier);
    }

    public function getResultMessage($record, SearchTerm $searchTerm, array $context = []): string
    {
        $full = $context['full'] ?? false;
        $this->showLimits = !$full;
        $locale = $context['locale'] ?? null;
        $addCountry = $context['addCountry'] ?? false;
        $addTime = $context['addTime'] ?? false;

        $m = $this->modifier;

        return $m->create()
            ->add($m->boldModifier())
            ->add($m->underlineModifier())
            ->add($m->prependModifier('💫 '))
            ->add($m->newLineModifier(2))
            ->add($m->appendModifier($m->implodeLinesModifier($this->getFeedbackSearchWrapMessageCallback(full: $full, addCountry: $addCountry, addTime: $addTime, locale: $locale))($record)))
            ->apply($this->trans('searches_title'))
        ;
    }

    public function getFeedbackSearchTelegramView(
        TelegramBot $bot,
        FeedbackSearch $feedbackSearch,
        bool $addSecrets = false,
        bool $addSign = false,
        bool $addCountry = false,
        bool $addTime = false,
        bool $addQuotes = false,
        string $locale = null,
        TelegramChannel $channel = null,
    ): string
    {
        $m = $this->modifier;

        return $m->create()
            ->add($m->newLineModifier(2))
            ->add(
                $m->appendModifier(
                    $m->linesModifier()(call_user_func(
                        $this->getFeedbackSearchWrapMessageCallback(
                            full: !$addSecrets,
                            addCountry: $addCountry,
                            addTime: $addTime,
                            locale: $locale
                        ),
                        $feedbackSearch
                    ))
                )
            )
            ->add($addQuotes ? $m->italicModifier() : $m->nullModifier())
            ->add($addSign ? $m->newLineModifier(2) : $m->nullModifier())
            ->add($addSign ? $m->appendModifier($this->feedbackTelegramReplySignViewProvider->getFeedbackTelegramReplySignView($bot, channel: $channel, localeCode: $locale)) : $m->nullModifier())
            ->apply($this->trans('search_title', locale: $locale))
        ;
    }

    private function getFeedbackSearchWrapMessageCallback(
        bool $full = false,
        bool $addCountry = false,
        bool $addTime = false,
        string $locale = null
    ): callable
    {
        return function (FeedbackSearch $item) use ($full, $addCountry, $addTime, $locale): array {
            $m = $this->modifier;
            $searchTerm = $this->feedbackSearchService->getSearchTerm($item);

            return [
                $m->create()
                    ->add($m->bracketsModifier($this->trans('search_term', locale: $locale)))
                    ->apply(
                        $this->searchTermTelegramViewProvider->getSearchTermTelegramView(
                            $this->searchTermProvider->getFeedbackSearchTermTransfer($searchTerm),
                            addSecrets: !$full,
                            localeCode: $locale
                        )
                    ),
                $m->create()
                    ->add($m->conditionalModifier($addCountry))
                    ->add($m->slashesModifier())
                    ->add($m->countryModifier(locale: $locale))
                    ->add($m->bracketsModifier($this->trans('country', locale: $locale)))
                    ->apply($item->getCountryCode()),
                $m->create()
                    ->add($m->conditionalModifier($addTime))
                    ->add($m->datetimeModifier(TimeProvider::DATE, timezone: $item->getUser()->getTimezone(), locale: $locale))
                    ->add($m->bracketsModifier($this->trans('created_at', locale: $locale)))
                    ->apply($item->getCreatedAt()),
            ];
        };
    }
}
