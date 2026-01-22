<?php

declare(strict_types=1);

namespace App\Service\Doctrine;

use App\Service\ORM\EntityManager;
use Doctrine\DBAL\TransactionIsolationLevel;
use RuntimeException;
use Throwable;

class DryRunner
{
    public function __construct(
        private readonly EntityManager $entityManager,
    )
    {
    }

    public function dryRun(callable $func, bool $readUncommitted = false): mixed
    {
        $result = null;
        $exceptionFunc = function () use ($func, &$result) {
            $result = $func();
            throw new RuntimeException('Dry run');
        };

        if ($readUncommitted) {
            $connection = $this->entityManager->getConnection();
            $connection->setTransactionIsolation(TransactionIsolationLevel::READ_UNCOMMITTED);
        }

        try {
            $this->entityManager->wrapInTransaction($exceptionFunc);
        } catch (Throwable $exception) {
            if ($readUncommitted) {
                $connection->setTransactionIsolation(TransactionIsolationLevel::REPEATABLE_READ);
            }

            if ($exception->getMessage() !== 'Dry run') {
                throw $exception;
            }
        }

        return $result;
    }
}