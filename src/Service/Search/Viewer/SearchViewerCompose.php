<?php
declare(strict_types=1);

namespace App\Service\Search\Viewer;

use Symfony\Contracts\Translation\TranslatorInterface;

class SearchViewerCompose
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly string $transDomainPrefix,
        private ?string $transDomain = null,
    )
    {
    }

    public function withTransDomain(string $transDomain): self
    {
        $new = clone $this;
        $new->transDomain = $transDomain;

        return $new;
    }

    public function trans($id, array $parameters = [], bool $generalDomain = false, string $locale = null): string
    {
        return $this->translator->trans(
            $id,
            parameters: $parameters,
            domain: $this->transDomainPrefix . ($generalDomain ? '' : ('.' . $this->transDomain)),
            locale: $locale
        );
    }
}
