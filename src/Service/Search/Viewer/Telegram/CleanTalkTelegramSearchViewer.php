<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer\Telegram;

use App\Entity\Feedback\SearchTerm;
use App\Model\Search\CleanTalk\CleanTalkEmail;
use App\Model\Search\CleanTalk\CleanTalkEmails;
use App\Service\Intl\TimeProvider;
use App\Service\Modifier;
use App\Service\Search\Viewer\SearchViewer;
use App\Service\Search\Viewer\SearchViewerCompose;
use App\Service\Search\Viewer\SearchViewerInterface;

class CleanTalkTelegramSearchViewer extends SearchViewer implements SearchViewerInterface
{
    public function __construct(SearchViewerCompose $searchViewerCompose, Modifier $modifier)
    {
        parent::__construct($searchViewerCompose->withTransDomain('clean_talk'), $modifier);
    }

    public function getResultMessage($record, SearchTerm $searchTerm, array $context = []): string
    {
        if (is_string($record)) {
            return $record;
        }

        $full = $context['full'] ?? false;
        $this->showLimits = !$full;

        return match (get_class($record)) {
            CleanTalkEmails::class => $this->getEmailsMessage($record, $full),
        };
    }

    private function getEmailsMessage(CleanTalkEmails $record, bool $full): string
    {
        $m = $this->modifier;

        return $m->create()
            ->add($m->boldModifier())
            ->add($m->underlineModifier())
            ->add($m->prependModifier('💫 '))
            ->add($m->newLineModifier(2))
            ->add(
                $m->appendModifier(
                    $m->implodeLinesModifier(fn (CleanTalkEmail $item): array => [
                        $m->create()
                            ->add($m->slashesModifier())
                            ->add($full ? $m->linkModifier($item->getHref()) : $m->nullModifier())
                            ->add($m->boldModifier())
                            ->apply($item->getAddress()),
                        $m->create()
                            ->add($m->redGreenModifier())
                            ->add($m->appendModifier(' '))
                            ->add($m->appendModifier($this->trans('attacked_sites') . ': ' . $item->getAttackedSites()))
                            ->apply($item->getAttackedSites() > 0),
                        $m->create()
                            ->add($m->redGreenModifier(red: $this->trans('blacklisted'), green: $this->trans('not_blacklisted')))
                            ->apply($item->isBlacklisted()),
                        $m->create()
                            ->add($m->redGreenModifier(red: $this->trans('not_real'), green: $this->trans('real')))
                            ->apply(!$item->isReal()),
                        $m->create()
                            ->add($m->redGreenModifier(red: $this->trans('disposable'), green: $this->trans('not_disposable')))
                            ->apply($item->isDisposable()),
                        $m->create()
                            ->add($m->datetimeModifier(TimeProvider::DATE))
                            ->add($m->bracketsModifier($this->trans('last_update')))
                            ->apply($item->getLastUpdate()),
                    ])($record->getItems())
                )
            )
            ->apply($this->trans('emails_title'))
        ;
    }
}
