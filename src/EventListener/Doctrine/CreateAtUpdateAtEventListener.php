<?php

declare(strict_types=1);

namespace App\EventListener\Doctrine;

use DateTimeImmutable;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

class CreateAtUpdateAtEventListener
{
    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if (method_exists($entity, 'getCreatedAt') && $entity->getCreatedAt() === null && method_exists($entity, 'setCreatedAt')) {
            $entity->setCreatedAt(new DateTimeImmutable());
        }
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if (method_exists($entity, 'setUpdatedAt')) {
            $entity->setUpdatedAt(new DateTimeImmutable());
        }
    }
}