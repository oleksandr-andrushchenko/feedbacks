<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer\Telegram;

use App\Entity\Feedback\SearchTerm;
use App\Enum\Feedback\SearchTermType;
use App\Model\Search\Blackbox\BlackboxFeedback;
use App\Model\Search\Blackbox\BlackboxFeedbacks;
use App\Service\Intl\TimeProvider;
use App\Service\Modifier;
use App\Service\Search\Viewer\SearchViewer;
use App\Service\Search\Viewer\SearchViewerCompose;
use App\Service\Search\Viewer\SearchViewerInterface;

class BlackboxTelegramSearchViewer extends SearchViewer implements SearchViewerInterface
{
    public function __construct(SearchViewerCompose $searchViewerCompose, Modifier $modifier)
    {
        parent::__construct($searchViewerCompose->withTransDomain('blackbox'), $modifier);
    }

    public function getResultMessage($record, SearchTerm $searchTerm, array $context = []): string
    {
        if (is_string($record)) {
            return $record;
        }

        $full = $context['full'] ?? false;
        $this->showLimits = !$full;

        $m = $this->modifier;

        $term = $searchTerm->getNormalizedText();
        $personSearch = $searchTerm->getType() === SearchTermType::person_name;
        $phoneSearch = $searchTerm->getType() === SearchTermType::phone_number;

        return $m->create()
            ->add($m->boldModifier())
            ->add($m->underlineModifier())
            ->add($m->prependModifier('‼️ '))
            ->add($m->newLineModifier(2))
            ->add(
                $m->appendModifier(
                    $m->implodeLinesModifier(fn (BlackboxFeedback $item): array => [
                        $m->create()
                            ->add($m->emptyNullModifier())
                            ->add($full ? $m->nullModifier() : $m->wordSecretsModifier(excepts: $personSearch ? $term : null))
                            ->add($m->slashesModifier())
                            ->add($full ? $m->linkModifier($item->getHref()) : $m->nullModifier())
                            ->add($m->boldModifier())
                            ->add($m->bracketsModifier($this->trans('name')))
                            ->apply($item->getName()),
                        $m->create()
                            ->add($m->emptyNullModifier())
                            ->add($full ? $m->nullModifier() : $m->wordSecretsModifier(excepts: $phoneSearch ? substr($term, 2) : null))
                            ->add($m->slashesModifier())
                            ->add($m->prependModifier(' '))
                            ->add($m->prependModifier($m->redModifier()(true)))
                            ->add($m->bracketsModifier($this->trans('phone')))
                            ->apply($item->getPhone()),
                        $m->create()
                            ->add($m->emptyNullModifier())
                            ->add($m->slashesModifier())
                            ->add($m->spoilerModifier())
                            ->add($m->bracketsModifier($this->trans('comment')))
                            ->apply($item->getComment()),
                        $m->create()
                            ->add($m->filterModifier())
                            ->add($m->implodeModifier(', '))
                            ->add($m->emptyNullModifier())
                            ->add($m->bracketsModifier($item->getType()))
                            ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                            ->add($m->slashesModifier())
                            ->add($m->bracketsModifier($this->trans('warehouse')))
                            ->apply([$item->getCity(), $item->getWarehouse()]),
                        $m->create()
                            ->add($m->slashesModifier())
                            ->add($m->countryModifier())
                            ->add($m->bracketsModifier($this->trans('country')))
                            ->apply('ua'),
                        $m->create()
                            ->add($m->datetimeModifier(TimeProvider::DATE))
                            ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                            ->add($m->slashesModifier())
                            ->add($m->bracketsModifier($this->trans('date')))
                            ->apply($item->getDate()),
                    ])($record instanceof BlackboxFeedbacks ? $record->getItems() : [$record])
                )
            )
            ->apply($this->trans('feedbacks_title'))
        ;
    }
}
