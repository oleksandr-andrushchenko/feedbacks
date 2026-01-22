<?php

declare(strict_types=1);

namespace App\Service\Site;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;


class SiteUrlGenerator implements UrlGeneratorInterface
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly UrlGeneratorInterface $urlGenerator,
    )
    {
    }

    public function getContext(): RequestContext
    {
        return $this->urlGenerator->getContext();
    }

    public function setContext(RequestContext $context): void
    {
        $this->urlGenerator->setContext($context);
    }

    public function generate(string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_PATH): string
    {
        if ($referenceType === self::ABSOLUTE_URL) {
            return sprintf('%s%s', $this->baseUrl, $this->urlGenerator->generate($name, $parameters, self::ABSOLUTE_PATH));
        }

        return $this->urlGenerator->generate($name, $parameters, $referenceType);
    }
}