<?php

declare(strict_types=1);

namespace App\Tests\Traits;

use App\Service\ORM\EntityManager;

trait EntityManagerProviderTrait
{
    public function getEntityManager(): EntityManager
    {
        return static::getContainer()->get('app.orm.entity_manager');
    }
}