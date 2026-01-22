<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer;

use App\Entity\Feedback\SearchTerm;
use App\Service\Modifier;

abstract class SearchViewer implements SearchViewerInterface
{
    public function __construct(
        protected readonly SearchViewerCompose $searchViewerCompose,
        protected readonly Modifier $modifier,
        protected ?bool $showLimits = null
    )
    {
    }

    public function getOnSearchMessage(SearchTerm $searchTerm, array $context = []): string
    {
        return '🔍 ' . $this->trans('on_search');
    }

    public function showLimits(): bool
    {
        return $this->showLimits === true;
    }

    public function getLimitsMessage(): string
    {
        $message = '🔒 ';
        $message .= $this->modifier->create()
            ->apply($this->trans('subscription_skipped_data', generalDomain: true))
        ;
        $message .= ' ';
        $message .= $this->modifier->create()
            ->apply($this->trans('subscription_skipped_links', generalDomain: true))
        ;
        $message .= ' ';
        $parameters = [
            'all_links' => $this->modifier->create()
                ->add($this->modifier->boldModifier())
                ->apply($this->trans('subscription_all_links', generalDomain: true)),
            'all_data' => $this->modifier->create()
                ->add($this->modifier->boldModifier())
                ->apply($this->trans('subscription_all_data', generalDomain: true)),
            'subscribe_command' => $this->modifier->create()
                ->add($this->modifier->boldModifier())
                ->apply('/subscribe'),
        ];
        $message .= $this->modifier->create()
            ->apply($this->trans('subscription_benefits', $parameters, generalDomain: true))
        ;

        return $message;
    }

    public function getEmptyMessage(SearchTerm $searchTerm, array $context = [], bool $good = null): string
    {
        $message = $this->trans('empty_result', generalDomain: true);

        if ($good) {
            $message .= ' ☑️ ';
            $message .= $this->trans('all_good', generalDomain: true);
        }

        return $message;
    }

    public function getErrorMessage(SearchTerm $searchTerm, array $context = []): string
    {
        return $this->trans('error_result', generalDomain: true);
    }

    protected function trans($id, array $parameters = [], bool $generalDomain = false, string $locale = null): string
    {
        return $this->searchViewerCompose->trans(
            $id,
            parameters: $parameters,
            generalDomain: $generalDomain,
            locale: $locale
        );
    }
}
