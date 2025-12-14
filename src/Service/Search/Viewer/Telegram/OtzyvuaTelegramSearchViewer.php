<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer\Telegram;

use App\Entity\Feedback\SearchTerm;
use App\Enum\Feedback\SearchTermType;
use App\Model\Search\Otzyvua\OtzyvuaFeedback;
use App\Model\Search\Otzyvua\OtzyvuaFeedbacks;
use App\Model\Search\Otzyvua\OtzyvuaFeedbackSearchTerm;
use App\Model\Search\Otzyvua\OtzyvuaFeedbackSearchTerms;
use App\Service\Intl\TimeProvider;
use App\Service\Modifier;
use App\Service\Search\Viewer\SearchViewer;
use App\Service\Search\Viewer\SearchViewerCompose;
use App\Service\Search\Viewer\SearchViewerInterface;

class OtzyvuaTelegramSearchViewer extends SearchViewer implements SearchViewerInterface
{
    public function __construct(SearchViewerCompose $searchViewerCompose, Modifier $modifier)
    {
        parent::__construct($searchViewerCompose->withTransDomain('otzyvua'), $modifier);
    }

    public function getResultMessage($record, SearchTerm $searchTerm, array $context = []): string
    {
        if (is_string($record)) {
            return $record;
        }

        $full = $context['full'] ?? false;
        $this->showLimits = !$full;

        return match (get_class($record)) {
            OtzyvuaFeedbackSearchTerms::class => $this->getFeedbackSearchTermsMessage($record, $searchTerm, $full),
            OtzyvuaFeedbacks::class => $this->getFeedbacksMessage($record, $searchTerm, $full),
        };
    }

    private function getFeedbackSearchTermsMessage(OtzyvuaFeedbackSearchTerms $record, SearchTerm $searchTerm, bool $full): string
    {
        $m = $this->modifier;

        $term = $searchTerm->getNormalizedText();
        $phoneSearch = $searchTerm->getType() === SearchTermType::phone_number;

        return $m->create()
            ->add($m->boldModifier())
            ->add($m->underlineModifier())
            ->add($m->prependModifier('💫 '))
            ->add($m->newLineModifier(2))
            ->add(
                $m->appendModifier(
                    $m->implodeLinesModifier(fn (OtzyvuaFeedbackSearchTerm $item): array => [
                        $m->create()
                            ->add($m->emptyNullModifier())
                            ->add($full || !$phoneSearch ? $m->nullModifier() : $m->wordSecretsModifier(excepts: ['+' . $term, $term]))
                            ->add($m->slashesModifier())
                            ->add($full ? $m->linkModifier($item->getHref()) : $m->nullModifier())
                            ->add($m->boldModifier())
                            ->add($m->bracketsModifier($this->trans('name')))
                            ->apply($item->getName()),
                        $m->create()
                            ->add($m->emptyNullModifier())
                            ->add($m->slashesModifier())
                            ->add($m->underlineModifier())
                            ->add($m->bracketsModifier($this->trans('category')))
                            ->apply($item->getCategory()),
                        $m->create()
                            ->add($m->ratingModifier())
                            ->add($m->bracketsModifier($this->trans('rating', ['value' => $item->getRating(), 'total' => 5])))
                            ->apply((string) $item->getRating()),
                        $m->create()
                            ->add($m->emptyNullModifier())
                            ->add($m->numberFormatModifier(thousandsSeparator: ' '))
                            ->add($m->bracketsModifier($this->trans('feedback_count')))
                            ->apply((string) $item->getCount()),
                    ])($record->getItems())
                )
            )
            ->apply($this->trans('feedback_search_terms_title'))
        ;
    }

    private function getFeedbacksMessage(OtzyvuaFeedbacks $record, SearchTerm $searchTerm, bool $full): string
    {
        $m = $this->modifier;

        $term = $searchTerm->getNormalizedText();
        $phoneSearch = $searchTerm->getType() === SearchTermType::phone_number;

        return $m->create()
            ->add($m->boldModifier())
            ->add($m->underlineModifier())
            ->add($m->prependModifier('💫 '))
            ->add($m->newLineModifier(2))
            ->add($m->appendModifier($m->implodeLinesModifier(fn (OtzyvuaFeedback $item): array => [
                $m->create()
                    ->add($m->emptyNullModifier())
                    ->add($full || !$phoneSearch ? $m->nullModifier() : $m->wordSecretsModifier(excepts: ['+' . $term, $term]))
                    ->add($m->slashesModifier())
                    ->add($full ? $m->linkModifier($item->getHref()) : $m->nullModifier())
                    ->add($m->boldModifier())
                    ->add($m->bracketsModifier($this->trans('title')))
                    ->apply($item->getTitle()),
                $m->create()
                    ->add($m->conditionalModifier($phoneSearch))
                    ->add($m->emptyNullModifier())
                    ->add($m->slashesModifier())
                    ->add($m->bracketsModifier($this->trans('phone')))
                    ->apply($term),
                $m->create()
                    ->add($m->ratingModifier())
                    ->add($m->bracketsModifier($this->trans('rating', ['value' => $item->getRating(), 'total' => 5])))
                    ->apply((string) $item->getRating()),
                $m->create()
                    ->add($m->emptyNullModifier())
                    ->add($full || !$phoneSearch ? $m->nullModifier() : $m->wordSecretsModifier())
                    ->add($m->slashesModifier())
                    ->add($full || !$phoneSearch ? $m->spoilerModifier() : $m->nullModifier())
                    ->add($m->bracketsModifier($this->trans('description')))
                    ->apply($item->getDescription()),
                $m->create()
                    ->add($m->emptyNullModifier())
                    ->add($full || !$phoneSearch ? $m->nullModifier() : $m->wordSecretsModifier())
                    ->add($m->slashesModifier())
                    ->add($full ? $m->linkModifier($item->getAuthorHref()) : $m->nullModifier())
                    ->add($m->bracketsModifier($this->trans('author')))
                    ->apply($item->getAuthorName()),
                $m->create()
                    ->add($m->slashesModifier())
                    ->add($m->countryModifier())
                    ->add($m->bracketsModifier($this->trans('country')))
                    ->apply('ua'),
                $m->create()
                    ->add($m->datetimeModifier(TimeProvider::DATE))
                    ->add($m->slashesModifier())
                    ->add($full || !$phoneSearch ? $m->nullModifier() : $m->wordSecretsModifier())
                    ->add($m->bracketsModifier($this->trans('created_at')))
                    ->apply($item->getCreatedAt()),
            ])($record->getItems())))
            ->apply($this->trans('feedbacks_title'))
        ;
    }
}
