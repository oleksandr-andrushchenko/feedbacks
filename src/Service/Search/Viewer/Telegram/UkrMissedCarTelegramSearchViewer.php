<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer\Telegram;

use App\Entity\Feedback\SearchTerm;
use App\Model\Search\UkrMissedCar\UkrMissedCar;
use App\Service\Modifier;
use App\Service\Search\Viewer\SearchViewer;
use App\Service\Search\Viewer\SearchViewerCompose;
use App\Service\Search\Viewer\SearchViewerInterface;

class UkrMissedCarTelegramSearchViewer extends SearchViewer implements SearchViewerInterface
{
    public function __construct(SearchViewerCompose $searchViewerCompose, Modifier $modifier)
    {
        parent::__construct($searchViewerCompose->withTransDomain('ukr_missed_cars'), $modifier);
    }

    public function getResultMessage($record, SearchTerm $searchTerm, array $context = []): string
    {
        $full = $context['full'] ?? false;
        $this->showLimits = !$full;
        $m = $this->modifier;
        $term = $searchTerm->getNormalizedText();

        return $m->create()
            ->add($m->boldModifier())
            ->add($m->underlineModifier())
            ->add($m->prependModifier('🚨 '))
            ->add($m->newLineModifier(2))
            ->add(
                $m->appendModifier(
                    $m->implodeLinesModifier(fn (UkrMissedCar $item): array => [
                        $m->create()
                            ->add($m->emptyNullModifier())
                            ->add($full ? $m->nullModifier() : $m->wordSecretsModifier(excepts: $term))
                            ->add($m->slashesModifier())
                            ->add($m->boldModifier())
                            ->add($m->bracketsModifier($this->trans('car_number')))
                            ->apply($item->getCarNumber()),
                        $m->create()
                            ->add($m->emptyNullModifier())
                            ->add($m->appendModifier(' '))
                            ->add($m->appendModifier($item->getModel()))
                            ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                            ->add($m->slashesModifier())
                            ->add($m->bracketsModifier($this->trans('color_and_model')))
                            ->apply($item->getColor()),
                        $m->create()
                            ->add($m->emptyNullModifier())
                            ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                            ->add($m->slashesModifier())
                            ->add($m->bracketsModifier($this->trans('chassis_number')))
                            ->apply($item->getChassisNumber()),
                        $m->create()
                            ->add($m->emptyNullModifier())
                            ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                            ->add($m->slashesModifier())
                            ->add($m->bracketsModifier($this->trans('body_number')))
                            ->apply($item->getBodyNumber()),
                        $m->create()
                            ->add($m->emptyNullModifier())
                            ->add($full ? $m->nullModifier() : $m->wordSecretsModifier())
                            ->add($m->slashesModifier())
                            ->add($m->bracketsModifier($this->trans('region')))
                            ->apply($item->getRegion()),
                    ])($record)
                )
            )
            ->apply($this->trans('missed_cars_title'))
        ;
    }
}
