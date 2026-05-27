<?php
declare(strict_types=1);

namespace App\Service;

use App\Service\Util\Array\ArrayKeyQuoter;
use Symfony\Contracts\Translation\TranslatorInterface;

class Translator implements TranslatorInterface
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly ArrayKeyQuoter $arrayKeyQuoter,
        private readonly ?string $domain = null,
        private readonly ?string $locale = null,
    )
    {
    }

    public function trans(string $id, array $parameters = [], string $domain = null, string $locale = null): string
    {
        return $this->translator->trans(
            $id,
            $this->arrayKeyQuoter->quoteKeys($parameters),
            $domain ?? $this->domain,
            $locale ?? $this->locale,
        );
    }

    public function getLocale(): string
    {
        return $this->translator->getLocale();
    }
}