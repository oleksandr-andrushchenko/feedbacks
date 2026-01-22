<?php

declare(strict_types=1);

namespace App\Tests\Traits;

use App\Service\IdGenerator;

trait IdGeneratorProviderTrait
{
    public function getIdGenerator(): IdGenerator
    {
        return static::getContainer()->get('app.id_generator');
    }
}