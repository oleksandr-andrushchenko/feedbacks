<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer\Telegram;

use App\Entity\Feedback\SearchTerm;
use App\Model\Search\UkrCorrupt\UkrCorruptPerson;
use App\Model\Search\UkrCorrupt\UkrCorruptPersons;
use App\Service\Intl\TimeProvider;
use App\Service\Modifier;
use App\Service\Search\Viewer\SearchViewer;
use App\Service\Search\Viewer\SearchViewerCompose;
use App\Service\Search\Viewer\SearchViewerInterface;

class UkrCorruptTelegramSearchViewer extends SearchViewer implements SearchViewerInterface
{
    public function __construct(SearchViewerCompose $searchViewerCompose, Modifier $modifier)
    {
        parent::__construct($searchViewerCompose->withTransDomain('ukr_corrupt'), $modifier);
    }

    public function getResultMessage($record, SearchTerm $searchTerm, array $context = []): string
    {
        if (is_string($record)) {
            return $record;
        }

        $full = $context['full'] ?? false;
        $this->showLimits = !$full;

        return match (get_class($record)) {
            UkrCorruptPersons::class => $this->getPersonsMessage($record, $searchTerm, $full),
        };
    }

    public function getPersonsMessage(UkrCorruptPersons $record, SearchTerm $searchTerm, bool $full): string
    {
        $m = $this->modifier;
        $term = $searchTerm->getNormalizedText();

        return $m->create()
            ->add($m->boldModifier())
            ->add($m->underlineModifier())
            ->add($m->prependModifier('‼️ '))
            ->add($m->newLineModifier(2))
            ->add(
                $m->appendModifier(
                    $m->implodeLinesModifier(fn (UkrCorruptPerson $item): array => [
                        $m->create()
                            ->add($m->filterModifier())
                            ->add($m->implodeModifier(' '))
                            ->add($m->trimModifier())
                            ->add($m->emptyNullModifier())
                            ->add($full ? $m->nullModifier() : $m->wordSecretsModifier(excepts: $term))
                            ->add($m->slashesModifier())
                            ->add($m->boldModifier())
                            ->add($m->bracketsModifier($this->trans('name')))
                            ->apply([$item->getLastName(), $item->getFirstName(), $item->getPatronymic()]),
                        $m->create()
                            ->add($m->emptyNullModifier())
                            ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                            ->add($m->slashesModifier())
                            ->add($m->bracketsModifier($this->trans('type')))
                            ->apply($item->getEntityType()),
                        $m->create()
                            ->add($m->emptyNullModifier())
                            ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                            ->add($m->prependModifier(' '))
                            ->add($m->prependModifier($m->redModifier()(true)))
                            ->add($m->bracketsModifier($this->trans('offense')))
                            ->apply($item->getOffenseName()),
                        $m->create()
                            ->add($m->emptyNullModifier())
                            ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                            ->add($m->slashesModifier())
                            ->add($m->bracketsModifier($item->getPunishmentType()))
                            ->apply($item->getPunishment()),
                        $m->create()
                            ->add($m->emptyNullModifier())
                            ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                            ->add($m->slashesModifier())
                            ->add($m->bracketsModifier($this->trans('court_case_number')))
                            ->apply($item->getCourtCaseNumber()),
                        $m->create()
                            ->add($m->filterModifier())
                            ->add($m->implodeModifier('; '))
                            ->add($m->trimModifier())
                            ->add($m->emptyNullModifier())
                            ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                            ->add($m->slashesModifier())
                            ->add($m->bracketsModifier($this->trans('codex_articles')))
                            ->apply($item->getCodexArticles()),
                        $m->create()
                            ->add($m->emptyNullModifier())
                            ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                            ->add($m->slashesModifier())
                            ->add($m->bracketsModifier($this->trans('court_name')))
                            ->apply($item->getCourtName()),
                        $m->create()
                            ->add($m->datetimeModifier(TimeProvider::DATE))
                            ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                            ->add($m->slashesModifier())
                            ->add($m->bracketsModifier($this->trans('sentence_date')))
                            ->apply($item->getSentenceDate()),
                        $m->create()
                            ->add($m->datetimeModifier(TimeProvider::DATE))
                            ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                            ->add($m->slashesModifier())
                            ->add($m->bracketsModifier($this->trans('punishment_start')))
                            ->apply($item->getPunishmentStart()),
                    ])($record->getItems())
                )
            )
            ->apply($this->trans('persons_title'))
        ;
    }
}
