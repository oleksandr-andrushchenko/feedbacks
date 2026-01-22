<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer\Telegram;

use App\Entity\Feedback\Feedback;
use App\Entity\Feedback\SearchTerm;
use App\Entity\Telegram\TelegramBot;
use App\Entity\Telegram\TelegramChannel;
use App\Service\Feedback\FeedbackService;
use App\Service\Feedback\Telegram\Bot\View\FeedbackTelegramReplySignViewProvider;
use App\Service\Feedback\Telegram\View\MultipleSearchTermTelegramViewProvider;
use App\Service\Intl\TimeProvider;
use App\Service\Search\Viewer\SearchViewer;
use App\Service\Search\Viewer\SearchViewerCompose;
use App\Service\Search\Viewer\SearchViewerInterface;
use App\Service\Modifier;

class FeedbackTelegramSearchViewer extends SearchViewer implements SearchViewerInterface
{
    public function __construct(
        SearchViewerCompose $searchViewerCompose,
        Modifier $modifier,
        private readonly MultipleSearchTermTelegramViewProvider $multipleSearchTermTelegramViewProvider,
        private readonly FeedbackTelegramReplySignViewProvider $feedbackTelegramReplySignViewProvider,
        private readonly FeedbackService $feedbackService,
    )
    {
        parent::__construct($searchViewerCompose->withTransDomain('feedback'), $modifier);
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
            ->add($m->appendModifier($m->implodeLinesModifier($this->getFeedbackWrapMessageCallback(full: $full, addCountry: $addCountry, addTime: $addTime, locale: $locale))($record)))
            ->apply($this->trans('feedbacks_title'))
        ;
    }

    public function getFeedbackTelegramView(
        TelegramBot $bot,
        Feedback $feedback,
        bool $addSecrets = false,
        bool $addSign = false,
        bool $addCountry = false,
        bool $addTime = false,
        bool $addQuotes = false,
        TelegramChannel $channel = null,
        string $locale = null,
    ): string
    {
        $m = $this->modifier;

        return $m->create()
            ->add($m->newLineModifier(2))
            ->add(
                $m->appendModifier(
                    $m->linesModifier()(call_user_func(
                        $this->getFeedbackWrapMessageCallback(
                            full: !$addSecrets,
                            addCountry: $addCountry,
                            addTime: $addTime,
                            locale: $locale
                        ),
                        $feedback
                    ))
                )
            )
            ->add($addQuotes ? $m->italicModifier() : $m->nullModifier())
            ->add($addSign ? $m->newLineModifier(2) : $m->nullModifier())
            ->add($addSign ? $m->appendModifier($this->feedbackTelegramReplySignViewProvider->getFeedbackTelegramReplySignView($bot, channel: $channel, localeCode: $locale)) : $m->nullModifier())
            ->apply($this->trans('feedback_title', locale: $locale))
        ;
    }

    private function getFeedbackWrapMessageCallback(
        bool $full = false,
        bool $addCountry = false,
        bool $addTime = false,
        string $locale = null
    ): callable
    {
        $m = $this->modifier;

        return fn (Feedback $item): array => [
            $m->create()
                ->add($m->bracketsModifier($this->trans('search_terms', locale: $locale)))
                ->apply(
                    $this->multipleSearchTermTelegramViewProvider->getFeedbackSearchTermsTelegramView(
                        $this->feedbackService->getSearchTerms($item),
                        addSecrets: !$full,
                        locale: $locale
                    )
                ),
            $m->create()
                ->add($m->markModifier())
                ->add($m->appendModifier(' '))
                ->add($m->appendModifier($this->trans('mark_' . ($item->getRating()->value > 0 ? '+1' : $item->getRating()->value), locale: $locale)))
                ->add($m->bracketsModifier($this->trans('mark', locale: $locale)))
                ->apply($item->getRating()->value),
            $m->create()
                ->add($m->slashesModifier())
                ->add($m->spoilerModifier())
                ->add($m->bracketsModifier($this->trans('description', locale: $locale)))
                ->apply($item->getText()),
            $m->create()
                ->add($m->conditionalModifier($addCountry))
                ->add($m->slashesModifier())
                ->add($m->countryModifier(locale: $locale))
                ->add($m->bracketsModifier($this->trans('country', locale: $locale)))
                ->apply($item->getCountryCode()),
            $m->create()
                ->add($m->conditionalModifier($addTime))
                ->add($m->datetimeModifier(TimeProvider::DATE, timezone: $this->feedbackService->getUser($item)->getTimezone(), locale: $locale))
                ->add($m->bracketsModifier($this->trans('created_at', locale: $locale)))
                ->apply($item->getCreatedAt()),
        ];
    }
}
