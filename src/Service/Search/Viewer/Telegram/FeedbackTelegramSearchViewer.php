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
use App\Service\Modifier;
use App\Service\Search\Viewer\SearchViewer;
use App\Service\Search\Viewer\SearchViewerCompose;
use App\Service\Search\Viewer\SearchViewerInterface;

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
            ->add($m->appendModifier($this->getFeedbackView($record, !$full, $addCountry, $addTime, locale: $locale)))
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

        return $this->getFeedbackView(
            $feedback,
            $addSecrets,
            $addCountry,
            $addTime,
            $addQuotes,
            $locale,
            sign: $m->create()
                ->add($m->conditionalModifier($addSign))
                ->apply(
                    $this->feedbackTelegramReplySignViewProvider->getFeedbackTelegramReplySignView(
                        $bot,
                        $channel,
                        $locale,
                        separator: ' • '
                    )
                ),
            tagsSeparator: ' ▫️ ',
            termsSeparator: ' ▫️ '
        );
    }

    public function getFeedbackView(
        Feedback $feedback,
        bool $addSecrets = false,
        bool $addCountry = false,
        bool $addTime = false,
        bool $addQuotes = false,
        string $locale = null,
        string $sign = null,
        bool $addTermTypes = false,
        string $tagsSeparator = ' • ',
        string $termsSeparator = ' • ',
    ): string
    {
        $m = $this->modifier;

        return $m->create()
            ->add($m->implodeModifier($m->create()->add($m->newLineModifier(2))->apply()))
            ->apply([
                // mark + text
                $m->create()
                    ->add($m->implodeModifier(' '))
                    ->add($addQuotes ? $m->italicModifier() : $m->nullModifier())
                    ->apply([
                        $m->create()->add($m->markModifier())->apply($feedback->getRating()->value),
                        $feedback->getText(),
                    ]),
                // tags: terms, country, time
                $m->create()
                    ->add($m->filterModifier())
                    ->add($m->implodeModifier($tagsSeparator))
                    ->apply([
//                        $m->create()
//                            ->apply(
//                                $this->multipleSearchTermTelegramViewProvider->getFeedbackSearchTermsTelegramView(
//                                    $this->feedbackService->getSearchTerms($feedback),
//                                    $addSecrets,
//                                    $locale,
//                                    $addTermTypes,
//                                    $termsSeparator,
//                                )
//                            ),
                        $m->create()
                            ->add($m->conditionalModifier($addCountry))
                            ->add($m->slashesModifier())
                            ->add($m->countryModifier($locale))
                            ->apply($feedback->getCountryCode()),
                        $m->create()
                            ->add($m->conditionalModifier($addTime))
                            ->add(
                                $m->datetimeModifier(
                                    TimeProvider::SHORT_DATE,
                                    $this->feedbackService->getUser($feedback)->getTimezone(),
                                    $locale
                                )
                            )
                            ->apply($feedback->getCreatedAt()),
                    ]),
                // sign
                $m->create()
                    ->add($m->conditionalModifier($sign))
                    ->apply($sign),
            ])
        ;
    }
}
