<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer\Telegram;

use App\Entity\Feedback\SearchTerm;
use App\Model\Search\UkrWantedPerson\UkrWantedPerson;
use App\Model\Search\UkrWantedPerson\UkrWantedPersons;
use App\Service\Intl\TimeProvider;
use App\Service\Modifier;
use App\Service\Search\Viewer\SearchViewer;
use App\Service\Search\Viewer\SearchViewerCompose;
use App\Service\Search\Viewer\SearchViewerInterface;

class UkrWantedPersonTelegramSearchViewer extends SearchViewer implements SearchViewerInterface
{
    public function __construct(SearchViewerCompose $searchViewerCompose, Modifier $modifier)
    {
        parent::__construct($searchViewerCompose->withTransDomain('ukr_wanted_persons'), $modifier);
    }

    public function getResultMessage($record, SearchTerm $searchTerm, array $context = []): string
    {
        if (is_string($record)) {
            return $record;
        }

        $full = $context['full'] ?? false;
        $this->showLimits = !$full;

        return match (get_class($record)) {
            UkrWantedPersons::class => $this->getPersonsMessage($record, $searchTerm, $full),
            UkrWantedPerson::class => $this->getPersonMessage($record, $searchTerm, $full),
        };
    }

    public function getPersonWrapMessageCallback(SearchTerm $searchTerm, bool $full): callable
    {
        $m = $this->modifier;
        $term = $searchTerm->getNormalizedText();

        return fn (UkrWantedPerson $item): array => [
            $m->create()
                ->add($m->filterModifier())
                ->add($m->implodeModifier(' '))
                ->add($m->trimModifier())
                ->add($m->emptyNullModifier())
                ->add(
                    $m->bracketsModifier(
                        $m->create()
                            ->add($m->filterModifier())
                            ->add($m->implodeModifier(' '))
                            ->add($m->trimModifier())
                            ->add($m->emptyNullModifier())
                            ->add($m->prependModifier(': '))
                            ->add($m->prependModifier($this->trans('rus_name')))
                            ->apply([$item->getRusSurname(), $item->getRusName(), $item->getRusPatronymic()])
                    )
                )
                ->add($full ? $m->nullModifier() : $m->wordSecretsModifier(excepts: $term))
                ->add($m->slashesModifier())
                ->add($full ? $m->linkModifier($item->getHref()) : $m->nullModifier())
                ->add($m->boldModifier())
                ->add($m->bracketsModifier($this->trans('name')))
                ->apply([$item->getUkrSurname(), $item->getUkrName(), $item->getUkrPatronymic()]),
            $m->create()
                ->add($m->emptyNullModifier())
                ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                ->add($m->slashesModifier())
                ->add($m->bracketsModifier($this->trans('gender')))
                ->apply($item->getGender()),
            $m->create()
                ->add($m->datetimeModifier(TimeProvider::DATE))
                ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                ->add($m->slashesModifier())
                ->add($m->bracketsModifier($this->trans('born_at')))
                ->apply($item->getBornAt()),
            $m->create()
                ->add($m->emptyNullModifier())
                ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                ->add($m->prependModifier(' '))
                ->add($m->prependModifier($m->redModifier()(true)))
                ->add($m->slashesModifier())
                ->add($m->bracketsModifier($this->trans('category')))
                ->apply($item->getCategory()),
            $m->create()
                ->add($m->emptyNullModifier())
                ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                ->add($m->slashesModifier())
                ->add($m->bracketsModifier($this->trans('codex_article')))
                ->apply($item->getCodexArticle()),
            $m->create()
                ->add($m->datetimeModifier(TimeProvider::DATE))
                ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                ->add($m->slashesModifier())
                ->add($m->bracketsModifier($this->trans('absent_at')))
                ->apply($item->getAbsentAt()),
            $m->create()
                ->add($m->emptyNullModifier())
                ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                ->add($m->slashesModifier())
                ->add($m->bracketsModifier($this->trans('absent_place')))
                ->apply($item->getAbsentPlace()),
            $m->create()
                ->add($m->emptyNullModifier())
                ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                ->add($m->slashesModifier())
                ->add($m->bracketsModifier($this->trans('precaution')))
                ->apply($item->getPrecaution()),
            $m->create()
                ->add($m->emptyNullModifier())
                ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                ->add($m->slashesModifier())
                ->add($m->bracketsModifier($this->trans('region')))
                ->apply($item->getRegion()),
            $m->create()
                ->add($m->emptyNullModifier())
                ->add($m->slashesModifier())
                ->add($m->bracketsModifier($this->trans('call_to')))
                ->apply($item->getCallTo()),
        ];
    }

    private function getPersonsMessage(UkrWantedPersons $record, SearchTerm $searchTerm, bool $full): string
    {
        $m = $this->modifier;

        return $m->create()
            ->add($m->boldModifier())
            ->add($m->underlineModifier())
            ->add($m->prependModifier('🚨 '))
            ->add($m->newLineModifier(2))
            ->add($m->appendModifier($m->implodeLinesModifier($this->getPersonWrapMessageCallback($searchTerm, $full))($record->getItems())))
            ->apply($this->trans('persons_title'))
        ;
    }

    private function getPersonMessage(UkrWantedPerson $record, SearchTerm $searchTerm, bool $full): string
    {
        $m = $this->modifier;

        return $m->create()
            ->add($m->boldModifier())
            ->add($m->underlineModifier())
            ->add($m->prependModifier('💫 '))
            ->add($m->newLineModifier(2))
            ->add($m->appendModifier($m->implodeLinesModifier($this->getPersonWrapMessageCallback($searchTerm, $full))([$record])))
            ->apply($this->trans('person_title'))
        ;
    }
}
