<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer\Telegram;

use App\Entity\Feedback\SearchTerm;
use App\Model\Search\ShouldIAnswer\ShouldIAnswerReview;
use App\Model\Search\ShouldIAnswer\ShouldIAnswerReviews;
use App\Service\Intl\TimeProvider;
use App\Service\Modifier;
use App\Service\Search\Viewer\SearchViewer;
use App\Service\Search\Viewer\SearchViewerCompose;
use App\Service\Search\Viewer\SearchViewerInterface;

class ShouldIAnswerTelegramSearchViewer extends SearchViewer implements SearchViewerInterface
{
    public function __construct(SearchViewerCompose $searchViewerCompose, Modifier $modifier)
    {
        parent::__construct($searchViewerCompose->withTransDomain('should_i_answer'), $modifier);
    }

    public function getResultMessage($record, SearchTerm $searchTerm, array $context = []): string
    {
        if (is_string($record)) {
            return $record;
        }

        $full = $context['full'] ?? false;
        $this->showLimits = !$full;

        return match (get_class($record)) {
            ShouldIAnswerReviews::class => $this->getReviewsMessage($record, $full),
        };
    }

    private function getReviewsMessage(ShouldIAnswerReviews $record, bool $full): string
    {
        $m = $this->modifier;

        $this->showLimits = !$full;

        return $m->create()
            ->add($m->boldModifier())
            ->add($m->underlineModifier())
            ->add($m->prependModifier('💫 '))
            ->add($m->newLineModifier(2))
            ->add($m->appendModifier($record->getHeader()))
            ->add($m->newLineModifier(2))
            ->add($m->appendModifier($record->getInfo()))
            ->add($m->newLineModifier(2))
            ->add(
                $m->appendModifier(
                    $m->create()
                        ->add($m->markModifier())
                        ->add($m->appendModifier(' '))
                        ->add($m->appendModifier($this->trans('mark_' . ($record->getScore() > 0 ? '+1' : $record->getScore()))))
//                            ->add($m->bracketsModifier($this->trans('mark')))
                        ->apply($record->getScore())
                )
            )
            ->add($m->newLineModifier(2))
            ->add(
                $m->appendModifier(
                    $m->implodeLinesModifier(fn (ShouldIAnswerReview $item): array => [
                        $m->create()
                            ->add($m->emptyNullModifier())
                            ->add($m->slashesModifier())
                            ->add($m->boldModifier())
                            ->add($m->bracketsModifier($this->trans('name')))
                            ->apply($item->getName()),
                        $m->create()
                            ->add($m->emptyNullModifier())
                            ->add($m->slashesModifier())
                            ->add($m->spoilerModifier())
                            ->add($m->bracketsModifier($this->trans('description')))
                            ->apply($item->getDescription()),
                        $m->create()
                            ->add($m->ratingModifier())
                            ->add($m->bracketsModifier($this->trans('rating', ['value' => $item->getRating(), 'total' => 5])))
                            ->apply((string) $item->getRating()),
                        $m->create()
                            ->add($m->emptyNullModifier())
                            ->add($m->slashesModifier())
                            ->add($m->bracketsModifier($this->trans('author')))
                            ->apply($item->getAuthor()),
                        $m->create()
                            ->add($m->slashesModifier())
                            ->add($m->countryModifier())
                            ->add($m->bracketsModifier($this->trans('country')))
                            ->apply('us'),
                        $m->create()
                            ->add($m->datetimeModifier(TimeProvider::DATE))
                            ->add($m->slashesModifier())
                            ->add($m->bracketsModifier($this->trans('date_published')))
                            ->apply($item->getDatePublished()),
                    ])($record->getItems())
                )
            )
            ->apply($this->trans('reviews_title'))
        ;
    }
}
