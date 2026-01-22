<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer\Telegram;

use App\Entity\Feedback\SearchTerm;
use App\Model\Search\Clarity\ClarityEdr;
use App\Model\Search\Clarity\ClarityEdrs;
use App\Model\Search\Clarity\ClarityPerson;
use App\Model\Search\Clarity\ClarityPersonCourt;
use App\Model\Search\Clarity\ClarityPersonCourts;
use App\Model\Search\Clarity\ClarityPersonDebtor;
use App\Model\Search\Clarity\ClarityPersonDebtors;
use App\Model\Search\Clarity\ClarityPersonDeclaration;
use App\Model\Search\Clarity\ClarityPersonDeclarations;
use App\Model\Search\Clarity\ClarityPersonEdr;
use App\Model\Search\Clarity\ClarityPersonEdrs;
use App\Model\Search\Clarity\ClarityPersonEnforcement;
use App\Model\Search\Clarity\ClarityPersonEnforcements;
use App\Model\Search\Clarity\ClarityPersons;
use App\Model\Search\Clarity\ClarityPersonSecurities;
use App\Model\Search\Clarity\ClarityPersonSecurity;
use App\Service\Intl\TimeProvider;
use App\Service\Modifier;
use App\Service\Search\Viewer\SearchViewer;
use App\Service\Search\Viewer\SearchViewerCompose;
use App\Service\Search\Viewer\SearchViewerInterface;
use DateTimeImmutable;

class ClarityTelegramSearchViewer extends SearchViewer implements SearchViewerInterface
{
    public function __construct(SearchViewerCompose $searchViewerCompose, Modifier $modifier)
    {
        parent::__construct($searchViewerCompose->withTransDomain('clarity'), $modifier);
    }

    public function getResultMessage($record, SearchTerm $searchTerm, array $context = []): string
    {
        if (is_string($record)) {
            return $record;
        }

        $full = $context['full'] ?? false;
        $this->showLimits = !$full;

        return match (get_class($record)) {
            ClarityPersons::class => $this->getPersonsMessage($record, $searchTerm, $full),
            ClarityPersonEdrs::class => $this->getPersonEdrsMessage($record, $searchTerm, $full),
            ClarityPersonSecurities::class => $this->getPersonSecurityMessage($record, $searchTerm, $full),
            ClarityPersonCourts::class => $this->getPersonCourtsMessage($record, $full),
            ClarityPersonEnforcements::class => $this->getPersonEnforcementsMessage($record, $searchTerm, $full),
            ClarityPersonDebtors::class => $this->getPersonDebtorsMessage($record, $searchTerm, $full),
            ClarityPersonDeclarations::class => $this->getPersonDeclarationsMessage($record, $searchTerm, $full),
            ClarityEdrs::class => $this->getEdrsMessage($record, $searchTerm, $full),
        };
    }

    private function getPersonsMessage(ClarityPersons $record, SearchTerm $searchTerm, bool $full): string
    {
        $m = $this->modifier;
        $term = $searchTerm->getNormalizedText();

        return $m->create()
            ->add($m->boldModifier())
            ->add($m->underlineModifier())
            ->add($m->prependModifier('💫 '))
            ->add($m->newLineModifier(2))
            ->add(
                $m->appendModifier(
                    $m->implodeLinesModifier(fn (ClarityPerson $item): array => [
                        $m->create()
                            ->add($m->emptyNullModifier())
                            ->add($full ? $m->nullModifier() : $m->wordSecretsModifier(excepts: $term))
                            ->add($m->slashesModifier())
                            ->add($full ? $m->linkModifier($item->getHref()) : $m->nullModifier())
                            ->add($m->boldModifier())
                            ->add($m->bracketsModifier($this->trans('person_name')))
                            ->apply($item->getName()),
                        $m->create()
                            ->add($m->conditionalModifier($item->getCount() > 0))
                            ->add($m->emptyNullModifier())
                            ->add($m->slashesModifier())
                            ->apply($this->trans('person_count', ['count' => $item->getCount()])),
                    ])($record->getItems())
                )
            )
            ->apply($this->trans('persons_title'))
        ;
    }

    private function getPersonEdrsMessage(ClarityPersonEdrs $record, SearchTerm $searchTerm, bool $full): string
    {
        $term = $searchTerm->getNormalizedText();
        $m = $this->modifier;

        return $m->create()
            ->add($m->boldModifier())
            ->add($m->underlineModifier())
            ->add($m->prependModifier('💫 '))
            ->add($m->newLineModifier(2))
            ->add(
                $m->appendModifier(
                    $m->implodeLinesModifier(fn (ClarityPersonEdr $item): array => [
                        $m->create()
                            ->add($m->emptyNullModifier())
                            ->add($full ? $m->nullModifier() : $m->wordSecretsModifier(excepts: $term))
                            ->add($m->slashesModifier())
                            ->add($full ? $m->linkModifier($item->getHref()) : $m->nullModifier())
                            ->add($m->boldModifier())
                            ->add($m->bracketsModifier($this->trans('person_name')))
                            ->apply($item->getName()),
                        $m->create()
                            ->add($m->emptyNullModifier())
                            ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                            ->add($m->slashesModifier())
                            ->add($m->bracketsModifier($this->trans('person_edr_type')))
                            ->apply($item->getType()),
                        $m->create()
                            ->add($m->emptyNullModifier())
                            ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                            ->add($m->slashesModifier())
                            ->add($m->bracketsModifier($this->trans('edr_number')))
                            ->apply($item->getNumber()),
                        $m->create()
                            ->add($m->greenWhiteModifier($this->trans('active'), $this->trans('not_active')))
                            ->apply($item->getActive()),
                        $m->create()
                            ->add($m->emptyNullModifier())
                            ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                            ->add($m->slashesModifier())
                            ->add($m->bracketsModifier($this->trans('address')))
                            ->apply($item->getAddress()),
                    ])($record->getItems())
                )
            )
            ->apply($this->trans('person_edrs_title'))
        ;
    }

    private function getPersonSecurityMessage(ClarityPersonSecurities $record, SearchTerm $searchTerm, bool $full): string
    {
        $m = $this->modifier;
        $term = $searchTerm->getNormalizedText();

        return $m->create()
            ->add($m->boldModifier())
            ->add($m->underlineModifier())
            ->add($m->prependModifier('🚨 '))
            ->add($m->newLineModifier(2))
            ->add(
                $m->appendModifier(
                    $m->implodeLinesModifier(fn (ClarityPersonSecurity $item): array => [
                        $m->create()
                            ->add($m->emptyNullModifier())
                            ->add($full ? $m->nullModifier() : $m->wordSecretsModifier(excepts: $term))
                            ->add($m->slashesModifier())
                            ->add($m->boldModifier())
                            ->add($m->bracketsModifier($this->trans('person_name')))
                            ->apply($item->getName()),
                        $m->create()
                            ->add($m->emptyNullModifier())
                            ->add($m->datetimeModifier(TimeProvider::DATE))
                            ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                            ->add($m->slashesModifier())
                            ->add($m->bracketsModifier($this->trans('born_at')))
                            ->apply($item->getBornAt()),
                        $m->create()
                            ->add($m->emptyNullModifier())
                            ->add($m->ucFirstModifier())
                            ->add($m->slashesModifier())
                            ->add($m->prependModifier(' '))
                            ->add($m->prependModifier($m->redModifier()(true)))
                            ->add($m->bracketsModifier($this->trans('category')))
                            ->apply($item->getCategory()),
                        $m->create()
                            ->add($m->emptyNullModifier())
                            ->add($m->datetimeModifier(TimeProvider::DATE))
                            ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                            ->add($m->slashesModifier())
                            ->add($m->bracketsModifier($this->trans('absent_at')))
                            ->apply($item->getAbsentAt()),
                        $m->create()
                            ->add($m->emptyNullModifier())
                            ->add($m->slashesModifier())
                            ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                            ->add($m->bracketsModifier($this->trans('accusation')))
                            ->apply($item->getAccusation()),
                        $m->create()
                            ->add($m->slashesModifier())
                            ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                            ->add($m->bracketsModifier($this->trans('precaution')))
                            ->apply($item->getPrecaution()),
                    ])($record->getItems())
                )
            )
            ->apply($this->trans('security_title'))
        ;
    }

    private function getPersonCourtsMessage(ClarityPersonCourts $record, bool $full): string
    {
        $m = $this->modifier;

        return $m->create()
            ->add($m->boldModifier())
            ->add($m->underlineModifier())
            ->add($m->prependModifier('‼️ '))
            ->add($m->newLineModifier(2))
            ->add(
                $m->appendModifier(
                    $m->implodeLinesModifier(fn (ClarityPersonCourt $item): array => [
                        $m->create()
                            ->add($m->emptyNullModifier())
                            ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                            ->add($m->slashesModifier())
                            ->add($m->boldModifier())
                            ->add($m->bracketsModifier($this->trans('case_number')))
                            ->apply($item->getNumber()),
                        $m->create()
                            ->add($m->emptyNullModifier())
                            ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                            ->add($m->slashesModifier())
                            ->apply($item->getState()),
                        $m->create()
                            ->add($m->redWhiteModifier())
                            ->add($m->appendModifier(' '))
                            ->add(
                                $m->appendModifier(
                                    $m->create()
                                        ->add($m->emptyNullModifier())
                                        ->add($m->ucFirstModifier())
                                        ->add($m->slashesModifier())
                                        ->apply($item->getSide())
                                )
                            )
                            ->apply(!str_contains($item->getSide(), 'заявник')),
                        $m->create()
                            ->add($m->emptyNullModifier())
                            ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                            ->add($m->ucFirstModifier())
                            ->add($m->slashesModifier())
                            ->add($m->underlineModifier())
                            ->add($m->bracketsModifier($this->trans('desc')))
                            ->apply($item->getDesc()),
                        $m->create()
                            ->add($m->emptyNullModifier())
                            ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                            ->add($m->slashesModifier())
                            ->add($m->bracketsModifier($this->trans('court')))
                            ->apply($item->getPlace()),
                    ])($record->getItems())
                )
            )
            ->apply($this->trans('courts_title'))
        ;
    }

    private function getPersonEnforcementsMessage(ClarityPersonEnforcements $record, SearchTerm $searchTerm, bool $full): string
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
                    $m->implodeLinesModifier(fn (ClarityPersonEnforcement $item): array => [
                        $m->create()
                            ->add($m->emptyNullModifier())
                            ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                            ->add($m->slashesModifier())
                            ->add($m->boldModifier())
                            ->add($m->bracketsModifier($this->trans('enf_number')))
                            ->apply($item->getNumber()),
                        $m->create()
                            ->add($m->datetimeModifier(TimeProvider::DATE))
                            ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                            ->add($m->slashesModifier())
                            ->add($m->bracketsModifier($this->trans('opened_at')))
                            ->apply($item->getOpenedAt()),
                        $m->create()
                            ->add($m->emptyNullModifier())
                            ->add($full ? $m->nullModifier() : $m->wordSecretsModifier(excepts: $term))
                            ->add($m->slashesModifier())
                            ->add($m->bracketsModifier($this->trans('debtor')))
                            ->apply($item->getDebtor()),
                        $m->create()
                            ->add($m->datetimeModifier(TimeProvider::DATE))
                            ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                            ->add($m->slashesModifier())
                            ->add($m->bracketsModifier($this->trans('born_at')))
                            ->apply($item->getBornAt()),
                        $m->create()
                            ->add($m->redWhiteModifier())
                            ->add($m->appendModifier(' '))
                            ->add(
                                $m->appendModifier(
                                    $m->create()
                                        ->add($m->emptyNullModifier())
                                        ->add($m->ucFirstModifier())
                                        ->add($m->slashesModifier())
                                        ->apply($item->getState())
                                )
                            )
                            ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                            ->apply(str_contains($item->getState(), 'Відкрито')),
                        $m->create()
                            ->add($m->emptyNullModifier())
                            ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                            ->add($m->slashesModifier())
                            ->add($m->bracketsModifier($this->trans('collector')))
                            ->apply($item->getCollector()),
                    ])($record->getItems())
                )
            )
            ->apply($this->trans('enforcements_title'))
        ;
    }

    private function getPersonDebtorsMessage(ClarityPersonDebtors $record, SearchTerm $searchTerm, bool $full): string
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
                    $m->implodeLinesModifier(fn (ClarityPersonDebtor $item): array => [
                        $m->create()
                            ->add($m->emptyNullModifier())
                            ->add($full ? $m->nullModifier() : $m->wordSecretsModifier(excepts: $term))
                            ->add($m->slashesModifier())
                            ->add($m->boldModifier())
                            ->add($m->bracketsModifier($this->trans('person_name')))
                            ->apply($item->getName()),
                        $m->create()
                            ->add($m->datetimeModifier(TimeProvider::DATE))
                            ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                            ->add($m->slashesModifier())
                            ->add($m->bracketsModifier($this->trans('born_at')))
                            ->apply($item->getBornAt()),
                        $m->create()
                            ->add($m->redWhiteModifier($this->trans('actual'), $this->trans('not_actual')))
                            ->add($m->bracketsModifier($this->trans('actual_at')))
                            ->apply($item->getActualAt() > new DateTimeImmutable()),
                        $m->create()
                            ->add($m->emptyNullModifier())
                            ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                            ->add($m->slashesModifier())
                            ->add($m->bracketsModifier($this->trans('category')))
                            ->apply($item->getCategory()),
                    ])($record->getItems())
                )
            )
            ->apply($this->trans('debtors_title'))
        ;
    }

    private function getPersonDeclarationsMessage(ClarityPersonDeclarations $record, SearchTerm $searchTerm, bool $full): string
    {
        $m = $this->modifier;
        $term = $searchTerm->getNormalizedText();

        return $m->create()
            ->add($m->boldModifier())
            ->add($m->underlineModifier())
            ->add($m->prependModifier('💫 '))
            ->add($m->newLineModifier(2))
            ->add(
                $m->appendModifier(
                    $m->implodeLinesModifier(fn (ClarityPersonDeclaration $item): array => [
                        $m->create()
                            ->add($m->emptyNullModifier())
                            ->add($full ? $m->nullModifier() : $m->wordSecretsModifier(excepts: $term))
                            ->add($m->slashesModifier())
                            ->add($full ? $m->linkModifier($item->getHref()) : $m->nullModifier())
                            ->add($m->boldModifier())
                            ->add($m->bracketsModifier($this->trans('person_name')))
                            ->apply($item->getName()),
                        $m->create()
                            ->add($m->emptyNullModifier())
                            ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                            ->add($m->slashesModifier())
                            ->add($m->bracketsModifier($this->trans('year')))
                            ->apply($item->getYear()),
                        $m->create()
                            ->add($m->emptyNullModifier())
                            ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                            ->add($m->slashesModifier())
                            ->add($m->bracketsModifier($this->trans('position')))
                            ->apply($item->getPosition()),
                    ])($record->getItems())
                )
            )
            ->apply($this->trans('person_declarations_title'))
        ;
    }

    private function getEdrsMessage(ClarityEdrs $record, SearchTerm $searchTerm, bool $full): string
    {
        $m = $this->modifier;
        $term = $searchTerm->getNormalizedText();

        return $m->create()
            ->add($m->boldModifier())
            ->add($m->underlineModifier())
            ->add($m->prependModifier('💫 '))
            ->add($m->newLineModifier(2))
            ->add(
                $m->appendModifier(
                    $m->implodeLinesModifier(fn (ClarityEdr $item): array => [
                        $m->create()
                            ->add($m->emptyNullModifier())
                            ->add($full ? $m->nullModifier() : $m->wordSecretsModifier(excepts: $term))
                            ->add($m->slashesModifier())
                            ->add($full ? $m->linkModifier($item->getHref()) : $m->nullModifier())
                            ->add($m->boldModifier())
                            ->add($m->bracketsModifier($this->trans('edr_name')))
                            ->apply($item->getName()),
                        $m->create()
                            ->add($m->emptyNullModifier())
                            ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                            ->add($m->slashesModifier())
                            ->add($m->bracketsModifier($this->trans('edr_type')))
                            ->apply($item->getType()),
                        $m->create()
                            ->add($m->greenWhiteModifier($this->trans('active'), $this->trans('not_active')))
                            ->apply($item->getActive()),
                        $m->create()
                            ->add($m->emptyNullModifier())
                            ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                            ->add($m->slashesModifier())
                            ->add($m->bracketsModifier($this->trans('address')))
                            ->apply($item->getAddress()),
                    ])($record->getItems())
                )
            )
            ->apply($this->trans('edrs_title'))
        ;
    }
}
