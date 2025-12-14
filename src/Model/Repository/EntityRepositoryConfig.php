<?php

declare(strict_types=1);

namespace App\Model\Repository;

readonly class EntityRepositoryConfig
{
    public function __construct(
        private string $engine,
    )
    {
    }

    public function isDynamodb(): bool
    {
        return false;
        return $this->engine === 'dynamodb';
    }
}