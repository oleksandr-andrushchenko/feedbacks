<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer\Telegram;

use App\Entity\Feedback\SearchTerm;
use App\Enum\Feedback\SearchTermType;
use App\Model\Search\BusinessGuide\BusinessGuideEnterprise;
use App\Model\Search\BusinessGuide\BusinessGuideEnterprises;
use App\Service\Modifier;
use App\Service\Search\Viewer\SearchViewer;
use App\Service\Search\Viewer\SearchViewerCompose;
use App\Service\Search\Viewer\SearchViewerInterface;

class BusinessGuideTelegramSearchViewer extends SearchViewer implements SearchViewerInterface
{
    public function __construct(SearchViewerCompose $searchViewerCompose, Modifier $modifier)
    {
        parent::__construct($searchViewerCompose->withTransDomain('business_guide'), $modifier);
    }

    public function getResultMessage($record, SearchTerm $searchTerm, array $context = []): string
    {
        if (is_string($record)) {
            return $record;
        }

        $full = $context['full'] ?? false;
        $this->showLimits = !$full;

        return match (get_class($record)) {
            BusinessGuideEnterprises::class => $this->getEnterprisesMessage($record, $searchTerm, $full),
            BusinessGuideEnterprise::class => $this->getEnterpriseMessage($record, $searchTerm, $full),
        };
    }

    public function getEnterpriseWrapMessageCallback(SearchTerm $searchTerm, bool $full): callable
    {
        $m = $this->modifier;

        $term = $searchTerm->getNormalizedText();
        $personSearch = $searchTerm->getType() === SearchTermType::person_name;
        $phoneSearch = $searchTerm->getType() === SearchTermType::phone_number;
        $orgSearch = $searchTerm->getType() === SearchTermType::organization_name;
        $placeSearch = $searchTerm->getType() === SearchTermType::place_name;

        return fn (BusinessGuideEnterprise $item): array => [
            $m->create()
                ->add($m->emptyNullModifier())
                ->add($full ? $m->nullModifier() : $m->wordSecretsModifier(excepts: $personSearch || $orgSearch || $placeSearch ? $term : null))
                ->add($m->slashesModifier())
                ->add($full ? $m->linkModifier($item->getHref()) : $m->nullModifier())
                ->add($m->boldModifier())
                ->add($m->bracketsModifier($this->trans('name')))
                ->apply($item->getName()),
            $m->create()
                ->add($m->filterModifier())
                ->add($m->implodeModifier(' '))
                ->add($m->emptyNullModifier())
                ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                ->add($m->slashesModifier())
                ->add($m->bracketsModifier($this->trans('address')))
                ->apply([$item->getCountry(), $item->getAddress()]),
            $m->create()
                ->add($m->emptyNullModifier())
                ->add($full ? $m->nullModifier() : $m->wordSecretsModifier(excepts: $phoneSearch ? $term : null))
                ->add($m->slashesModifier())
                ->add($m->bracketsModifier($this->trans('phone')))
                ->apply($item->getPhone()),
            $m->create()
                ->add($m->emptyNullModifier())
                ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                ->add($m->slashesModifier())
                ->add($m->bracketsModifier($this->trans('ceo')))
                ->apply($item->getCeo()),
            $m->create()
                ->add($m->emptyNullModifier())
                ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                ->add($m->slashesModifier())
                ->add($m->bracketsModifier($this->trans('number')))
                ->apply($item->getNumber()),
            $m->create()
                ->add($m->emptyNullModifier())
                ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                ->add($m->slashesModifier())
                ->add($m->bracketsModifier($this->trans('desc')))
                ->apply($item->getDesc()),
            $m->create()
                ->add($m->filterModifier())
                ->add($m->implodeModifier('; '))
                ->add($m->emptyNullModifier())
                ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                ->add($m->slashesModifier())
                ->add($m->bracketsModifier($this->trans('sectors')))
                ->apply($item->getSectors()),
        ];
    }

    private function getEnterprisesMessage(BusinessGuideEnterprises $record, SearchTerm $searchTerm, bool $full): string
    {
        $m = $this->modifier;

        return $m->create()
            ->add($m->boldModifier())
            ->add($m->underlineModifier())
            ->add($m->prependModifier('💫 '))
            ->add($m->newLineModifier(2))
            ->add($m->appendModifier($m->implodeLinesModifier($this->getEnterpriseWrapMessageCallback($searchTerm, $full))($record->getItems())))
            ->apply($this->trans('enterprises_title'))
        ;
    }

    private function getEnterpriseMessage(BusinessGuideEnterprise $record, SearchTerm $searchTerm, bool $full): string
    {
        $m = $this->modifier;

        return $m->create()
            ->add($m->boldModifier())
            ->add($m->underlineModifier())
            ->add($m->prependModifier('💫 '))
            ->add($m->newLineModifier(2))
            ->add($m->appendModifier($m->implodeLinesModifier($this->getEnterpriseWrapMessageCallback($searchTerm, $full))([$record])))
            ->apply($this->trans('enterprise_title'))
        ;
    }
}
