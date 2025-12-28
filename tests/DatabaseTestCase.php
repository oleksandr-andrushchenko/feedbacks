<?php

declare(strict_types=1);

namespace App\Tests;

use App\Tests\Traits\ConsoleCommandRunnerTrait;
use App\Tests\Traits\EntityManagerProviderTrait;
use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;

abstract class DatabaseTestCase extends KernelTestCase
{
    use EntityManagerProviderTrait;
    use ConsoleCommandRunnerTrait;

    protected static bool $databaseBooted = false;
    protected static ?Fixtures $fixtures = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->databaseUp();
    }

    public function tearDown(): void
    {
        $this->databaseDown();

        parent::tearDown();
    }

    protected function databaseUp(): void
    {
        $this->bootDatabase()
            ->rollBackIfNeed()
            ->beginTransaction()
        ;
    }

    protected function databaseDown(): void
    {
        $this->rollBackIfNeed();
    }

    protected function beginTransaction(): void
    {
        if ($this->getEntityManager()->getConfig()->isDynamodb()) {
            return;
        }

        $conn = $this->getEntityManager()->getConnection();

        $tables = $conn->executeQuery('SHOW TABLES')->fetchFirstColumn();

        foreach ($tables as $table) {
            $conn->executeQuery(sprintf('ALTER TABLE %s AUTO_INCREMENT = 1', $table));
        }

        $conn->beginTransaction();
    }

    protected function rollBackIfNeed(): static
    {
        if ($this->getEntityManager()->getConfig()->isDynamodb()) {
            return $this;
        }

        $conn = $this->getEntityManager()->getConnection();

        try {
            while ($conn->isTransactionActive()) {
                try {
                    $conn->rollBack();
                } catch (ConnectionException) {
                }
            }
        } catch (Throwable $exception) {
            if (!$this->isUnknownDatabaseException($exception)) {
                throw $exception;
            }
        }

        return $this;
    }

    protected function isUnknownDatabaseException(Throwable $exception): bool
    {
        return str_contains($exception->getMessage(), 'Unknown database');
    }

    protected function bootDatabase(): static
    {
        if (static::$databaseBooted) {
            return $this;
        }

        if ($this->getEntityManager()->getConfig()->isDynamodb()) {
            $em = $this->getEntityManager()->getDynamodb();
            $schemaTool = $em->getSchemaTool();
            try {
                $schemaTool->dropTables();
            } catch (Throwable) {
            }
            $schema = json_decode($this->runConsoleCommand('dynamodb:schema:extract'), true);
            $schemaTool->createTables($schema);
        } else {
            $schemaTool = new SchemaTool($this->getEntityManager()->getDoctrine());
            try {
                $schemaTool->dropDatabase();
            } catch (Throwable) {
            }
            try {
                $this->createDatabase();
            } catch (Throwable) {
            }
            $this->updateDatabase();
        }

        static::$databaseBooted = true;

        return $this;
    }

    /**
     * @return $this
     * @throws \Doctrine\DBAL\Exception
     * @see CreateDatabaseDoctrineCommand
     */
    protected function createDatabase(): static
    {
        if ($this->getEntityManager()->getConfig()->isDynamodb()) {
            return $this;
        }

        $em = $this->getEntityManager();
        $conn = $em->getConnection();

        $params = $conn->getParams();

        if (isset($params['primary'])) {
            $params = $params['primary'];
        }

        $hasPath = isset($params['path']);
        $name = $hasPath ? $params['path'] : ($params['dbname'] ?? false);
        unset($params['dbname'], $params['path'], $params['url']);
        $tmpConnection = DriverManager::getConnection($params);

        $schemaManager = method_exists($tmpConnection, 'createSchemaManager')
            ? $tmpConnection->createSchemaManager()
            : $tmpConnection->getSchemaManager();
        if (!$hasPath) {
            $name = $tmpConnection->getDatabasePlatform()->quoteSingleIdentifier($name);
        }

        $schemaManager->createDatabase($name);

        return $this;
    }

    protected function updateDatabase(): static
    {
        if ($this->getEntityManager()->getConfig()->isDynamodb()) {
            return $this;
        }

        $em = $this->getEntityManager()->getDoctrine();
        $metadata = $em->getMetadataFactory()->getAllMetadata();

        $schemaTool = new SchemaTool($em);
        $schemaTool->updateSchema($metadata);

        return $this;
    }

    protected function bootFixtures(array $refs): self
    {
        if (static::$fixtures === null) {
            static::$fixtures = new Fixtures($this->getEntityManager());
        }

        static::$fixtures->bootFixtures($refs);

        return $this;
    }
}