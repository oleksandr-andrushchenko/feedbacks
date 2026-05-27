<?php
declare(strict_types=1);

namespace App\Tests\Fake\Service;

use Symfony\Contracts\Translation\TranslatorInterface;

class FakeTranslator implements TranslatorInterface
{
    public function getLocale(): string
    {
        return '';
    }

    public function trans(string $id, array $parameters = [], string $domain = null, string $locale = null): string
    {
        return $id . implode(' ', $parameters);
    }
}

