<?php
declare(strict_types=1);

namespace App\Tests\Traits;

use Symfony\Contracts\Translation\TranslatorInterface;

trait TranslatorProviderTrait
{
    public function getTranslator(): TranslatorInterface
    {
        return static::getContainer()->get('app.translator');
    }
}